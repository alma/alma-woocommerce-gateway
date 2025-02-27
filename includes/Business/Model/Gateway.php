<?php

namespace Alma\Gateway\Business\Model;

use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\WooCommerce\Proxy\HooksProxy;
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
	 * @var HooksProxy
	 */
	private $hooks_proxy;

	/**
	 * Gateway constructor.
	 */
	public function __construct() {
		$this->id                 = 'alma';
		$this->method_title       = L10nHelper::__( 'Alma' );
		$this->method_description = L10nHelper::__( 'Payer en plusieurs fois avec Alma.' );
		$this->has_fields         = false;

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		// Sauvegarde des paramètres
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
	}

	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => L10nHelper::__( 'Activer/Désactiver' ),
				'type'    => 'checkbox',
				'label'   => L10nHelper::__( 'Activer Alma' ),
				'default' => 'yes',
			),
			'title'   => array(
				'title'       => L10nHelper::__( 'Titre' ),
				'type'        => 'text',
				'description' => L10nHelper::__( 'Titre affiché lors du paiement.' ),
				'default'     => L10nHelper::__( 'Paiement en plusieurs fois avec Alma' ),
			),
		);
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
