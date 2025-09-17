<?php

namespace Alma\Gateway\Infrastructure\Adapter;

use Alma\API\Domain\Adapter\UserAdapterInterface;
use BadMethodCallException;
use WP_User;

/**
 * Class UserAdapter
 */
class UserAdapter implements UserAdapterInterface {

	private WP_User $wpUser;

	public function __construct( WP_User $wpUser ) {
		$this->wpUser = $wpUser;
	}

	/**
	 * Dynamic call to all WP_User methods
	 */
	public function __call( string $name, array $arguments ) {
		// Convert camelCase to snake_case
		$snakeCaseName = strtolower( preg_replace( '/(?<!^)([A-Z0-9])/', '_$1', $name ) );

		if ( method_exists( $this->wpUser, $snakeCaseName ) ) {
			return $this->wpUser->{$snakeCaseName}( ...$arguments );
		}

		throw new BadMethodCallException( "Method $name (→ $snakeCaseName) does not exists on WP_User" );
	}

	/**
	 * Get the user ID.
	 * Force the mapping of getId to get_ID() from WP_User
	 *
	 * @return int
	 */
	public function getId(): int {
		return $this->wpUser->get_ID();
	}

	/**
	 * Check if the user can manage Alma (i.e., has 'manage_woocommerce' capability).
	 *
	 * @return bool
	 */
	public function canManageAlma(): bool {
		return $this->wpUser->has_cap( 'manage_woocommerce' );
	}

	public function getDisplayName(): string {
		return $this->wpUser->get_display_name();
	}
}
