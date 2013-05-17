<?php


if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}



class OrganizationalMessageNotifier_Overview_Table extends WP_List_Table {


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

		switch( $_REQUEST["orderby"] ) {
			case "title":
				$orderby = "title";
				break;
			case "date": // fall through
			default:
				$orderby = "date";
				break;
		}
		
		$order = ( $_REQUEST["order"] == "asc" ) ? "ASC" : "DESC";
		
		$current_page = $this->get_pagenum();
				
		$data = omn_get_messages( $orderby, $order );

		//TODO brat z databaze jen presne to, co opravdu potrebujeme		
        $total_items = count($data); 
        $data = array_slice($data,(($current_page-1)*$per_page),$per_page);
        
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
        if( omn_get_nonreading_user_count( $item->id ) > 0 ) {
        	$actions[] = sprintf( '<a href="?page=%s&action=%s&id=%s">%s</a>', $_REQUEST['page'], 'expire-message', $item->id, __( "Expire", OMN_TXD ) );
		}
		        							
		return "<strong>$title</strong>".$this->row_actions( $actions );
	}
	
	
	function column_author( $item ) {
		$author_data = get_userdata( $item->author );
		return $author_data->user_login;
	}
	
	
	function column_content( $item ) {
		return wp_kses_data( stripslashes( $item->text ) );
	}
	
	
	function column_didnt_read( $item ) {
		$nrus = omn_get_nonreading_users( $item->id );
		$nru_strings = array();
		foreach( $nrus as $nru ) {
			$userdata = get_userdata( $nru );
			$nru_strings[] = 
				"{$userdata->user_login} <a href=\"index.php?page=omn-superadmin-overview&action=delete-notification&user=$nru&message={$item->id}\">&times;</a>";
		}
		if( count( $nrus ) > 0 ) {
			return count( $nrus ).": ".implode( ", ", $nru_strings );
		} else {
			return __( '(has been read by everyone)', OMN_TEXTDOMAIN );
		}					
	}


}


?>
