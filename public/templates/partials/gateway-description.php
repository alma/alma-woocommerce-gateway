<?php
/**
 * Template.
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates/partials
 */

/**
 * @var array  $args Template arguments
 * @var string $args ['alma_woocommerce_gateway_description'] Gateway description
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}
?>
<div style="flex-basis: 100%;"><p><?php echo $args['alma_woocommerce_gateway_description']; ?></p></div>
