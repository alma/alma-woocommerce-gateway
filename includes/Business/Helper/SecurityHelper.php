<?php

namespace Alma\Gateway\Business\Helper;

use Alma\API\Lib\RequestUtils;
use Alma\Gateway\Business\Exception\SecurityException;

class SecurityHelper {
	/**
	 * Validate the given signature
	 * @throws SecurityException
	 */
	public function validate_ipn_signature( string $payment_id, string $api_key, string $signature ) {
		if ( empty( $payment_id ) || empty( $api_key ) || empty( $signature ) ) {
			throw new SecurityException(
				sprintf(
					'[ALMA] Missing required parameters, payment_id: %s, api_key: %s, signature: %s',
					$payment_id,
					$api_key,
					$signature
				)
			);
		}
		if ( ! RequestUtils::isHmacValidated( $payment_id, $api_key, $signature ) ) {
			throw new SecurityException( '[ALMA] Invalid signature' );
		}
	}
}
