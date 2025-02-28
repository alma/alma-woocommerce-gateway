<?php

namespace Alma\Gateway\Business\Model;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\GatewayFormHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Plugin;
use WC_Payment_Gateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
class Gateway extends WC_Payment_Gateway {

	/**
	 * @var string|null
	 */
	public $method_description;
	/**
	 * @var string|null
	 */
	public $method_title;
	/**
	 * @var string
	 */
	public $id;
	/**
	 * @var false
	 */
	public $has_fields;

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->id                 = 'alma';
		$this->method_title       = L10nHelper::__( 'Alma' );
		$this->method_description = L10nHelper::__( 'Payer en plusieurs fois avec Alma.' );
		$this->has_fields         = false;
		$this->icon               = $this->get_icon();
		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 * @throws ContainerException
	 */
	public function get_icon() {

		/** @var AssetsHelper $asset_helper */
		$asset_helper = Plugin::get_container()->get( AssetsHelper::class );

		return $asset_helper->get_image( 'images/alma_logo.svg' );
	}

	/**
	 * @throws ContainerException
	 */
	public function init_form_fields() {
		/** @var GatewayFormHelper $gateway_form_helper */
		$gateway_form_helper = Plugin::get_container()->get( GatewayFormHelper::class );

		$this->form_fields = array_merge(
			$this->form_fields,
			$gateway_form_helper->enabled_field(),
			$gateway_form_helper->api_key_fieldset()
		);

		return $this->form_fields;
	}

	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Exemple : URL de paiement Alma (à remplacer par l'API réelle)
		$payment_url = 'https://sandbox.getalma.eu/payment-link';

		$order->update_status( 'pending', L10nHelper::__( 'En attente de paiement via Alma' ) );

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}
}
