<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\AdminNotificationHelperInterface;

class AdminNotificationHelper implements AdminNotificationHelperInterface {

	/**
	 * Notify the user with an error message.
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	public static function notifyError( string $message ): void {
		add_action( 'admin_notices', function () use ( $message ) {
			echo sprintf(
				'<div class="notice notice-error is-dismissible"><p><strong>Alma</strong>: %s</p></div>',
				$message
			);
		} );
	}

	/**
	 * Notify the user with a warning message.
	 *
	 * @param string $message The warning message to display.
	 *
	 * @return void
	 */
	public static function notifyWarning( string $message ): void {
		add_action( 'admin_notices', function () use ( $message ) {
			echo sprintf(
				'<div class="notice notice-warning is-dismissible"><p><strong>Alma</strong>: %s</p></div>',
				$message
			);
		} );
	}

	/**
	 * Notify the user with a notice message.
	 *
	 * @param string $message The notice message to display.
	 *
	 * @return void
	 */
	public static function notifyInfo( string $message ): void {
		add_action( 'admin_notices', function () use ( $message ) {
			echo sprintf(
				'<div class="notice notice-info is-dismissible"><p><strong>Alma</strong>: %s</p></div>',
				$message
			);
		} );
	}

	/**
	 * Notify the user with a success message.
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	public static function notifySuccess( string $message ): void {
		add_action( 'admin_notices', function () use ( $message ) {
			echo sprintf(
				'<div class="notice notice-success is-dismissible"><p><strong>Alma</strong>: %s</p></div>',
				$message
			);
		} );
	}
}
