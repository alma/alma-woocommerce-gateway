<?php

namespace AlmaIntegrationCms\Sniffs\Scope;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class NoPublicMemberVarSniff implements Sniff {
	/**
	 * Détermine les jetons que le sniff doit écouter.
	 * On écoute le jeton de visibilité 'public'.
	 *
	 * @return array
	 */
	public function register() {
		return [ T_PUBLIC ];
	}

	/**
	 * Traite les jetons trouvés.
	 *
	 * @param File $phpcsFile L'objet File en cours.
	 * @param int  $stackPtr L'index du jeton T_PUBLIC.
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$tokens = $phpcsFile->getTokens();
		$error  = 'Les propriétés de classe ne doivent pas être déclarées public. Utilisez private ou protected.';

		// Chercher le prochain T_VARIABLE après T_PUBLIC en sautant les types et espaces
		$nextVar = $phpcsFile->findNext(
			[ T_VARIABLE ],
			$stackPtr + 1,
			null,
			false,
			null,
			true
		);

		// Vérifier qu'il y a bien une variable
		if ( $nextVar === false ) {
			return;
		}

		// Vérifier si on est dans une classe/trait
		$prevClass = $phpcsFile->findPrevious( [ T_CLASS, T_TRAIT ], $stackPtr - 1 );
		if ( $prevClass === false ) {
			return;
		}

		// Vérifier que ce n'est pas une méthode
		$isFunction = $phpcsFile->findNext( T_FUNCTION, $stackPtr + 1, $nextVar );
		if ( $isFunction === false ) {
			// C'est une propriété de classe typée ou non
			$phpcsFile->addError( $error, $stackPtr, 'Found' );
		}
	}
}
