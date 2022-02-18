<?php

namespace _;

/*
 *
 * @link              https://github.com/midweste/wp-underscore
 * @since             1.0.0
 * @package           wp-underscore
 *
 * @wordpress-plugin
 * Plugin Name:       WP Underscore
 * Plugin URI:        https://github.com/midweste/wp-underscore
 * Description:       WP Underscore is a plugin for wordpress developers with helper libraries and additional functions.
 * Version:           1.0.0
 * Author:            midweste
 * Author URI:        https://github.com/midweste/wp-underscore
 * License:           GPL-2.0+
 * Requires PHP:      7.2
 */

define( 'WPUNDERSCORE', dirname( __FILE__ ) );

call_user_func(function () {
	//require_once WPUNDERSCORE . '/vendor/autoload.php';
	foreach ( glob( WPUNDERSCORE . '/src/functions/*.php' ) as $autoload ) {
		require_once $autoload;
	}
});
