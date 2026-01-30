<?php

namespace Alma\Gateway\Infrastructure\Repository;

use Alma\API\Domain\Repository\BusinessEventsRepositoryInterface;
use Alma\Gateway\Application\Service\BusinessEventsService;

class BusinessEventsRepository implements BusinessEventsRepositoryInterface
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

		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");

		if ($table_exists) {
			return;
		}

		$sql = "CREATE TABLE $table_name (
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
	 * Return cart row with order_id if exist in the database or return null.
	 * object(stdClass)#4815 (1) { ["order_id"]=> string(3) "278" } => if order converted
	 * object(stdClass)#4736 (1) { ["order_id"]=> NULL } => if cart exist and order not yet converted
	 * NULL => if cart ID does not exist
	 * @param int $cartId
	 *
	 * @return object|null
	 */
	public function getCartRowIfExist(int $cartId): ?object {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT order_id FROM $table_name WHERE cart_id = %d",
			$cartId
		) );

		return $result ?: null;
	}

	/**
	 * If cart ID already exists in the database.
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
	 * If cart ID was already converted to order.
	 * @param int $cartId
	 *
	 * @return bool
	 */
	public function alreadyConverted(int $cartId): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT order_id FROM $table_name WHERE cart_id = %d",
			$cartId
		) );

		if ($result === null) {
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

	/**
	 * @param int $cartId
	 * @param int $orderId
	 *
	 * @return void
	 */
	public function saveOrderId(int $cartId, int $orderId): void {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$wpdb->update(
			$table_name,
			[
				'order_id' => $orderId,
			],
			[
				'cart_id' => $cartId,
			],
			[
				'order_id' => '%d',
			],
			[
				'cart_id' => '%d',
			]
		);
	}

	/**
	 * @param int    $cartId
	 * @param string $almaPaymentId
	 *
	 * @return void
	 */
	public function saveAlmaPaymentId(int $cartId, string $almaPaymentId): void {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$wpdb->update(
			$table_name,
			[
				'alma_payment_id' => $almaPaymentId,
			],
			[
				'cart_id' => $cartId,
			],
			[
				'alma_payment_id' => '%s',
			],
			[
				'cart_id' => '%d',
			]
		);
	}
}
