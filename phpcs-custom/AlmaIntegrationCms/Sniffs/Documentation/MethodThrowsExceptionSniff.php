<?php

declare( strict_types=1 );

namespace AlmaIntegrationCms\Sniffs\Documentation;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class MethodThrowsExceptionSniff implements Sniff {
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

		// Get the scope of the function.
		if ( ! isset( $tokens[ $stackPtr ]['scope_opener'], $tokens[ $stackPtr ]['scope_closer'] ) ) {
			return;
		}
		$scopeOpener = $tokens[ $stackPtr ]['scope_opener'];
		$scopeCloser = $tokens[ $stackPtr ]['scope_closer'];

		// Check for throw statements inside the method.
		$throwPtr = $phpcsFile->findNext( T_THROW, $scopeOpener + 1, $scopeCloser );
		if ( $throwPtr === false ) {
			// No throw statement, no need to check for @throws.
			return;
		}

		// Find the PHPDoc for the method.
		$docCommentEnd = $phpcsFile->findPrevious( T_DOC_COMMENT_CLOSE_TAG, $stackPtr - 1 );
		if ( $docCommentEnd === false ) {
			$phpcsFile->addError( 'Method contains a throw statement but has no PHPDoc.', $stackPtr,
				'MissingDocblock' );

			return;
		}

		$docCommentOpen = $tokens[ $docCommentEnd ]['comment_opener'];
		$hasThrowsTag   = false;
		for ( $i = $docCommentOpen; $i < $docCommentEnd; $i ++ ) {
			if ( $tokens[ $i ]['code'] === T_DOC_COMMENT_TAG && $tokens[ $i ]['content'] === '@throws' ) {
				$hasThrowsTag = true;
				break;
			}
		}

		if ( ! $hasThrowsTag ) {
			$phpcsFile->addError( 'Missing @throws tag in PHPDoc for method containing a throw statement.', $stackPtr,
				'MissingThrowsTag' );
		}
	}
}
