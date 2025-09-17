<?php
/**
 * EncryptorHelper.
 *
 * @since 4.1.1
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Business/Helper
 * @namespace Alma\Gateway\Business\Helper
 */

namespace Alma\Gateway\Application\Helper;

use Alma\API\Domain\Exception\RequirementsException;
use Alma\Gateway\Application\Exception\Helper\EncryptorHelperException;
use Alma\Gateway\Infrastructure\Exception\CmsException;
use Alma\Gateway\Infrastructure\Helper\SecurityHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

/**
 * EncryptorHelper
 */
class EncryptorHelper {

	/**
	 * The method.
	 *
	 * @var string $method
	 */
	protected $method;

	/**
	 * The Key.
	 *
	 * @var string $key
	 */
	protected $key;

	/**
	 * The iv.
	 *
	 * @var string $iv
	 */
	protected $iv;

	/**
	 * The library.
	 *
	 * @var string
	 */
	protected $library = false;

	/**
	 * EncryptorHelper constructor.
	 *
	 * @param string $method The method.
	 *
	 * @throws EncryptorHelperException
	 * @todo check with security if we need to change algorythm
	 */
	public function __construct( string $method = 'AES-256-CTR' ) {

		try {
			$key_salt = SecurityHelper::getKeySalt();
		} catch ( CmsException $e ) {
			throw new EncryptorHelperException( 'The constant NONCE_SALT is not defined' );
		}

		$methods = openssl_get_cipher_methods();

		if ( ! in_array( $method, $methods, true ) && ! empty( $methods ) ) {
			$this->method = $methods[0];
		} else {
			$this->method = $method;
		}

		$this->library = 'openssl';
		$this->key     = substr( sha1( $key_salt, true ), 0, 16 );
		$this->iv      = substr( $key_salt, 0, 16 );
	}

	/**
	 * Encrypt the data.
	 *
	 * @param string $data The data.
	 *
	 * @return string   The data encrypted
	 * @phpcs base64_encode is marked as safe to ignore by phpcs as it is not used to obfuscate code, but to encode binary data.
	 */
	public function encrypt( string $data ): string {
		$data = openssl_encrypt( $data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );

		return base64_encode( $data );// phpcs:ignore
	}

	/**
	 * Decrypt the data.
	 *
	 * @param string $encrypted_data The crypted data.
	 *
	 * @return string The decrypted data.
	 * @phpcs base64_encode is marked as safe to ignore by phpcs as it is not used to obfuscate code, but to decode binary data.
	 */
	public function decrypt( string $encrypted_data = '' ): string {

		if ( empty( $encrypted_data ) ) {
			return $encrypted_data;
		}

		$encrypted_data = base64_decode( $encrypted_data );// phpcs:ignore

		return openssl_decrypt( $encrypted_data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );
	}
}
