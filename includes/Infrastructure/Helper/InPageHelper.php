<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class InPageHelper {

	/**
	 * In non-blocks mode, we need a redirection on the checkout page to re-display the Alma form,
	 * this method returns the redirection URL with the payment ID as a query parameter
	 *
	 * @param string $paymentId
	 *
	 * @return string
	 */
	public static function getInPageRedirectionUrl( string $paymentId ): string {
		$redirectionUrl = wc_get_checkout_url();

		return add_query_arg(
			array(
				'alma' => 'inPage',
				'pid'  => $paymentId,
			),
			$redirectionUrl
		);
	}
}
