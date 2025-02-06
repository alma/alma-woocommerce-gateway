<?php
/**
 * NoticesService.
 *
 * @since 4.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Admin/Services
 * @namespace Alma\Woocommerce\Admin\Services
 */

namespace Alma\Woocommerce\Admin\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Woocommerce\Helpers\ConstantsHelper;

/**
 * Class that represents admin notices.
 *
 * @since 4.0.0
 */
class NoticesService {


	/**
	 * Notices (array)
	 *
	 * @var array
	 */
	public $notices = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
	}

	/***
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @param string $slug Unique slug.
	 * @param string $class Css class.
	 * @param string $message The message.
	 * @param bool   $dismissible Is this dismissible.
	 *
	 * @return void
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = array(
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		);
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		foreach ( $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'alma-gateway-for-woocommerce-hide-notice', $notice_key ), 'wc_alma_hide_notices_nonce', ConstantsHelper::NOTICE_NONCE_NAME ) ); ?>"
					class="woocommerce-message-close notice-dismiss"
					style="position:relative;float:right;padding:9px 9px 9px 9px;text-decoration:none;"></a>
				<?php
			}

			echo '<p>';
			echo wp_kses(
				$notice['message'],
				array(
					'a' => array(
						'href'   => array(),
						'target' => array(),
					),
				)
			);
			echo '</p></div>';
		}
	}

	/**
	 * Hide the notices.
	 *
	 * @return void
	 */
	public function hide_notices() {
		if (
			isset( $_GET['alma-gateway-for-woocommerce-hide-notice'] )
			&& isset( $_GET[ ConstantsHelper::NOTICE_NONCE_NAME ] )
		) {
			if ( ! wp_verify_nonce( wc_clean( wp_unslash( $_GET[ ConstantsHelper::NOTICE_NONCE_NAME ] ) ), 'wc_alma_hide_notices_nonce' ) ) {
				wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', 'alma-gateway-for-woocommerce' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Cheatin&#8217; huh?', 'alma-gateway-for-woocommerce' ) );
			}

			$notice = wc_clean( wp_unslash( $_GET['alma-gateway-for-woocommerce-hide-notice'] ) );

			if ( false !== strpos( $notice, '_upe' ) ) {
				update_option( 'wc_alma_show_' . $notice . '_notice', 'no' );
			}
		}
	}
}
