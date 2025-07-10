<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Alma\Gateway\WooCommerce\Proxy\WordPressProxy;

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
	<?php WordPressProxy::set_nonce( 'alma_pnx_gateway_nonce_action', 'alma_pnx_gateway_nonce_field' ); ?>
</fieldset>
