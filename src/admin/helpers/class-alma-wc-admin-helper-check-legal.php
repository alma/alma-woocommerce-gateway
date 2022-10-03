<?php
/**
 * Alma Admin Notes Legal Checkout helper
 *
 * @package Alma_WC_Helper_Admin_Check_Legal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Alma_WC_Admin_Helper_Check_Legal
 *
 * Display the legal modal checkout
 */
class Alma_WC_Admin_Helper_Check_Legal {

	const ID = 'alma';

	/**
	 * Logger.
	 *
	 * @var Alma_WC_Logger
	 */
	protected $logger;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->logger = new Alma_WC_Logger();
	}

	/**
	 * Initialize our hooks.
	 * Display legal notice checkout
	 * Includes the required assets
	 *
	 * @return void
	 */
	public function init() {
		set_transient( 'alma-admin-soc-panel', true, 5 );

		add_action( 'admin_notices', array( $this, 'get_modal_checkout_legal' ) );

		wp_enqueue_style( 'alma-admin-styles-modal-checkout-legal', Alma_WC_Admin_Helper_General::get_asset_admin_url( 'css/alma-admin-modal-checkout-legal.css' ), array(), ALMA_WC_VERSION );

		wp_enqueue_script(
			'alma-admin-scripts-modal-checkout-legal',
			Alma_WC_Admin_Helper_General::get_asset_admin_url( 'js/alma-admin-modal-checkout-legal.js' ),
			array(
				'jquery-effects-highlight',
				'jquery-ui-selectmenu',
			),
			ALMA_WC_VERSION,
			true
		);

		wp_localize_script(
			'alma-admin-scripts-modal-checkout-legal',
			'ajax_object',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
		);

		add_action( 'wp_ajax_legal_alma', array( $this, 'legal_alma' ) );
	}

	/**
	 * Call the api consent and save consent in DB.
	 *
	 * @return void
	 */
	public function legal_alma() {
		$value = null;

		try {
			set_transient( 'alma-admin-soc-panel', true, 5 );
			$endpoint = null;
			$value    = sanitize_text_field( $_POST['accept'] ); // phpcs:ignore WordPress.Security.NonceVerification

			switch ( $value ) {
				case 'accept':
					$value    = 'yes';
					$endpoint = 'addConsent';
					break;
				case 'deny':
					$value    = 'no';
					$endpoint = 'removeConsent';
					break;
				default:
					$this->logger->error( sprintf( 'Share of checkout legal acceptance, wrong value received %s', $value ) );
					add_action( 'admin_notices', array( $this, 'alma_admin_soc__error' ) );
					wp_die();
			}

			if ( ! $this->send_consent( $endpoint ) ) {
				add_action( 'admin_notices', array( $this, 'alma_admin_soc__error' ) );
				wp_die();
			}

			// Save in BDD.
			alma_wc_plugin()->settings->share_of_checkout_enabled      = $value;
			alma_wc_plugin()->settings->share_of_checkout_enabled_date = gmdate( 'Y-m-d' );
			alma_wc_plugin()->settings->save();
		} catch ( \Exception $e ) {
			$this->logger->error(
				sprintf( 'Fail to call or save share of checkout action %s', $endpoint ),
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				)
			);
			delete_transient( 'alma-admin-soc-panel' );

			add_action( 'admin_notices', array( $this, 'alma_admin_soc__error' ) );
		}
	}

	/**
	 * Api call to send consent.
	 *
	 * @param string $endpoint The endpoint to call addConsent/removeConsent.
	 *
	 * @return bool
	 */
	public function send_consent( $endpoint ) {
		$alma = alma_wc_plugin()->get_alma_client();

		if ( ! $alma ) {
			$this->logger->error( sprintf( 'Fail to call share of checkout action %s - no client found', $endpoint ) );
			return false;
		}

		try {
			$alma->shareOfCheckout->$endpoint(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		} catch ( \Alma\API\RequestError $e ) {
			$this->logger->error(
				sprintf( 'Fail to call share of checkout action %s', $endpoint ),
				array(
					'message' => $e->getMessage(),
					'trace'   => $e->getTraceAsString(),
				)
			);
			return false;
		}

		delete_transient( 'alma-admin-soc-panel' );
		return true;
	}

	/**
	 * Admin notices for soc errors.
	 *
	 * @return void
	 */
	public function alma_admin_soc__error() {
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php esc_html_e( 'Impossible to save the settings, please try again later', 'alma-gateway-for-woocommerce' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Build and display the modal.
	 *
	 * @return void
	 */
	public function get_modal_checkout_legal() {
		$title     = self::get_modal_title();
		$alma_logo = Alma_WC_Admin_Helper_General::get_icon( $this->get_modal_title(), self::ID );
		if ( get_transient( 'alma-admin-soc-panel' ) ) {

			ob_start();
			?>

		<div class="notice notice-info notice-modal-checkout-legal">
			<div class="modal-checkout-legal-logo"> <?php echo wp_kses_post( $alma_logo ); ?></div>
			<div>
				<div>
					<h1><?php echo esc_attr( $title ); ?></h1>
				</div>
				<div>
					<p>
						<?php
						echo wp_kses_post(
							__(
								'By accepting this option, you enable Alma to analyze the usage of your payment methods and get information in order to improve your clients’ experience.',
								'alma-gateway-for-woocommerce'
							)
						);
						?>
						<br>
						<?php
						echo wp_kses_post(
							__(
								'You can <a href="mailto:support@getalma.eu">opt out and erase your data</a> at any
                        moment.',
								'alma-gateway-for-woocommerce'
							)
						);
						?>
					</p>
					<p class="legal-checkout-collapsible">
						<?php esc_html_e( 'Know more about collected data', 'alma-gateway-for-woocommerce' ); ?>
						<span id="legal-collapse-chevron" class="legal-checkout-chevron bottom"></span>
					</p>
					<ul class="legal-checkout-content">
						<li> <?php esc_html_e( 'total quantity of orders, amounts and currencies', 'alma-gateway-for-woocommerce' ); ?></li>
						<li> <?php esc_html_e( 'payment provider for each order', 'alma-gateway-for-woocommerce' ); ?></li>
					</ul>
				</div>
				<div id="legal-checkout-choices">
					<button class="button-checkout-legal reject-legal-alma" data-value="deny"><?php esc_html_e( 'REJECT', 'alma-gateway-for-woocommerce' ); ?></button>
					<button class="button-checkout-legal accept-legal-alma" data-value="accept"><?php esc_html_e( 'ACCEPT', 'alma-gateway-for-woocommerce' ); ?></button>
				</div>
			</div>
		</div>
			<?php
			$data = ob_get_clean();
			echo wp_kses_post( $data );

			/* Delete transient, only display this notice once. */
			delete_transient( 'alma-admin-soc-panel' );
		}
	}

	/**
	 * Get the modal title.
	 *
	 * @return string
	 */
	public static function get_modal_title() {
		return __( 'Increase your performance & get insights !', 'alma-gateway-for-woocommerce' );
	}


}
