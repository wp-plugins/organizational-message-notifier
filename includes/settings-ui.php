<?php

namespace OrganizationalMessageNotifier\SettingsUI {
	
	use \OrganizationalMessageNotifier\z;
	use \OrganizationalMessageNotifier\Settings;
	use \OrganizationalMessageNotifier\Log;
	use \OrganizationalMessageNotifier\MessageTargets;
	
	/** Settings page name */
	const PAGE = "omn-settings";
	
	
	/* Admin menu */
	add_action( "network_admin_menu", "\OrganizationalMessageNotifier\SettingsUI\\network_admin_menu" );
	
	
	function network_admin_menu() {
		
		add_submenu_page(
				"settings.php",
				__( "Organizational Message Notifier", OMN_TXD ),
				__( "Organizational Message Notifier", OMN_TXD ),
				"manage_network_options",
				PAGE,
				"\OrganizationalMessageNotifier\SettingsUI\page_handler" );
	}
	
	
	add_action( "admin_menu", "\OrganizationalMessageNotifier\SettingsUI\admin_menu" );
	
	
	function admin_menu() {
		if( !is_multisite() ) {
			
			add_submenu_page(
					"options-general.php",
					__( "Organizational Message Notifier", OMN_TXD ),
					__( "Organizational Message Notifier", OMN_TXD ),
					"manage_options",
					PAGE,
					"\OrganizationalMessageNotifier\SettingsUI\page_handler" );
			
		}
	}
	
	
	function page_handler() {
		
		?>
			<div class="wrap">
		<?php
		
		$action = isset( $_REQUEST["action"] ) ? $_REQUEST["action"] : "default";
				
		switch( $action ) {
			
			case 'wls-register':
				$ok = Log::register();
				if( $ok ) {
					z::nag( __( 'Successfully registered with WLS', OMN_TXD ) );
				} else {
					z::nagerr( __( 'Error while registering with WLS.', OMN_TXD ) );
				}
				show_default_page();
				break;
				
			case 'wls-unregister':
				$ok = Log::unregister();
				if( $ok ) {
					z::nag( __( 'Successfully unregistered from WLS, log entries deleted.', OMN_TXD ) );
				} else {
					z::nagerr( __( 'Error while unregistering from WLS.', OMN_TXD ) );
				}
				show_default_page();
				break;
				
			case 'update-settings':
				$sd = $_POST['settings'];
				$settings = Settings::getInstance();
				$settings->set( $sd );
				$settings->mail_notification["enabled"] = isset( $sd["mail_notification"]["enabled"] );
				$settings->hide_donation_button = isset( $sd["hide_donation_button"] );
				$settings->save();
				z::nag( "Settings have been updated.", OMN_TXD );
				show_default_page();
				break;
				
			default:
				show_default_page();
				break;
		}
		
		?>
			</div>
		<?php
	}
	
	
	function show_default_page() {
		
		$settings = Settings::getInstance();
		
		?>
		<h2><?php _e( 'Organizational Message Notifier', OMN_TXD ); ?></h2>
		<?php
		
			z::maybe_donation_button( $settings->hide_donation_button, OMN_TXD );

			if( defined( 'WLS' ) ) {
				?>
				<h3><?php _e( 'Registration with WLS', OMN_TXD ); ?></h3>
		    	<p><?php _e( 'Wordpress Logging Service was detected.', OMN_TXD ); ?></p>
	    		<?php
	    			if( !wls_is_registered( OMN_LOGNAME ) ) {
	    				?>
						<form method="post"">
							<input type="hidden" name="action" value="wls-register" />
							<p class="submit">
								<input type="submit" value="<?php _e( 'Register with WLS', OMN_TXD ); ?>" />
							</p>
						</form>
						<?php
					} else {
						?>
						<form method="post">
							<input type="hidden" name="action" value="wls-unregister" />
							<p class="submit">
								<input type="submit" style="color:red;" value="<?php _e( 'Unregister from WLS and delete log entries', OMN_TXD ); ?>" />
							</p>
						</form>
						<?php
					}
				?>
				<?php
        	}
        ?>
        <form method="post">
        	<h3><?php _e( 'Basic settings', OMN_TXD ); ?></h3>
            <input type="hidden" name="action" value="update-settings" />
           	<table class="form-table">
           		<tr valign="top">
                	<th>
                		<label><?php _e( 'Default message target', OMN_TXD ); ?></label><br />
                	</th>
                	<td>
						<?php
							foreach( MessageTargets::values() as $value ) {
								echo "<input type=\"radio\" name=\"settings[default_target]\" value=\"{$value}\" " . checked( $settings->default_target, $value, false ) . " />&nbsp;" . MessageTargets::desc( $value ) ."<br />";
							}
						?>
                	</td>
                	<td>
                		<small>
                			<?php _e( 'If the "Specific users" option is checked, enter their ID\'s separated by commas.', OMN_TXD ); ?><br />
                			<?php _e( 'Also don\'t forget to adjust the user capability condition (see below).', OMN_TXD ); ?>
                		</small>
                	</td>
                </tr>
                <tr valign="top">
                	<th>
                		<label><?php _e( "Specific users", OMN_TXD ); ?></label><br />
                	</th>
                	<td>
						<input type="text" name="settings[specific_users_ids]" value="<?php echo $settings->specific_users_ids; ?>" />
					</td>
					<td>
						<small><?php _e( "This has meaning only if default target is set to \"Specific users\".", OMN_TXD ); ?></small>
					</td>
				</tr>
				<tr valign="top">
                	<th>
                		<label><?php _e( "Target roles", OMN_TXD ); ?></label><br />
                	</th>
                	<td>
						<input type="text" name="settings[target_role]" value="<?php echo $settings->target_role; ?>" />
					</td>
					<td>
						<small>
							<?php _e( "This has meaning only if default target is set to \"Users with specified role\".", OMN_TXD ); ?>
							<?php printf( __( "You can enter more roles separated by %s without spaces.", OMN_TXD ), "<code>,</code>" ); ?>
						</small>
					</td>
				</tr>
				<tr valign="top">
                	<th>
                		<label><?php _e( "Allow other than default target", OMN_TXD ); ?></label><br />
                	</th>
                	<td>
						<input type="checkbox" name="settings[allow_nondefault_target]" <?php checked( $settings->allow_nondefault_target ); ?> />
					</td>
					<td>
						<small><?php _e( "If not checked, there will be no option to select target when creating a message.", OMN_TXD ); ?></small>
					</td>
				</tr>
                <tr valign="top">
                	<th>
                		<label><?php _e( 'User capability neccessary to show ALL messages', OMN_TXD ); ?></label><br />
                	</th>
                	<td>
                		<input type="text" name="settings[minimal_capability]" value="<?php echo( $settings->minimal_capability ); ?>" />
                	</td>
                	<td>
						<small>
							<?php _e( "Currently the plugin stores only messages and information which message is targeted for which user. After user marks the message as read, target is deleted.", OMN_TXD ); ?>
							<?php _e( "So if an user wants to view already read messages, we can show him either all or none of them. This setting determines which users will have access to all messages.", OMN_TXD ); ?>
							<?php printf( __( "See %sWordPress Codex%s for information about capabilities.", OMN_TXD ), '<a href="http://codex.wordpress.org/Roles_and_Capabilities">', '</a>' ); ?>
							<?php _e( "If you need message history for each user, please contact plugin developer with feature request.", OMN_TXD ); ?>
						</small>
					</td>
                </tr>
	           	<tr valign="top">
                	<th>
                		<label><?php _e( 'Hide donation button', OMN_TXD ); ?></label><br />
                	</th>
                	<td>
                		<input type="checkbox" name="settings[hide_donation_button]" <?php checked( $settings->hide_donation_button ); ?> />
                	</td>
                	<td><small><?php _e( 'If you don\'t want to be bothered again...', OMN_TXD ); ?></small></td>
                </tr>
           	</table>
           	<h3><?php _e( 'E-mail notification', OMN_TXD ); ?></h3>
           	<table class="form-table">
				<tr valign="top">
                	<th>
                		<label><?php _e( 'Enable e-mail notification', OMN_TXD ); ?></label><br />
                	</th>
                	<td>
                		<input type="checkbox" name="settings[mail_notification][enabled]" <?php checked( $settings->mail_notification["enabled"] ); ?> />
                	</td>
                </tr>
                <tr valign="top">
                	<th>
                		<label><?php _e( "Template", OMN_TXD ); ?></label>
                	</th>
                	<td>
                		<label><?php _e( "Subject", OMN_TXD ); ?>: </label>
                		<input type="text" name="settings[mail_notification][subject]" value="<?php echo esc_attr( $settings->mail_notification["subject"] ); ?>" /><br />
                		<textarea name="settings[mail_notification][message]" rows="5" cols="50"><?php
                			echo esc_textarea( $settings->mail_notification["message"] );
                		?></textarea>
                	</td>
                </tr>
			</table>
			
			<?php submit_button( __( "Save", OMN_TXD ), "primary" ); ?>
			    	
		</form>
		<?php
	}
	
	
	
}

?>
