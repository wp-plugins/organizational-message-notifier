<?php


/*****************************************************************************\
		SETTINGS
\*****************************************************************************/


define( 'OMN_SETTINGS', 'omn_settings' );


function omn_get_settings() {
	$defaults = array(
		'default_target' => 'admins',
		'specific_users_ids' => '',
		'minimal_capability' => 'manage_options',
		'hide_donation_button' => false,
		"mail_notification" => array(
			"enabled" => false,
			"subject" => "subject",
			"message" => "message"
		)
	);
	
	$settings = get_site_option( OMN_SETTINGS, array() );
	
	return wp_parse_args( $settings, $defaults );	
}


function omn_update_settings( $settings ) {
	$settings["mail_notification"]["enabled"] = isset( $settings["mail_notification"]["enabled"] );
	update_site_option( OMN_SETTINGS, $settings );
}



function omn_settings_page() {

	if( isset($_REQUEST['action']) ) {
        $action = $_REQUEST['action'];
    } else {
        $action = 'default';
    }
    
    switch( $action ) {
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
    	omn_settings_page_default();
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
    	omn_settings_page_default();
    	break;    
    case 'update-settings':
		omn_update_settings( $_POST['settings'] );
		omn_settings_page_default();
    	break;
    default:
    	omn_settings_page_default();
    }
    
}


function omn_settings_page_default() {
	extract( omn_get_settings() );
	?>
	<div id="wrap">
		<h2><?php _e( 'Organizational Message Notifier', OMN_TEXTDOMAIN ); ?></h2>
		<?php
			if( !$hide_donation_button ) {
				?>
				<h3><?php _e( 'Please consider a donation', OMN_TEXTDOMAIN ); ?></h3>
				<p>
					<?php _e( 'I spend quite a lot of my precious time working on opensource WordPress plugins. If you find this one useful, please consider helping me develop it further. Even the smallest amount of money you are willing to spend will be welcome.', OMN_TEXTDOMAIN ); ?>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="39WB3KGYFB3NA">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" style="border:none;" >
						<img style="display:none;" alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</p>
				<?php
			}
		?>
		<?php
			if( defined( 'WLS' ) ) {
				?>			 
				<h3><?php _e( 'Registration with WLS', SUH_TEXTDOMAIN ); ?></h3>
		    	<p><?php _e( 'Wordpress Logging Service was detected.', SUH_TEXTDOMAIN ); ?></p>
	    		<?php
	    			if( !wls_is_registered( OMN_LOGNAME ) ) {
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
								<input type="submit" style="color:red;" value="<?php _e( 'Unregister from WLS and delete log entries', OMN_TEXTDOMAIN ); ?>" />    
							</p> 
						</form>
						<?php
					}
				?>
				<?php
        	} 
        ?>
        <form method="post">
        	<h3><?php _e( 'Basic settings', OMN_TEXTDOMAIN ); ?></h3>
            <input type="hidden" name="action" value="update-settings" />
           	<table class="form-table">
           		<tr valign="top">
                	<th>
                		<label><?php _e( 'Default message target', OMN_TEXTDOMAIN ); ?></label><br />
                	</th>
                	<td>
                		<input type="radio" name="settings[default_target]" value="admins" <?php checked( $default_target, 'admins' ); ?> />&nbsp;<?php _e( 'Administrators of currently active blogs', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="settings[default_target]" value="admins_by_admin_email" <?php checked( $default_target, 'admins_by_admin_email' ); ?> />&nbsp;<?php _e( 'Administrators of blogs (by admin e-mail address)', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="settings[default_target]" value="all_users" <?php checked( $default_target, 'all_users' ); ?> />&nbsp;<?php _e( 'All users in the network', OMN_TEXTDOMAIN ); ?><br />
                		<input type="radio" name="settings[default_target]" value="specific" <?php checked( $default_target, 'specific' ); ?> />&nbsp;<?php _e( 'Specific users: ', OMN_TEXTDOMAIN ); ?>&nbsp;<input type="text" name="settings[specific_users_ids]" value="<?php echo $specific_users_ids; ?>" />
                	</td>
                	<td>
                		<small>
                			<?php _e( 'If the "Specific users" option is checked, enter their ID\'s separated by commas.', OMN_TEXTDOMAIN ); ?><br />
                			<?php _e( 'Also don\'t forget to adjust the user capability condition (see below).', OMN_TEXTDOMAIN ); ?>
                		</small>
                	</td>
                </tr>
                <tr valign="top">
                	<th>
                		<label><?php _e( 'User capability neccessary to show messages', OMN_TEXTDOMAIN ); ?></label><br />
                	</th>
                	<td>
                		<input type="text" name="settings[minimal_capability]" value="<?php echo( $minimal_capability ); ?>" />
                	</td>
                	<td><small><?php printf( __( 'Who will have access to messages? See %sWordPress Codex%s for information about capabilities.', OMN_TEXTDOMAIN ), '<a href="http://codex.wordpress.org/Roles_and_Capabilities">', '</a>' ); ?></small></td>
                </tr>
	           	<tr valign="top">
                	<th>
                		<label><?php _e( 'Hide donation button', OMN_TEXTDOMAIN ); ?></label><br />
                	</th>
                	<td>
                		<input type="checkbox" name="settings[hide_donation_button]" 
                			<?php if( $hide_donation_button ) echo 'checked="checked"'; ?>
                		/>
                	</td>
                	<td><small><?php _e( 'If you don\'t want to be bothered again...', OMN_TEXTDOMAIN ); ?></small></td>
                </tr>
           	</table>
           	<h3><?php _e( 'E-mail notification', OMN_TEXTDOMAIN ); ?></h3>
           	<table class="form-table">
				<tr valign="top">
                	<th>
                		<label><?php _e( 'Enable e-mail notification', OMN_TEXTDOMAIN ); ?></label><br />
                	</th>
                	<td>
                		<input type="checkbox" name="settings[mail_notification][enabled]" <?php checked( $mail_notification["enabled"] ); ?> />
                	</td>
                </tr>
                <tr valign="top">
                	<th>
                		<label><?php _e( "Template", OMN_TXD ); ?></label>
                	</th>
                	<td>
                		<label><?php _e( "Subject", OMN_TXD ); ?>: </label>
                		<input type="text" name="settings[mail_notification][subject]" value="<?php echo esc_attr( $mail_notification["subject"] ); ?>" /><br />
                		<textarea name="settings[mail_notification][message]" rows="5" cols="50"><?php
                			echo esc_textarea( $mail_notification["message"] );
                		?></textarea>
                	</td>
                </tr>
			</table>        	
			<p class="submit">
	            <input type="submit" class="button-primary" value="<?php _e( 'Save', OMN_TEXTDOMAIN ); ?>" />    
	        </p>
		</form>
	</div>
	<?php
}





?>
