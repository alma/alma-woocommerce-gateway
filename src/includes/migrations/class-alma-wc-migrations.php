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
 * Alma_WC_Migration
 */
class Alma_WC_Migrations extends Alma_WC_Migrations_Abstract {
	const VERSION_REGEX               = '#^\d+\.\d+\.\d+$#';
	const MIGRATION_CLASS_REGEX       = '#^Alma_WC_Migration_(\d+_\d+_\d+)$#';
	const MIGRATION_FILE_PREFIX_REGEX = '#^class-alma-wc-migration-#';
	const MIGRATION_CLASS_PREFIX      = 'Alma_WC_Migration_';
	const MIGRATION_FILE_SUFFIX_REGEX = '#\.php$#';

	/**
	 * Migration tracing tool
	 *
	 * @var Alma_WC_Migrations_Traces
	 */
	private $traces;

	/**
	 * Alma_WC_Migrations constructor.
	 *
	 * @param string $from_version Version source from where to migrate.
	 * @param string $to_version Version target where to migrate.
	 */
	public function __construct( $from_version, $to_version ) {
		$this->traces = new Alma_WC_Migrations_Traces( $from_version, $to_version );

		parent::__construct( $from_version, $to_version );
	}

	/**
	 * Migrate plugin data to a specific version
	 */
	public function up() {
		if ( ! $this->should_i_run() ) {
			return;
		}

		$this->traces->ready();
		if ( ! $this->check_versions() ) {
			$this->traces->fail();

			return;
		}
		foreach ( glob( ALMA_WC_PLUGIN_PATH . '/includes/migrations/class-alma-wc-migration-*' ) as $file_path ) {
			$migration = $this->get_migration( $file_path );
			if ( ! $migration ) {
				continue;
			}

			$this->traces->add( sprintf( 'migration "%s": RUNNING', get_class( $migration ) ) );
			$migration->up();
			$this->traces->add( sprintf( 'migration "%s": OK', get_class( $migration ) ) );
		}
		$this->traces->success();
	}

	/**
	 * Check traces to allow running this migration or not
	 *
	 * @return bool
	 */
	private function should_i_run() {
		$count = $this->traces->count();

		return 0 === $count || (
				$count < 10
				&& ! $this->has_success()
				&& $this->traces->count( Alma_WC_Migrations_Traces::FAIL ) < 5
			);
	}

	/**
	 * Check version format
	 *
	 * @return bool
	 */
	protected function check_versions() {
		if ( ! preg_match( self::VERSION_REGEX, $this->from_version ) ) {
			$this->traces->add( sprintf( 'Migration "from" version mismatch version pattern "%s"', $this->from_version ) );

			return false;
		}
		if ( ! preg_match( self::VERSION_REGEX, $this->to_version ) ) {
			$this->traces->add( sprintf( 'Migration "to" version mismatch version pattern "%s"', $this->to_version ) );

			return false;
		}

		return true;
	}

	/**
	 * Get a Migration object from a file path
	 *
	 * @param string $file_path as FQDN File Path.
	 *
	 * @return Alma_WC_Migrations_Interface|null
	 */
	protected function get_migration( $file_path ) {
		$file       = basename( $file_path );
		$class_name = preg_replace(
			array( self::MIGRATION_FILE_PREFIX_REGEX, '#-#', self::MIGRATION_FILE_SUFFIX_REGEX ),
			array( self::MIGRATION_CLASS_PREFIX, '_', '' ),
			$file
		);
		if ( ! class_exists( $class_name ) ) {
			$this->traces->add( sprintf( 'Migration class mismatch version pattern "%s"', $class_name ) );

			return null;
		}
		if ( ! preg_match( self::MIGRATION_CLASS_REGEX, $class_name, $match_class ) ) {
			$this->traces->add( sprintf( 'Migration class mismatch version pattern "%s"', $class_name ) );

			return null;
		}
		$migration_version = str_replace( '_', '.', $match_class[1] );
		if (
			! version_compare( $migration_version, $this->from_version, '>' )
			|| ! version_compare( $migration_version, $this->to_version, '<=' )
		) {
			return null;
		}
		$migration = new $class_name( $this->from_version, $this->to_version );
		if ( ! $migration instanceof Alma_WC_Migrations_Interface ) {
			$this->traces->add( sprintf( 'Migration does not implement the Alma_WC_Migrations_Interface "%s"', $class_name ) );

			return null;
		}

		return $migration;
	}

	/**
	 * Check if one of the traces is a success one.
	 *
	 * @return bool
	 */
	public function has_success() {
		return $this->traces->has_success();
	}
}
