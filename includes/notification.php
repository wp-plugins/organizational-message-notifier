<?php

/* ************************************************************************* *\
	ADMIN NOTIFICATION
\* ************************************************************************* */

add_action( 'admin_notices','omn_admin_notices' );


function omn_admin_notices() {
	$count = omn_unread_count();
	if( $count > 0 ) {
		$url = omn_get_messages_page_link( get_current_user_id() );
		omn_nag( sprintf( __( '%sYou have %d unread messages%s concerning blog operation. For reading them continue, please, %shere%s.', OMN_TEXTDOMAIN ), '<strong>', $count, '</strong>', '<a href="'.$url.'">', '</a>.' ) );
	}
}


function omn_get_messages_page_link( $user_id ) {
	$userdata = get_userdata( $user_id );
	switch_to_blog( $userdata->primary_blog );
	$url = site_url( '/wp-admin/index.php?page=omn-messages' );
	restore_current_blog();
	return $url;
}



add_action( 'admin_bar_menu', 'omn_admin_bar_menu' );

function omn_admin_bar_menu() { 
    global $wp_admin_bar;
	$count = omn_unread_count();
	if( $count > 0 ) {
		$wp_admin_bar->add_menu( array( 
			'id' => 'omn-notice',
			'title' => sprintf( __( 'You have %d unread messages! Click here to read them.', OMN_TEXTDOMAIN ), $count ),
			'href' => omn_get_messages_page_link( get_current_user_id() )
		) );
	}
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
    case 'show-all':
    	omn_messages_page_default( 'all' );
    	break;
	default:
		omn_messages_page_default();
		break;
	}
}

// TODO OMN_TEXTDOMAIN
/*  <?php _e( '', OMN_TEXTDOMAIN ); ?>
*/

function omn_messages_page_default( $show = 'unread' ) {
	omn_log( 'visiting message list.' );
	?>
	<div class="wrap">
		<h2><?php _e( 'Organizational messages', OMN_TEXTDOMAIN ); ?></h2>
		<?php
			if( $show == 'all' ) {
				$messages = omn_get_messages();
			} else {
				$messages = omn_get_unread_messages();
			}
			if( $messages != NULL ) {
				if( $show == 'unread' ) {
					?>
					<p><?php _e( 'Please read following messages and then mark them as read.', OMN_TEXTDOMAIN ); ?></p>
					<?php
				} else {
					?>
					<p><?php _e( 'Here is a list of all organizational messages.', OMN_TEXTDOMAIN ); ?></p>
					<?php
				}
				?>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
						    <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TEXTDOMAIN ); ?><br /><?php _e( 'Date', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Text', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Action', OMN_TEXTDOMAIN ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
						    <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TEXTDOMAIN ); ?><br /><?php _e( 'Date', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Text', OMN_TEXTDOMAIN ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Action', OMN_TEXTDOMAIN ); ?></th>
						</tr>
					</tfoot>
					<?php
						foreach( $messages as $message ) {
							$authordata = get_userdata( $message->author );
							if( !empty( $message->link ) ) {
								$title = '<a href="'.$message->link.'">'.$message->title.'</a>';
							} else {
								$title = $message->title;
							}
							$text = stripslashes( do_shortcode( $message->text ) );
							?>
							<tr>
								<td><strong><?php echo $title; ?></strong></td>
								<td><?php echo $authordata->user_login.'<br />'.$message->date; ?></td>
								<td><?php echo $text; ?></td>
								<td>
									<strong>
									<?php 
										if( $show == 'unread' or omn_is_message_unread( $message->id ) ) {
											echo '<a href="index.php?page=omn-messages&action=read&id='.$message->id.'">'.__( 'I have read it.', OMN_TEXTDOMAIN ).'</a>'; 
										}
									?>
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
				<p><?php sprintf( 
					__( 'There are no%smessages. If you think this is an error, contact your webmaster.', OMN_TEXTDOMAIN ), 
					( $show == 'unread' ) ? __( ' unread ', OMN_TEXTDOMAIN ) : ' ' 
				); ?></p>
				<?php
			}

			if( $show == 'unread' ) {
				?>
				<p><a href="index.php?page=omn-messages&action=show-all"><?php _e( 'Show all messages', OMN_TEXTDOMAIN ); ?></a></p>
				<?php
			} else {
				?>
				<p><a href="index.php?page=omn-messages&action=default"><?php _e( 'Show only unread messages', OMN_TEXTDOMAIN ); ?></a></p>
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


?>
