<?php

// Version: 2012-07-07

namespace OrganizationalMessageNotifier {

	class z {

	
		static function nag( $message ) {
			echo '<div id="message" class="updated"><p>'.$message.'</p></div>';
		}


		static function nagerr( $message ) {
			echo '<div id="message" class="error"><p>'.$message.'</p></div>';
		}

		
		static function is_php_version( $requested ) {
			$requested = explode( ".", $requested );
			$current = explode( ".", phpversion() );
			if( $current[0] > $requested[0] ) {
				return true;
			} else if( $current[0] == $requested[0] ) {
				if( $current[1] > $requested[1] ) {
					return true;
				} else if( $current[1] == $requested[1] && $current[2] >= $requested[2] ) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		
		static function maybe_donation_button( $hide = false, $txd = "" ) {
			if( !$hide ) {
				?>
				<h3><?php _e( 'Please consider a donation', $txd ); ?></h3>
				<p>
					<?php _e( 'I spend quite a lot of my precious time working on opensource WordPress plugins. If you find this one useful, please consider helping me develop it further. Even the smallest amount of money you are willing to spend will be welcome.', $txd ); ?>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post"><input type="hidden" name="cmd" value="_s-xclick">
						<input type="hidden" name="hosted_button_id" value="39WB3KGYFB3NA">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!" style="border:none;" >
						<img style="display:none;" alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form>
				</p>
				<?php
			}
		}
		
		
		static function donation_button_option( $checked, $txd = "", $field_name = "hide_donation_button" ) {
			?>
				<tr valign="top">
                	<th>
                		<label><?php _e( 'Hide donation button', $txd ); ?></label><br />
                	</th>
                	<td>
                		<input type="checkbox" name="<?php echo $field_name; ?>" <?php checked( $checked ); ?> />
                	</td>
                	<td><small><?php _e( 'If you don\'t want to be bothered again...', $txd ); ?></small></td>
                </tr>
            <?php
		}
	
	
	}

}

?>
