<?php


/* ************************************************************************* *\
	INSTALLATION
\* ************************************************************************* */


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


/* ************************************************************************* *\
	DATABASE ACCESS
\* ************************************************************************* */


function omn_messages_table() {
	global $wpdb;
	return $wpdb->base_prefix.'omn_messages';
}


function omn_unread_table() {
	global $wpdb;
	return $wpdb->base_prefix.'omn_unread';
}


function omn_get_messages( $orderby = "date", $order = "DESC") {
	global $wpdb;
	$results = $wpdb->get_results( 'SELECT * FROM '.omn_messages_table()." ORDER BY $orderby $order" );
	return $results;
}


function omn_is_message_unread( $message_id ) {
	global $wpdb;
	$query = $wpdb->prepare( '
		SELECT COUNT(*) 
		FROM '.omn_unread_table().'
		WHERE (
			message_id = %d 
			AND user_id = %d 
		)',
		$message_id, get_current_user_id()
	);
	return ( $wpdb->get_var( $query ) > 0 );
}


function omn_unread_count() {
	global $wpdb;
	$count = $wpdb->get_var( 
		$wpdb->prepare( 'SELECT COUNT(1) FROM '.omn_unread_table().' WHERE user_id = %d', get_current_user_id() ) 
	);
	return $count;
}


function omn_get_unread_messages() {
	global $wpdb;
	$query = $wpdb->prepare( '
		SELECT messages.id AS id, messages.title AS title, messages.link AS link, 
			messages.text AS text, messages.date AS date, messages.author AS author
		FROM '.omn_unread_table().' AS unread
			JOIN '.omn_messages_table().' AS messages
			ON unread.message_id = messages.id
		WHERE unread.user_id = %d
		ORDER BY date DESC',
		get_current_user_id()
	);
	$results = $wpdb->get_results( $query );
	return $results;
}


function omn_get_nonreading_users( $message_id ) {
	global $wpdb;
	return $wpdb->get_col( $wpdb->prepare( 
		'SELECT user_id
		FROM '.omn_unread_table().'
		WHERE message_id = %d',
		$message_id 
	) );
}


function omn_get_nonreading_user_count( $message_id ) {
	global $wpdb;
	return $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM ".omn_unread_table()." WHERE message_id = %d", $message_id ) );
}


function omn_read_message( $message_id ) {
	global $wpdb;
	$wpdb->query( $wpdb->prepare( 
		'DELETE FROM '.omn_unread_table().'
		WHERE (
			user_id = %d
			AND message_id = %d
		)',
		get_current_user_id(), $message_id ) );
	omn_log( 'marking message '.$message_id.' as read.', 2 );
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


function omn_delete_message( $message_id ) {
	global $wpdb;
	omn_log( 'Deleting message '.$message_id.'.', 3 );
	$wpdb->query( $wpdb->prepare( 'DELETE FROM '.omn_messages_table().' WHERE id = %d', $message_id ) );
	omn_delete_notifications( $message_id );
}

?>
