<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class Alma_WC_Logger {
	const LOG_HANDLE = 'alma';

	static private function get_logger() {
		if ( version_compare( wc()->version, '3.0', '<' ) ) {
			return new WC_Logger();
		} else {
			return wc_get_logger();
		}
	}

	static public function log( $message, $level = LOG_INFO ) {
		if ( ! alma_wc_plugin()->settings->is_logging_enabled() ) {
			return;
		}

		$logger = self::get_logger();

		$levels = array(
			LOG_DEBUG   => 'debug',
			LOG_INFO    => 'info',
			LOG_NOTICE  => 'notice',
			LOG_WARNING => 'warning',
			LOG_ERR     => 'error',
			LOG_CRIT    => 'critical',
			LOG_ALERT   => 'alert',
			LOG_EMERG   => 'emergency',
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

	static public function debug( $message ) {
		self::log( $message, LOG_DEBUG );
	}

	static public function info( $message ) {
		self::log( $message, LOG_INFO );
	}

	static public function notice( $message ) {
		self::log( $message, LOG_NOTICE );
	}

	static public function warning( $message ) {
		self::log( $message, LOG_WARNING );
	}

	static public function error( $message ) {
		self::log( $message, LOG_ERR );
	}

	static public function critical( $message ) {
		self::log( $message, LOG_CRIT );
	}

	static public function alert( $message ) {
		self::log( $message, LOG_ALERT );
	}

	static public function emergency( $message ) {
		self::log( $message, LOG_EMERG );
	}
}
