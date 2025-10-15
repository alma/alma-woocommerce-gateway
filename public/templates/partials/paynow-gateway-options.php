<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<fieldset>
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
