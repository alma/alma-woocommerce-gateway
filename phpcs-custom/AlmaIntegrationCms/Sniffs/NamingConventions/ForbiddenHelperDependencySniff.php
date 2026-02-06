<?php

declare( strict_types=1 );

namespace AlmaIntegrationCms\Sniffs\Architecture;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Forbid classes suffixed with 'Helper' from calling classes suffixed with
 * 'Controler', 'Service', 'Repository', 'Provider', or 'Gateway'.
 */
class ForbiddenHelperDependencySniff implements Sniff {

	/**
	 * Forbidden class suffixes.
	 *
	 * @var string[]
	 */
	private array $forbiddenSuffixes = [ 'Controller', 'Service', 'Repository', 'Provider', 'Gateway' ];

	/**
	 * Register the tokens to listen for.
	 *
	 * @return int[]
	 */
	public function register() {
		return [ T_NEW, T_DOUBLE_COLON ];
	}

	/**
	 * Process each token.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();

		$className = $this->getEnclosingClassName( $phpcsFile, $stackPtr );
		if ( ! $className || ! preg_match( '/Helper$/', $className ) ) {
			return; // Not a Helper class
		}

		$calledClass = null;

		if ( $tokens[ $stackPtr ]['code'] === T_NEW ) {
			$calledClass = $this->getClassNameAfterNew( $phpcsFile, $stackPtr );
		} elseif ( $tokens[ $stackPtr ]['code'] === T_DOUBLE_COLON ) {
			$calledClass = $this->getClassNameBeforeDoubleColon( $phpcsFile, $stackPtr );
		}

		if ( $calledClass !== null ) {
			$this->checkForbiddenSuffix( $phpcsFile, $stackPtr, $calledClass );
		}
	}

	/**
	 * Get the enclosing class/interface/trait name.
	 */
	private function getEnclosingClassName( File $phpcsFile, $stackPtr ): ?string {
		$prevClassPtr = $phpcsFile->findPrevious( [ T_CLASS, T_INTERFACE, T_TRAIT ], $stackPtr );
		if ( $prevClassPtr === false ) {
			return null;
		}

		return $phpcsFile->getDeclarationName( $prevClassPtr ) ?: null;
	}

	/**
	 * Get the fully qualified class name after 'new'.
	 */
	private function getClassNameAfterNew( File $phpcsFile, $stackPtr ): ?string {
		$next = $phpcsFile->findNext( [ T_STRING, T_NS_SEPARATOR ], $stackPtr + 1 );
		if ( $next === false ) {
			return null;
		}

		$calledClass = '';
		$tokens      = $phpcsFile->getTokens();
		for ( $i = $stackPtr + 1; $i <= $next; $i ++ ) {
			$calledClass .= $tokens[ $i ]['content'];
		}

		return $calledClass ?: null;
	}

	/**
	 * Get the fully qualified class name before '::'.
	 * Ignores ::class references and constants (all uppercase).
	 */
	private function getClassNameBeforeDoubleColon( File $phpcsFile, $stackPtr ): ?string {
		$tokens = $phpcsFile->getTokens();
		$next   = $phpcsFile->findNext( [ T_STRING ], $stackPtr + 1 );
		if ( $next === false ) {
			return null;
		}

		$nextContent = $tokens[ $next ]['content'];

		// Ignore ::class and constants
		if ( strtolower( $nextContent ) === 'class' || preg_match( '/^[A-Z0-9_]+$/', $nextContent ) ) {
			return null;
		}

		$prev = $phpcsFile->findPrevious( [ T_STRING, T_NS_SEPARATOR ], $stackPtr - 1 );
		if ( $prev === false ) {
			return null;
		}

		$calledClass = '';
		for ( $i = $prev; $i < $stackPtr; $i ++ ) {
			$calledClass .= $tokens[ $i ]['content'];
		}

		return $calledClass ?: null;
	}

	/**
	 * Check if the called class has a forbidden suffix.
	 */
	private function checkForbiddenSuffix( File $phpcsFile, $stackPtr, string $calledClass ): void {
		foreach ( $this->forbiddenSuffixes as $suffix ) {
			if ( preg_match( '/' . preg_quote( $suffix, '/' ) . '$/', $calledClass ) ) {
				$phpcsFile->addError(
					sprintf( "Helpers can't call %s.", $calledClass ),
					$stackPtr,
					'ForbiddenHelperDependency'
				);
				break;
			}
		}
	}
}
