<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

?>

<input
	type="radio"
	value="<?php echo esc_attr( $plan_key ); ?>"
	id="<?php echo esc_attr( \Alma\Woocommerce\Helpers\Alma_Constants_Helper::GATEWAY_ID_IN_PAGE ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
	name="<?php echo esc_attr( \Alma\Woocommerce\Helpers\Alma_Constants_Helper::ALMA_FEE_PLAN_IN_PAGE ); ?>"
    class="alma_fee_plan_in_page"
	data-default="<?php echo $is_checked ? '1' : '0'; ?>"
	<?php echo $is_checked ? 'checked' : ''; ?>
>
<label
	class="checkbox"
	style="margin-right: 10px; display: inline;"
	for="<?php echo esc_attr( \Alma\Woocommerce\Helpers\Alma_Constants_Helper::GATEWAY_ID_IN_PAGE ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
>

				<img src="<?php echo esc_attr( $logo_url ); ?>"
				 style="float: unset !important; width: auto !important; height: 30px !important;  border: none !important; vertical-align: middle; display: inline-block;"
				 alt="
					<?php
					// translators: %s: plan_key alt image.
					echo esc_html( sprintf( __( '%s installments', 'alma-gateway-for-woocommerce' ), $plan_key ) );
					?>
					"/>
		</label>
