<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_Resource_Usage_By_User_Table extends WP_List_Table {

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
			// 'tracking_account_id' => 'Account ID',
			// 'tracking_contact_id' => 'Contact ID',
			'company' => 'Company',
			'full_name' => 'Contact',
			'user_email' => 'Email',
			'reporting_role' => 'User Type',
			'partner' => 'Partner',
			'time' => 'Date',
			'lang_code' => 'Language',
			'category' => 'Category',
			'sub_category' => 'Sub-Category',
			'post_title' => 'Resource Name',
			'name' => 'Type of Resource',
	  );
		if($this->is_partner){
			unset($columns['partner']);
		}
		if(IP_Helpers::is_shared_partner()){
			$columns['child_partner'] = 'Child Partner';
		}
	  return $columns;
	}

	public function prepare_items() {
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = array();
		$this->_column_headers = array($columns, $hidden, $sortable);
		$data = $this->data;

		$perPage = 20;
		$currentPage = $this->get_pagenum();
		$totalItems = count($data);

		$this->set_pagination_args( array(
						'total_items' => $totalItems,
						'per_page'    => $perPage
		) );
		$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

	  $this->items = $data;
	}

	public function get_sortable_columns() {
      return array(
      	'resource_id' => array('resource_id', false),
      	'category' => array('category', false),
      	'sub_category' => array('sub_category', false),
      	'post_title' => array('post_title', false),
      	'name' => array('name', false)
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
			case 'time' :
			case 'lang_code' :
			case 'category' :
			case 'sub_category' :
			case 'resource_id' :
			case 'post_title' :
			case 'name' :
			case 'partner' :
				return $item[ $column_name ];
			case 'reporting_role' :
				return IP_Helpers::get_user_role_title($item[ $column_name ]);
			case 'child_partner' :
				if($item[$column_name]){
					return get_the_title($item[ $column_name ]);
				}
				return '';
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}
}