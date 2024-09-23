<?php
/**
 * SecurityHelper
 *
 * @package Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

use Alma\API\Lib\PaymentValidator;
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
	 * The payment validator.
	 *
	 * @var PaymentValidator
	 */
	protected $payment_validator;

	/**
	 * SecurityHelper constructor.
	 *
	 * @param AlmaLogger       $logger The logger.
	 * @param PaymentValidator $payment_validator The payment validator.
	 */
	public function __construct( $logger, $payment_validator ) {
		$this->logger            = $logger;
		$this->payment_validator = $payment_validator;
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
			throw new AlmaInvalidSignatureException( sprintf( '[ALMA] Missing required parameters, payment_id: %s, api_key: %s, signature: %s', $payment_id, $api_key, $signature ) );
		}
		if ( ! $this->payment_validator->isHmacValidated( $payment_id, $api_key, $signature ) ) {
			throw new AlmaInvalidSignatureException( '[ALMA] Invalid signature' );
		}
	}
}
