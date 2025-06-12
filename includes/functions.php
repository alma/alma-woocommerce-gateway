<?php

add_action(
	'wp_footer',
	function () {
		if ( ! is_checkout() ) {
			return;
		}

		echo '<h1>All Payment Gateways</h1>';
		echo '<pre>';
		print_r( WC()->payment_gateways()->payment_gateways() );// phpcs:ignore
		echo '</pre>';

		echo '<h1>Available Payment Gateways</h1>';
		echo '<pre>';
		print_r( WC()->payment_gateways->get_available_payment_gateways() );// phpcs:ignore
		echo '</pre>';
	}
);
