<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'IRB_Resource_Usage' ) ) {
	require_once( plugin_dir_path(__FILE__).'../class_ir_resource_usage.php' );
}

class IRB_Partner_Resource_Usage extends IRB_Resource_Usage {

	function __construct(){
		$this->user_role_permission = 'le_partner_reports';
		$this->page_slug = 'partner-'.$this->page_slug;
		$this->menu_parent = 'partner-'.$this->menu_parent;
		$this->is_partner = true;
		
		parent::__construct();
 	}

}