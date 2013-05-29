<?php

namespace OrganizationalMessageNotifier\Notification {
	
	use \OrganizationalMessageNotifier\MessagesUI as mui;
	use \OrganizationalMessageNotifier\z;
	use \OrganizationalMessageNotifier\Settings;
	use \OrganizationalMessageNotifier\Database as db;
	
	function get_messages_page_link( $user_id ) {
		if( is_multisite() ) {
			$userdata = get_userdata( $user_id );
			switch_to_blog( $userdata->primary_blog );
		}
		$url = site_url( "/wp-admin/index.php?page=" . mui\PAGE_READING );
		if( is_multisite() ) {
			restore_current_blog();
		}
		return $url;
	}


	/* Admin notices */
	add_action( "admin_notices", "\OrganizationalMessageNotifier\Notification\admin_notices" );
	
	
	function admin_notices() {
		$count = db\get_user_unread_message_count( get_current_user_id() );
		if( $count > 0 ) {
			$settings = Settings::getInstance();
			//if( current_user_can( $settings->minimal_capability ) ) {
				$url = get_messages_page_link( get_current_user_id() );
				z::nag( sprintf( __( '%sYou have %d unread messages%s concerning website operation. For reading them continue, please, %shere%s.', OMN_TXD ), '<strong>', $count, '</strong>', '<a href="'.$url.'">', '</a>.' ) );
			//}
		}
	}
	
	
	/* Admin bar menu */
	add_action( "admin_bar_menu", "\OrganizationalMessageNotifier\Notification\admin_bar_menu" );
	
	function admin_bar_menu() {
		global $wp_admin_bar;
		$count = db\get_user_unread_message_count( get_current_user_id() );
		if( $count > 0 ) {
			$settings = Settings::getInstance();
			//if( current_user_can( $settings->minimal_capability ) ) {
				$wp_admin_bar->add_menu( array(
						'id' => 'omn-notice',
						'title' => sprintf( __( 'You have %d unread messages! Click here to read them.', OMN_TXD ), $count ),
						'href' => get_messages_page_link( get_current_user_id() ) ) );
			//}
		}
	}
		
}

?>
