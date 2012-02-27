<?php
/*
Plugin Name: Organizational message notifier
Description: Systém upozornění adminů na organizační zprávy týkající se chodu webu. Superadministrátorský plugin. <strong>Vyvinuto a určeno pro blogosphere.cz</strong>
Version: 1.2
Author: Zaantar
Author URI: http://zaantar.eu
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


/* ************************************************************************* *\
	INSTALLATION
\* ************************************************************************* */

register_activation_hook( __FILE__,'omn_plugin_activation' );

function omn_plugin_activation() {
	global $wpdb;
	
	$query_messages = 'CREATE TABLE IF NOT EXISTS '.$wpdb->base_prefix.'omn_messages (
		id INT NOT NULL AUTO_INCREMENT,
		title VARCHAR(200),
		text LONGTEXT,
		date DATETIME,
		link VARCHAR(200),
		author BIGINT(20),
		UNIQUE ( id ),
		PRIMARY KEY ( id )
	)';
	
	$wpdb->query( $wpdb->prepare( $query_messages ) );
	
	$query_unread = 'CREATE TABLE IF NOT EXISTS '.$wpdb->base_prefix.'omn_unread (
		id INT NOT NULL AUTO_INCREMENT,
		message_id INT,
		user_id BIGINT(20),
		UNIQUE ( id ),
		PRIMARY KEY ( id )
	)';
	
	$wpdb->query( $wpdb->prepare( $query_unread ) );
}


function omn_log( $message ) {
	$filename = dirname(__FILE__).'/log.txt';

	$file = fopen( $filename, 'ä́' );
	if( is_user_logged_in() ) {
		$user = wp_get_current_user();
		$user = $user->user_login;
	} else {
		$user = '*visitor*';
	}
	$date = date( 'Y-m-d H:i:s' );
	
	$blogid = get_current_blog_id();
	switch_to_blog( $blogid );
	$blogname = get_bloginfo( 'siteurl' );
	restore_current_blog();
	fwrite( $file, '***  '.$date.', '.$user.' @ '.$blogid.' ('.$blogname.'):: '.$message."\n\n" );
	fclose( $file );
}

/* ************************************************************************* *\
	ADMIN MENU & NOTICES
\* ************************************************************************* */


add_action( 'network_admin_menu','omn_network_admin_menu' );

function omn_network_admin_menu() {
	/*add_submenu_page( 'settings.php', 'Nastavení Organizational message notifier', 'OMN', 'manage_network_options', 
		'omn-settings', 'omn_settings_page' );*/
	add_submenu_page( 'index.php', 'Zprávy v systému OMN', 'Zprávy OMN', 'manage_network_options', 
		'omn-superadmin-overview', 'omn_superadmin_overview_page' );
}

add_action( 'admin_menu','omn_admin_menu' );

function omn_admin_menu() {
	add_submenu_page( 'index.php', 'Zprávy v systému OMN', 'Zprávy OMN', 'manage_options', 
		'omn-messages', 'omn_messages_page' );
}


function omn_nag( $message ) {
	echo( '<div id="message" class="updated"><p>'.$message.'</p></div>' );
}

function omn_nagerr( $message ) {
	echo( '<div id="message" class="error"><p>'.$message.'</p></div>' );
}


/* ************************************************************************* *\
	SUPERADMIN OVERVIEW
\* ************************************************************************* */


function omn_superadmin_overview_page() {
	if( !function_exists( 'wiw_get_blog_owner_ids' ) ) {
		omn_nagerr( 'Tento plugin bude správně fungovat pouze za přítomnosti pluginu Who is who.' );
	}

	if( isset($_REQUEST['action']) ) {
        $action = $_REQUEST['action'];
    } else {
        $action = 'default';
    }
    
    switch( $action ) {
    case 'add':
    	omn_superadmin_overview_page_add();
    	break;
    default:
    	omn_superadmin_overview_page_default();
    	break;
    }
}


function omn_superadmin_overview_page_default() {
	global $wpdb;	
	
	$query_messages = 'SELECT * FROM '.$wpdb->base_prefix.'omn_messages ORDER BY id DESC';
	$messages = $wpdb->get_results( $wpdb->prepare( $query_messages ) );
	
	?>
	<div class="wrap">
		<h2>Zprávy v systému OMN</h2>
		<table class="widefat" cellspacing="0">
		    <thead>
		        <tr>
		            <th scope="col" class="manage-column">id</th>
		            <th scope="col" class="manage-column">Zpráva</th>
		            <th scope="col" class="manage-column">Autor</th>
		            <th scope="col" class="manage-column">Datum</th>
		            <th scope="col" class="manage-column">Text zprávy</th>
		            <th scope="col" class="manage-column">Dosud nečetli</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
		            <th scope="col" class="manage-column">id</th>
		            <th scope="col" class="manage-column">Zpráva</th>
		            <th scope="col" class="manage-column">Autor</th>
		            <th scope="col" class="manage-column">Datum</th>
		            <th scope="col" class="manage-column">Text zprávy</th>
		            <th scope="col" class="manage-column">Dosud nečetli</th>
				</tr>
			</tfoot>
			<?php
				foreach( $messages as $message ) {
					$query_unread = 'SELECT user_id
						FROM '.$wpdb->base_prefix.'omn_unread
						WHERE message_id='.$message->id;
					$unreads = $wpdb->get_col( $wpdb->prepare( $query_unread ) );
					$unread_string = '';
					$unread_count = 0;
					foreach( $unreads as $unread ) {
						$userdata = get_userdata( $unread );
						if( !empty( $unread_string ) ) {
							$unread_string .= ', ';
						}
						$unread_string .= $userdata->user_login;
						$unread_count++;
					}
					if( $unread_count > 0 ) {
						$unread_string = $unread_count.': '.$unread_string;
					} else {
						$unread_string = '(zprávu přečetli všichni adresáti)';
					}
					$authordata = get_userdata( $message->author );
					if( !empty( $message->link ) ) {
						$title = '<a href="'.$message->link.'">'.$message->title.'</a>';
					} else {
						$title = $message->title;
					}
					?>
					<tr>
						<td><?php echo $message->id; ?></td>
						<td><strong><?php echo $title; ?></strong></td>
						<td><?php echo $authordata->user_login; ?></td>
						<td><?php echo $message->date; ?></td>
						<td><?php echo $message->text; ?></td>
						<td><?php echo $unread_string; ?></td>
					</tr>
					<?php
				}
			?>
		</table>
		<h3>Přidání nové zprávy</h3>
		<form method="post" action="index.php?page=omn-superadmin-overview">
            <input type="hidden" name="action" value="add" />
            <table class="form-table">
            	<tr>
            		<th>Titulek</th><td><input type="text" name="title" /></td>
            	</tr>
            	<tr>
            		<th>Odkaz</th><td><input type="text" name="link" /></td>
            	</tr>
            	<tr>
            		<th>Text</th>
            		<td><textarea name="text"></textarea></td>
            	</tr>
            </table>
            <p class="submit">
	            <input type="submit" value="Přidat zprávu" />    
	        </p>
		</form>
	</div>
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


/* ************************************************************************* *\
	CREATE MESSAGE
\* ************************************************************************* */


// přidá zprávu dle zadaných parametrů a vrátí true, pokud uspěje, jinak false.
function omn_create_message( $title, $text, $link, $author_id ) {
	global $wpdb;

	// přidáme záznam do databáze
	$message_data = array( 'title' => $title, 'text' => $text, 'link' => $link, 'author' => $author_id, 'date' => date( 'c' ) );
	omn_log( 'creating new message: '.print_r( $message_data ) );
	$inok = $wpdb->insert( $wpdb->base_prefix.'omn_messages', 
		$message_data,
		array( '%s', '%s', '%s', '%d', '%s' ) 
	);
	$message_id = $wpdb->insert_id;
	if( !$inok ) {
		return false;
		omn_log( 'error adding new message into the database.' );
	}
	
	// vytvoříme seznam vlastníků blogů (toť uživatelé, kteří mají zprávu číst)
	$owners = omn_get_blog_owners();
	omn_log( 'adding target (blog owners): '.print_r( $owners ) );
	
	// přidáme cílové uživatele do unread
	foreach( $owners as $owner ) {
		$wpdb->insert( $wpdb->base_prefix.'omn_unread',
			array( 'message_id' => $message_id, 'user_id' => $owner ),
			array( '%d', '%d' )
		);
	}
	
	// hotovo
	omn_log( 'message created successfully with id '.$message_id );
	return true;
}


// vrátí array user_id vlastníků blogů
function omn_get_blog_owners() {
	if( !function_exists( 'wiw_get_blog_owner_ids' ) ) {
		return array();
	}
	return wiw_get_blog_owner_ids();
}


/* ************************************************************************* *\
	ADMIN NOTIFICATION
\* ************************************************************************* */

add_action( 'admin_notices','omn_admin_notices' );

function omn_admin_notices() {
	$count = omn_unread_count();
	if( $count > 0 ) {
		$userdata = get_userdata( get_current_user_id () );
		switch_to_blog( $userdata->primary_blog );
		$url = site_url( '/wp-admin/index.php?page=omn-messages' );
		restore_current_blog();
		omn_nag( '<strong>Máte '.$count.' nepřečtených zpráv</strong> týkajících se provozu blogu. Pro jejich přečtení pokračujte, prosím, <a href="'.$url.'">sem</a>.' );
	}
}


function omn_unread_count() {
	global $wpdb;
	$query = 'SELECT COUNT(*) FROM '.$wpdb->base_prefix.'omn_unread WHERE user_id='.get_current_user_id();
	$count = $wpdb->get_var( $wpdb->prepare( $query ) );
	return $count;
}


function omn_messages_page() {
	if( isset($_REQUEST['action']) ) {
        $action = $_REQUEST['action'];
    } else {
        $action = 'default';
    }
    
    switch( $action ) {
    case 'read':
    	omn_messages_page_read();
    	break;
	default:
		omn_messages_page_default();
		break;
	}
}

function omn_messages_page_default() {
	$unread_messages = omn_get_unread_messages();
	omn_log( 'visiting message list.' );
	?>
	<div class="wrap">
		<h2>Zprávy v systému OMN</h2>
		<?php
			if( $unread_messages != NULL ) {
				?>
				<p>Přečtěte si, prosím, následující zprávy a pak je označte za přečtené.</p>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
						    <th scope="col" class="manage-column">Zpráva</th>
						    <th scope="col" class="manage-column">Datum</th>
						    <th scope="col" class="manage-column">Text zprávy</th>
						    <th scope="col" class="manage-column">Autor</th>
						    <th scope="col" class="manage-column">Akce</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
						    <th scope="col" class="manage-column">Zpráva</th>
						    <th scope="col" class="manage-column">Datum</th>
						    <th scope="col" class="manage-column">Text zprávy</th>
						    <th scope="col" class="manage-column">Autor</th>
						    <th scope="col" class="manage-column">Akce</th>
						</tr>
					</tfoot>
					<?php
						foreach( $unread_messages as $message ) {
							$authordata = get_userdata( $message->author );
							if( !empty( $message->link ) ) {
								$title = '<a href="'.$message->link.'">'.$message->title.'</a>';
							} else {
								$title = $message->title;
							}
							?>
							<tr>
								<td><strong><?php echo $title; ?></strong></td>
								<td><?php echo $message->date; ?></td>
								<td><?php echo $message->text; ?></td>
								<td><?php echo $authordata->user_login; ?></td>
								<td>
									<strong>
										<?php echo '<a href="index.php?page=omn-messages&action=read&id='.$message->id.'">Označit zprávu za přečtenou</a>'; ?>
									</strong>
								</td>
							</tr>
							<?php
						}
					?>
				</table>
				<?php
			} else {
				?>
				<p>Nemáte žádné nepřečtené zprávy.</p>
				<?php
			}
		?>			
	</div>
	<?php
}


function omn_messages_page_read() {
	omn_read_message( $_REQUEST['id'] );
	omn_messages_page_default();
}



function omn_get_unread_messages() {
	global $wpdb;
	$query = '
		SELECT messages.id AS id, messages.title AS title, messages.link AS link, 
			messages.text AS text, messages.date AS date, messages.author AS author
		FROM '.$wpdb->base_prefix.'omn_unread AS unread
			JOIN '.$wpdb->base_prefix.'omn_messages AS messages
			ON unread.message_id = messages.id
		WHERE unread.user_id = '.get_current_user_id();
	$results = $wpdb->get_results( $wpdb->prepare( $query ) );
	return $results;
}


function omn_read_message( $message_id ) {
	global $wpdb;
	$query = 'DELETE FROM '.$wpdb->base_prefix.'omn_unread
		WHERE user_id = '.get_current_user_id().'
			AND message_id = '.$message_id;
	
	$wpdb->query( $wpdb->prepare( $query ) );
	omn_log( 'marking message '.$message_id.' as read.' );
}

?>
