<?php

namespace Alma\Gateway\Business\Gateway\Frontend;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Helper\TemplateHelper;
use Alma\Gateway\Plugin;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class PayNowGateway extends AbstractFrontendGateway {

	public const GATEWAY_TYPE = 'pay-now';

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->title        = 'Pay now with Alma';
		$this->method_title = L10nHelper::__( 'Payment with Alma' );

		parent::__construct();
	}

	/**
	 * Expose the payment fields to the frontend.
	 *
	 * @return void
	 * @throws ContainerException
	 */
	public function payment_fields() {
		/** @var TemplateHelper $template_helper */
		$template_helper = Plugin::get_container()->get( TemplateHelper::class );
		$template_helper->get_template(
			'pay-now-gateway-options.php',
			array(),
			'partials'
		);
	}

	/**
	 * No extras fields to validate for Pay Now gateway.
	 *
	 * @param $order
	 *
	 * @return array
	 */
	protected function process_payment_fields( $order ): array {
		$order->update_meta_data( '_alma_installments', 1 );
		$order->save();

		return array();
	}
}
