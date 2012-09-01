<?php
/*
Plugin Name: Organizational message notifier
Description: Allows network admin to send organizational messages to blog admins. Includes read confirmation.
Version: 1.5.5
Author: Zaantar
Donate link: http://zaantar.eu/financni-prispevek
Author URI: http://zaantar.eu
Plugin URI: http://wordpress.org/extend/plugins/organizational-message-notifier

License: GPL2
*/

/*
    Copyright 2011 Zaantar (email: zaantar@gmail.com)

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


require_once plugin_dir_path( __FILE__ ).'includes/overview.php';
require_once plugin_dir_path( __FILE__ ).'includes/overview-table.php';
require_once plugin_dir_path( __FILE__ ).'includes/notification.php';
require_once plugin_dir_path( __FILE__ ).'includes/database.php';
require_once plugin_dir_path( __FILE__ ).'includes/settings.php';

register_activation_hook( __FILE__,'omn_plugin_activation' );


/*****************************************************************************\
		I18N
\*****************************************************************************/


define( 'OMN_TEXTDOMAIN', 'organizational-message-notifier' );
define( "OMN_TXD", OMN_TEXTDOMAIN );


add_action( 'init', 'omn_load_textdomain' );

function omn_load_textdomain() {
	$plugin_dir = basename( dirname(__FILE__) );
	load_plugin_textdomain( OMN_TEXTDOMAIN, false, $plugin_dir.'/languages' );
}



/* ************************************************************************* *\
	ADMIN MENU & NOTICES
\* ************************************************************************* */


add_action( 'network_admin_menu','omn_network_admin_menu' );

function omn_network_admin_menu() {
	add_submenu_page( 
		'index.php', 
		__( 'Organizational messages', OMN_TEXTDOMAIN ), 
		__( 'Organizational messages', OMN_TEXTDOMAIN ), 
		'manage_network_options', 
		'omn-superadmin-overview', 
		'omn_superadmin_overview_page' 
	);
	add_submenu_page( 
		'settings.php', 
		__( 'Organizational Message Notifier', OMN_TEXTDOMAIN ), 
		__( 'Organizational Message Notifier', OMN_TEXTDOMAIN ), 
		'manage_network_options', 
		'omn-settings',
		'omn_settings_page'
	);

}

add_action( 'admin_menu','omn_admin_menu' );

function omn_admin_menu() {
	extract( omn_get_settings() );
	add_submenu_page( 
		'index.php', 
		__( 'Organizational messages', OMN_TEXTDOMAIN ), 
		__( 'Organizational messages', OMN_TEXTDOMAIN ), 
		$minimal_capability, 
		'omn-messages', 
		'omn_messages_page' 
	);
}


function omn_nag( $message ) {
	echo( '<div id="message" class="updated"><p>'.$message.'</p></div>' );
}

function omn_nagerr( $message ) {
	echo( '<div id="message" class="error"><p>'.$message.'</p></div>' );
}


define( 'OMN_LOGNAME', 'organizational-message-notifier' );

function omn_log( $message, $category = 1 ) {
	if( defined( 'WLS' ) && wls_is_registered( OMN_LOGNAME ) ) {
		wls_simple_log( OMN_LOGNAME, $message, $category );
	}
}


?>
