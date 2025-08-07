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

use Alma\Gateway\Application\Exception\RequirementsException;

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
	 * @throws RequirementsException Requirement exception.
	 */
	public function __construct( string $method = 'AES-256-CTR' ) {
		$key_salt = $this->get_key_salt();

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

	/**
	 *  Get the salt.
	 *
	 * @return string
	 * @throws RequirementsException  Requirement exception.
	 */
	protected function get_key_salt(): string {
		if ( defined( 'NONCE_SALT' ) ) {
			return NONCE_SALT;
		}

		throw new RequirementsException( 'The constant NONCE_SALT must to be defined in wp-config.php' );
	}
}
