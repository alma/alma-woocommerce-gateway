<?php
/**
 * @see Infrastructure/Gateway/Frontend/PayNowGateway.php
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<fieldset class="alma_woocommerce_gateway_fieldset  alma_woocommerce_gateway_pay-now">
	<p>
		<?php
		esc_html_e(
			'Payer maintenant avec Alma.',
			'alma-gateway-for-woocommerce'
		);
		?>
	</p>
	<p>
	</p>
	<?php
	/** @var string $alma_woocommerce_gateway_nonce */
	echo $alma_woocommerce_gateway_nonce;
	?>
</fieldset>

<?php /** @var string $alma_woocommerce_gateway_in_page_iframe_selector */ ?>
<div id="<?php echo $alma_woocommerce_gateway_in_page_iframe_selector; ?>"></div>
