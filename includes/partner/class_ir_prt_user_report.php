<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );


if ( ! class_exists( 'IRB_User_Report' ) ) {
	require_once( plugin_dir_path(__FILE__).'../class_ir_user_report.php' );
}

class IRB_Partner_User_Report extends IRB_User_Report {

	function __construct(){
		$this->user_role_permission = 'le_partner_reports';
		$this->page_slug = 'partner-'.$this->page_slug;
		$this->hours_page_slug = 'partner-'.$this->hours_page_slug;
		$this->menu_parent = 'partner-'.$this->menu_parent;
		$this->is_partner = true;
		$this->filters['roles'] = array('client');
		parent::__construct();
 	}

	// Override special case
	function get_user_ids_by_role(){
		return IRB_Util::get_user_ids_by_role($this->filters['roles'], $this->is_partner);
	}
}