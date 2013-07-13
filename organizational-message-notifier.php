<?php
/*
Plugin Name: Organizational message notifier
Description: Allows network admin to send organizational messages to blog admins. Includes read confirmation.
Version: 2.0.3
Author: Zaantar
Donate link: http://zaantar.eu/financni-prispevek
Author URI: http://zaantar.eu
Plugin URI: http://wordpress.org/extend/plugins/organizational-message-notifier
License: GPL2
*/

/*
    Copyright 2011-2013 Zaantar (email: zaantar@zaantar.eu)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


namespace OrganizationalMessageNotifier {

	use \OrganizationalMessageNotifier\Database as db;
	use \OrganizationalMessageNotifier\z;

	/* Assure that 'class-wp-list-table.php' is available. */
	if(!class_exists('WP_List_Table')) {
		require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
	}

	/* Include all neccessary plugin files */
	$includes = array(
			"database.php",
			"message-table.php",
			"messages-ui.php",
			"messages.php",
			"notification.php",
			"settings-ui.php",
			"settings.php",
			"zan.php" );

	/* Include the files defined above */
	foreach( $includes as $include ) {
		require_once plugin_dir_path( __FILE__ ) . "includes/$include";
	}


	/* Installation */
	register_activation_hook( __FILE__, "\OrganizationalMessageNotifier\install" );

	function install() {
		db\create_tables();
	}


	/* I18N */
	define( "OMN_TXD", "organizational-message-notifier" );

	add_action( 'init', "\OrganizationalMessageNotifier\load_plugin_textdomain" );


	function load_plugin_textdomain() {
		\load_plugin_textdomain( OMN_TXD, false, basename( dirname(__FILE__) ) . "/languages" );
	}

	/* Logging */
	define( "OMN_LOGNAME", "organizational-message-notifier" );
	
	
	class Log {
	
		/* This is a static class */
		private function __construct() { }
	
		static function dberror( $action ) {
			global $wpdb;
			self::log( "Database error while performing action '$action': QUERY '{$wpdb->last_query}'; RESULT '".print_r( $wpdb->last_result, true )."'; ERROR '{$wpdb->last_error}'.", 4 );
		}
	
	
		static function log( $message, $severity ) {
			if( defined( 'WLS' ) && wls_is_registered( OMN_LOGNAME ) ) {
				wls_simple_log( OMN_LOGNAME, $message, $severity );
			}
		}
	
	
		static function debug( $message ) {
			self::log( $message, 1 );
		}
	
	
		static function info( $message ) {
			self::log( $message, 2 );
		}
	
		static function warning( $message ) {
			self::log( $message, 3 );
		}
	
		static function error( $message ) {
			self::log( $message, 4 );
		}
	
		static function fatal( $message ) {
			self::log( $message, 5 );
		}
	
	
		static function register() {
			if( defined( "WLS" ) ) {
				return wls_register( OMN_LOGNAME, __( "Organizational Message Notifier events.", OMN_TXD ) );
			} else {
				return false;
			}
		}
	
	
		static function unregister() {
			if( defined( "WLS" ) ) {
				return wls_unregister( OMN_LOGNAME );
			} else {
				return false;
			}
		}
	
	}

}

?>
