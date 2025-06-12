<?php

namespace Alma\Gateway\WooCommerce\Gateway;

use Alma\API\Entities\EligibilityList;
use Alma\API\Entities\FeePlanList;
use Alma\API\Exceptions\EligibilityServiceException;
use Alma\API\Exceptions\MerchantServiceException;
use Alma\Gateway\Business\Exception\ContainerException;
use Alma\Gateway\Business\Helper\AssetsHelper;
use Alma\Gateway\Business\Helper\L10nHelper;
use Alma\Gateway\Business\Service\LoggerService;
use Alma\Gateway\Plugin;
use Alma\Gateway\WooCommerce\Proxy\WooCommerceProxy;
use WC_Payment_Gateway;

/**
 * Class Gateway
 * Should extend WC_Payment_Gateway
 */
abstract class AbstractGateway extends WC_Payment_Gateway {

	public const GATEWAY_TYPE = 'abstract';

	/** @var string Identifier */
	public $id;
	protected bool $is_eligible = false;
	private EligibilityList $eligibility_list;
	private FeePlanList $fee_plan_list;

	/**
	 * Gateway constructor.
	 * @throws ContainerException
	 */
	public function __construct() {
		$this->id                 = sprintf( 'alma_%s_gateway', $this->get_type() );
		$this->method_description = L10nHelper::__( 'Install Alma and boost your sales! It\'s simple and guaranteed, your cash flow is secured. 0 commitment, 0 subscription, 0 risk.' );
		$this->has_fields         = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->icon = $this->get_icon_url();

		add_action(
			'woocommerce_update_options_payment_gateways_alma_config_gateway',
			array( $this, 'process_admin_options' ),
			1
		);
	}

	/**
	 * Set the eligibility of the gateway based on the eligibility list.
	 * @throws EligibilityServiceException|MerchantServiceException
	 */
	public function configure_eligibility( EligibilityList $eligibility_list ): void {
		$this->eligibility_list = $eligibility_list->filterEligibilityList( $this->get_type() );
		foreach ( $this->eligibility_list as $eligibility ) {
			if ( $eligibility->isEligible() ) {
				$this->is_eligible = true;

				return;
			}
		}
	}

	/**
	 * Set the max amount of the gateway based on the fee plans.
	 * @throws EligibilityServiceException|MerchantServiceException
	 */
	public function configure_fee_plans( FeePlanList $fee_plan_list ): void {
		$this->fee_plan_list = $fee_plan_list->filterFeePlanList( $this->get_type() );
		foreach ( $this->fee_plan_list as $fee_plan ) {
			if ( $fee_plan->getMaxPurchaseAmount() > $this->max_amount ) {
				$this->max_amount = $fee_plan->getMaxPurchaseAmount();
			}
		}
	}

	public function init_form_fields() {
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

		$logger = Plugin::get_container()->get( LoggerService::class );

		// Get the cart total amount
		$cart  = WC()->cart;
		$total = WooCommerceProxy::get_cart_total();

		if ( ! $cart || $total < 0 || $total > $this->max_amount ) {
			return false;
		}

		if ( ! $this->is_eligible ) {
			return false;
		}

		return parent::is_available();
	}

	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		// Example : URL de paiement Alma (à remplacer par l'API réelle)
		$payment_url = 'https://sandbox.getalma.eu/payment-link';

		$order->update_status( 'pending', L10nHelper::__( 'En attente de paiement via Alma' ) );

		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}

	protected function get_type(): string {
		return static::GATEWAY_TYPE;
	}
}
