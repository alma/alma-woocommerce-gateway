<?php

namespace Alma\Gateway\WooCommerce\Model;

use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\EncryptorHelper;
use Alma\Gateway\Business\Helper\GatewayFormHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\API\EligibilityService;
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
		$this->method_title       = L10nHelper::__( 'Payment in instalments and deferred with Alma - 2x 3x 4x' );
		$this->method_description = L10nHelper::__( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.' );
		$this->has_fields         = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->icon  = $this->get_icon_url();
		$this->title = 'Alma';

		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'process_admin_options' )
		);
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
			$gateway_form_helper->api_key_fieldset(),
			$gateway_form_helper->debug_fieldset(),
			$gateway_form_helper->l10n_fieldset()
		);

		return $this->form_fields;
	}

	/**
	 * Get Alma icon.
	 *
	 * @return string The icon path.
	 * @throws ContainerException
	 */
	public function get_icon_url(): string {

		/** @var AssetsHelper $asset_helper */
		$asset_helper = Plugin::get_container()->get( AssetsHelper::class );

		return $asset_helper->get_image( 'images/alma_logo.svg' );
	}

	/**
	 * Is gateway available?
	 * @return bool
	 */
	public function is_available(): bool {

		if ( is_admin() ) {
			return true;
		}

		// Get the cart total amount
		$cart = WC()->cart;
		if ( ! $cart ) {
			return false;
		}

		$eligibilities = Plugin::get_container()->get( EligibilityService::class )->is_eligible(
			array(
				'purchase_amount' => $cart->get_total( '' ),
				'currency'        => get_woocommerce_currency(),
			)
		);

		$total = $cart->get_total( '' );
		if ( $total < 0 || $total > $this->max_amount ) {
			return false;
		}

		return parent::is_available();
	}

	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		// Exemple : URL de paiement Alma (à remplacer par l'API réelle)
		$payment_url = 'https://sandbox.getalma.eu/payment-link';

		$order->update_status( 'pending', L10nHelper::__( 'En attente de paiement via Alma' ) );

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}

	/**
	 * Encrypt keys before saving.
	 *
	 * @throws ContainerException
	 */
	public function process_admin_options(): bool {

		// Force key encryption before saving.
		$post_data  = $this->get_post_data();
		$this->data = $this->encrypt_keys( $post_data );

		return parent::process_admin_options();
	}

	/**
	 * Encrypt keys.
	 *
	 * @param $post_data array The whole post data.
	 *
	 * @throws ContainerException
	 */
	private function encrypt_keys( array $post_data ): array {
		/** @var EncryptorHelper $encryptor_helper */
		$encryptor_helper = Plugin::get_container()->get( EncryptorHelper::class );

		if ( ! empty( $post_data['woocommerce_alma_live_api_key'] ) ) {
			$post_data['woocommerce_alma_live_api_key'] = $encryptor_helper->encrypt( $post_data['woocommerce_alma_live_api_key'] );
		}

		if ( ! empty( $post_data['woocommerce_alma_test_api_key'] ) ) {
			$post_data['woocommerce_alma_test_api_key'] = $encryptor_helper->encrypt( $post_data['woocommerce_alma_test_api_key'] );
		}

		return $post_data;
	}
}
