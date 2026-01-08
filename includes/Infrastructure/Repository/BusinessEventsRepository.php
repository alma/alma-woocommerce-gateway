<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\BusinessEventsRepositoryInterface;
use Alma\Gateway\Application\Service\BusinessEventsService;

//class BusinessEventsRepository implements BusinessEventsRepositoryInterface // TODO: Uncomment when interface was released in PHP Client
class BusinessEventsRepository
{
	/**
	 * Create the necessary table in the database for Business Event.
	 *
	 * @return void
	 */
	public function createTableIfNotExists(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`cart_id` BIGINT(20) NOT NULL,
			`order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
			`alma_payment_id` VARCHAR(255) DEFAULT NULL,
			`is_bnpl_eligible` TINYINT(1) DEFAULT NULL,
			PRIMARY KEY (`cart_id`),
	        UNIQUE KEY `unique_alma_payment_id` (`alma_payment_id`)
		) $charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * @param int $cartId
	 *
	 * @return bool
	 */
	public function alreadyExist(int $cartId): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE cart_id = %d",
			$cartId
		) );

		if ($result === '0') {
			return false;
		}

		return true;
	}

	/**
	 * @param int $cartId
	 *
	 * @return void
	 */
	public function saveCartId(int $cartId): void {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$wpdb->insert(
			$table_name,
			[
				'cart_id' => $cartId,
			],
			[
				'%d',
			]
		);
	}

	/**
	 * @param int  $cartId
	 * @param bool $isEligible
	 *
	 * @return void
	 */
	public function saveEligibility(int $cartId, bool $isEligible): void {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$wpdb->update(
			$table_name,
			[
				'is_bnpl_eligible' => $isEligible ? 1 : 0,
			],
			[
				'cart_id' => $cartId,
			],
			[
				'%d',
			],
			[
				'%d',
			]
		);
	}

	/**
	 * @param int $orderId
	 *
	 * @return object|null
	 */
	public function getRowByOrderId(int $orderId): ?object {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM $table_name WHERE order_id = %d",
			$orderId
		) );

		return $result ?: null;
	}
}
