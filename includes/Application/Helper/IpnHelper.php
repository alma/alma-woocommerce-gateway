<?php

namespace Alma\Gateway\Application\Helper;

use Alma\API\Domain\Exception\SecurityException;

class IpnHelper {

	/**
	 * Validate the given signature
	 *
	 * @throws SecurityException
	 */
	public function validateIpnSignature( string $payment_id, string $api_key, string $signature ) {
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
		if ( ! $this->isHmacValidated( $payment_id, $api_key, $signature ) ) {
			throw new SecurityException( '[ALMA] Invalid signature' );
		}
	}

	/**
	 * Validate the HMAC signature of the request
	 *
	 * @param string $data
	 * @param string $apiKey
	 * @param string $signature
	 *
	 * @return bool
	 */
	private function isHmacValidated( $data, $apiKey, $signature ) {
		return is_string( $data ) &&
		       is_string( $apiKey ) &&
		       hash_hmac( 'sha256', $data, $apiKey ) === $signature;
	}
}
