<?php

namespace Alma\Woocommerce\Repositories;

class AlmaBusinessEventRepository
{
	public function create_alma_business_data_table()
	{
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'alma_business_data';

		$sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'alma_business_data` (
            `id_alma_business_data` int(10) NOT NULL AUTO_INCREMENT,
            `id_cart` int(10) NOT NULL,
            `id_order` int(10) DEFAULT NULL,
            `alma_payment_id` varchar(255) DEFAULT NULL,
            `is_bnpl_eligible` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
            `plan_key` varchar(255) NOT NULL,
            PRIMARY KEY (`id_alma_business_data`),
            UNIQUE KEY `unique_id_cart` (`id_cart`),
            UNIQUE KEY `unique_alma_payment_id` (`alma_payment_id`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

		$sql = "CREATE TABLE $table_name (
		  `id_alma_business_data` int(10) NOT NULL AUTO_INCREMENT,
            `cart_id` int(10) NOT NULL,
            `session_id` int(10) NOT NULL,
            `user_id` int(10) NOT NULL,
            `order_id` int(10) DEFAULT NULL,
            `alma_payment_id` varchar(255) DEFAULT NULL,
            `is_bnpl_eligible` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
            `plan_key` varchar(255) NOT NULL,
            PRIMARY KEY (`id_alma_business_data`),
            UNIQUE KEY `unique_cart_id` (`cart_id`),
            UNIQUE KEY `unique_alma_payment_id` (`alma_payment_id`)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}