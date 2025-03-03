<?php

add_action(
	'wp_footer',
	function () {
		if ( ! is_checkout() ) {
			return;
		}

		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

		echo '<pre>';
		print_r( $available_gateways );// phpcs:ignore
		echo '</pre>';
	}
);
