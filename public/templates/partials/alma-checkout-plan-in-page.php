<?php
/**
 * Template.
 *
 * @since 5.0.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

?>

<input
	type="radio"
	style="float: none;"
	value="<?php echo esc_attr( $plan_key ); ?>"
	id="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID_IN_PAGE ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
	name="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::ALMA_FEE_PLAN_IN_PAGE ); ?>"
	class="alma_fee_plan_in_page"
	data-default="<?php echo $is_checked ? '1' : '0'; ?>"
	data-settings-decimal-separator="<?php echo esc_attr( $decimal_separator ); ?>"
	data-settings-thousand-separator="<?php echo esc_attr( $thousand_separator ); ?>"
	data-settings-nb-decimals="<?php echo esc_attr( $decimals ); ?>"
	<?php echo $is_checked ? 'checked' : ''; ?>
>
<label
	class="checkbox"
	style="float:none; margin-right: 10px; display: inline;"
	for="<?php echo esc_attr( \Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID_IN_PAGE ); ?>_alma_fee_plan_<?php echo esc_attr( $plan_key ); ?>"
>
<?php
if (
		Alma\Woocommerce\Helpers\ConstantsHelper::GATEWAY_ID_PAY_NOW === $id
		|| \Alma\Woocommerce\Helpers\ConstantsHelper::PAY_NOW_FEE_PLAN === $plan_key
	) {
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
		</label>
	<?php } ?>
