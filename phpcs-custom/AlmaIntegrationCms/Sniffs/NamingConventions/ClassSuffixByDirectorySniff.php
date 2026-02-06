<?php

declare( strict_types=1 );

namespace AlmaIntegrationCms\Sniffs\Architecture;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Ensures classes follow directory-based suffix conventions.
 * Example:
 * - /Service/ => *Service
 * - /Exception/ => *Exception
 * - /Helper/ => *Helper
 * - /Gateway/ => *Gateway or *GatewayInterface
 */
class ClassSuffixByDirectorySniff implements Sniff {
	/**
	 * Directory → required suffix(es).
	 *
	 * @var array<string, string[]>
	 */
	private array $directorySuffixMap = [
		'Adapter'    => [ 'Adapter', 'AdapterInterface', 'AdapterException' ],
		'Block'      => [ 'Block', 'BlockInterface', 'BlockException', 'BlockFactory' ],
		'Config'     => [ 'Config', 'ConfigException' ],
		'Controller' => [ 'Controller', 'ControllerException' ],
		'Exception'  => [ 'Exception' ],
		'Gateway'    => [ 'Gateway', 'GatewayInterface', 'GatewayException' ],
		'Helper'     => [ 'Helper', 'HelperException' ],
		'Mapper'     => [ 'Mapper', 'MapperInterface', 'MapperException' ],
		'Provider'   => [ 'Provider', 'ProviderFactory', 'ProviderInterface', 'ProviderException' ],
		'Repository' => [ 'Repository', 'RepositoryInterface', 'RepositoryException' ],
		'Service'    => [ 'Service', 'ServiceException' ],
	];

	/**
	 * Listen only for class declarations.
	 *
	 * @return int[]
	 */
	public function register() {
		return [ T_CLASS, T_INTERFACE ];
	}

	/**
	 * Process each class or interface declaration.
	 *
	 * @param File $phpcsFile
	 * @param int  $stackPtr
	 *
	 * @return void
	 */
	public function process( File $phpcsFile, $stackPtr ) {
		$className = $phpcsFile->getDeclarationName( $stackPtr );
		if ( ! $className ) {
			return;
		}

		$filePath = $phpcsFile->getFilename();

		foreach ( $this->directorySuffixMap as $directory => $suffixes ) {
			if ( preg_match( '#[\\\\/]' . $directory . '[\\\\/]#i', $filePath ) ) {

				// Check if class name ends with one of the allowed suffixes
				$matchesSuffix = false;
				foreach ( $suffixes as $suffix ) {
					if ( preg_match( '/' . preg_quote( $suffix, '/' ) . '$/', $className ) ) {
						$matchesSuffix = true;
						break;
					}
				}

				if ( ! $matchesSuffix ) {
					$phpcsFile->addError(
						sprintf(
							"Classes in '%s' directory must be suffixed with one of: %s (found: %s).",
							$directory,
							implode( ', ', $suffixes ),
							$className
						),
						$stackPtr,
						'WrongClassSuffix'
					);
				}

				// Stop after first match
				return;
			}
		}
	}
}
