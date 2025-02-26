<?php

/**
 * @see https://developer.wordpress.org/plugins/settings/custom-settings-page/
 */

namespace Alma\Gateway\WooCommerce\Proxy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsProxy to manage WordPress/WooCommerce settings.
 */
class SettingsProxy {

	public function set_admin_form() {

		/**
		 * Register our alma_settings_init to the admin_init action hook.
		 */
		add_action( 'admin_init', array( $this, 'alma_settings_init' ) );

		/**
		 * Register our alma_options_page to the admin_menu action hook.
		 */
		add_action( 'admin_menu', array( $this, 'alma_options_page' ) );
	}

	/**
	 * custom option and settings
	 */
	public function alma_settings_init() {
		// Register a new setting for "alma" page.
		register_setting( 'alma', 'alma_options' );

		// Register a new section in the "alma" page.
		add_settings_section(
				'alma_section_developers',
				__( 'The Matrix has you.', 'alma-gateway-for-woocommerce' ),
				array(
						$this,
						'alma_section_developers_callback'
				),
				'alma'
		);

		// Register a new field in the "alma_section_developers" section, inside the "alma" page.
		add_settings_field(
				'alma_field_pill', // As of WP 4.6 this value is used only internally.
				// Use $args' label_for to populate the id inside the callback.
				__( 'Pill', 'alma-gateway-for-woocommerce' ),
				array( $this, 'alma_field_pill_cb' ),
				'alma',
				'alma_section_developers',
				array(
						'label_for'        => 'alma_field_pill',
						'class'            => 'alma_row',
						'alma_custom_data' => 'custom',
				)
		);
	}

	/**
	 * Add the top level menu page.
	 */
	public function alma_options_page() {
		add_menu_page(
				'alma',
				'alma Options',
				'manage_options',
				'alma',
				array( $this, 'alma_options_page_html' )
		);
	}

	/**
	 * Top level menu callback function
	 */
	public function alma_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'alma_messages', 'alma_message', __( 'Settings Saved', 'alma-gateway-for-woocommerce' ), 'updated' );
		}

		// show error/update messages
		settings_errors( 'alma_messages' );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		echo '<form action="options.php" method="post">';
		// output security fields for the registered setting "alma"
		settings_fields( 'alma' );
		// output setting sections and their fields
		// (sections are registered for "alma", each field is registered to a specific section)
		do_settings_sections( 'alma' );
		// output save settings button
		submit_button( 'Save Settings' );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Developers section callback function.
	 *
	 * @param array $args The settings array, defining title, id, callback.
	 */
	public function alma_section_developers_callback( $args ) {
		echo '<p id="' . esc_attr( $args['id'] ) . '">';
		esc_html_e( 'Follow the white rabbit.', 'alma-gateway-for-woocommerce' );
		echo '</p>';
	}

	/**
	 * Pill field callback function.
	 *
	 * WordPress has magic interaction with the following keys: label_for, class.
	 * - the "label_for" key value is used for the "for" attribute of the <label>.
	 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
	 * Note: you can add custom key value pairs to be used inside your callbacks.
	 *
	 * @param array $args
	 */
	public function alma_field_pill_cb( $args ) {
		// Get the value of the setting we've registered with register_setting()
		$options = get_option( 'alma_options' );
		echo '<select
			id="' . esc_attr( $args['label_for'] ) . '"
			data-custom="' . esc_attr( $args['alma_custom_data'] ) . '"
			name="alma_options[' . esc_attr( $args['label_for'] ) . ']">';
		$selected = isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'red', false ) ) : ( '' );
		echo printf( '<option value="red %s">%s</option>', $selected, esc_html( 'red pill' ) );
		$selected = isset( $options[ $args['label_for'] ] ) ? ( selected( $options[ $args['label_for'] ], 'blue', false ) ) : ( '' );
		echo printf( '<option value="blue %s">%s</option>', $selected, esc_html( 'blue pill' ) );
		echo '</select>';
		echo '<p class="description">';
		esc_html_e( 'You take the blue pill and the story ends. You wake in your bed and you believe whatever you want to believe.', 'alma-gateway-for-woocommerce' );
		echo '</p>';
		echo '<p class="description">';
		esc_html_e( 'You take the red pill and you stay in Wonderland and I show you how deep the rabbit-hole goes.', 'alma-gateway-for-woocommerce' );
		echo '</p>';
	}
}
