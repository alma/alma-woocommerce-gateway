<?php

namespace Alma\Gateway\Application\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Helper\AjaxHelper;
use Alma\Plugin\Application\Helper\AdminHelperInterface;

class AdminHelper implements AdminHelperInterface {
	/**
	 * Check if the current user can manage Alma settings.
	 *
	 * @param string $customMessage Custom message to display if the user cannot manage Alma settings.
	 *
	 * @return void
	 */
	public static function canManageAlmaError( string $customMessage = 'Forbidden, current user don\'t have rights.' ): void {
		AjaxHelper::sendForbiddenResponse( $customMessage );
	}

	/**
	 * TODO Clarify the purpose of this argument
	 * Send a success response with a message.
	 *
	 * @param bool $data
	 *
	 * @return void
	 */
	public static function success( bool $data ): void {
		AjaxHelper::sendSuccessResponse( $data );
	}
}
