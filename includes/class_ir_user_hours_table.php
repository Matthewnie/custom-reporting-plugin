<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_User_Hours_Table extends WP_List_Table {

	public $data;
	public $is_partner;

	function __construct($items, $is_partner){
		$this->data = $items;
		$this->is_partner = $is_partner;

		parent::__construct( [
			'singular' => __( 'Users', 'sp' ), //singular name of the listed records
			'plural'   => __( 'Users', 'sp' ), //plural name of the listed records
			'ajax'     => false //should this table support ajax?
		] );
	}

	function get_columns(){
	  $columns = array(
	    // 'user_id' => 'Customer ID',
			// 'tracking_account_id' => 'Account ID',
			// 'tracking_contact_id' => 'Contact ID',
			'full_name' => 'Customer Name',
			'company' => 'Company',
			'user_email' => 'Email',
			'date' => 'Date',
			'hours' => 'Hours',
			'capabilities' => 'Role',
			'partner_title' => 'Partner',
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

	  // $sortable = $this->get_sortable_columns();
		$data = $this->data;

		$data = $this->sort_data($data);

		$perPage = 20;
		$currentPage = $this->get_pagenum();
		$totalItems = count($data);

		$this->set_pagination_args( array(
						'total_items' => $totalItems,
						'per_page'    => $perPage
		) );
		$data = array_slice($data,(($currentPage-1)*$perPage),$perPage);

	  $this->_column_headers = array($columns, $hidden, $sortable);
	  $this->items = $data;

	}

	public function sort_data($data){
		// Most are sorted by MYSQL
		$sort_key = false;
		$orderby = '';
		$order = 'ASC';
		if(!empty($_GET['orderby']))
		{
			$orderby = $_GET['orderby'];
		}
		// If order is set use this as the order
		if(!empty($_GET['order']))
		{
			$order = $_GET['order'];
		}

		switch($orderby){
			case 'unique_views':
			case 'resources_count':
				$sort_key = $orderby;
			 break;
		}

		if($sort_key){
			usort($data, $this->build_sorter($sort_key));
			if($order=='desc'){
				$data = array_reverse($data);
			}
		}


		return $data;
	}

	function build_sorter($key) {
		return function ($a, $b) use ($key) {
						return strnatcmp($a[$key], $b[$key]);
		};
	}

	public function get_sortable_columns() {
      return array(
      	'full_name' => array('full_name', false),
      	'company' => array('company', false),
      	'last_login' => array('last_login', false),
      	'user_registered' => array('user_registered', false),
      	'login_count' => array('login_count', false),
      	'resources_count' => array('resources_count', false),
      	'contact_a_coach_count' => array('contact_a_coach_count', false),
      	'capabilities' => array('capabilities', false),
      	'partner_title' => array('partner_title', false),
      	'time_in_portal' => array('time_in_portal', false),
      	'unique_views' => array('unique_views', false),
    	);
  }

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
			case 'user_id' :
			case 'tracking_account_id' :
			case 'tracking_contact_id' :
			case 'full_name' :
			case 'company' :
			case 'user_email' :
			case 'date' :
			case 'hours' :
			case 'capabilities':
	  		return $item[ $column_name ];
	  	case 'capabilities-old':
				return implode(', ', array_keys(IRB_Util::removeWPML(unserialize($item[ $column_name ])), true));
	  	// case 'time_in_portal':
	  	// 	return IRB_Util::formatTimeInPortal( ($item[ $column_name ] / 60) / 60 );
	  	// 	// return var_dump(unserialize($item[ $column_name ]));
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}

}