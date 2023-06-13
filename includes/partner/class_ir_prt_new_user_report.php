<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
if ( ! class_exists( 'IRB_New_User_Report' ) ) {
	require_once( plugin_dir_path(__FILE__).'../class_ir_new_user_report.php' );
}

class IRB_Partner_New_User_Report extends IRB_New_User_Report {

	function __construct(){
		$this->user_role_permission = 'le_partner_reports';
		$this->page_slug = 'partner-'.$this->page_slug;
		$this->menu_parent = 'partner-'.$this->menu_parent;
		$this->is_partner = true;

		parent::__construct();
 	}

}