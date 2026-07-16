<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Repository;

use Alma\Client\Domain\Entity\EligibilityList;
use Alma\Client\Domain\Entity\FeePlanList;
use Alma\Gateway\Application\Provider\EligibilityProvider;
use Alma\Gateway\Application\Provider\EligibilityProviderFactory;
use Alma\Gateway\Application\Provider\FeePlanProvider;
use Alma\Gateway\Application\Provider\FeePlanProviderFactory;
use Alma\Gateway\Application\Service\BusinessEventsService;
use Alma\Gateway\Application\Service\ConfigService;
use Alma\Gateway\Infrastructure\Exception\Repository\FeePlanRepositoryException;
use Alma\Gateway\Infrastructure\Repository\FeePlanRepository;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Exception;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Subclass to expose protected retrieveFeePlans() for testing.
 */
class TestableFeePlanRepository extends FeePlanRepository {

	/** @var LoggerInterface|null */
	public ?LoggerInterface $injectedLogger = null;

	public function callRetrieveFeePlans( int $cartTotal = 0, bool $forceRefresh = false ) {
		return $this->retrieveFeePlans( $cartTotal, $forceRefresh );
	}

	public function exposeCacheKey(): string {
		return $this->getFeePlansCacheKey();
	}

	public function exposeEligibilityCacheKey( array $eligibilityDtoArray ): string {
		return $this->getEligibilityCacheKey( $eligibilityDtoArray );
	}

	protected function getLogger(): LoggerInterface {
		if ( $this->injectedLogger !== null ) {
			return $this->injectedLogger;
		}

		return new class implements LoggerInterface {
			public function emergency( $message, array $context = [] ): void {
			}

			public function alert( $message, array $context = [] ): void {
			}

			public function critical( $message, array $context = [] ): void {
			}

			public function error( $message, array $context = [] ): void {
			}

			public function warning( $message, array $context = [] ): void {
			}

			public function notice( $message, array $context = [] ): void {
			}

			public function info( $message, array $context = [] ): void {
			}

			public function debug( $message, array $context = [] ): void {
			}

			public function log( $level, $message, array $context = [] ): void {
			}
		};
	}
}

class FeePlanRepositoryTest extends TestCase {

	use MockeryPHPUnitIntegration;

	/** @var ConfigService|Mockery\MockInterface */
	private $configService;

	/** @var BusinessEventsService|Mockery\MockInterface */
	private $businessEventsService;

	/** @var FeePlanProviderFactory|Mockery\MockInterface */
	private $feePlanProviderFactory;

	/** @var EligibilityProviderFactory|Mockery\MockInterface */
	private $eligibilityProviderFactory;

	/** @var FeePlanProvider|Mockery\MockInterface */
	private $feePlanProvider;

	/** @var TestableFeePlanRepository */
	private TestableFeePlanRepository $repository;

	// -------------------------------------------------------------------------
	// Cache key tests
	// -------------------------------------------------------------------------

	public function testFeePlansCacheKeyDiffersBetweenEnvironments(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $feePlanList );

		$capturedKeys = [];
		Functions\expect( 'set_transient' )
			->twice()
			->andReturnUsing( function ( $key, $value, $ttl ) use ( &$capturedKeys ) {
				$capturedKeys[] = $key;

				return true;
			} );

		// Live env
		$configLive = $this->makeConfigMock( 'merchant_test_123', true );
		$repoLive   = $this->makeRepository( $configLive );
		FeePlanRepository::clearTransientCache();
		$repoLive->callRetrieveFeePlans( 0, true );

		// Test env
		$configTest = $this->makeConfigMock( 'merchant_test_123', false );
		$repoTest   = $this->makeRepository( $configTest );
		FeePlanRepository::clearTransientCache();
		$repoTest->callRetrieveFeePlans( 0, true );

		$this->assertCount( 2, $capturedKeys );
		$this->assertNotEquals( $capturedKeys[0], $capturedKeys[1] );
		$this->assertStringStartsWith( FeePlanRepository::TRANSIENT_FEE_PLANS_PREFIX, $capturedKeys[0] );
		$this->assertStringStartsWith( FeePlanRepository::TRANSIENT_FEE_PLANS_PREFIX, $capturedKeys[1] );
	}

	public function testFeePlansCacheKeyDiffersBetweenMerchants(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$capturedKeys = [];
		Functions\expect( 'set_transient' )
			->twice()
			->andReturnUsing( function ( $key, $value, $ttl ) use ( &$capturedKeys ) {
				$capturedKeys[] = $key;

				return true;
			} );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $feePlanList );

		// Merchant A
		$repoA = $this->makeRepository( $this->makeConfigMock( 'merchant_A', false ) );
		FeePlanRepository::clearTransientCache();
		$repoA->callRetrieveFeePlans( 0, true );

		// Merchant B
		$repoB = $this->makeRepository( $this->makeConfigMock( 'merchant_B', false ) );
		FeePlanRepository::clearTransientCache();
		$repoB->callRetrieveFeePlans( 0, true );

		$this->assertCount( 2, $capturedKeys );
		$this->assertNotEquals( $capturedKeys[0], $capturedKeys[1] );
	}

	public function testCacheKeyForSameMerchantAndEnvironmentIsStable(): void {
		$key1 = $this->repository->exposeCacheKey();
		$key2 = $this->repository->exposeCacheKey();

		$this->assertSame( $key1, $key2 );
		$this->assertStringStartsWith( FeePlanRepository::TRANSIENT_FEE_PLANS_PREFIX, $key1 );
	}

	// -------------------------------------------------------------------------
	// Static cache tests
	// -------------------------------------------------------------------------

	public function testApiIsCalledOnlyOnceWhenStaticCacheIsHit(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$feePlanList = $this->makeFeePlanList();

		// API called only once despite two calls to retrieveFeePlans
		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		$this->repository->callRetrieveFeePlans();
		$this->repository->callRetrieveFeePlans(); // second call: static cache hit
	}

	public function testStaticCacheIsResetByClearTransientCache(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$feePlanList = $this->makeFeePlanList();

		// API called twice because clearTransientCache() resets static cache between calls
		$this->feePlanProvider->expects( 'getFeePlanList' )->twice()->andReturn( $feePlanList );

		$this->repository->callRetrieveFeePlans();
		FeePlanRepository::clearTransientCache();
		$this->repository->callRetrieveFeePlans();
	}

	// -------------------------------------------------------------------------
	// Transient cache tests
	// -------------------------------------------------------------------------

	public function testApiIsNotCalledWhenTransientCacheIsHit(): void {
		$feePlanList = $this->makeFeePlanList();
		$serialized  = serialize( $feePlanList );

		// Transient returns a valid serialized FeePlanList
		Functions\when( 'get_transient' )->justReturn( $serialized );

		// API must NOT be called
		$this->feePlanProvider->expects( 'getFeePlanList' )->never();

		$this->repository->callRetrieveFeePlans();
	}

	public function testApiIsCalledAndResultStoredWhenTransientCacheIsMiss(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		Functions\expect( 'set_transient' )
			->once()
			->with(
				Mockery::on( fn( $key ) => 0 === strpos( $key, FeePlanRepository::TRANSIENT_FEE_PLANS_PREFIX ) ),
				Mockery::type( 'string' ),
				FeePlanRepository::TRANSIENT_FEE_PLANS_TTL
			)
			->andReturn( true );

		$this->repository->callRetrieveFeePlans();
	}

	public function testTransientCacheIsIgnoredWhenForceRefreshIsTrue(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$feePlanList = $this->makeFeePlanList();
		$serialized  = serialize( $feePlanList );

		// Even with a valid transient, forceRefresh=true must bypass it
		Functions\when( 'get_transient' )->justReturn( $serialized );
		Functions\when( 'set_transient' )->justReturn( true );

		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		$this->repository->callRetrieveFeePlans( 0, true );
	}

	public function testTransientWithInvalidDataFallsBackToApi(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'set_transient' )->justReturn( true );

		// Transient returns garbage data — should fall back to API
		Functions\when( 'get_transient' )->justReturn( 'not-valid-serialized-data' );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		$this->repository->callRetrieveFeePlans();
	}

	// -------------------------------------------------------------------------
	// Eligibility transient cache tests
	// -------------------------------------------------------------------------

	public function testEligibilityApiIsNotCalledWhenTransientCacheIsHit(): void {
		$eligibilityProvider = $this->arrangeShopContext();

		$serializedEligibility = serialize( new EligibilityList() );
		// Eligibility transient hits; fee-plan transient misses (so fee plans still resolve).
		Functions\when( 'get_transient' )->alias(
			fn( $key ) => strpos( $key, FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0
				? $serializedEligibility
				: false
		);
		Functions\when( 'set_transient' )->justReturn( true );

		// Cache hit: the eligibility API must NOT be called.
		$eligibilityProvider->expects( 'getEligibilityList' )->never();

		$this->repository->callRetrieveFeePlans( 10000 );
	}

	public function testEligibilityApiIsCalledAndStoredWithEligibilityTtlOnCacheMiss(): void {
		$eligibilityProvider = $this->arrangeShopContext();

		Functions\when( 'get_transient' )->justReturn( false );

		$sets = [];
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $ttl ) use ( &$sets ) {
				$sets[] = [ 'key' => $key, 'ttl' => $ttl ];

				return true;
			}
		);

		$eligibilityProvider->expects( 'getEligibilityList' )->once()->andReturn( new EligibilityList() );

		$this->repository->callRetrieveFeePlans( 10000 );

		$eligibilitySets = array_values(
			array_filter(
				$sets,
				fn( $s ) => strpos( $s['key'], FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0
			)
		);

		$this->assertCount( 1, $eligibilitySets, 'Eligibility result must be stored once on cache miss.' );
		$this->assertSame( FeePlanRepository::TRANSIENT_ELIGIBILITY_TTL, $eligibilitySets[0]['ttl'] );
	}

	public function testEligibilityTransientWithWrongTypeFallsBackToApi(): void {
		$eligibilityProvider = $this->arrangeShopContext();

		// A valid serialized object of the WRONG type (not an EligibilityList): it
		// unserializes cleanly but must fail the instanceof guard and trigger a refetch.
		$wrongType = serialize( new FeePlanList() );
		Functions\when( 'get_transient' )->alias(
			fn( $key ) => strpos( $key, FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0
				? $wrongType
				: false
		);
		Functions\when( 'set_transient' )->justReturn( true );

		$eligibilityProvider->expects( 'getEligibilityList' )->once()->andReturn( new EligibilityList() );

		$this->repository->callRetrieveFeePlans( 10000 );
	}

	public function testEligibilityTransientWithCorruptStringFallsBackToApi(): void {
		$eligibilityProvider = $this->arrangeShopContext();

		// A corrupt (non-unserializable) string must be swallowed silently by
		// @unserialize and trigger a refetch — never surface a PHP notice/warning
		// (which PHPUnit would convert to an exception and rethrow as a
		// FeePlanRepositoryException). Mirrors testTransientWithInvalidDataFallsBackToApi.
		Functions\when( 'get_transient' )->alias(
			fn( $key ) => strpos( $key, FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0
				? 'not-valid-serialized-data'
				: false
		);
		Functions\when( 'set_transient' )->justReturn( true );

		$eligibilityProvider->expects( 'getEligibilityList' )->once()->andReturn( new EligibilityList() );

		$this->repository->callRetrieveFeePlans( 10000 );
	}

	public function testBusinessEventIsUpdatedOnEligibilityCacheHit(): void {
		// Shop context (non-admin, cart total > 0) built inline rather than via
		// arrangeShopContext(), because we need a strict expectation on
		// updateEligibility() instead of the loose allows() it sets.
		Functions\when( 'is_admin' )->justReturn( false );
		unset( $_GET['rest_route'] );
		$_SERVER['REQUEST_URI'] = '/';
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$wc           = new \stdClass();
		$wc->cart     = null;
		$wc->customer = null;
		Functions\when( 'WC' )->justReturn( $wc );

		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $this->makeFeePlanList() );

		$eligibilityProvider = Mockery::mock( EligibilityProvider::class );
		$this->eligibilityProviderFactory->allows( '__invoke' )->andReturn( $eligibilityProvider );

		// Eligibility served from cache: the API must NOT be called...
		$serializedEligibility = serialize( new EligibilityList() );
		Functions\when( 'get_transient' )->alias(
			fn( $key ) => strpos( $key, FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0
				? $serializedEligibility
				: false
		);
		Functions\when( 'set_transient' )->justReturn( true );
		$eligibilityProvider->expects( 'getEligibilityList' )->never();

		// ...but the business event MUST still fire, with the cached EligibilityList,
		// so analytics are not silently dropped on cached reads.
		$this->businessEventsService
			->expects( 'updateEligibility' )
			->once()
			->with( Mockery::type( EligibilityList::class ) );

		$this->repository->callRetrieveFeePlans( 10000 );
	}

	// -------------------------------------------------------------------------
	// Eligibility branch guard tests
	// -------------------------------------------------------------------------

	public function testEligibilityIsNotFetchedInAdminContext(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $this->makeFeePlanList() );

		// is_admin() defaults to true in setUp(): even with cartTotal > 0 the eligibility
		// branch must be skipped entirely — no provider, no API, no business event.
		$this->eligibilityProviderFactory->expects( '__invoke' )->never();
		$this->businessEventsService->expects( 'updateEligibility' )->never();

		$this->repository->callRetrieveFeePlans( 10000 );
	}

	public function testEligibilityIsNotFetchedWhenCartTotalIsZero(): void {
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $this->makeFeePlanList() );

		// Non-admin, but cartTotal = 0: the cart-total half of the guard must skip the
		// eligibility branch on its own.
		$this->eligibilityProviderFactory->expects( '__invoke' )->never();
		$this->businessEventsService->expects( 'updateEligibility' )->never();

		$this->repository->callRetrieveFeePlans( 0 );
	}

	public function testEligibilityCacheKeyDiffersBetweenEnvironments(): void {
		$keys = $this->captureEligibilityKeysForConfigs(
			[
				$this->makeConfigMock( 'merchant_x', true ),
				$this->makeConfigMock( 'merchant_x', false ),
			]
		);

		$this->assertCount( 2, $keys );
		$this->assertNotEquals( $keys[0], $keys[1] );
		$this->assertStringStartsWith( FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX, $keys[0] );
	}

	public function testEligibilityCacheKeyDiffersBetweenMerchants(): void {
		$keys = $this->captureEligibilityKeysForConfigs(
			[
				$this->makeConfigMock( 'merchant_A', false ),
				$this->makeConfigMock( 'merchant_B', false ),
			]
		);

		$this->assertCount( 2, $keys );
		$this->assertNotEquals( $keys[0], $keys[1] );
	}

	public function testEligibilityCacheKeyIsStableForSameInputs(): void {
		// Same merchant, same environment, same (empty) cart context -> identical key.
		$keys = $this->captureEligibilityKeysForConfigs(
			[
				$this->makeConfigMock( 'merchant_x', false ),
				$this->makeConfigMock( 'merchant_x', false ),
			]
		);

		$this->assertCount( 2, $keys );
		$this->assertSame( $keys[0], $keys[1] );
	}

	public function testEligibilityCacheKeyVariesWithCartContent(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		// The key hashes the eligibility DTO, so two different carts (here: different
		// purchase amounts) MUST resolve to two different cache entries — otherwise a
		// cart would be served another cart's cached eligibility. This locks the DTO
		// into the key: dropping it from the hash would make this test fail.
		$cartA = [ 'purchase_amount' => 10000, 'queries' => [] ];
		$cartB = [ 'purchase_amount' => 50000, 'queries' => [] ];

		$keyA  = $this->repository->exposeEligibilityCacheKey( $cartA );
		$keyB  = $this->repository->exposeEligibilityCacheKey( $cartB );
		$keyA2 = $this->repository->exposeEligibilityCacheKey( $cartA );

		$this->assertStringStartsWith( FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX, $keyA );
		$this->assertNotEquals( $keyA, $keyB, 'Different cart content must produce different eligibility cache keys.' );
		$this->assertSame( $keyA, $keyA2, 'Identical cart content must produce a stable eligibility cache key.' );
	}

	// -------------------------------------------------------------------------
	// getAll() / getAllWithEligibility() internal adapter cache tests
	// -------------------------------------------------------------------------

	public function testGetAllReturnsSameInstanceOnSecondCall(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		$result1 = $this->repository->getAll();
		$result2 = $this->repository->getAll();

		$this->assertSame( $result1, $result2 );
	}

	public function testGetAllWithEligibilityReturnsSameInstanceForSameCartTotal(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->expects( 'getFeePlanList' )->once()->andReturn( $feePlanList );

		$result1 = $this->repository->getAllWithEligibility( 0 );
		$result2 = $this->repository->getAllWithEligibility( 0 );

		$this->assertSame( $result1, $result2 );
	}

	// -------------------------------------------------------------------------
	// clearTransientCache() test
	// -------------------------------------------------------------------------

	public function testClearTransientCacheDeletesTransientsFromDatabase(): void {
		global $wpdb;

		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->options = 'wp_options';

		$wpdb->expects( 'prepare' )
		     ->once()
		     ->andReturn( 'DELETE FROM wp_options WHERE ...' );

		$wpdb->expects( 'query' )
		     ->once()
		     ->with( 'DELETE FROM wp_options WHERE ...' );

		FeePlanRepository::clearTransientCache();

		// Restore a valid wpdb mock for tearDown
		$this->mockWpdb();
	}

	public function testClearTransientCacheResetsStaticCache(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );

		$feePlanList = $this->makeFeePlanList();
		$this->feePlanProvider->expects( 'getFeePlanList' )->twice()->andReturn( $feePlanList );

		$this->repository->callRetrieveFeePlans(); // populates static cache
		FeePlanRepository::clearTransientCache();   // must clear static cache
		$this->repository->callRetrieveFeePlans(); // must call API again
	}

	// -------------------------------------------------------------------------
	// Error handling test
	// -------------------------------------------------------------------------

	public function testRetrieveFeePlansThrowsRepositoryExceptionWhenApiThrows(): void {
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'get_transient' )->justReturn( false );

		$this->feePlanProvider
			->expects( 'getFeePlanList' )
			->once()
			->andThrow( new Exception( 'API error' ) );

		$this->expectException( FeePlanRepositoryException::class );

		$this->repository->callRetrieveFeePlans();
	}

	// -------------------------------------------------------------------------
	// Lifecycle
	// -------------------------------------------------------------------------

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// WordPress functions used in FeePlanRepository
		Functions\when( 'is_admin' )->justReturn( true );

		// $wpdb must exist before clearTransientCache()
		$this->mockWpdb();
		FeePlanRepository::clearTransientCache();

		$this->configService              = $this->makeConfigMock( 'merchant_test_123', false );
		$this->businessEventsService      = Mockery::mock( BusinessEventsService::class );
		$this->feePlanProviderFactory     = Mockery::mock( FeePlanProviderFactory::class );
		$this->eligibilityProviderFactory = Mockery::mock( EligibilityProviderFactory::class );
		$this->feePlanProvider            = Mockery::mock( FeePlanProvider::class );

		// Factory returns the mocked provider
		$this->feePlanProviderFactory->allows( '__invoke' )->andReturn( $this->feePlanProvider )->byDefault();

		$this->repository = $this->makeRepository( $this->configService );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		$this->mockWpdb();
		FeePlanRepository::clearTransientCache();
		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Sets up a minimal $wpdb mock in the global scope.
	 */
	private function mockWpdb(): void {
		global $wpdb;
		$wpdb          = Mockery::mock( 'wpdb' );
		$wpdb->options = 'wp_options';
		$wpdb->allows( 'prepare' )->andReturn( 'DELETE ...' )->byDefault();
		$wpdb->allows( 'query' )->andReturn( true )->byDefault();
	}

	/**
	 * Build a ConfigService mock with the given merchant_id and isLive flag.
	 */
	private function makeConfigMock( string $merchantId, bool $isLive ): ConfigService {
		$config = Mockery::mock( ConfigService::class );
		$config->allows( 'getMerchantId' )->andReturn( $merchantId )->byDefault();
		$config->allows( 'isLive' )->andReturn( $isLive )->byDefault();
		$config->allows( 'hasSetting' )->andReturn( true )->byDefault();
		$config->allows( 'createSetting' )->andReturn( null )->byDefault();
		$config->allows( 'isFeePlanEnabled' )->andReturn( false )->byDefault();
		$config->allows( 'getMinPurchaseAmount' )->andReturn( 5000 )->byDefault();
		$config->allows( 'getMaxPurchaseAmount' )->andReturn( 200000 )->byDefault();

		return $config;
	}

	/**
	 * Build a TestableFeePlanRepository with the given config.
	 * Note: getLogger() is overridden in TestableFeePlanRepository, no need to inject it.
	 */
	private function makeRepository( ConfigService $config ): TestableFeePlanRepository {
		return new TestableFeePlanRepository(
			$config,
			$this->businessEventsService,
			$this->feePlanProviderFactory,
			$this->eligibilityProviderFactory
		);
	}

	/**
	 * Build a minimal FeePlanList that survives serialize/unserialize.
	 */
	private function makeFeePlanList(): FeePlanList {
		return new FeePlanList();
	}

	/**
	 * Put the repository in a shop (non-admin) context with a cart total > 0 so that
	 * retrieveFeePlans() exercises the eligibility branch. WC()->cart / ->customer are
	 * left null: CartAdapter / CustomerAdapter null-guard, so EligibilityMapper builds a
	 * clean DTO without touching a real WooCommerce cart.
	 *
	 * @return EligibilityProvider|Mockery\MockInterface The mocked eligibility provider.
	 */
	private function arrangeShopContext() {
		Functions\when( 'is_admin' )->justReturn( false );
		unset( $_GET['rest_route'] );
		$_SERVER['REQUEST_URI'] = '/';
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$wc           = new \stdClass();
		$wc->cart     = null;
		$wc->customer = null;
		Functions\when( 'WC' )->justReturn( $wc );

		// Fee plans are not the subject of these tests: let them resolve from the API.
		$this->feePlanProvider->allows( 'getFeePlanList' )->andReturn( $this->makeFeePlanList() );
		$this->businessEventsService->allows( 'updateEligibility' );

		$eligibilityProvider = Mockery::mock( EligibilityProvider::class );
		$this->eligibilityProviderFactory->allows( '__invoke' )->andReturn( $eligibilityProvider );

		return $eligibilityProvider;
	}

	/**
	 * Drive retrieveFeePlans() in a shop context once per config and return the eligibility
	 * transient keys passed to set_transient (one per config, in call order).
	 *
	 * @param ConfigService[] $configs
	 *
	 * @return string[]
	 */
	private function captureEligibilityKeysForConfigs( array $configs ): array {
		$eligibilityProvider = $this->arrangeShopContext();
		$eligibilityProvider->allows( 'getEligibilityList' )->andReturn( new EligibilityList() );

		Functions\when( 'get_transient' )->justReturn( false );

		$keys = [];
		Functions\when( 'set_transient' )->alias(
			function ( $key, $value, $ttl ) use ( &$keys ) {
				if ( strpos( $key, FeePlanRepository::TRANSIENT_ELIGIBILITY_PREFIX ) === 0 ) {
					$keys[] = $key;
				}

				return true;
			}
		);

		foreach ( $configs as $config ) {
			FeePlanRepository::clearTransientCache();
			$this->makeRepository( $config )->callRetrieveFeePlans( 10000 );
		}

		return $keys;
	}
}

