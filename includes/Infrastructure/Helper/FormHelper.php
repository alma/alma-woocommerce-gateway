<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\FormHelperInterface;

class FormHelper implements FormHelperInterface {

	/**
	 * Set a token field for form submission.
	 *
	 * @param string $token The nonce field name.
	 * @param string $action The action name.
	 *
	 * @return string The generated token.
	 */
	public function generateTokenField( string $token, string $action ): string {
		return wp_nonce_field( $action, $token, true, false );
	}

	/**
	 * Checks if the token field is valid.
	 *
	 * @param string $token The nonce field name.
	 * @param string $action The action name.
	 *
	 * @return bool True if the nonce is valid, false otherwise.
	 */
	public function validateTokenField( string $token, string $action ): bool {
		if ( ! isset( $_POST[ $token ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST[ $token ], $action ) ) {
			return false;
		}

		return true;
	}
}
