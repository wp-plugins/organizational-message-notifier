<?php

namespace OrganizationalMessageNotifier\Messages {
	
	use \OrganizationalMessageNotifier\Database as db;
	use \OrganizationalMessageNotifier\Log;
	use \OrganizationalMessageNotifier\Settings;
	use \OrganizationalMessageNotifier\MessageTargets;
	
	function get_target_ids( $target, $target_details ) {
		global $wpdb;
		
		$target_ids = array();
		switch( $target ) {
				
			case MessageTargets::ADMINS:
				$target_ids = get_blog_owners();
				Log::log( 'adding target (blog owners): '.print_r( $target_ids, true ), 1 );
				break;
		
			case MessageTargets::ADMINS_BY_EMAIL:
				$target_ids = get_admins_by_email();
				Log::log( 'adding target (admin emails): '.print_r( $target_ids, true ), 1 );
				break;
		
			case MessageTargets::ALL_USERS:
				$target_ids = $wpdb->get_col( 'SELECT ID FROM '.$wpdb->users );
				Log::log( 'adding target (all users): '.print_r( $target_ids, true ), 1 );
				break;
		
			case MessageTargets::SPECIFIC:
				$target_ids = explode( ',', $target_details );
				Log::log( 'adding target (specific): '.print_r( $target_ids, true ), 1 );
				break;
				
			case MessageTargets::ROLE:
				$target_ids = get_user_ids_by_role( $target_details );
				Log::debug( "adding target (roles = {$target_details}): " . print_r( $target_ids, true ) );
				break;
		}
		return $target_ids;
		
	}
	
	
	function create_message( $target, $title, $text, $link, $target_details = "" ) {
		
		$target_ids = get_target_ids( $target, $target_details );
		
		$author_id = get_current_user_id();
		
		$settings = Settings::getInstance();
		
		global $wpdb;
		
		$message_id = db\insert_message( $title, $text, $link, $author_id, date( "c" ) );
		if( $message_id == false ) {
			return false;
		}
		
		// přidáme cílové uživatele do unread
		foreach( $target_ids as $target_id ) {
			db\insert_target( $message_id, $target_id );
		}
		
		// pokud je zapnuto upozorňování mailem, rozešleme zprávy.
		if( $settings->mail_notification["enabled"] ) {
			$addresses = array();
			foreach( $target_ids as $target_id ) {
				$user_data = get_userdata( $target_id );
				$addresses[] = $user_data->user_email;
			}
			/*$headers = array();
				foreach( $addresses as $address ) {
			$headers[] = "Bcc: $address";
			}*/
			$ret = wp_mail( /*get_bloginfo( "admin_email" )*/ $settings->addresses, $settings->mail_notification["subject"], $settings->mail_notification["message"] );
			Log::debug( "Sending e-mail messages to target users: ".implode( ", ", $addresses )." (success: \"$ret\")." );
		}
		
		// hotovo
		Log::log( 'Message created successfully with id '.$message_id, 2 );
		return true;
	}
	
	
	// vrátí array user_id vlastníků blogů
	function get_blog_owners() {

		// nejdrive ziskame id vsech blogu
		$blogs = wp_get_sites( array( 'public_only' => false ) );
	
		$result = array();
		foreach( $blogs as $blog ) {
			$users = get_users( array(
					'blog_id' => $blog['blog_id'],
					'role' => 'administrator'
			) );
		
			// nasypeme id uzivatelu do pole s vysledky tak, abychom se vyhnuli duplicitam
			foreach( $users as $user ) {
				$result[$user->user_login] = $user->ID;
			}
		}
	
		Log::debug( 'omn_get_blog_owners | result: '.print_r( $result, true ) );
	
		return $result;
	}
	
	
	function get_admins_by_email() {
		$blogs = wp_get_sites( array( 'public_only' => false ) );
		$emails = array();
		foreach( $blogs as $blog ) {
			$email = get_blog_option( $blog['blog_id'], "admin_email" );
			$emails[$email] = $email;
		}
		$results = array();
		foreach( $emails as $email ) {
			$user = get_user_by_email( $email );
			$results[] = $user->ID;
		}
		Log::debug( 'omn_get_admins_by_email | result: '.print_r( $results, true ) );
		return $results;
	}
	
	
	function get_user_ids_by_role( $role_names ) {
		
		$roles = explode( ",", $role_names );
		$users = array();
		
		/* Is there a better option? http://wordpress.stackexchange.com/questions/39315/get-multiple-roles-with-get-users */
		foreach( $roles as $role ) {
        	$users_query = new \WP_User_Query( array(
		            'fields' => 'all_with_meta',
		            'role' => $role,
		            'orderby' => 'display_name' ) );
        	$results = $users_query->get_results();
        	if($results) {
        		$users = array_merge( $users, $results );
        	}
		}
    
		$target_ids = array();
		foreach( $users as $user ) {
			$target_ids[] = $user->ID;
		}
		return $target_ids;
	}
	
	
	/* ************************************************************************* *\
	 		WP_GET_SITES
	\* ************************************************************************* */
	
	// from http://core.trac.wordpress.org/attachment/ticket/14511/wp-get-sites.php
	
	/**
	 * Return a list of sites for the current network
	 *
	 * @since 3.1.0
	 *
	 * @param array|string $args Optional. Override default arguments.
	 * @return array site list and values
	 */
	function wp_get_sites($args){
		// replacement for wp-includes/ms-deprecated.php#get_blog_list
		// see wp-admin/ms-sites.php#352
		//  also wp-includes/ms-functions.php#get_blogs_of_user
		//  also wp-includes/post-template.php#wp_list_pages
		global $wpdb;
	
		$defaults = array(
				'include_id' 		,				// includes only these sites in the results, comma-delimited
				'exclude_id' 		,				// excludes these sites from the results, comma-delimted
				'blogname_like' 	,				// domain or path is like this value
				'ip_like'			,				// Match IP address
				'reg_date_since'	,				// sites registered since (accepts pretty much any valid date like tomorrow, today, 5/12/2009, etc.)
				'reg_date_before'	,				// sites registered before
				'include_user_id'	,				// only sites owned by these users, comma-delimited
				'exclude_user_id'	,				// don't include sites owned by these users, comma-delimited
				'include_spam'		=> false,		// Include sites marked as "spam"
				'include_deleted'	=> false,		// Include deleted sites
				'include_archived'	=> false,		// Include archived sites
				'include_mature'	=> false,		// Included blogs marked as mature
				'public_only'		=> true,		// Include only blogs marked as public
				'sort_column'		=> 'registered',// or registered, last_updated, blogname, site_id
				'order'				=> 'asc',		// or desc
				'limit_results'		,				// return this many results
				'start'				,				// return results starting with this item
		);
		function make_email_list_by_user_id($user_ids){
			$the_users = explode(',',$user_ids);
			$the_emails = array();
			foreach( (array) $the_users as $user_id){
				$the_user = get_userdata($user_id);
				$the_emails[] = $the_user->user_email;
			}
			return $the_emails;
		}
	
	
		// array_merge
		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );
	
		//$query = "SELECT * FROM {$wpdb->blogs}, {$wpdb->registration_log} WHERE site_id = '{$wpdb->siteid}' AND {$wpdb->blogs}.blog_id = {$wpdb->registration_log}.blog_id ";
		$query = "SELECT * FROM {$wpdb->blogs} WHERE site_id = '{$wpdb->siteid}' ";
		if ( isset($include_id) ) {
			$list = implode("','", explode(',', $include_id));
			$query .= " AND {$wpdb->blogs}.blog_id IN ('{$list}') ";
		}
		if ( isset($exclude_id) ) {
			$list = implode("','", explode(',', $exclude_id));
			$query .= " AND {$wpdb->blogs}.blog_id NOT IN ('{$list}') ";
		}
		if ( isset($blogname_like) ) {
			$query .= " AND ( {$wpdb->blogs}.domain LIKE '%".$blogname_like."%' OR {$wpdb->blogs}.path LIKE '%".$blogname_like."%' ) ";
		}
		/*if ( isset($ip_like) ) {
			$query .= " AND {$wpdb->registration_log}.IP LIKE '%".$ip_like."%' ";
		}
		if( isset($reg_date_since) ){
		$query .= " AND unix_timestamp({$wpdb->registration_log}.date_registered) > '".strtotime($reg_date_since)."' ";
		}
		if( isset($reg_date_before) ){
		$query .= " AND unix_timestamp({$wpdb->registration_log}.date_registered) < '".strtotime($reg_date_before)."' ";
		}
		if ( isset($include_user_id) ) {
		$the_emails = make_email_list_by_user_id($include_user_id);
		$list = implode("','", $the_emails);
		$query .= " AND {$wpdb->registration_log}.email IN ('{$list}') ";
		}
		if ( isset($exclude_user_id) ) {
		$the_emails = make_email_list_by_user_id($include_user_id);
		$list = implode("','", $the_emails);
		$query .= " AND {$wpdb->registration_log}.email NOT IN ('{$list}') ";
		}
		if ( isset($ip_like) ) {
		$query .= " AND {$wpdb->registration_log}.IP LIKE ('%".$ip_like."%') ";
		}*/
	
		if( $public_only ) {
			$query .= " AND {$wpdb->blogs}.public = '1'";
		}
	
		$query .= " AND {$wpdb->blogs}.archived = ". (($include_archived) ? "'1'" : "'0'");
		$query .= " AND {$wpdb->blogs}.mature = ". (($include_mature) ? "'1'" : "'0'");
		$query .= " AND {$wpdb->blogs}.spam = ". (($include_spam) ? "'1'" : "'0'");
		$query .= " AND {$wpdb->blogs}.deleted = ". (($include_deleted) ? "'1'" : "'0'");
	
		if ( $sort_column == 'site_id' ) {
			$query .= ' ORDER BY {$wpdb->blogs}.blog_id ';
		} elseif ( $sort_column == 'lastupdated' ) {
			$query .= ' ORDER BY last_updated ';
		} elseif ( $sort_column == 'blogname' ) {
			$query .= ' ORDER BY domain ';
		} else {
			$sort_column = 'registered';
			$query .= " ORDER BY {$wpdb->blogs}.registered ";
		}
	
		$order = ( 'desc' == $order ) ? "DESC" : "ASC";
		$query .= $order;
	
		$limit = '';
		if( isset($limit_results) ){
			if( isset($start) ){
				$limit = $start." , ";
			}
			$query .= "LIMIT ".$limit.$limit_results;
		}
	
		$results = $wpdb->get_results( $query , ARRAY_A );
	
		return $results;
	}
}

?>
