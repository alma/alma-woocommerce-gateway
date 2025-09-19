<?php

namespace Alma\Gateway\Application\Helper;

use Alma\API\Domain\Helper\AdminHelperInterface;
use Alma\Gateway\Infrastructure\Helper\AjaxHelper;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

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
