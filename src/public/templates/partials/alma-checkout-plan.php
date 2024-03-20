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
	id="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
	name="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::ALMA_FEE_PLAN ); ?>"
	data-default="<?php echo $is_checked ? '1' : '0'; ?>"
	style="<?php echo ( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID_PAY_NOW === $id ) ? 'margin: 18px -1px;' : 'margin-right: -1px;'; ?>; vertical-align: middle;float: none; "
	<?php echo $is_checked ? 'checked' : ''; ?>
	onchange="if (this.checked) { jQuery( '<?php echo esc_js( $plan_class ); ?>' ).hide(); jQuery(this).closest('li.wc_payment_method').find( '<?php echo esc_js( $plan_id ); ?>' ).show() }"
>
<label
	class="checkbox"
	style="float: none; margin-right: 10px; display: inline;"
	for="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
>

	<?php
	if ( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID_PAY_NOW === $id ) {
		?>
		<span class="logoContainer" style="margin-top: 10px !important;">
		<img src="<?php echo esc_attr( $logo_url ); ?>" style="float: unset !important; width: auto !important; height: 15px !important;  border: none !important; vertical-align: middle; display: inline-block; margin-left: 2px;" alt="
					<?php
					// translators: %s: plan_key alt image.
					echo esc_html( sprintf( __( '%s installments', 'alma-gateway-for-woocommerce' ), $plan_key ) );
					?>
					"/>
		<span class="pay-now-text">
		<?php echo esc_html( $logo_text ); ?>
		</span>
	</span>
		<?php
	} else {
		?>
			<img src="<?php echo esc_attr( $logo_url ); ?>" style="float: unset !important; width: auto !important; height: 30px !important;  border: none !important; vertical-align: middle; display: inline-block;" alt="
					<?php
					// translators: %s: plan_key alt image.
					echo esc_html( sprintf( __( '%s installments', 'alma-gateway-for-woocommerce' ), $plan_key ) );
					?>
					"/>
		<?php

	}
	?>
</label>
