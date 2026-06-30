<?php

namespace Alma\Gateway\Infrastructure\Service;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Application\Service\ConfigService;
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
	private string $source;
	private ConfigService $configService;

	public function __construct( ConfigService $configService, string $source = 'alma' ) {
		$this->woo_logger = new WC_Logger();
		$this->source     = $source;
		$this->configService = $configService;
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param string $level One of the PSR-3 log levels.
	 * @param string $message The log message, which may contain placeholders in the form {placeholder}.
	 * @param array  $context An array of context values to replace placeholders in the message. The keys should correspond to the placeholder names without the curly braces.
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 *
	 */
	public function log( $level, $message, array $context = array() ): void {
		if (! $this->configService->isDebug() ) {
			return;
		}
		if ( ! in_array( $level, $this->get_psr_levels(), true ) ) {
			throw new InvalidArgumentException( "Invalid log level: $level" );
		}

		$message = $this->interpolate( (string) $message, $context );
		$this->woo_logger->log( $level, $message, array( 'source' => $this->source ) );
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
