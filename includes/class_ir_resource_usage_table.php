<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_Resource_Usage_Table extends WP_List_Table {

	public $data;
	public $is_partner;

	function __construct($items, $is_partner){
		$this->data = $items;
		$this->is_partner = $is_partner;

		parent::__construct( [
			'singular' => __( 'Resource', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Resources', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		] );
	}

	function get_columns(){
	  $columns = array(
	    'resource_id' 	=> 'Resource ID',
	    'category'    	=> 'Category',
	    'sub_category'  => 'Sub-Category',
	    'post_title'   	=> 'Resource Name',
			'post_excerpt'   	=> 'Excerpt',
	    'name'   				=> 'Type of Resource',
	    'views'   			=> '# Views',
	    'partner_title' => 'Partner',
	    'post_date' 		=> 'Created',
			'post_modified' => 'Modified',
			'language_code' => 'Language',
			'training_views' => 'Training Views'
	  );

		if($this->is_partner){
			unset($columns['partner_title']);
		}

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
      	'resource_id' => array('resource_id', false),
      	'category' => array('category', false),
      	'sub_category' => array('sub_category', false),
      	'post_title' => array('post_title', false),
      	'name' => array('name', false),
      	'views' => array('views', false),
      	'post_date' => array('post_date', false),
      	'post_modified' => array('post_modified', false),
    	);
  }

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	  	case 'resource_id':
	  	case 'category':
	  	case 'sub_category':
	  	case 'post_title':
	  	case 'post_excerpt':
	  	case 'name':
	  	case 'views':
	  	case 'partner_title':
	  	case 'language_code':
	  	case 'training_views':
				return $item[ $column_name ];
			case 'post_date':
	  	case 'post_modified':
	  		return date('Y-m-d', strtotime($item[ $column_name ]));
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}

	private function sort_data( $a, $b ) {
    // Set defaults
    $orderby = 'post_modified';
    $order = 'desc';
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
		
		if( $orderby === 'views' || $orderby === 'resource_id' ){
			if($order === 'asc')
			{
					return (int)$a[$orderby] - (int)$b[$orderby];
			}
			return (int)$b[$orderby] - (int)$a[$orderby];
		}
		else {
			$result = strcasecmp( $a[$orderby], $b[$orderby] );
			if($order === 'asc')
			{
					return $result;
			}
			return -$result;
		}

  }

}