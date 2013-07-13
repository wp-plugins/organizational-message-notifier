<?php

namespace OrganizationalMessageNotifier\MessagesUI {
	
	use \OrganizationalMessageNotifier\z;
	use \OrganizationalMessageNotifier\Settings;
	use \OrganizationalMessageNotifier\Log;
	use \OrganizationalMessageNotifier\Database as db;
	use \OrganizationalMessageNotifier\Messages as msg;
	use \OrganizationalMessageNotifier\MessageTargets;
	
	/** Page for message management */
	const PAGE_MANAGEMENT = "omn-management";
	const PAGE_READING = "omn-reading";
	
	/* Admin menus */
	add_action( "network_admin_menu", "\OrganizationalMessageNotifier\MessagesUI\\network_admin_menu" );
	
	
	function network_admin_menu() {
		add_submenu_page(
				'index.php',
				__( "Organizational message management", OMN_TXD ),
				__( "Organizational message management", OMN_TXD ),
				'manage_network_options',
				PAGE_MANAGEMENT,
				"\OrganizationalMessageNotifier\MessagesUI\handle_management_page" );
	}
	
	
	add_action( "admin_menu", "\OrganizationalMessageNotifier\MessagesUI\admin_menu" );
	
	
	function admin_menu() {
		
		$settings = Settings::getInstance();
		
		$unread_count = db\get_user_unread_message_count( get_current_user_id() );
		
		if( $unread_count > 0 || current_user_can( $settings->minimal_capability ) ) {
			add_submenu_page(
					'index.php',
					__( 'Organizational messages', OMN_TXD ),
					__( 'Organizational messages', OMN_TXD ),
					"read",
					PAGE_READING,
					"\OrganizationalMessageNotifier\MessagesUI\handle_reading_page" );
		}
		
		if( !is_multisite() ) {
			add_submenu_page(
					'index.php',
					__( "Organizational message management", OMN_TXD ),
					__( "Organizational message management", OMN_TXD ),
					"manage_options",
					PAGE_MANAGEMENT,
					"\OrganizationalMessageNotifier\MessagesUI\handle_management_page" );
		}
	}
	
	
	/* Management page */
	function handle_management_page() {
		
		?>
			<div class="wrap">
		<?php
		
		$action = isset( $_REQUEST["action"] ) ? $_REQUEST["action"] : "default";
		
		switch( $action ) {

			case 'add':
				$ok = msg\create_message( $_POST["target"], $_POST['title'], $_POST['text'], $_POST['link'], $_POST['target_details'] );
				if( $ok ) {
					z::nag( __( "Message created.", OMN_TXD ) );
				} else {
					z::nagerr( __( "Error while creating message.", OMN_TXD ) );
				}
				show_default_management_page();
				break;
				
			case 'delete-notification':
				$ok = db\delete_target( $_GET['message'], $_GET['user'] );
				if( $ok ) {
					z::nag( __( "Target deleted.", OMN_TXD ) );
				} else {
					z::nagerr( __( "Error while deleting target.", OMN_TXD ) );
				}
				show_default_management_page();
				break;
				
			case 'expire-message':
				$ok = db\delete_all_targets( $_GET['id'] );
				if( $ok ) {
					z::nag( __( "Message expired.", OMN_TXD ) );
				} else {
					z::nagerr( __( "Error while expiring the message.", OMN_TXD ) );
				}
				show_default_management_page();
				break;
				
			case 'delete-message':
				$ok = db\delete_message( $_GET['id'] );
				if( $ok ) {
					z::nag( __( "Message deleted.", OMN_TXD ) );
				} else {
					z::nagerr( __( "Error while deleting the message.", OMN_TXD ) );
				}
				show_default_management_page();
				break;
				
			default:
				show_default_management_page();
				break;
		}
		
		?>
			</div>
		<?php
		
	}
	
	
	function show_default_management_page() {

		$settings = Settings::getInstance();
		
		$table = new MessageTable();
		$table->prepare_items();
		
		?>
			<div class="wrap">
				<h2><?php _e( 'Organizational messages', OMN_TXD ); ?></h2>
				
				<form method="get">
		            <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		            <?php $table->display(); ?>
		        </form>
		        
				<h3><?php _e( 'Create new message', OMN_TXD ); ?></h3>
				<form method="post">
		            <input type="hidden" name="action" value="add" />
		            <table class="form-table">
		            	<tr valign="top">
		            		<th><?php _e( 'Title', OMN_TXD ); ?></th>
		            		<td><input type="text" name="title" /></td>
		            	</tr>
		            	<tr valign="top">
		            		<th><?php _e( 'Link', OMN_TXD ); ?></th>
		            		<td><input type="text" name="link" /></td>
		            	</tr>
		            	<trvalign="top">
		            		<th><?php _e( 'Content', OMN_TXD ); ?></th>
		            		<td><textarea name="text" rows="15" cols="60"></textarea></td>
		            	</tr>
		            	<tr valign="top">
		            		<th><?php _e( 'Target', OMN_TXD ); ?></th>
		            		<td>
								<?php
									switch( $settings->default_target ) {
										case MessageTargets::SPECIFIC:
											$target_details = $settings->specific_users_ids;
											break;
										case MessageTargets::ROLE:
											$target_details = $settings->target_role;
											break;
										default:
											$target_details = "";
											break;
									}

									if( $settings->allow_nondefault_target ) {
										foreach( MessageTargets::values() as $value ) {
											echo "<input type=\"radio\" name=\"target\" value=\"{$value}\" " . checked( $settings->default_target, $value, false ) . " />&nbsp;" . MessageTargets::desc( $value ) ."<br />";
										}
										?>
										<input type="text" name="target_details" value="<?php echo $target_details; ?>" />
										<?php
									} else {
										echo MessageTargets::desc( $settings->default_target ) . ( empty( $target_details ) ? "" : ": " . $target_details );
										?>
										<input type="hidden" name="target" value="<?php echo $settings->default_target; ?>" />
										<input type="hidden" name="target_details" value="<?php echo $target_details; ?>" />
										<?php
									}
								?>
		                		
		                	</td>
		                	<?php
		                		if( $settings->allow_nondefault_target ) {
									echo "<td><small>";
									_e( 'If the "Specified users" option is checked, enter their ID\'s separated by commas.', OMN_TXD );
									_e( 'If the "Users with specified role" option is checked, enter valid user roles separated by commas.', OMN_TXD );
									echo "</small></td>";
								}
		                	?>
		            	</tr>
		            </table>
		            <?php submit_button( __( 'Create', OMN_TXD ), "primary" )?>
				</form>
			</div>
			<?php
	}
	
	
	/* Message reading page */
	function handle_reading_page() {

		?>
			<div class="wrap">
		<?php
		
		$action = isset( $_REQUEST["action"] ) ? $_REQUEST["action"] : "default";
		
		switch( $action ) {
			case 'read':
				$message_id = $_REQUEST['id'];
				Log::log( "Marking message $message_id as read.", 2 );
				$ok = db\delete_target( $message_id, get_current_user_id() );
				if( $ok ) {
					z::nag( __( "Message has been marked as read. Thank you!", OMN_TXD ) );
				} else {
					z::nagerr( __( "Error while marking message as read.", OMN_TXD ) );
				}
				show_default_reading_page();
				break;
				
			case 'show-all':
				show_default_reading_page( "all" );
				break;
				
			default:
				show_default_reading_page( "unread" );
				break;
		}
		
		?>
			</div>
		<?php
	}
	
	
	function show_default_reading_page( $show = "unread" ) {
		
		Log::debug( 'visiting message list.' );
		
		$settings = Settings::getInstance();
		$restricted_to_unread = false;
		
		if( !current_user_can( $settings->minimal_capability ) ) {
			$show = "unread";
			$restricted_to_unread = true;
		}
		
		?>
		<h2><?php _e( 'Organizational messages', OMN_TXD ); ?></h2>
		<?php
		
			if( $show == 'all' ) {
				$messages = db\get_messages();
			} else {
				$messages = db\get_user_unread_messages( get_current_user_id() );
			}
			
			if( $messages != NULL ) {
				if( $show == 'unread' ) {
					?>
					<p><?php _e( 'Please read following messages and then mark them as read.', OMN_TXD ); ?></p>
					<?php
				} else {
					?>
					<p><?php _e( 'Here is a list of all organizational messages.', OMN_TXD ); ?></p>
					<?php
				}
				?>
				<table class="widefat" cellspacing="0">
					<thead>
						<tr>
						    <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TXD ); ?><br /><?php _e( 'Date', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Text', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Action', OMN_TXD ); ?></th>
						</tr>
					</thead>
					<tfoot>
						<tr>
						    <th scope="col" class="manage-column"><?php _e( 'Message', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Author', OMN_TXD ); ?><br /><?php _e( 'Date', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Text', OMN_TXD ); ?></th>
						    <th scope="col" class="manage-column"><?php _e( 'Action', OMN_TXD ); ?></th>
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
										if( $show == 'unread' || db\target_exists( $message->id, get_current_user_id() ) ) {
											echo "<a href=\"index.php?page=" . PAGE_READING . "&action=read&id={$message->id}\">" . __( 'I have read it.', OMN_TXD ) . "</a>";
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
				<p><?php printf(
					__( 'There are no%smessages.', OMN_TXD ),
					( $show == 'unread' ) ? __( ' unread ', OMN_TXD ) : ' '
				); ?></p>
				<?php
			}

			if( $show == 'unread' ) {
				if( !$restricted_to_unread ) {
					echo "<p><a href=\"index.php?page=" . PAGE_READING . "&action=show-all\">" . __( 'Show all messages', OMN_TXD ) . "</a></p>";
				}
			} else {
				echo "<p><a href=\"index.php?page=" . PAGE_READING . "&action=default\">" . __( 'Show only unread messages', OMN_TXD ) . "</a></p>";
			}
		?>
		<?php
	}
	
}

?>
