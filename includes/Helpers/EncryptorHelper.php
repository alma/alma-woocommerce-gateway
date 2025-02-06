<?php
/**
 * EncryptorHelper.
 *
 * @since 4.1.1
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\Woocommerce\Exceptions\RequirementsException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
	public function __construct( $method = 'AES-256-CTR' ) {
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
	 */
	public function encrypt( $data ) {
		$data = openssl_encrypt( $data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );

		return base64_encode( $data ); // phpcs:ignore
	}

	/**
	 * Decrypt the data.
	 *
	 * @param string $encrypted_data The crypted data.
	 *
	 * @return string The decrypted data.
	 */
	public function decrypt( $encrypted_data = '' ) {
		$data = $encrypted_data;

		if ( empty( $data ) ) {
			return $data;
		}

		$data = base64_decode( $data ); // phpcs:ignore
		$data = openssl_decrypt( $data, $this->method, $this->key, OPENSSL_RAW_DATA, $this->iv );

		return $data;
	}

	/**
	 *  Get the salt.
	 *
	 * @return string
	 * @throws RequirementsException  Requirement exception.
	 */
	protected function get_key_salt() {
		if ( defined( 'NONCE_SALT' ) ) {
			return NONCE_SALT;
		}

		throw new RequirementsException( 'The constant NONCE_SALT must to be defined in wp-config.php' );
	}
}

