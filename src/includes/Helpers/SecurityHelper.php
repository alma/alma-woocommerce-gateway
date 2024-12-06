<?php
/**
 * SecurityHelper
 *
 * @package Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\API\Lib\RequestUtils;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\Exceptions\AlmaInvalidSignatureException;

/**
 * Class SecurityHelper
 */
class SecurityHelper {

	/**
	 * The logger.
	 *
	 * @var AlmaLogger
	 */
	protected $logger;

	/**
	 * SecurityHelper constructor.
	 *
	 * @param AlmaLogger $logger The logger.
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Validate the IPN signature
	 *
	 * @param string $payment_id The payment ID.
	 * @param string $api_key The API key.
	 * @param string $signature The signature.
	 *
	 * @return void
	 *
	 * @throws AlmaInvalidSignatureException If the signature is invalid.
	 */
	public function validate_ipn_signature( $payment_id, $api_key, $signature ) {
		if ( empty( $payment_id ) || empty( $api_key ) || empty( $signature ) ) {
			throw new AlmaInvalidSignatureException(
				sprintf( '[ALMA] Missing required parameters, payment_id: %s, api_key: %s, signature: %s', $payment_id, $api_key, $signature )
			);
		}
		if ( ! RequestUtils::isHmacValidated( $payment_id, $api_key, $signature ) ) {
			throw new AlmaInvalidSignatureException( '[ALMA] Invalid signature' );
		}
	}

	/**
	 * Validate the collect data signature
	 *
	 * @param string $merchant_id merchant id.
	 * @param string $api_key The API key.
	 * @param string $signature The signature.
	 *
	 * @return void
	 *
	 * @throws AlmaInvalidSignatureException If the signature is invalid.
	 */
	public function validate_collect_data_signature( $merchant_id, $api_key, $signature ) {
		if ( empty( $merchant_id ) || empty( $api_key ) || empty( $signature ) ) {
			throw new AlmaInvalidSignatureException(
				sprintf( '[ALMA] Missing required parameters, merchant_id: %s, api_key: %s, signature: %s', $merchant_id, $api_key, $signature )
			);
		}
		if ( ! RequestUtils::isHmacValidated( $merchant_id, $api_key, $signature ) ) {
			throw new AlmaInvalidSignatureException( '[ALMA] Invalid signature' );
		}
	}
}
