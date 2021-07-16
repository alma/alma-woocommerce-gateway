<?php
/**
 * Alma payments plugin for WooCommerce
 *
 * @package Alma_WooCommerce_Gateway
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}
if ( ! defined( 'ALMA_WC_PLUGIN_PATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}


/**
 * Class Alma_WC_Migrations_Option
 */
class Alma_WC_Migrations_Traces {
	const FAIL    = 'fail';
	const SUCCESS = 'success';
	const READY   = 'ready';
	/**
	 * The option key for migration from one version to an other
	 *
	 * @var string
	 */
	private $migration_option_key;
	/**
	 * The key of current traces for given migration (sub array in this options)
	 *
	 * @var string
	 */
	private $trace_key;
	/**
	 * All traces for this migration
	 *
	 * @var string[][]
	 */
	private $traces;
	/**
	 * The logger
	 *
	 * @var Alma_WC_Logger
	 */
	private $logger;
	/**
	 * The starting DateTime object
	 *
	 * @var DateTime
	 */
	private $start_date;

	/**
	 * Alma_WC_Migrations_Option constructor.
	 *
	 * @param string $from_version Version source from where to migrate.
	 * @param string $to_version Version target where to migrate.
	 */
	public function __construct( $from_version, $to_version ) {
		$this->logger               = new Alma_WC_Logger();
		$this->migration_option_key = sprintf( 'alma_migration_from_%s_to_%s', $from_version, $to_version );
		$previous_traces            = get_option( $this->migration_option_key, array() );
		$this->start_date           = new DateTime();
		$this->trace_key            = $this->start_date->format( 'Y-m-d H:i:s' );
		$this->traces               = array_merge( $previous_traces, array( $this->trace_key => array() ) );
	}

	/**
	 * Log a trace about migration
	 *
	 * @param string $trace a log trace about migration.
	 */
	public function add( $trace ) {
		$this->log( $trace );
		$this->traces[ $this->trace_key ][ $this->timer() ] = $trace;
		$this->update();
	}

	/**
	 * Update options
	 */
	protected function update() {
		update_option( $this->migration_option_key, $this->traces );
	}

	/**
	 * Set state success
	 */
	public function success() {
		$this->set_state( self::SUCCESS );
	}

	/**
	 * Set the state in current migration_key
	 *
	 * @param string $state the state to set.
	 */
	public function set_state( $state ) {
		$this->set( 'state', $state );
		$this->add( sprintf( 'state => %s', $state ) );
	}

	/**
	 * Set or replace a trace by key
	 *
	 * @param int|string $key the key sub option in array.
	 * @param mixed      $value the value in sub option array.
	 */
	public function set( $key, $value ) {
		$this->log( sprintf( '%s => %s', $key, $value ) );
		$this->traces[ $this->trace_key ][ $key ] = $value;
		$this->update();
	}

	/**
	 * Set state fail
	 */
	public function fail() {
		$this->set_state( self::FAIL );
	}

	/**
	 * Set state ready
	 */
	public function ready() {
		$this->set_state( self::READY );
	}

	/**
	 * Count migrations optionally filter by state
	 *
	 * @param null|string $state optional filter.
	 *
	 * @return int
	 */
	public function count( $state = null ) {
		if ( ! $state ) {
			return count( $this->traces );
		}

		return count(
			array_filter(
				$this->traces,
				function ( $trace ) use ( $state ) {
					return isset( $trace['state'] ) && $trace['state'] === $state;
				}
			)
		);
	}

	/**
	 * Log message with migration & trace key prefixes
	 *
	 * @param string $message the message to log.
	 */
	private function log( $message ) {
		$this->logger->info( sprintf( '%s => %s : %s', $this->migration_option_key, $this->trace_key, $message ) );
	}

	/**
	 * Check if one of the traces is a success one.
	 *
	 * @return bool
	 */
	public function has_success() {
		return $this->count( self::SUCCESS ) >= 1;
	}

	/**
	 * Generate ellapsed time since start date at each call
	 *
	 * @return string
	 */
	private function timer() {
		return ( $this->start_date->diff( new DateTime() ) )->format( '%H:%I:%S.%F' );
	}
}
