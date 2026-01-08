<?php

namespace Alma\Gateway\Infrastructure\Helper;

class OrderHelper
{
	/**
	 * Get array of paid statuses from WooCommerce, 'processing' and 'completed' by default.
	 *
	 * @return array
	 */
	public static function wcGetIsPaidStatuses(): int {
		return wc_get_is_paid_statuses();
	}
}
