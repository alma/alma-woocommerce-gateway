<?php

namespace Alma\Gateway\Application\Helper;

use Alma\API\Domain\Exception\SecurityException;

class IpnHelper {

	/**
	 * Validate the given signature
	 *
	 * @param string $paymentId The payment ID associated with the IPN
	 * @param string $apiKey The API key used to generate the HMAC signature
	 * @param string $signature The HMAC signature to validate
	 *
	 * @throws SecurityException
	 */
	public function validateIpnSignature( string $paymentId, string $apiKey, string $signature ) {
		if ( empty( $paymentId ) || empty( $apiKey ) || empty( $signature ) ) {
			throw new SecurityException(
				sprintf(
					'[ALMA] Missing required parameters, payment_id: %s, api_key: %s, signature: %s',
					$paymentId,
					$apiKey,
					$signature
				)
			);
		}
		if ( ! $this->isHmacValidated( $paymentId, $apiKey, $signature ) ) {
			throw new SecurityException( '[ALMA] Invalid signature' );
		}
	}

	/**
	 * Validate the HMAC signature of the request
	 *
	 * @param string $data The data to validate (e.g., payment ID)
	 * @param string $apiKey The API key used to generate the HMAC signature
	 * @param string $signature The HMAC signature to validate
	 *
	 * @return bool
	 */
	private function isHmacValidated( $data, $apiKey, $signature ) {
		return is_string( $data ) &&
		       is_string( $apiKey ) &&
		       hash_hmac( 'sha256', $data, $apiKey ) === $signature;
	}
}
