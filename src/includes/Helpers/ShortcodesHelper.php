<?php
/**
 * ShortcodesHelper.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Helpers
 * @namespace Alma\Woocommerce\Helpers
 */

namespace Alma\Woocommerce\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Woocommerce\Handlers\CartHandler;
use Alma\Woocommerce\Handlers\ProductHandler;

/**
 * Class Wc_ShortcodesHelper
 */
class ShortcodesHelper {



	/**
	 * Init cart widget shortcode
	 *
	 * @param CartHandler $handler as cart widget handler.
	 *
	 * @return void
	 */
	public function init_cart_widget_shortcode( CartHandler $handler ) {
		add_shortcode(
			ConstantsHelper::CART_SHORTCODE_TAG,
			function ( $atts, $content = '' ) use ( $handler ) {
				return $this->alma_cart_widget( $handler, $atts, $content );
			}
		);
	}

	/**
	 * Alma Cart Widget Shortcode
	 *
	 * @param CartHandler  $handler Cart handler.
	 * @param array|string $atts Shortcode attributes.
	 * @param string       $content Shortcode content ([sc]content[/sc]).
	 *
	 * @return string the rendered cart eligibility widget to inject
	 */
	protected function alma_cart_widget( CartHandler $handler, $atts, $content = '' ) {

		if ( $handler->is_already_rendered() ) {

			return $this->render_empty( ConstantsHelper::CART_SHORTCODE_TAG, $handler->get_eligibility_widget_already_rendered_message(), $atts, $content );
		}

		ob_start();
		$handler->display_cart_eligibility();

		return $this->render( ConstantsHelper::CART_SHORTCODE_TAG, $atts, ob_get_clean(), $content );
	}

	/**
	 * Render default widget template with empty content if debug is not configured into shortcode
	 *
	 * @param string $shortcode_tag as shortcode name.
	 * @param string $content as HTML Alma Widget or debug content.
	 * @param mixed  $atts as shortcode attributes.
	 * @param string $shortcode_content Shortcode content ([sc]content[/sc]).
	 *
	 * @return string
	 */
	protected function render_empty( $shortcode_tag, $content, $atts, $shortcode_content = '' ) {
		if ( $this->is_debug( $atts ) ) {
			return $this->render( $shortcode_tag, $atts, $content, $shortcode_content );
		}

		return $this->render( $shortcode_tag, $atts, '', $shortcode_content );
	}

	/**
	 * Define if debug is given in shortcode attributes and if value is equivalent to true
	 *
	 * @param mixed $atts as shortcode attributes.
	 *
	 * @return bool
	 */
	protected function is_debug( $atts ) {
		return isset( $atts['debug'] ) && ( filter_var( $atts['debug'], FILTER_VALIDATE_BOOLEAN ) );
	}

	/**
	 * Render default widget template
	 *
	 * @param string $shortcode_tag as shortcode name.
	 * @param mixed  $atts as shortcode attributes.
	 * @param string $alma_content as HTML Alma Widget or debug content.
	 * @param string $shortcode_content Shortcode content ([sc]content[/sc]).
	 *
	 * @return string the rendered eligibility widget to inject
	 */
	protected function render( $shortcode_tag, $atts, $alma_content, $shortcode_content = '' ) {
		$class         = isset( $atts['class'] ) ? htmlspecialchars( $atts['class'] ) : '';
		$style         = '';
		$debug_content = '';
		if ( $this->is_debug( $atts ) ) {
			$class        .= ' debug';
			$style         = 'border:red solid;padding: 0px 10px 10px 10px';
			$style_title   = 'color:red;font-size:12px;margin:-3px 0px 0px -13px;border:red solid;padding-left:3px;max-width:200px;';
			$debug_content = sprintf( '<div style="%s">%s</div>', $style_title, $shortcode_tag );
		}

		return sprintf( '<div class="%s %s" style="%s">%s<div class="alma_wc_content">%s</div>%s</div>', $shortcode_tag, $class, $style, $debug_content, do_shortcode( $shortcode_content ), $alma_content );

	}

	/**
	 * Init product widget shortcode
	 *
	 * @param ProductHandler $handler as product widget handler.
	 *
	 * @return void
	 */
	public function init_product_widget_shortcode( ProductHandler $handler ) {
		add_shortcode(
			ConstantsHelper::PRODUCT_SHORTCODE_TAG,
			function ( $atts, $content = '' ) use ( $handler ) {
				return $this->alma_product_widget( $handler, $atts, $content );
			}
		);
	}

	/**
	 * Alma Product Widget Shortcode
	 *
	 * @param ProductHandler $handler Cart handler.
	 * @param array|string   $atts Shortcode attributes.
	 * @param string         $content Shortcode content ([sc]content[/sc]).
	 *
	 * @return string the rendered product eligibility widget to inject
	 */
	protected function alma_product_widget( ProductHandler $handler, $atts, $content = '' ) {

		if ( $handler->is_already_rendered() ) {
			return $this->render_empty( ConstantsHelper::PRODUCT_SHORTCODE_TAG, $handler->get_eligibility_widget_already_rendered_message(), $atts, $content );
		}

		$product = wc_get_product( isset( $atts['id'] ) ? $atts['id'] : false );

		if ( ! $product ) {
			/* translators: %s: #product_id (if any) */
			$product_not_found_content = sprintf( __( 'Product%s not found - Not displaying Alma', 'alma-gateway-for-woocommerce' ), isset( $atts['id'] ) ? ' with id #' . $atts['id'] : '' );

			return $this->render_empty( ConstantsHelper::PRODUCT_SHORTCODE_TAG, $product_not_found_content, $atts, $content );
		}

		ob_start();
		$handler->inject_payment_plan( $product );

		return $this->render( ConstantsHelper::PRODUCT_SHORTCODE_TAG, $atts, ob_get_clean(), $content );
	}

}
