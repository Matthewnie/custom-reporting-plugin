<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

if ( ! class_exists( 'IRB_Dashboard' ) ) {
	require_once( plugin_dir_path(__FILE__).'../class_ir_dashboard.php' );
}

class IRB_Partner_Dashboard extends IRB_Dashboard {

	function __construct(){
		parent::__construct();
		$this->user_role_permission = 'le_partner_reports';
		$this->is_partner = true;
		$this->menu_parent = 'partner-'.$this->menu_parent;
		$this->page_slug = $this->menu_parent;
	}

 	function reporting_menu() {
		add_menu_page( 'Reports', 'Reports', $this->user_role_permission, $this->menu_parent, array($this, 'dashboard'), 'dashicons-chart-area', 29 );
		add_submenu_page($this->menu_parent, 'Summary', 'Summary', $this->user_role_permission,$this->menu_parent);

	}

}