<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\SecurityHelperInterface;
use Alma\Gateway\Infrastructure\Exception\CmsException;

class SecurityHelper implements SecurityHelperInterface {

	/**
	 *  Get the salt.
	 *
	 * @return string The salt.
	 *
	 * @throws CmsException If the NONCE_SALT constant is not defined.
	 */
	public static function getKeySalt(): string {
		if ( defined( 'NONCE_SALT' ) ) {
			return NONCE_SALT;
		}

		throw new CmsException( 'The constant NONCE_SALT must to be defined in wp-config.php' );
	}

	/**
	 * Set a token for form submission.
	 *
	 * @param string $action The action name.
	 *
	 * @return string The generated token.
	 */
	public function generateToken( string $action ): string {
		return wp_create_nonce( $action );
	}

	/**
	 * Checks if the token is valid.
	 *
	 * @param string $token The token field name.
	 * @param string $action The action name.
	 *
	 * @return bool True if the token is valid, false otherwise.
	 */
	public function validateToken( string $token, string $action ): bool {
		if ( ! isset( $_POST[ $token ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST[ $token ], $action ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validates an AJAX request token.
	 *
	 * @param string $token The token field name.
	 *
	 * @return bool True if the token is valid, false otherwise.
	 */
	public function validateAjaxToken( string $token ): bool {
		return check_ajax_referer( $token, 'security' );
	}
}
