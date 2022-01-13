#!/bin/bash
set -Eeauo pipefail

wp_set_option_arr() {
  echo "$2" \
  | php -r "echo json_encode(unserialize(fgets(STDIN)));" \
  | /usr/local/bin/wp --format=json --path=/var/www/html --allow-root option set $1
}
wp_set_option() {
  /usr/local/bin/wp --path=/var/www/html --allow-root option set $1 "$2"
}
wp_set_option_arr woocommerce_admin_notices                     'a:2:{i:0;s:20:"no_secure_connection";i:1;s:23:"regenerating_thumbnails";}'
wp_set_option_arr woocommerce_all_except_countries              'a:0:{}'
wp_set_option_arr woocommerce_onboarding_profile                'a:8:{s:12:"setup_client";b:0;s:8:"industry";a:1:{i:0;a:1:{s:4:"slug";s:21:"electronics-computers";}}s:13:"product_types";a:2:{i:0;s:8:"physical";i:1;s:9:"downloads";}s:13:"product_count";s:4:"1-10";s:14:"selling_venues";s:2:"no";s:19:"business_extensions";a:1:{i:0;s:20:"woocommerce-services";}s:5:"theme";s:10:"storefront";s:9:"completed";b:1;}'
wp_set_option_arr woocommerce_specific_allowed_countries        'a:0:{}'
wp_set_option_arr woocommerce_specific_ship_to_countries        'a:0:{}'
wp_set_option_arr woocommerce_task_list_tracked_completed_tasks 'a:3:{i:0;s:13:"store_details";i:1;s:8:"products";i:2;s:8:"payments";}'

wp_set_option woocommerce_catalog_columns                   3
wp_set_option woocommerce_catalog_rows                      4
wp_set_option woocommerce_currency                          'EUR'
wp_set_option woocommerce_currency_pos                      'right'
wp_set_option woocommerce_default_country                   'FR'
wp_set_option woocommerce_price_decimal_sep                 ','
wp_set_option woocommerce_store_city                        'Paris'
wp_set_option woocommerce_store_postcode                    '75001'
wp_set_option woocommerce_store_address                     '1 rue de la paix'
wp_set_option woocommerce_price_thousand_sep                ' '
wp_set_option woocommerce_task_list_welcome_modal_dismissed 'yes'
