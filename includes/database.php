<?php

namespace OrganizationalMessageNotifier\Database {
	
	use \OrganizationalMessageNotifier\Log;
	
	const MESSAGES = "omn_messages";
	const UNREAD = "omn_unread";
	
	/** Returns a table name for provided constant (must be one of the above). */
	function tn( $table_name ) {
		global $wpdb;
		return $wpdb->base_prefix . $table_name;
	}
	
	
	function create_tables() {
		global $wpdb;
	
		$query_messages = "CREATE TABLE IF NOT EXISTS " . tn( MESSAGES ) . " (
				id INT NOT NULL AUTO_INCREMENT,
				title VARCHAR(200),
				text LONGTEXT,
				date DATETIME,
				link VARCHAR(200),
				author BIGINT(20),
				UNIQUE (id),
				PRIMARY KEY (id) )";
	
		$wpdb->query( $query_messages );
	
		$query_unread = "CREATE TABLE IF NOT EXISTS " . tn( UNREAD ) . " (
				id INT NOT NULL AUTO_INCREMENT,
				message_id INT,
				user_id BIGINT(20),
				UNIQUE ( id ),
				PRIMARY KEY ( id ) )";
	
		$wpdb->query( $query_unread );
	}
	
	
	function get_messages( $orderby = "date", $order = "DESC", $limit = NULL, $offset = 0 ) {
		global $wpdb;
		$limit = ( $limit == NULL ) ? "" : $wpdb->prepare( "LIMIT %d OFFSET %d", $limit, $offset );
		$results = $wpdb->get_results( "SELECT * FROM " . tn( MESSAGES ) . " ORDER BY $orderby $order $limit" );
		return $results;
	}
	
	
	function get_message_count() {
		global $wpdb;
		return $wpdb->get_var( "SELECT COUNT(1) FROM " . tn( MESSAGES ) );
	}
	
	
	function get_who_didnt_read_message( $message_id ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM " . tn( UNREAD ) . " WHERE message_id = %d", $message_id ) );
	}
	
	
	function get_count_who_didnt_read_message( $message_id ) {
		global $wpdb;
		return $wpdb->get_col( $wpdb->prepare( "SELECT COUNT(1) FROM " . tn( UNREAD ) . " WHERE message_id = %d", $message_id ) );
	}
	
	
	function get_user_unread_messages( $user_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
				"SELECT messages.id AS id, messages.title AS title, messages.link AS link,
					messages.text AS text, messages.date AS date, messages.author AS author
				FROM " . tn( UNREAD ). " AS unread JOIN " . tn( MESSAGES ) . " AS messages ON unread.message_id = messages.id
				WHERE unread.user_id = %d
				ORDER BY date DESC",
				$user_id );
		$results = $wpdb->get_results( $query );
		return $results;
	}
	
	
	function get_user_unread_message_count( $user_id ) {
		global $wpdb;
		$count = $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(1) FROM ". tn( UNREAD ). " WHERE user_id = %d", $user_id ) );
		return $count;
	}
	
	
	function insert_message( $title, $text, $link, $author, $date ) {
		global $wpdb;
		
		Log::info( "Creating new message: title = $title, link = \"$link\", author = $author, date = $date, text: $text" );
		
		$iok = $wpdb->insert(
				tn( MESSAGES ),
				array(
						"title" => $title,
						"text" => $text,
						"link" => $link,
						"author" => $author,
						"date" => $date ),
				array( '%s', '%s', '%s', '%d', '%s' ) );
		
		$message_id = $wpdb->insert_id;
		if( $iok === false ) {
			Log::dberror( "add new message" );
			return false;
		} else {
			return $message_id;
		}
	}
	
	
	function insert_target( $message_id, $user_id ) {
		global $wpdb;
		$iok = $wpdb->insert(
				tn( UNREAD ),
				array( "message_id" => $message_id, "user_id" => $user_id ),
				array( '%d', '%d' )	);
		
		if( $iok === false ) {
			Log::dberror( "add new target" );
			return false;
		} else {
			return true;
		}
	}
	
	
	function target_exists( $message_id, $user_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
				"SELECT COUNT(1) FROM " . tn( UNREAD ) . "WHERE ( message_id = %d AND user_id = %d	)",
				$message_id,
				$user_id );
		return ( $wpdb->get_var( $query ) > 0 );
	}
	
	
	function delete_target( $message_id, $user_id ) {
		global $wpdb;
		$query = "DELETE FROM " . tn( UNREAD ) . " WHERE ( message_id = %d AND user_id= %d )";
		$ok = $wpdb->query( $wpdb->prepare( $query, $message_id, $user_id ) );
		if( $ok === false ) {
			Log::dberror( "delete target" );
			return false;
		} else {
			Log::info( "Message $message_id marked as read for user $user_id." );
			return true;
		}
	}
	
	
	function delete_all_targets( $message_id ) {
		global $wpdb;
		$query = "DELETE FROM " . tn( UNREAD ). " WHERE message_id = %d";
		$ok = $wpdb->query( $wpdb->prepare( $query, $message_id ) );
		if( $ok === false ) {
			Log::dberror( "delete all targets" );
			return false;
		} else {
			Log::log( 'Deleting all targets for message '.$message_id.'.', 3 );
			return true;
		}
	}
	
	
	function delete_message( $message_id ) {
		global $wpdb;
		Log::log( 'Deleting message '.$message_id.'.', 3 );
		
		if( get_count_who_didnt_read_message( $message_id ) > 0 ) {
			$ok = delete_all_targets( $message_id );
			if( !$ok ) {
				return false;
			}
		}
		
		$ok = $wpdb->query( $wpdb->prepare( "DELETE FROM " . tn( MESSAGES ) . " WHERE id = %d", $message_id ) );
		if( $ok === false ) {
			Log::dberror( "delete message" );
			return false;
		} else {
			Log::debug( "Message $message_id deleted." );
			return true;
		}
	}
}

?>
