<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_Internal_Resources_Table extends WP_List_Table {

	public $data;

	function __construct($items){
		$this->data = $items;

		parent::__construct( [
			'singular' => __( 'Resource', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Resources', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		] );
	}

	function get_columns(){
	  $columns = array(
	    'post_id' 	=> 'Resource ID',
	    'post_title'    	=> 'Post Title',
	    'post_owner'    	=> 'Post Owner',
	    'related_resource'  => 'Related Resource',
	    'related_status'  => 'Related Status',
	    'related_owner'  => 'Related Owner',
	  );
	  return $columns;
	}

	function prepare_items() {
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = array();

	  $sortable = $this->get_sortable_columns();
    $data = $this->data;
    usort( $data, array( &$this, 'sort_data' ) );

	  $this->_column_headers = array($columns, $hidden, $sortable);
	  $this->items = $data;

	}

	public function get_sortable_columns() {
      return array(
      	'post_id' => array('post_id', false),
      	'post_title' => array('post_title', false),
      	'post_owner' => array('post_owner', false),
      	'related_resource' => array('related_resource', false),
      	'related_status' => array('related_status', false),
      	'related_owner' => array('related_owner', false),
    	);
  }

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	  	case 'post_id':
	  	case 'post_title':
	  	case 'post_owner':
	  	case 'related_resource':
	  	case 'related_status':
	  	case 'related_owner':
	  		return $item[ $column_name ];
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}

	private function sort_data( $a, $b ) {
    // Set defaults
    $orderby = 'post_title';
    $order = 'asc';
    // If orderby is set, use this as the sort column
    if(!empty($_GET['orderby']))
    {
        $orderby = $_GET['orderby'];
    }
    // If order is set use this as the order
    if(!empty($_GET['order']))
    {
        $order = $_GET['order'];
    }
    $result = strcasecmp( $a[$orderby], $b[$orderby] );
    if($order === 'asc')
    {
        return $result;
    }
    return -$result;
  }

}