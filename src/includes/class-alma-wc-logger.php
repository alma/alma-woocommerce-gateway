<?php
/**
 * Alma_WC_Logger.
 *
 * @package Alma_WooCommerce_Gateway
 * @subpackage Alma_WooCommerce_Gateway/includes
 */

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

/**
 * Alma_WC_Logger.
 */
class Alma_WC_Logger extends AbstractLogger {

	const LOG_HANDLE = 'alma';

	/**
	 * Get logger.
	 *
	 * @return WC_Logger
	 */
	private static function get_logger() {
		if ( version_compare( wc()->version, '3.0', '<' ) ) {
			return new WC_Logger();
		} else {
			return wc_get_logger();
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level Log level.
	 * @param string $message Message.
	 * @param array  $context Context.
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		$logger = self::get_logger();

		$levels = array(
			LogLevel::DEBUG     => 'debug',
			LogLevel::INFO      => 'info',
			LogLevel::NOTICE    => 'notice',
			LogLevel::WARNING   => 'warning',
			LogLevel::ERROR     => 'error',
			LogLevel::CRITICAL  => 'critical',
			LogLevel::ALERT     => 'alert',
			LogLevel::EMERGENCY => 'emergency',
		);

		if ( version_compare( wc()->version, '3.0', '<' ) ) {
			$level   = strtoupper( $levels[ $level ] );
			$message = "[$level] " . $message;
			$logger->add( self::LOG_HANDLE, $message );
		} else {
			$method = $levels[ $level ];
			$logger->$method( $message, array( 'source' => self::LOG_HANDLE ) );
		}
	}

	/**
	 * Log all exceptions stack trace prefixed with an error message.
	 *
	 * @param string    $message   as base error message to log.
	 * @param Exception $exception as exception to log.
	 * @param array     $extra_context as info to add to the context.
	 */
	public function log_stack_trace( $message, Exception $exception, $extra_context = array() ) {
		$cnt = 0;

		do {
			$key = sprintf( '%s#%s', $message, $cnt );

			$context = array(
				'ExceptionMessage'        => $exception->getMessage(),
				'ExceptionTraceAsAString' => $exception->getTraceAsString(),
			);

			if ( count( $extra_context ) > 0 ) {
				$context = array_merge( $extra_context, $context );
			}

			$this->error( $key, $context );

			$exception = $exception->getPrevious();
			$cnt++;
		} while ( $exception );
	}

}
