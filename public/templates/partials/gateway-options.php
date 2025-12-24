<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

/** @var string $alma_woocommerce_gateway_plan_key */
/** @var string $alma_woocommerce_gateway_logo_url */
/** @var string $alma_woocommerce_gateway_fee_plan_label */
/** @var string $alma_woocommerce_gateway_payment_method */
/** @var int $alma_woocommerce_gateway_fee_plan_count */
/** @var string $alma_woocommerce_gateway_description */

?>
<div class="alma_woocommerce_gateway_fieldset alma_woocommerce_gateway_<?php echo esc_attr( $alma_woocommerce_gateway_payment_method ); ?>">
	<p><?php echo esc_attr( $alma_woocommerce_gateway_description ); ?></p>
	<input
		type="radio"
		value="<?php echo esc_attr( $alma_woocommerce_gateway_plan_key ); ?>"
		id="<?php echo esc_attr( $alma_woocommerce_gateway_plan_key ); ?>"
		name="alma_plan_key"
	>
	<label
		class="checkbox alma_woocommerce_gateway_checkbox"
		for="<?php echo esc_attr( $alma_woocommerce_gateway_plan_key ); ?>"
	>
		<span class="alma_woocommerce_gateway_logo">
			<img src="<?php echo esc_attr( $alma_woocommerce_gateway_logo_url ); ?>"
				alt="
				<?php
				// translators: %s: plan_key alt image.
				echo esc_html(
					sprintf(
					// translators: %s => Installments count
						__( '%s installments', 'alma-gateway-for-woocommerce' ),
						$alma_woocommerce_gateway_plan_key
					)
				);
				?>
				"/>
			<span class="alma_woocommerce_gateway_label">
				<?php echo esc_html( $alma_woocommerce_gateway_fee_plan_label ); ?>
			</span>
		</span>
	</label>
</div>
