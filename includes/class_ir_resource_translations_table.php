<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_Resource_Translations_Table extends WP_List_Table {

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
	    'resource_id' 	=> 'Resource ID',
	    'post_title'    	=> 'Title',
	    'language'  => 'Language',
			'post_modified'   	=> 'Modified',
			'translation_post_id'   	=> 'Translation ID',
	    'translation_title'   				=> 'Translation Title',
	    'translation_modified'   			=> 'Translation Modified',
	    'translation_status'   			=> 'Translation Status'
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
      	'resource_id' => array('resource_id', false),
				'post_title' => array('post_title', false),
      	'post_modified' => array('post_modified', false),
      	'translation_post_id' => array('translation_post_id', false),
      	'translation_title' => array('translation_title', false),
      	'translation_modified' => array('translation_modified', false),
      	'translation_status' => array('translation_status', false),
    	);
  }

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	  	case 'resource_id':
	  	case 'post_title':
	  	case 'language':
	  	case 'post_title':
	  	case 'translation_post_id':
	  	case 'translation_title':
	  	case 'translation_status':
				return $item[ $column_name ];
			case 'post_date':
	  	case 'post_modified':
	  	case 'translation_modified':
	  		return $item[$column_name] ? date('Y-m-d', strtotime($item[ $column_name ])) : '';
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