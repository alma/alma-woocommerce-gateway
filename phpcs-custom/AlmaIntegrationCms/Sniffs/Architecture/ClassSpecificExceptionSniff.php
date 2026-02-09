<?php

declare( strict_types=1 );

namespace AlmaIntegrationCms\Sniffs\Architecture;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ClassSpecificExceptionSniff implements Sniff {
	/**
	 * @return array<int>
	 */
	public function register(): array {
		return [ T_FUNCTION ];
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ): void {
		$tokens = $phpcsFile->getTokens();

		// Find the class/trait name and namespace.
		$scopePtr = $phpcsFile->findPrevious( Tokens::$ooScopeTokens, ( $stackPtr - 1 ) );
		if ( $scopePtr === false ) {
			return; // Not in a class or trait, skip.
		}
		$isTrait = $tokens[ $scopePtr ]['code'] === T_TRAIT;

		$className = $phpcsFile->getDeclarationName( $scopePtr );
		if ( $className === null ) {
			return;
		}

		$namespace     = $this->getNamespace( $phpcsFile, $scopePtr );
		$fullClassName = trim( $namespace . '\\' . $className, '\\' );

		$allowedExceptions = $this->getAllowedExceptions( $fullClassName, $className );

		$functionNamePtr = $phpcsFile->findNext( T_STRING, $stackPtr + 1 );
		if ( $functionNamePtr !== false && $tokens[ $functionNamePtr ]['content'] === '__call' ) {
			$allowedExceptions[] = 'BadMethodCallException';
		}

		if ( $isTrait ) {
			$allowedExceptions[] = 'ParametersException';
		}

		// Get the scope of the function.
		if ( ! isset( $tokens[ $stackPtr ]['scope_opener'], $tokens[ $stackPtr ]['scope_closer'] ) ) {
			return;
		}
		$scopeOpener = $tokens[ $stackPtr ]['scope_opener'];
		$scopeCloser = $tokens[ $stackPtr ]['scope_closer'];

		$currentPtr = $scopeOpener;
		while ( ( $throwPtr = $phpcsFile->findNext( T_THROW, $currentPtr + 1, $scopeCloser ) ) !== false ) {
			$newPtr = $phpcsFile->findNext( T_NEW, $throwPtr + 1, $scopeCloser );
			if ( $newPtr === false ) {
				$currentPtr = $throwPtr;
				continue;
			}

			$exceptionClassPtr = $phpcsFile->findNext( T_STRING, $newPtr + 1, $scopeCloser );
			if ( $exceptionClassPtr !== false ) {
				$thrownException = $tokens[ $exceptionClassPtr ]['content'];
				if ( ! in_array( $thrownException, $allowedExceptions ) ) {
					$error = 'Method is throwing an invalid exception type. Found %s, but expected one of: %s.';
					$data  = [ $thrownException, implode( ', ', $allowedExceptions ) ];
					$phpcsFile->addError( $error, $exceptionClassPtr, 'IncorrectExceptionType', $data );
				}
			}
			$currentPtr = $throwPtr;
		}
	}

	/**
	 * @param string $fullClassName
	 * @param string $className
	 *
	 * @return array<string>
	 */
	private function getAllowedExceptions( string $fullClassName, string $className ): array {
		// Remove 'Abstract' prefix if present to use the same exception as the concrete class
		$exceptionBaseName = $className;
		if ( strpos( $className, 'Abstract' ) === 0 ) {
			$exceptionBaseName = substr( $className, 8 ); // Remove 'Abstract' prefix
		}

		$allowedExceptions = [ $exceptionBaseName . 'Exception' ];

		// Generic exception for Gateways
		if ( strpos( $fullClassName, 'Alma\Gateway\Infrastructure\Gateway\\' ) === 0 ) {
			$allowedExceptions[] = 'GatewayException';
		}

		// Generic exception for CheckoutBlocks
		if ( strpos( $fullClassName, 'Alma\Gateway\Infrastructure\Block\Gateway\\' ) === 0 ) {
			$allowedExceptions[] = 'CheckoutBlockException';
		}

		// Generic exception for WidgetBlocks
		if ( strpos( $fullClassName, 'Alma\Gateway\Infrastructure\Block\Widget\\' ) === 0 ) {
			$allowedExceptions[] = 'WidgetBlockException';
		}

		// Generic exception for Helper classes
		if ( strpos( $fullClassName, '\Helper\\' ) !== false ) {
			$allowedExceptions[] = 'HelperException';
		}

		// Generic exception for AbstractLoggerService which must respect the LoggerInterface contract.
		if ( $fullClassName === 'Alma\Gateway\Infrastructure\Service\AbstractLoggerService' ) {
			$allowedExceptions[] = 'InvalidArgumentException';
		}

		return $allowedExceptions;
	}

	/**
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return string
	 */
	private function getNamespace( File $phpcsFile, $stackPtr ): string {
		$namespacePtr = $phpcsFile->findPrevious( T_NAMESPACE, $stackPtr );
		if ( $namespacePtr === false ) {
			return '';
		}

		$endOfNamespacePtr = $phpcsFile->findNext( T_SEMICOLON, $namespacePtr );
		$namespace         = $phpcsFile->getTokensAsString( $namespacePtr + 1, $endOfNamespacePtr - $namespacePtr - 1 );

		return trim( $namespace );
	}
}
