<?php

namespace Alma\Woocommerce\Services;

use Alma\Woocommerce\AlmaLogger;

class AlmaBusinessEventService {

	/**
	 * @var \Alma\Woocommerce\AlmaLogger|mixed|null
	 */
	private $logger;

	public function __construct( $logger = null ) {
		if ( is_null( $logger ) ) {
			$logger = new AlmaLogger();
		}
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	public function init_order_confirmed_hook() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );
	}

	public function order_status_changed( $order_id, $old_status, $new_status ) {
		$this->logger->info( 'Order Status changed in Alma business event', [
				'orderId'    => $order_id,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'order'      => wc_get_order( $order_id )
			]
		);
		//var_dump(wc_get_order( $order_id ));die();
	}

	public function init_cart_initiated_hook() {
		add_action( 'woocommerce_add_to_cart', array( $this, 'cart_initiated' ), 10, 3 );
	}

	public function cart_initiated( $cart_id = null , $product_id = null, $request_quantity = null, $variation_id = null , $variation = null, $cart_item_data = null ) {
		$this->logger->info( 'Add to cart Alma business event', [
				'$cart_id'          => $cart_id,
				'$product_id'       => $product_id,
				'$request_quantity' => $request_quantity,
				'$variation_id'     => $variation_id,
				'$variation'        => $variation,
				'$cart_item_data' => $cart_item_data,
				'cart' =>  \WC()->cart
			]
		);
	}

}