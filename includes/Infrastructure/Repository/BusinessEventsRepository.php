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
	public function createTable(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			`alma_business_data_id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			`cart_id` BIGINT(20) NOT NULL,
			`order_id` BIGINT(20) UNSIGNED DEFAULT NULL,
			`alma_payment_id` VARCHAR(255) DEFAULT NULL,
			`is_bnpl_eligible` TINYINT(1) DEFAULT NULL,
			PRIMARY KEY (`alma_business_data_id`),
			UNIQUE KEY unique_event_id (event_id)
			UNIQUE KEY `unique_cart_id` (`cart_id`),
	        UNIQUE KEY `unique_alma_payment_id` (`alma_payment_id`)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function isCartIdValid(int $cartId): bool {
		global $wpdb;
		$table_name = $wpdb->prefix . BusinessEventsService::ALMA_BUSINESS_EVENT_TABLE;

		$result = $wpdb->get_row( $wpdb->prepare(
			"SELECT order_id FROM $table_name WHERE cart_id = %d",
			$cartId
		) );

		if (! $result) {
			return false;
		}
		if ($result->order_id !== null) {
			return false;
		}

		return true;
	}
}
