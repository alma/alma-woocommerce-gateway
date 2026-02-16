<?php
/**
 * Ensures that all PHP files have ABSPATH check at the beginning.
 *
 * @package AlmaIntegrationCms
 */

namespace AlmaIntegrationCms\Sniffs\Security;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Class AbspathCheckSniff
 *
 * Verifies that PHP files contain the WordPress security check:
 * if ( ! defined( 'ABSPATH' ) ) { die(); }
 *
 * @package AlmaIntegrationCms\Sniffs\Security
 */
class AbspathCheckSniff implements Sniff {

	/**
	 * Returns the token types that this sniff is interested in.
	 *
	 * @return array<int>
	 */
	public function register(): array {
		return array( T_OPEN_TAG );
	}

	/**
	 * Processes the tokens that this sniff is interested in.
	 *
	 * @param File $phpcsFile The file where the token was found.
	 * @param int  $stackPtr The position in the stack where the token was found.
	 *
	 * @return int|void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$fileName = $phpcsFile->getFilename();

		if ( $this->shouldSkipFile( $fileName ) ) {
			return $phpcsFile->numTokens + 1;
		}

		if ( ! $this->isFirstOpeningTag( $phpcsFile, $stackPtr ) ) {
			return;
		}

		$namespacePos = $this->findNamespacePosition( $phpcsFile, $stackPtr );
		if ( ! $namespacePos ) {
			// No namespace, check at the beginning
			if ( ! $this->hasAbspathCheck( $phpcsFile, $stackPtr ) ) {
				$this->addAbspathError( $phpcsFile, $stackPtr );
			}

			return $phpcsFile->numTokens + 1;
		}

		// Find the position after namespace declaration
		$afterNamespacePos = $this->findPositionAfterNamespace( $phpcsFile, $namespacePos );

		// Check if ABSPATH is present between namespace and first use statement
		if ( ! $this->hasAbspathCheckBetweenNamespaceAndUse( $phpcsFile, $afterNamespacePos ) ) {
			$this->addAbspathError( $phpcsFile, $namespacePos );
		}

		// Only check the first opening tag
		return $phpcsFile->numTokens + 1;
	}

	/**
	 * Check if the file should be skipped from ABSPATH check.
	 *
	 * @param string $fileName The file name.
	 *
	 * @return bool
	 */
	private function shouldSkipFile( string $fileName ): bool {
		$skipPatterns = array(
			'/tests/',
			'/Tests/',
			'/vendor/',
			'/phpcs-custom/',
		);

		foreach ( $skipPatterns as $pattern ) {
			if ( strpos( $fileName, $pattern ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the current token is the first opening PHP tag.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position in the stack.
	 *
	 * @return bool
	 */
	private function isFirstOpeningTag( File $phpcsFile, int $stackPtr ): bool {
		$tokens = $phpcsFile->getTokens();

		if ( $stackPtr === 0 ) {
			return true;
		}

		return $tokens[ $stackPtr - 1 ]['code'] === T_INLINE_HTML;
	}

	/**
	 * Check if the file contains ABSPATH check.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position in the stack.
	 *
	 * @return bool
	 */
	private function hasAbspathCheck( File $phpcsFile, int $stackPtr ): bool {
		$tokens      = $phpcsFile->getTokens();
		$searchLimit = min( $stackPtr + 1000, $phpcsFile->numTokens );

		for ( $i = $stackPtr; $i < $searchLimit; $i ++ ) {
			if ( $this->isDefinedFunction( $tokens, $i ) ) {
				if ( $this->checkForAbspathInDefined( $phpcsFile, $tokens, $i ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the current token is a 'defined' function call.
	 *
	 * @param array $tokens The tokens array.
	 * @param int   $pos The position in the tokens array.
	 *
	 * @return bool
	 */
	private function isDefinedFunction( array $tokens, int $pos ): bool {
		return $tokens[ $pos ]['code'] === T_STRING && $tokens[ $pos ]['content'] === 'defined';
	}

	/**
	 * Check if the defined() function contains ABSPATH constant.
	 *
	 * @param File  $phpcsFile The file being scanned.
	 * @param array $tokens The tokens array.
	 * @param int   $pos The position of the 'defined' token.
	 *
	 * @return bool
	 */
	private function checkForAbspathInDefined( File $phpcsFile, array $tokens, int $pos ): bool {
		$nextToken = $phpcsFile->findNext( T_WHITESPACE, $pos + 1, null, true );

		if ( ! $nextToken || $tokens[ $nextToken ]['code'] !== T_OPEN_PARENTHESIS ) {
			return false;
		}

		$closeParenthesis = $tokens[ $nextToken ]['parenthesis_closer'];

		for ( $j = $nextToken + 1; $j < $closeParenthesis; $j ++ ) {
			if ( $tokens[ $j ]['code'] === T_CONSTANT_ENCAPSED_STRING ) {
				$constantName = trim( $tokens[ $j ]['content'], '\'"' );
				if ( $constantName === 'ABSPATH' ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Add error for missing ABSPATH check.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position in the stack.
	 *
	 * @return void
	 */
	private function addAbspathError( File $phpcsFile, int $stackPtr ): void {
		$error = 'File must contain ABSPATH security check after namespace and before use statements: if ( ! defined( \'ABSPATH\' ) ) { die( \'Not allowed\' ); }';
		$phpcsFile->addError( $error, $stackPtr, 'MissingABSPATHCheck' );
	}

	/**
	 * Find the position of the namespace declaration.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $stackPtr The position in the stack.
	 *
	 * @return int|false
	 */
	private function findNamespacePosition( File $phpcsFile, int $stackPtr ) {
		return $phpcsFile->findNext( T_NAMESPACE, $stackPtr );
	}

	/**
	 * Find the position after the namespace declaration (after semicolon).
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $namespacePos The position of namespace keyword.
	 *
	 * @return int
	 */
	private function findPositionAfterNamespace( File $phpcsFile, int $namespacePos ): int {
		$semicolonPos = $phpcsFile->findNext( T_SEMICOLON, $namespacePos );
		if ( $semicolonPos ) {
			return $semicolonPos + 1;
		}

		return $namespacePos + 1;
	}

	/**
	 * Check if ABSPATH check is present between namespace and first use statement.
	 *
	 * @param File $phpcsFile The file being scanned.
	 * @param int  $startPos The position to start searching from.
	 *
	 * @return bool
	 */
	private function hasAbspathCheckBetweenNamespaceAndUse( File $phpcsFile, int $startPos ): bool {
		$tokens = $phpcsFile->getTokens();

		// Find the first use statement
		$firstUsePos = $phpcsFile->findNext( T_USE, $startPos );

		// If no use statement, search until reasonable limit
		$searchLimit = $firstUsePos ? $firstUsePos : min( $startPos + 100, $phpcsFile->numTokens );

		// Search for ABSPATH check between namespace and first use
		for ( $i = $startPos; $i < $searchLimit; $i ++ ) {
			if ( $this->isDefinedFunction( $tokens, $i ) ) {
				if ( $this->checkForAbspathInDefined( $phpcsFile, $tokens, $i ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
