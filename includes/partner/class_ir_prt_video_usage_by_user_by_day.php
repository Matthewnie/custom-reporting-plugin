<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );


if ( ! class_exists( 'IRB_Video_Usage_By_User_By_Day' ) ) {
	require_once( plugin_dir_path(__FILE__).'../class_ir_rvideo_usage_by_user_by_day.php' );
}

class IRB_Partner_Video_Usage_By_User_By_Day extends IRB_Video_Usage_By_User_By_Day {

	function __construct(){
		$this->user_role_permission = 'le_partner_reports';
		$this->page_slug = 'partner-'.$this->page_slug;
		$this->menu_parent = 'partner-'.$this->menu_parent;
		$this->is_partner = true;
		parent::__construct();
 	}
}