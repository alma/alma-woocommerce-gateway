<?php
/**
 * Template.
 *
 * @since 4.2.0
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/public/templates
 */

?>
<h5 id="<?php echo esc_html( $id ); ?>_accordion" style="font-weight: 400; font-size: 1.1em;">
	<?php echo esc_html( $title ); ?>
</h5>

<div>
<?php echo $description; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<br>
