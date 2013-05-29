<?php

namespace OrganizationalMessageNotifier\MessagesUI {

	use \OrganizationalMessageNotifier\Database as db;
	
	class MessageTable extends \WP_List_Table {
	
	
		function __construct() {
			parent::__construct( array(
					'singular'  => __( "message", OMN_TXD ),
					'plural'    => __( "messages", OMN_TXD ),
					'ajax'      => false
			) );
		}
	
	
		function get_columns() {
			$columns = array(
					"date" => __( "Date", OMN_TXD ),
					"title" => __( "Message title", OMN_TXD ),
					"author" => __( "Author", OMN_TXD ),
					"content" => __( "Content", OMN_TXD ),
					"didnt_read" => __( "Users who didn't read it yet", OMN_TXD )
			);
			return $columns;
		}
	
	
		function get_sortable_columns() {
			return array(
					"date" => array( "date", true ),
					"author" => array( "author", false )
			);
		}
	
	
		function prepare_items() {
			$per_page = 10;
	
			$columns = $this->get_columns();
			$hidden = array();
			$sortable = $this->get_sortable_columns();
	
			$this->_column_headers = array($columns, $hidden, $sortable);
	
			$orderby = empty( $_REQUEST["orderby"]  ) ? "date" : $_REQUEST["orderby"];
			$order = ( $_REQUEST["order"] == "asc" ) ? "ASC" : "DESC";
	
			$current_page = $this->get_pagenum();
	
			$data = db\get_messages( $orderby, $order, $per_page, ( $current_page - 1 ) * $per_page );
	
			$total_items = db\get_message_count();
	
			$this->items = $data;
	
			$this->set_pagination_args( array(
					'total_items' => $total_items,
					'per_page'    => $per_page,
					'total_pages' => ceil($total_items/$per_page)
			) );
	
		}
	
	
		function column_date( $item ) {
			return $item->date;
		}
	
	
		function column_title( $item ) {
			if( !empty( $item->link ) ) {
				$title = "<a href=\"{$item->link}\">{$item->title}</a>";
			} else {
				$title = $item->title;
			}
	
			$actions = array(
					'delete' => sprintf( '<a href="?page=%s&action=%s&id=%s">%s</a>', $_REQUEST['page'], 'delete-message', $item->id, __( "Delete", OMN_TXD ) )
			);
			if( db\get_count_who_didnt_read_message( $item->id ) > 0 ) {
				$actions[] = sprintf( '<a href="?page=%s&action=%s&id=%s">%s</a>', $_REQUEST['page'], 'expire-message', $item->id, __( "Expire", OMN_TXD ) );
			}
			 
			return "<strong>$title</strong>" . $this->row_actions( $actions );
		}
	
	
		function column_author( $item ) {
			$author_data = get_userdata( $item->author );
			return $author_data->user_login;
		}
	
	
		function column_content( $item ) {
			return wp_kses_data( stripslashes( $item->text ) );
		}
	
	
		function column_didnt_read( $item ) {
			$nrus = db\get_who_didnt_read_message( $item->id );
			$nru_strings = array();
			foreach( $nrus as $nru ) {
				$userdata = get_userdata( $nru );
				$nru_strings[] = "{$userdata->user_login} <a href=\"index.php?page=omn-management&action=delete-notification&user=$nru&message={$item->id}\">&times;</a>";
			}
			if( count( $nrus ) > 0 ) {
				return count( $nrus ).": ".implode( ", ", $nru_strings );
			} else {
				return __( '(has been read by everyone)', OMN_TXD );
			}
		}
	
	
	}
}
?>