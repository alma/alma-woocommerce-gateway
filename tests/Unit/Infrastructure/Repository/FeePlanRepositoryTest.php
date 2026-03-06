<?php

namespace Alma\Gateway\Tests\Unit\Infrastructure\Repository;

use Alma\Client\Domain\Entity\FeePlanList;
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
				Mockery::on( fn( $key ) => str_starts_with( $key, FeePlanRepository::TRANSIENT_FEE_PLANS_PREFIX ) ),
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
}

