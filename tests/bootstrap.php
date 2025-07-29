<?php

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// Pay attention that this NONCE_SALT is used to encrypt keys in OptionsServiceTest.php. Do not change it.
if ( ! defined( 'NONCE_SALT' ) ) {
	define( 'NONCE_SALT', 'youhou! this is super key!' );
}


require_once ABSPATH . '/vendor/autoload.php';
