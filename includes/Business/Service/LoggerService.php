<?php

namespace Alma\Gateway\Business\Service;

use Alma\Gateway\WooCommerce\Service\AbstractLoggerService;
use Psr\Log\LogLevel;

class LoggerService extends AbstractLoggerService {

	public function emergency( $message, array $context = array() ): void {
		parent::log( LogLevel::EMERGENCY, $message, $context );
	}

	public function alert( $message, array $context = array() ): void {
		parent::log( LogLevel::ALERT, $message, $context );
	}

	public function critical( $message, array $context = array() ): void {
		parent::log( LogLevel::CRITICAL, $message, $context );
	}

	public function error( $message, array $context = array() ): void {
		parent::log( LogLevel::ERROR, $message, $context );
	}

	public function warning( $message, array $context = array() ): void {
		parent::log( LogLevel::WARNING, $message, $context );
	}

	public function notice( $message, array $context = array() ): void {
		parent::log( LogLevel::NOTICE, $message, $context );
	}

	public function info( $message, array $context = array() ): void {
		parent::log( LogLevel::INFO, $message, $context );
	}

	public function debug( $message, array $context = array() ): void {
		parent::log( LogLevel::DEBUG, $message, $context );
	}
}
