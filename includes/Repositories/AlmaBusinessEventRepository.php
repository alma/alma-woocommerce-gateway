<?php

namespace Alma\Woocommerce\Repositories;

class AlmaBusinessEventRepository
{
	public function create_alma_business_data_table()
	{
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'alma_business_data';

		$sql = "CREATE TABLE $table_name (
		    `alma_business_data_id` bigint(20) NOT NULL AUTO_INCREMENT,
	        `cart_id` bigint(20) NOT NULL,
	        `order_id` bigint(20) unsigned DEFAULT NULL,
	        `alma_payment_id` varchar(255) DEFAULT NULL,
	        `is_bnpl_eligible` tinyint(1) DEFAULT NULL,
	        `plan_key` varchar(255) DEFAULT NULL,
	        PRIMARY KEY (`alma_business_data_id`),
	        UNIQUE KEY `unique_cart_id` (`cart_id`),
	        UNIQUE KEY `unique_alma_payment_id` (`alma_payment_id`)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php'; // NOSONAR
		dbDelta( $sql );
	}
}