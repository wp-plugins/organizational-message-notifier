<?php

/* ************************************************************************* *\
	SUPERADMIN OVERVIEW
\* ************************************************************************* */

// TODO chybejici uzivatele v omn_get_blog_owners (napr. pierre)

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
	case 'wls-register':
    	$ok = wls_register( OMN_LOGNAME, __( 'Organizational Message Notifier events.', OMN_TEXTDOMAIN ) );
    	if( $ok ) {
    		$info = __( 'Successfully registered with WLS', OMN_TEXTDOMAIN );
    		omn_nag( $info );
    		omn_log( $info, 2 );
    	} else {
    		$info = __( 'Error while registering with WLS.', OMN_TEXTDOMAIN );
    		omn_nagerr( $info );
    		omn_log( $info, WLS_ERROR );
    	}
    	omn_superadmin_overview_page_default();
    	break;
    case 'wls-unregister':
    	$ok = wls_unregister( OMN_LOGNAME );
    	if( $ok ) {
    		$info = __( 'Successfully unregistered from WLS, log entries deleted.', OMN_TEXTDOMAIN );
    		omn_nag( $info );
    		omn_log( $info, 2 );
    	} else {
    		$info = __( 'Errrr while unregistering from WLS.', OMN_TEXTDOMAIN );
    		omn_nagerr( $info );
    		omn_log( $info, WLS_ERROR );
    	}
    	omn_superadmin_overview_page_default();
    	break;    
    default:
    	omn_superadmin_overview_page_default();
    	break;
    }
}

/* 
<?php _e( '', OMN_TEXTDOMAIN ); ?>
__( , OMN_TEXTDOMAIN )
*/
function omn_superadmin_overview_page_default() {

	$messages = omn_get_messages();
	
	?>
	<div class="wrap">
		<h2><?php _e( 'Organizational messages', OMN_TEXTDOMAIN ); ?></h2>
		<table class="widefat" cellspacing="0">
		    <thead>
		        <tr>
		            <th scope="col" class="manage-column"><?php _e( 'id', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TEXTDOMAIN ); ?><br /><?php _e( 'Date', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Content', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Have not read yet', OMN_TEXTDOMAIN ); ?></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
		            <th scope="col" class="manage-column"><?php _e( 'id', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TEXTDOMAIN ); ?><br /><?php _e( 'Date', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Content', OMN_TEXTDOMAIN ); ?></th>
		            <th scope="col" class="manage-column"><?php _e( 'Have not read yet', OMN_TEXTDOMAIN ); ?></th>
				</tr>
			</tfoot>
			<?php
				foreach( $messages as $message ) {
					$nonreading_users = omn_get_nonreading_users( $message->id );
					$unread_string = '';
					$unread_count = 0;
					foreach( $nonreading_users as $nonreading_user_id ) {
						$userdata = get_userdata( $nonreading_user_id );
						if( !empty( $unread_string ) ) {
							$unread_string .= ', ';
						}
						$unread_string .= $userdata->user_login.' <a href="index.php?page=omn-superadmin-overview&action=delete-notification&user='.$nonreading_user_id.'&message='.$message->id.'">&times;</a>';
						$unread_count++;
					}
					if( $unread_count > 0 ) {
						$unread_string = $unread_count.': '.$unread_string;
					} else {
						$unread_string = __( '(has been read by everyone)', OMN_TEXTDOMAIN );
					}
					$authordata = get_userdata( $message->author );
					if( !empty( $message->link ) ) {
						$title = '<a href="'.$message->link.'">'.$message->title.'</a>';
					} else {
						$title = $message->title;
					}
					$text = stripslashes( do_shortcode( $message->text ) );
					?>
					<tr>
						<td>
							<?php echo $message->id; ?><br />
							<?php
								if( $unread_count > 0 ) {
									?>
									<a href="index.php?page=omn-superadmin-overview&action=expire-message&id=<?php echo $message->id; ?>"><small>ex</small></a>
									<?php
								}
							?>
						</td>
						<td><strong><?php echo $title; ?></strong></td>
						<td><?php echo $authordata->user_login.'<br />'.$message->date; ?></td>
						<td><?php echo $text; ?></td>
						<td><?php echo $unread_string; ?></td>
					</tr>
					<?php
				}
			?>
		</table>
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
            </table>
            <p class="submit">
	            <input type="submit" class="button-primary" value="<?php _e( 'Create', OMN_TEXTDOMAIN ); ?>" />    
	        </p>
		</form>
		<?php
			if( defined( 'WLS' ) ) {
				?>			 
				<h3><?php _e( 'Registration with WLS', SUH_TEXTDOMAIN ); ?></h3>
		    	<p><?php _e( 'Wordpress Logging Service was detected.', SUH_TEXTDOMAIN ); ?></p>
	    		<?php
	    			if( !wls_is_registered( 'suh-log' ) or !wls_is_registered( 'suh-mail' ) ) {
	    				?>
						<form method="post"">
							<input type="hidden" name="action" value="wls-register" />
							<p class="submit">
								<input type="submit" value="<?php _e( 'Register with WLS', SUH_TEXTDOMAIN ); ?>" />    
							</p> 
						</form>
						<?php
					} else {
						?>
						<form method="post">
							<input type="hidden" name="action" value="wls-unregister" />
							<p class="submit">
								<input type="submit" style="color:red;" value="<?php _e( 'Unregister from WLS and delete log entries', SUH_TEXTDOMAIN ); ?>" />    
							</p> 
						</form>
						<?php
					}
				?>
				<?php
        	} 
        ?>	</div>
	<?php

}



function omn_superadmin_overview_page_add() {
	$ok = omn_create_message( $_REQUEST['title'], $_REQUEST['text'], $_REQUEST['link'], get_current_user_id() );
	if( $ok ) {
		omn_nag( 'Zpráva byla úspěšně přidána.' );
	} else {
		omn_nagerr( 'Při přidávání zprávy došlo k chybě.' );
	}
	omn_superadmin_overview_page_default();
}


function omn_delete_notification( $user_id, $message_id ) {
	global $wpdb;
	$query = 'DELETE FROM '.$wpdb->base_prefix.'omn_unread
		WHERE (
			message_id='.$message_id.'
			AND user_id='.$user_id.'
		)';
	$wpdb->query( $wpdb->prepare( $query ) );
}


function omn_delete_notifications( $message_id ) {
	global $wpdb;
	$query = 'DELETE FROM '.$wpdb->base_prefix.'omn_unread
		WHERE message_id='.$message_id;
	$wpdb->query( $wpdb->prepare( $query ) );
	omn_log( 'Deleting all notifications for message '.$message_id.'.', 3 );
}


/* ************************************************************************* *\
		CREATE MESSAGE
\* ************************************************************************* */


// přidá zprávu dle zadaných parametrů a vrátí true, pokud uspěje, jinak false.
function omn_create_message( $title, $text, $link, $author_id ) {
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
		return false;
		omn_log( 'error adding new message into the database.', 4 );
	}
	
	// vytvoříme seznam vlastníků blogů (toť uživatelé, kteří mají zprávu číst)
	$owners = omn_get_blog_owners();
	omn_log( 'adding target (blog owners): '.print_r( $owners, TRUE ) );
	
	// přidáme cílové uživatele do unread
	foreach( $owners as $owner ) {
		$wpdb->insert( $wpdb->base_prefix.'omn_unread',
			array( 'message_id' => $message_id, 'user_id' => $owner ),
			array( '%d', '%d' )
		);
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

	$sql = $wpdb->prepare($query);
	
	//omn_log( 'wp_get_sites query: '.$sql, 1 );

	$results = $wpdb->get_results($sql, ARRAY_A);

	return $results;	
}
	

?>