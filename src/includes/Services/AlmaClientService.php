<?php
/**
 * AlmaClientService.
 *
 * @since 5.8.2
 *
 * @package Alma_Gateway_For_Woocommerce
 * @subpackage Alma_Gateway_For_Woocommerce/includes/Services
 * @namespace Alma\Woocommerce\Services
 */

namespace Alma\Woocommerce\Services;

use Alma\API\Client;
use Alma\API\DependenciesError;
use Alma\API\Endpoints\Results\Eligibility;
use Alma\API\ParamsError;
use Alma\API\RequestError;
use Alma\Woocommerce\AlmaLogger;
use Alma\Woocommerce\AlmaSettings;
use Alma\Woocommerce\Builders\Helpers\CartHelperBuilder;
use Alma\Woocommerce\Exceptions\ApiClientException;
use Alma\Woocommerce\Factories\VersionFactory;
use Alma\Woocommerce\Helpers\CartHelper;
use Alma\Woocommerce\Helpers\EncryptorHelper;
use Alma\Woocommerce\WcProxy\OptionProxy;

class AlmaClientService {

	/**
	 * @var EncryptorHelper
	 */
	private $encryptor_helper;
	/**
	 * @var AlmaLogger
	 */
	private $logger;
	/**
	 * @var VersionFactory
	 */
	private $version_factory;
	/**
	 * @var CartHelper|null
	 */
	private $cart_helper;
	/**
	 * @var OptionProxy
	 */
	private $option_proxy;

	/**
	 * Alma client Service - Use to connect with Alma API
	 *
	 * @param      $encryption_helper
	 * @param      $version_factory
	 * @param      $cart_helper
	 * @param      $option_proxy
	 * @param      $logger
	 */
	public function __construct(
		$encryption_helper = null,
		$version_factory = null,
		$cart_helper = null,
		$option_proxy = null,
		$logger = null
	) {
		$this->encryptor_helper = $this->init_encryptor_helper( $encryption_helper );
		$this->version_factory  = $this->init_version_factory( $version_factory );
		$this->cart_helper      = $this->init_cart_helper( $cart_helper );
		$this->option_proxy     = $this->init_option_proxy( $option_proxy );
		$this->logger           = $this->init_alma_logger( $logger );
	}

	/**
	 * Get Alma client with the current API KEY and mode
	 *
	 * @return Client
	 * @throws ApiClientException
	 */
	public function get_alma_client() {
		try {
			$alma_client = new Client(
				$this->get_active_api_key(),
				array(
					'mode'   => $this->get_mode(),
					'logger' => $this->logger,
				)
			);
		} catch ( DependenciesError $e ) {
			throw new ApiClientException( $e->getMessage() );
		} catch ( ParamsError $e ) {
			throw new ApiClientException( $e->getMessage() );
		}


		$alma_client->addUserAgentComponent( 'WordPress', get_bloginfo( 'version' ) );
		$alma_client->addUserAgentComponent( 'WooCommerce', $this->version_factory->get_version() );
		$alma_client->addUserAgentComponent( 'Alma for WooCommerce', ALMA_VERSION );

		return $alma_client;
	}


	/**
	 * Get alma Eligibility from cart
	 *
	 * @param Client $alma_client
	 *
	 * @return Eligibility[] | []
	 */
	public function get_eligibility( $alma_client, $cart ) {
		try {
			$payload     = $this->get_eligibility_payload_from_cart( $cart );
			$eligibility = $alma_client->payments->eligibility( $payload, true );
		} catch ( RequestError $e ) {
			$this->logger->error( 'Error in get eligibility : ' . $e->getMessage() );

			return [];
		}

		return $eligibility;
	}

	/**
	 * Generate Payload for eligibility depending on cart
	 *
	 * @param \WC_Cart $cart
	 *
	 * @return array
	 */
	private function get_eligibility_payload_from_cart( $cart ) {
		$data = array(
			'purchase_amount' => (int) ( round( (float) $cart->get_total( '' ) * 100 ) ),
			'queries'         => $this->cart_helper->get_eligible_plans_for_cart(),
			'locale'          => apply_filters( 'alma_eligibility_user_locale', get_locale() ),
		);

		$billing_country  = $cart->get_customer()->get_billing_country();
		$shipping_country = $cart->get_customer()->get_shipping_country();

		if ( $billing_country ) {
			$data['billing_address'] = array( 'country' => $billing_country );
		}
		if ( $shipping_country ) {
			$data['shipping_address'] = array( 'country' => $shipping_country );
		}

		return $data;
	}


	/**
	 * Get the alma setting in DB
	 *
	 * @return array
	 */
	private function get_alma_settings() {
		return (array) $this->option_proxy->get_option( AlmaSettings::OPTIONS_KEY, array() );
	}

	/**
	 * Get active decrypted API key depending on mode
	 *
	 * @return string
	 * @throws ApiClientException
	 */
	private function get_active_api_key() {
		return $this->get_mode() === 'live' ? $this->get_live_api_key() : $this->get_test_api_key();
	}

	/**
	 * Return alma mode test|live set in DB
	 *
	 * @return string
	 * @throws ApiClientException
	 */
	private function get_mode() {
		$setting = $this->get_alma_settings();
		if ( isset( $setting['environment'] ) ) {
			return $setting['environment'] === 'live' ? 'live' : 'test';
		}
		throw new ApiClientException( 'No mode set' );
	}

	/**
	 * Gets API key for live environment.
	 *
	 * @return string
	 * @throws ApiClientException
	 */
	private function get_live_api_key() {
		$setting = $this->get_alma_settings();
		if ( isset( $setting['live_api_key'] ) ) {
			return $this->encryptor_helper->decrypt( $setting['live_api_key'] );
		}
		throw new ApiClientException( 'Live api key not set' );
	}

	/**
	 * Gets API key for test environment.
	 *
	 * @return string
	 * @throws ApiClientException
	 */
	private function get_test_api_key() {
		$setting = $this->get_alma_settings();
		if ( isset( $setting['test_api_key'] ) ) {
			return $this->encryptor_helper->decrypt( $setting['test_api_key'] );
		}
		throw new ApiClientException( 'Test api key not set' );
	}

	/**
	 * Init Alma logger
	 *
	 * @param AlmaLogger|null $logger
	 *
	 * @return AlmaLogger
	 */
	private function init_alma_logger( $logger ) {
		if ( ! isset( $logger ) ) {
			$logger = new AlmaLogger();
		}

		return $logger;
	}

	/**
	 * Init encryptor helper
	 *
	 * @param EncryptorHelper|null $encryptor_helper
	 *
	 * @return EncryptorHelper
	 */
	private function init_encryptor_helper( $encryptor_helper ) {
		if ( ! isset( $encryptor_helper ) ) {
			$encryptor_helper = new EncryptorHelper();
		}

		return $encryptor_helper;
	}

	/**
	 * Init version factory
	 *
	 * @param VersionFactory|null $version_factory
	 *
	 * @return VersionFactory
	 */
	private function init_version_factory( $version_factory ) {
		if ( ! isset( $version_factory ) ) {
			$version_factory = new VersionFactory();
		}

		return $version_factory;
	}

	/**
	 * Init cart helper
	 *
	 * @param CartHelper|null $cart_helper
	 *
	 * @return CartHelper
	 */
	private function init_cart_helper( $cart_helper ) {
		if ( ! isset( $cart_helper ) ) {
			$cart_helper_builder = new CartHelperBuilder();
			$cart_helper         = $cart_helper_builder->get_instance();
		}

		return $cart_helper;
	}

	/**
	 * Init option proxy
	 *
	 * @param OptionProxy|null $option_proxy
	 *
	 * @return OptionProxy
	 */
	private function init_option_proxy( $option_proxy ) {
		if ( ! isset( $option_proxy ) ) {
			$option_proxy = new OptionProxy();
		}

		return $option_proxy;
	}
}