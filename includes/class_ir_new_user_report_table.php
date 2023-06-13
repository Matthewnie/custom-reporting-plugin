<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class IRB_New_User_Report_Table extends WP_List_Table {

	public $data;
	public $is_partner = false;

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
		global $user_ID;
		
	  $columns = array(
			// 'tracking_account_id'		=> 'Account ID',
			// 'tracking_contact_id'		=> 'Contact ID',
	    'company' 							=> 'Company',
	    'full_name'    					=> 'Contact',
			'partner_title'					=> 'Partner',
			// 'address_line1'					=> 'Address Line 1',
			// 'address_line2'					=> 'Address Line 2',
			// 'address_city'					=> 'City',
			// 'address_state'					=> 'State',
			// 'address_zip'						=> 'Zipcode',
	    'user_email'    				=> 'Email',
	    'reporting_role'				=> 'User Type',
			'access_code'						=> 'Access Code',
	    'user_registered'   		=> 'Account Created',
			'expiration_date'				=> 'Exp. Date',
	    'hours'									=> 'Hours',
	    'views_downloads'				=> 'Total Views/Downloads',
	    'unique_views_downloads'=> 'Unique Views/Downloads',
	    'unique_views'					=> 'Resources Viewed',
			'posts'									=> 'List of Resources Viewed',
			'downloads'							=> 'Resources Downloaded',
			'download_titles'				=> 'List of Resources Downloaded',
	    'lang_views'						=> 'Languages',
	    // 'first_use'   					=> 'First Use',
	    'days_accessed'   			=> 'Days Accessed',
	    // 'date_last_accessed'   	=> 'Last Accessed',
	    'cac_count'							=> 'Contacted a Coach',
			// 'locked'								=> 'Locked'
	  );

		if($this->is_partner){
			unset($columns['partner_title']);
		}

		// if (IP_Helpers::is_shared_partner()) {
		// 	$columns['child_partner_title'] = 'Child Partner';
		// }

		if ($this->is_partner) {
			$partner_id = get_user_meta($user_ID, 'partner', true);
			// $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
			$use_custom_fields = get_post_meta($partner_id, 'use_custom_fields', true);
			$custom_fields_details =  get_field('custom_fields', $partner_id);
			if($use_custom_fields && $custom_fields_details){
				foreach ($custom_fields_details as $idx => $field) {
					$columns['custom_field_'.$idx] = $field['label'];
				}
			}
		}

	  return $columns;
	}

	function prepare_items() {
	  $columns = $this->get_columns();
	  $hidden = array();
	  $sortable = array();

	  $sortable = $this->get_sortable_columns();
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
			case 'login_count':
			case 'time_in_portal':
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
      	// 'date_last_accessed' => array('date_last_accessed', false),
      	'user_registered' => array('user_registered', false),
      	'login_count' => array('login_count', false),
      	'resources_count' => array('resources_count', false),
      	'contact_a_coach_count' => array('contact_a_coach_count', false),
      	'reporting_role' => array('reporting_role', false),
      	'partner_title' => array('partner_title', false),
      	'time_in_portal' => array('time_in_portal', false),
      	'unique_views' => array('unique_views', false),
      	'locked' => array('locked', false),
    	);
  }

	function column_default( $item, $column_name ) {
	  switch( $column_name ) { 
	  	case 'date_last_accessed':
	  	case 'expiration_date':
	  		return $item[ $column_name ] ? Date('m/d/Y', $item[ $column_name ]) : '';
			case 'empty':
				return '';
			case 'reporting_role':
				return IP_Helpers::get_user_role_title($item[ $column_name ]);
			case 'downloads':
	  	case 'company':
	  	case 'full_name':
	  	case 'user_email':
	  	case 'user_registered':
	  	case 'login_count':
	  	case 'views':
	  	case 'contact_a_coach_count':
	  	case 'cac_count':
	  	case 'partner_title':
	  	case 'child_partner_title':
			case 'time_in_portal':
			case 'unique_views':
			case 'lang_views':
			case 'access_code':
			case 'locked':
			case 'tracking_account_id':
			case 'tracking_contact_id':
			case 'hours':
			case 'first_use':
			case 'views_downloads':
			case 'unique_views_downloads':
			case 'days_accessed':
	  		return $item[ $column_name ];
			case 'download_titles':
			case 'posts':
	  		return (strlen($item[ $column_name ]) > 50 ? substr($item[ $column_name ], 0, 50).'...': $item[ $column_name ]);
	  	case 'capabilities':
				return implode(', ', array_keys(IRB_Util::removeWPML(unserialize($item[ $column_name ])), true));
			case 'address_line1':
			case 'address_line2':
			case 'address_city':
			case 'address_state':
			case 'address_zip':
				$pos = strrpos($column_name, '_');
				$idx = $pos === false ? $column_name : substr($column_name, $pos + 1);
				$data = unserialize($item['address']);
				$output = isset($data[$idx]) ? (is_array($data[$idx]) ? implode(', ', $data[$idx])  : $data[$idx]) : '';
				return $output;
			case 'custom_field_0':
			case 'custom_field_1':
			case 'custom_field_2':
			case 'custom_field_3':
			case 'custom_field_4':
				$idx = substr($column_name, -1);
				$data = unserialize($item['custom_fields']);
				// print_r($data);
				$output = isset($data[$idx]) ? (is_array($data[$idx]) ? implode(', ', $data[$idx])  : $data[$idx]) : '';
				return $output;
	  	// case 'time_in_portal':
	  	// 	return IRB_Util::formatTimeInPortal( ($item[ $column_name ] / 60) / 60 );
	  	// 	// return var_dump(unserialize($item[ $column_name ]));
	    default:
	      return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	  }
	}
}