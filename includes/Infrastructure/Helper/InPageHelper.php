<?php

namespace Alma\Gateway\Infrastructure\Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // @codeCoverageIgnore
}

use Alma\Gateway\Infrastructure\Adapter\FeePlanAdapter;

class InPageHelper {

	/**
	 * @param string         $paymentId
	 * @param FeePlanAdapter $feePlanAdapter
	 *
	 * @return string
	 */
	public static function getInPageRedirectionFallbackUrl( string $paymentId, FeePlanAdapter $feePlanAdapter ): string {
		$redirectionUrl = wc_get_checkout_url();

		return add_query_arg(
			array(
				'alma'    => 'inPage',
				'pid'     => $paymentId,
				'planKey' => $feePlanAdapter->getPlanKey(),
			),
			$redirectionUrl
		);
	}
}
