<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\Plugin\Infrastructure\Helper\ShopNotificationHelperInterface;

class ShopNotificationHelper implements ShopNotificationHelperInterface {

	/**
	 * Notify the user with an error message.
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	public static function notifyError( string $message ): void {
		wc_add_notice( $message, 'error' );
	}

	/**
	 * Notify the user with an info message.
	 *
	 * @param string $message The notice message to display.
	 *
	 * @return void
	 */
	public static function notifyInfo( string $message ): void {
		wc_add_notice( $message, 'notice' );
	}

	/**
	 * Notify the user with a success message.
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	public static function notifySuccess( string $message ): void {
		wc_add_notice( $message );
	}
}
