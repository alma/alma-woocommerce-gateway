<?php

namespace Alma\Gateway\Infrastructure\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not allowed' ); // Exit if accessed directly.
}

use Alma\Gateway\Infrastructure\Adapter\UserAdapter;
use Alma\Gateway\Infrastructure\Exception\Repository\UserRepositoryException;
use Alma\Plugin\Infrastructure\Adapter\UserAdapterInterface;
use Alma\Plugin\Infrastructure\Repository\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface {

	/**
	 * Get user by ID.
	 *
	 * @param int $userId User ID.
	 *
	 * @return UserAdapterInterface
	 * @throws UserRepositoryException If user not found.
	 */
	public function getById( int $userId ): UserAdapterInterface {
		$wp_user = get_user_by( 'id', $userId );

		if ( $wp_user ) {
			return new UserAdapter( $wp_user );
		}

		throw new UserRepositoryException( sprintf( 'Undefined User id: %d', $userId ) );
	}

}
