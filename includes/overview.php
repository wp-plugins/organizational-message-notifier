<?php

/* ************************************************************************* *\
	SUPERADMIN OVERVIEW
\* ************************************************************************* */


function omn_superadmin_overview_page() {

	if( isset($_REQUEST['action']) ) {
        $action = $_REQUEST['action'];
    } else {
        $action = 'default';
    }
    
    switch( $action ) {
    case 'add':
    	omn_superadmin_overview_page_add();
    	break;
    case 'delete-notification':
    	omn_delete_notification( $_GET['user'], $_GET['message'] );
    	omn_superadmin_overview_page_default();
    	break;
    case 'expire-message':
    	omn_delete_notifications( $_GET['id'] );
    	omn_superadmin_overview_page_default();
    	break;
    case 'delete-message':
    	omn_delete_message( $_GET['id'] );
    	omn_superadmin_overview_page_default();
    	break;
    default:
    	omn_superadmin_overview_page_default();
    	break;
    }
}


function omn_superadmin_overview_page_default() {

	$messages = omn_get_messages();
	extract( omn_get_settings() );
	
	$table = new OrganizationalMessageNotifier_Overview_Table();
	$table->prepare_items();
	
	?>
	<div class="wrap">
		<h2><?php _e( 'Organizational messages', OMN_TEXTDOMAIN ); ?></h2> 
		<form method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
            <?php $table->display(); ?>
        </form>
		<h3><?php _e( 'Create new message', OMN_TEXTDOMAIN ); ?></h3>
		<form method="post">
            <input type="hidden" name="action" value="add" />
            <table class="form-table">
            	<tr>
            		<th><?php _e( 'Title', OMN_TEXTDOMAIN ); ?></th>
            		<td><input type="text" name="title" /></td>
            	</tr>
            	<tr>
            		<th><?php _e( 'Link', OMN_TEXTDOMAIN ); ?></th>
            		<td><input type="text" name="link" /></td>
            	</tr>
            	<tr>
            		<th><?php _e( 'Content', OMN_TEXTDOMAIN ); ?></th>
            		<td><textarea name="text" rows="15" cols="60"></textarea></td>
            	</tr>
            	<tr>
            		<th><?php _e( 'Target', OMN_TEXTDOMAIN ); ?></th>
            		<td>
                		<input type="radio" name="target" value="admins" <?php checked( $default_target, 'admins' ); ?> />&nbsp;<?php _e( 'Administrators of currently active blogs', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="target" value="admins_by_admin_email" <?php checked( $default_target, 'admins_by_admin_email' ); ?> />&nbsp;<?php _e( 'Administrators of blogs (by admin e-mail address)', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="target" value="all_users" <?php checked( $default_target, 'all_users' ); ?> />&nbsp;<?php _e( 'All users in the network', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="target" value="specific" <?php checked ( $default_target == 'specific' ); ?> />&nbsp;<?php _e( 'Specific users: ', OMN_TEXTDOMAIN ); ?>&nbsp;<input type="text" name="specific_users_ids" value="<?php echo $specific_users_ids; ?>" />
                	</td>
                	<td><small><?php _e( 'If the "Specific users" option is checked, enter their ID\'s separated by commas.', OMN_TEXTDOMAIN ); ?></small></td>
            		</td>
            	</tr>
            </table>
            <p class="submit">
	            <input type="submit" class="button-primary" value="<?php _e( 'Create', OMN_TEXTDOMAIN ); ?>" />    
	        </p>
		</form>
	</div>
	<?php

}



function omn_superadmin_overview_page_add() {
	$target_ids = array();
	switch( $_POST['target'] ) {
	case 'admins':
		// vytvoříme seznam vlastníků blogů (toť uživatelé, kteří mají zprávu číst)
		$target_ids = omn_get_blog_owners();
		omn_log( 'adding target (blog owners): '.print_r( $target_ids, true ), 1 );
		break;
	case "admins_by_admin_email":
		$target_ids = omn_get_admins_by_email();
		omn_log( 'adding target (admin emails): '.print_r( $target_ids, true ), 1 );
		break;
	case 'all_users':
		global $wpdb;
		$target_ids = $wpdb->get_col( 'SELECT ID FROM '.$wpdb->users );
		omn_log( 'adding target (all users): '.print_r( $target_ids, true ), 1 );
		break;
	case 'specific':
		$target_ids = explode( ',', $_POST['specific_users_ids'] );
		omn_log( 'adding target (specific): '.print_r( $target_ids, true ), 1 );
		break;
	}
		
	$ok = omn_create_message( $_REQUEST['title'], $_REQUEST['text'], $_REQUEST['link'], get_current_user_id(), $target_ids );
	if( $ok ) {
		omn_nag( __( "Message created.", OMN_TXD ) );
	} else {
		omn_nagerr( __( "Error while creating message.", OMN_TXD ) );
	}
	omn_superadmin_overview_page_default();
}



/* ************************************************************************* *\
		CREATE MESSAGE
\* ************************************************************************* */


// přidá zprávu dle zadaných parametrů a vrátí true, pokud uspěje, jinak false.
function omn_create_message( $title, $text, $link, $author_id, $target_ids ) {

	extract( omn_get_settings() );

	global $wpdb;

	// přidáme záznam do databáze
	$message_data = array( 'title' => $title, 'text' => $text, 'link' => $link, 'author' => $author_id, 'date' => date( 'c' ) );
	omn_log( 'creating new message: '.print_r( $message_data, TRUE ) );
	$inok = $wpdb->insert( $wpdb->base_prefix.'omn_messages', 
		$message_data,
		array( '%s', '%s', '%s', '%d', '%s' ) 
	);
	$message_id = $wpdb->insert_id;
	if( !$inok ) {
		omn_log( "Database error while adding new message into the database: \"{$wpdb->last_query}\", \"{$wpdb->last_result}\", \"{$wpdb->last_error}\".", 4 );
		return false;
	}
	
	// přidáme cílové uživatele do unread
	foreach( $target_ids as $target_id ) {
		$wpdb->insert( $wpdb->base_prefix.'omn_unread',
			array( 'message_id' => $message_id, 'user_id' => $target_id ),
			array( '%d', '%d' )
		);
	}
	
	// pokud je zapnuto upozorňování mailem, rozešleme zprávy.
	if( $mail_notification["enabled"] ) {
		$addresses = array();
		foreach( $target_ids as $target_id ) {
			$user_data = get_userdata( $target_id );
			$addresses[] = $user_data->user_email;
		}
		/*$headers = array();
		foreach( $addresses as $address ) {
			$headers[] = "Bcc: $address";
		}*/
		$ret = wp_mail( /*get_bloginfo( "admin_email" )*/ $addresses, $mail_notification["subject"], $mail_notification["message"] );
		omn_log( "Sending e-mail messages to target users: ".implode( ", ", $addresses )." (success: \"$ret\")." );
	}
	
	// hotovo
	omn_log( 'message created successfully with id '.$message_id, 2 );
	return true;
}


// vrátí array user_id vlastníků blogů
function omn_get_blog_owners() {
	/* drive pouzivana oklika pres Who is who:	
	if( !function_exists( 'wiw_get_blog_owner_ids' ) ) {
		return array();
	}
	return wiw_get_blog_owner_ids();
	*/
	
	// nejdrive ziskame id vsech blogu
	$blogs = omn_wp_get_sites( array( 'public_only' => false ) );
	
	/*$blogs_log = array();
	foreach( $blogs as $blog ) {
		$blogs_log[] = $blog['domain'];
	}
	
	omn_log( 'omn_get_blog_owners | blogs: '.implode( ', ', $blogs_log ) );
	*/


	// obsolete: diky za get_users, 3.1!
	// pro kazdy blog seznam adminu
	// tohle je pekna prasarna, bohuzel jsem nenasel lepsi zpusob. nastesti se tahle funkce vola jen pri vytvareni
	// nove zpravy, takze to nebude bolet prilis
	/*global $wpdb;
	$result = array();
	foreach( $blogs as $blog ) {
		
		// toto najde vsechny uzivatele na danem blogu, kteri maji roli "administrator"
		$query = $wpdb->prepare( 
			'SELECT usermeta.user_id AS id FROM %s AS usermeta, %s AS users
			WHERE (
				( meta.meta_key LIKE %s ) 
				AND ( meta.meta_value LIKE %s ) 
				AND ( meta.user_id = usr.ID ) 
			)
			ORDER BY usr.display_name ASC', 
			$wpdb->usermeta, $wpdb->users, '"wp_'.$blog['blog_id'].'_capabilities"', '%administrator%' 
		);
		$owner_ids = $wpdb->get_results( $query );
		
		// nasypeme id uzivatelu do pole s vysledky tak, abychom se vyhnuli duplicitam
		foreach( $owner_ids as $owner_id ) {
			$result[$owner_id] = $owner_id;
		}
	}*/
	
	
	$result = array();
	foreach( $blogs as $blog ) {
		$users = get_users( array(
			'blog_id' => $blog['blog_id'],
			'role' => 'administrator'
		) );
		
		/*$users_log = array();
		foreach( $users as $user ) {
			$users_log[] = $user->user_login;
		}
		omn_log( 'omn_get_blog_owners | admin users for blog '.$blog['domain'].': '.implode( ', ', $users_log ) );
		*/
		
		// nasypeme id uzivatelu do pole s vysledky tak, abychom se vyhnuli duplicitam
		foreach( $users as $user ) {
			$result[$user->user_login] = $user->ID;
		}
	}
	
	omn_log( 'omn_get_blog_owners | result: '.print_r( $result, true ) );
	
	// hotovo
	return $result;
}


function omn_get_admins_by_email() {
	$blogs = omn_wp_get_sites( array( 'public_only' => false ) );
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
	omn_log( 'omn_get_admins_by_email | result: '.print_r( $results, true ) );
	return $results;
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
function omn_wp_get_sites($args){
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
	

?>
