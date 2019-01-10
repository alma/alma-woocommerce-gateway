<?php

use Psr\Log\LogLevel;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

class Alma_WC_Logger extends \Psr\Log\AbstractLogger
{
	const LOG_HANDLE = 'alma';

	static private function get_logger() {
		if ( version_compare( wc()->version, '3.0', '<' ) ) {
			return new WC_Logger();
		} else {
			return wc_get_logger();
		}
	}

	public function log($level, $message, array $context = array())
    {
        if ( ! alma_wc_plugin()->settings->is_logging_enabled() ) {
            return;
        }

        $logger = self::get_logger();

        $levels = array(
            LogLevel::DEBUG   => 'debug',
            LogLevel::INFO    => 'info',
            LogLevel::NOTICE  => 'notice',
            LogLevel::WARNING => 'warning',
            LogLevel::ERROR     => 'error',
            LogLevel::CRITICAL    => 'critical',
            LogLevel::ALERT   => 'alert',
            LogLevel::EMERGENCY   => 'emergency',
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
}
