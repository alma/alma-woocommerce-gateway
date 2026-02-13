<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Plugin\Infrastructure\Helper\SessionHelperInterface;

class SessionHelper implements SessionHelperInterface {

	/**
	 * Get something in WC session
	 *
	 * @param string $key
	 * @param        $default_session
	 *
	 * @return array|string|null
	 */
	public function getSession( string $key, $default_session = null ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return null;
		}

		return WC()->session->get( $key, $default_session );
	}

	/**
	 * Set something in WC session
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function setSession( string $key, $value ): void {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( $key, $value );
		}
	}

	/**
	 * Unset something in WC session
	 *
	 * @param string $key
	 */
	public function unsetKeySession( string $key ): void {
		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->__unset( $key );
		}
	}
}
