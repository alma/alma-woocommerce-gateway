<?php

namespace Alma\Gateway\Infrastructure\Helper;

use Alma\API\Domain\Helper\NotificationHelperInterface;

class NotificationHelper implements NotificationHelperInterface {

	/**
	 * Notify the user with an error message.
	 *
	 * @param string $message The error message to display.
	 *
	 * @return void
	 */
	public function notifyError( string $message ): void {
		wc_add_notice( $message, 'error' );
	}

	/**
	 * Notify the user with a notice message.
	 *
	 * @param string $message The notice message to display.
	 *
	 * @return void
	 */
	public function notifyNotice( string $message ): void {
		wc_add_notice( $message, 'notice' );
	}

	/**
	 * Notify the user with a success message.
	 *
	 * @param string $message The success message to display.
	 *
	 * @return void
	 */
	public function notifySuccess( string $message ): void {
		wc_add_notice( $message );
	}
}
