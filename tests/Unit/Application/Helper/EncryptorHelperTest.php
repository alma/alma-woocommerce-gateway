<?php

namespace Alma\Gateway\Tests\Unit\Application\Helper;

use Alma\Gateway\Application\Exception\Helper\EncryptorHelperException;
use Alma\Gateway\Application\Helper\EncryptorHelper;
use Alma\Gateway\Infrastructure\Exception\CmsException;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class EncryptorHelperTest extends TestCase {
	use MockeryPHPUnitIntegration;


	public function testEncrypt() {
		$encryptorHelper = new EncryptorHelper();
		$this->assertSame( '7TfjkH2klkQf09sdbBPeGg==', $encryptorHelper->encrypt( 'test' ) );
	}

	public function testDecrypt() {
		$encryptorHelper = new EncryptorHelper();

		$this->assertSame( 'test', $encryptorHelper->decrypt( '7TfjkH2klkQf09sdbBPeGg==' ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function testConstructException() {
		Mockery::mock( 'alias:Alma\Gateway\Infrastructure\Helper\SecurityHelper' )
		       ->shouldReceive( 'getKeySalt' )
		       ->andThrow( new CmsException( 'no NONCE_SALT' ) );
		$this->expectException( EncryptorHelperException::class );
		new EncryptorHelper();

	}


}