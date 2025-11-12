<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

class InPageHelper {

	/**
	 * @param string $paymentId
	 *
	 * @return string
	 */
	public function getInPageRedirectionUrl( string $paymentId ): string {
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
