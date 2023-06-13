<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_Video_Usage_By_User_By_Day_Table extends WP_List_Table {

	public $data;
	public $is_partner;

	public function __construct($items, $is_partner){
		$this->data = $items;
		$this->is_partner = $is_partner;

		parent::__construct( array( 
			'plural'	=>	'users',	// Plural value used for labels and the objects being listed.
			'singular'	=>	'user',		// Singular label for an object being listed, e.g. 'post'.
			'ajax'		=>	false,		// If true, the parent class will call the _js_vars() method in the footer		
		) );
	}

	public function get_columns(){
	  $columns = array(
	    // 'user_id'							=> 'Customer ID',
	    // 'tracking_account_id' => 'Account ID',
	    // 'tracking_contact_id' => 'Contact ID',
	    'full_name'   				=> 'Contact',
	    'company'   					=> 'Company',
	    'user_email'   				=> 'Email',
	    'role'   							=> 'User Type',
	    'partner' 						=> 'Partner',
	    'date' 								=> 'Date',
	    'video'								=> 'Video',
			'time_watched'				=> 'Time Watched',
			'end_percentage'			=> 'End %'
	  );

		if($this->is_partner){
			unset($columns['partner']);
		}

		if (IP_Helpers::is_shared_partner()) {
			$columns['child_partner'] = 'Child Partner';
		}
	  return $columns;
	}

	public function prepare_items() {
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$table_data = $this->data;

	  $this->items = $table_data;
	}

	public function get_sortable_columns() {
      return array(
      	'resource_id' => array('resource_id', false),
      	'category' => array('category', false),
      	'sub_category' => array('sub_category', false),
      	'post_title' => array('post_title', false),
      	'name' => array('name', false),
      	'views' => array('views', false),
    	);
  }

	public function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	  	case 'user_id' :
			case 'tracking_account_id' :
			case 'tracking_contact_id' :
			case 'full_name' :
			case 'company' :
			case 'user_email' :
			case 'partner' :
			case 'date' :
			case 'video' :
			case 'time_watched' :
			case 'child_partner' :
				return $item[ $column_name ];
			case 'role' :
				return IP_Helpers::get_user_role_title($item[ $column_name ]);
			case 'end_percentage':
				$percent = floatval($item[$column_name]);
				return  strval(round(($percent * 100))).'%';
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}
}