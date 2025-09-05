<?php

namespace Alma\Gateway\Infrastructure\WooCommerce\Service;

use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use WC_Logger;

abstract class AbstractLoggerService implements LoggerInterface {

	/**
	 * @var WC_Logger
	 */
	private WC_Logger $woo_logger;

	/**
	 * @var string
	 */
	private string $context;

	public function __construct( string $context = 'alma' ) {
		$this->woo_logger = new WC_Logger();
		$this->context    = $context;
	}

	public function log( $level, $message, array $context = array() ): void {
		if ( ! in_array( $level, $this->get_psr_levels(), true ) ) {
			throw new InvalidArgumentException( "Invalid log level: {$level}" );
		}

		$message = $this->interpolate( (string) $message, $context );
		$this->woo_logger->log( $level, $message, array( 'source' => $this->context ) );
	}

	private function interpolate( string $message, array $context ): string {
		$replace = array();

		foreach ( $context as $key => $value ) {
			if ( ! is_scalar( $value ) ) {
				$value = wp_json_encode( $value );
			}
			$replace[ '{' . $key . '}' ] = $value;
		}

		return strtr( $message, $replace );
	}

	private function get_psr_levels(): array {
		return array(
			LogLevel::EMERGENCY,
			LogLevel::ALERT,
			LogLevel::CRITICAL,
			LogLevel::ERROR,
			LogLevel::WARNING,
			LogLevel::NOTICE,
			LogLevel::INFO,
			LogLevel::DEBUG,
		);
	}
}
