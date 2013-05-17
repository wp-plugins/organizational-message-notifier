<?php

namespace OrganizationalMessageNotifier {
	
	/** Singleton providing access to settings. */
	class Settings {
	
		/** Name of the WordPress option where all settings are stored. */
		const SETTINGS_KEY = "omn_settings";
	
	
		/** Cached settings. We usually only read them and they don't change from
		 * the outside during execution. */
		private static $cache = false;
	
	
		/** Default values for settings. */
		private static $defaults = array(
				'default_target' => MessageTargets::ADMINS,
				"target_role" => "subscriber",
				"allow_nondefault_target" => false,
				'specific_users_ids' => '',
				'minimal_capability' => 'read',
				'hide_donation_button' => false,
				"mail_notification" => array(
						"enabled" => false,
						"subject" => "subject",
						"message" => "message" ) );
	
	
		private static $instance = false;
	
	
		static function getInstance() {
			if( self::$instance === false ) {
				self::$instance = new Settings();
			}
			return self::$instance;
		}
	
	
		/** Load settings into the cache. */
		private function __construct() {
			self::$cache = wp_parse_args( get_site_option( self::SETTINGS_KEY, array(), false ), self::$defaults );
		}
	
	
		/** Dynamic property getter - allows to access settings like $instance->setting_name.
		 *
		 * @return value of the setting or NULL if such setting doesn't exist.
		 */
		function __get( $property ) {
			return array_key_exists( $property, self::$cache ) ? self::$cache[$property] : NULL;
		}
	
	
		/** Dynamic property setter - allows to assign a value to a setting like
		 * $instance->setting_name = new_value. Note that settings must be saved to
		 * the database by save() to make them persistent.
		 */
		function __set( $property, $value ) {
			self::$cache[$property] = $value;
		}
	
	
		/** Save settings from cache to database. */
		function save() {
			update_site_option( self::SETTINGS_KEY, self::$cache );
		}
	
	
		/** Overwrite the cache. */
		function set( $data ) {
			self::$cache = $data;
		}
	
	}
	
	
	/** Enum of possible targets. */
	class MessageTargets {

		const ADMINS = "admins";
		const ADMINS_BY_EMAIL = "admins_by_admin_email";
		const ROLE = "role";
		const ALL_USERS = "all_users";
		const SPECIFIC = "specific";

		public static $multisite_values = array( self::ADMINS, self::ADMINS_BY_EMAIL, self::ROLE, self::ALL_USERS, self::SPECIFIC );
		public static $singlesite_values = array( self::ADMINS, self::ROLE, self::ALL_USERS, self::SPECIFIC );
		
		static function values() {
			return is_multisite() ? self::$multisite_values : self::$singlesite_values;
		}
		
		static function desc( $value ) {
			switch( $value ) {
			case self::ADMINS:
				return is_multisite() ? __( "Administrators of currently active blogs", OMN_TXD ) : __( "Administrators", OMN_TXD );
			case self::ADMINS_BY_EMAIL:
				return __( "Administrators of blogs (by admin e-mail address)", OMN_TXD );
			case self::ROLE:
				return is_multisite() ? __( "Users with specified role (on primary blog)", OMN_TXD ) : __( "Users with specified role", OMN_TXD );
			case self::ALL_USERS:
				return is_multisite() ? __( "All users in the network", OMN_TXD ) : __( "All users on the blog", OMN_TXD );
			case self::SPECIFIC:
				return __( "Specified users", OMN_TXD );
			default:
				return __( "error", OMN_TXD );
			}
		}

		private function __construct() { }
	}
}

?>
