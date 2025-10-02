<?php

namespace AlmaIntegrationCms\Sniffs\Architecture;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ForbiddenPrefixSniff implements Sniff {
	/**
	 * Liste par défaut — peut être surchargée via ruleset.xml si besoin.
	 * Exemples: wp_, wc_
	 *
	 * @var string[]
	 */
	public $forbiddenPrefixes = array( 'wp_', 'wc_' );

	/**
	 * On s'intéresse aux T_STRING (noms de fonctions / constantes / etc.)
	 */
	public function register() {
		return array( T_STRING );
	}

	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();
		$name   = $tokens[ $stackPtr ]['content'];

		// Vérifier que c'est bien un appel de fonction : le prochain token non-blanc est '('
		$next = $phpcsFile->findNext( array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), $stackPtr + 1, null, true );
		if ( $next === false || $tokens[ $next ]['code'] !== T_OPEN_PARENTHESIS ) {
			return;
		}

		foreach ( $this->forbiddenPrefixes as $prefix ) {
			if ( stripos( $name, $prefix ) === 0 ) {
				$error = sprintf(
					"Appel direct à la fonction '%s' (préfixe interdit '%s') — interdit dans la couche métier.",
					$name,
					$prefix
				);
				$phpcsFile->addError( $error, $stackPtr, 'ForbiddenPrefix' );

				return;
			}
		}
	}
}
