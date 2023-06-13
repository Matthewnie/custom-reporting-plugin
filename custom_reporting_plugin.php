<?php
/*
Plugin Name: Custom Reporting - Beta
Description: Reporting Module
Author: Matthew Niedzielski
Version: 2.1.1
*/

if (!defined('CUSTOM_REPORTING_BETA_VERSION'))
    define('CUSTOM_REPORTING_BETA_VERSION', '2.1.1');

global $let_db_version;
$let_db_version = '1.6.6';

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

class Custom_Reporting_Beta {

	function __construct(){
		register_activation_hook(__FILE__, array($this,'plugin_activate')); //activate hook
    register_deactivation_hook(__FILE__, array($this,'plugin_deactivate')); //deactivate hook
    
    add_action('plugins_loaded', array($this,'check_version'));

    add_action('ipn_reporting_repair_cron_jobs', array($this,'schedule_cron_jobs'));
    
    $this->load_dependencies();  
    $this->init();
    $this->add_acf_filters();
   }
   
  public function check_version() {
    if (CUSTOM_REPORTING_BETA_VERSION !== get_option('custom_reporting_beta_version'))
      $this->plugin_activate();
  }

 	private function load_dependencies()
  {
    require_once __DIR__.'/includes/class_ir_util.php';

    require_once __DIR__.'/includes/class_ir_dashboard.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_dashboard.php';

    require_once __DIR__.'/includes/class_ir_resource_usage.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_resource_usage.php';

    require_once __DIR__.'/includes/class_ir_resource_usage_by_user.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_resource_usage_by_user.php';
    
    require_once __DIR__.'/includes/class_ir_resource_usage_by_user_by_day.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_resource_usage_by_user_by_day.php';

    require_once __DIR__.'/includes/class_ir_video_usage_by_user_by_day.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_video_usage_by_user_by_day.php';

    // require_once __DIR__.'/includes/class_ir_user_report.php';
    // require_once __DIR__.'/includes/partner/class_ir_prt_user_report.php';
    
    require_once __DIR__.'/includes/class_ir_new_user_report.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_new_user_report.php';

    require_once __DIR__.'/includes/class_ir_user_hours.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_user_hours.php';

    require_once __DIR__.'/includes/class_ir_user_profile.php';

    require_once __DIR__.'/includes/class_ir_external_resources.php';
    require_once __DIR__.'/includes/partner/class_ir_prt_external_resources.php';

    require_once __DIR__ . '/includes/class_ir_resource_translations.php';

    require_once __DIR__.'/includes/class_ir_internal_resources.php';
    require_once __DIR__.'/includes/class_ir_salesforce_report.php';
  }

  private function init(){
    new IRB_Util();

    new IRB_Dashboard();
    new IRB_Partner_Dashboard();

    // new IRB_User_Report();
    // new IRB_Partner_User_Report();
    
    new IRB_New_User_Report();
    new IRB_Partner_New_User_Report();

    new IRB_Resource_Usage_By_User_By_Day();
    new IRB_Partner_Resource_Usage_By_User_By_Day();

    new IRB_Resource_Usage();
    new IRB_Partner_Resource_Usage();

    new IRB_Resource_Usage_By_User();
    new IRB_Partner_Resource_Usage_By_User();

    new IRB_Video_Usage_By_User_By_Day();
    new IRB_Partner_Video_Usage_By_User_By_Day();
    
    new IRB_User_Hours();
    // new IRB_Partner_User_Hours();
    
    new IRB_Resource_Translations();
    
    new IRB_External_Resources();
    new IRB_Partner_External_Resources();
    
    new IRB_Internal_Resources();
    
    new IRB_Salesforce_Report();

    // Disabled to allow BOTH versions of reporting to run. 
    if(!is_plugin_active('custom_reporting/custom_reporting.php')){
      new IRB_User_Profile();
    }

    add_filter( 'manage_partner_posts_columns', array( $this, 'add_partner_user_count_column' ) );
		add_filter( 'manage_partner_posts_custom_column', array( $this, 'fill_partner_user_count_column' ), 10, 3 );
  }

  private function add_acf_filters(){
    add_filter('acf/update_value/name=salesforce_notification_interval', array( $this, 'update_notification_interval'), 10, 4);
  }

  function update_notification_interval($value, $post_id, $field, $original){
    switch($value){
      case 'daily':
        $nextDate = date('Y-m-d', strtotime('tomorrow'));
        update_post_meta($post_id, 'salesforce_notification_next_date', $nextDate);
        break;
      case 'weekly':
        $nextDate = date('Y-m-d', strtotime('tomorrow'));
        update_post_meta($post_id, 'salesforce_notification_next_date', $nextDate);
        break;
      case 'firstofmonth':
        $nextDate = date('Y-m-d', strtotime('first day of next month'));
        update_post_meta($post_id, 'salesforce_notification_next_date', $nextDate);
        break;
    }
    return $value;
  }

  function add_partner_user_count_column( $columns ) {
    if(ICL_LANGUAGE_CODE =='es'){
      return $columns;
    }
    $columns['user_limit'] = __( 'User Limit' );
    // $columns['user_count'] = __( 'User Count' );
    // $columns['avail_count'] = __( 'Available Count' );

    return $columns;
  }
  function fill_partner_user_count_column( $column_name, $post_id ) {
    $english_ID = apply_filters( 'wpml_object_id', $post_id, 'partner', FALSE, 'en' );
    if(!$english_ID || $post_id!==$english_ID){
      return '';
    }

    if($column_name==='user_limit'){
      if (wp_get_post_parent_id($post_id) !== 0) {
        return;
      }

      $limit = get_metadata('post', $post_id, 'user_limit', true);
      echo $limit ? $limit : 0;
    }
    else if($column_name==='user_count'){
      if (wp_get_post_parent_id($post_id) !== 0) {
        echo $this->get_user_count_for_child_partner($post_id);
      }
      else {
        echo $this->get_user_count_for_partner($post_id) . ' ('.$this->get_locked_user_count_for_partner($post_id).' Locked)';
      }
    }
    else if($column_name==='avail_count'){
      return '';
      if (wp_get_post_parent_id($post_id) !== 0) {
        return;
      }

      $limit = get_metadata('post', $post_id, 'user_limit', true);
      $limit = $limit ? $limit : 0;

      if($limit === 0){
        echo 'No Limit';
      }
      else {
        echo (int)$limit - (int)$this->get_not_locked_user_count_for_partner($post_id);
      }
    }
  }

  static public function get_user_count_for_partner($partner_id){
    $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
    // get users by meta value of partner, and type of 
    $user_query = new WP_User_Query( array(
      'role' => 'client',
      'meta_key' => 'partner',
      'meta_value' => $partner_ids,
      'meta_compare' => 'IN'
    ) );
      return $user_query->get_total();
  }

  static public function get_locked_user_count_for_partner($partner_id){
    $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
    // get users by meta value of partner, and type of 
    $user_query = new WP_User_Query( array(
      'role' => 'client',
      'meta_query' => array(
        'relation' => 'AND',
        'partner ' => array(
          'key' => 'partner',
          'value' => $partner_ids,
          'compare' => 'IN'
        ),
        'locked' => array(
          'key' => 'baba_user_locked',
          'value' => 'yes'
        )
      )
    ) );
      return $user_query->get_total();
  }

  static public function get_not_locked_user_count_for_partner($partner_id)
  {
    $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);

    // get users by meta value of partner, and type of 
    $user_query = new WP_User_Query(array(
      'role' => 'client',
      'meta_query' => array(
        'relation' => 'AND',
        'partner ' => array(
          'key' => 'partner',
          'value' => $partner_ids,
          'compare' => 'IN'
        ),
        'locked' => array(
          'relation' => 'OR',
          array(
            'key' => 'baba_user_locked',
            'compare' => 'NOT EXISTS'
          ),
          array(
            'key' => 'baba_user_locked',
            'value' => ''
          )
        )
      )
    ) );
    return $user_query->get_total();
  }

  function get_user_count_for_child_partner($partner_id)
  {
    // get users by meta value of partner, and type of 
    $user_query = new WP_User_Query(array(
      'role' => 'client',
      'meta_key' => 'child_partner',
      'meta_value' => $partner_id
    ));
    return $user_query->get_total();
  }

  function plugin_activate(){
    $this->create_resource_tracking_table();
    $this->create_hours_tracking_table();
    $this->create_video_tracking_table();
    $this->create_user_resource_files_table();
    $this->schedule_cron_jobs();

    // Update version
    update_option('custom_reporting_beta_version', CUSTOM_REPORTING_BETA_VERSION);
  }

  function schedule_cron_jobs(){
    if (! wp_next_scheduled ( 'daily_salesforce_report_email' )) {
      wp_schedule_event( time(), 'le_ten_minutes', 'daily_salesforce_report_email' );
    }
    if (! wp_next_scheduled ( 'ipn_reporting_repair_cron_jobs' )) {
      wp_schedule_event( time(), 'le_ten_minutes', 'ipn_reporting_repair_cron_jobs' );
    }
  }

  function plugin_deactivate(){
    wp_clear_scheduled_hook('daily_salesforce_report_email');
    wp_clear_scheduled_hook('ipn_reporting_repair_cron_jobs');
  }

  private function create_resource_tracking_table(){
  	global $wpdb, $let_db_version;
  	$table_name = $wpdb->prefix . "le_user_resource_tracking";

  	$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id varchar(20) NOT NULL,
      lang_code varchar(7) NOT NULL,
		  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  PRIMARY KEY  (id),
      KEY user_id (user_id),
      KEY post_id (post_id),
      KEY lang_code (lang_code)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		add_option( 'let_db_version', $let_db_version );
  }

  private function create_hours_tracking_table(){
    global $wpdb, $let_db_version;
    $table_name = $wpdb->prefix . "le_user_hours_tracking";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
      id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
      user_id bigint(20) unsigned NOT NULL,
      date date DEFAULT '0000-00-00' NOT NULL,
      seconds bigint(20) unsigned DEFAULT '0' NOT NULL,
      hours float unsigned NOT NULL DEFAULT '0',
      location varchar(256) DEFAULT NULL,
      location_timestamp int(12) DEFAULT '0',
      legacy_data tinyint(4) DEFAULT '0',
      used_resource tinyint(4) DEFAULT '0',
      PRIMARY KEY  (id),
      UNIQUE KEY idx_date_user_id (date,user_id),
      KEY user_id (user_id),
      KEY date (date)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );

    add_option( 'let_db_version', $let_db_version );
  }

  private function create_video_tracking_table()
  {
    global $wpdb, $let_db_version;
    $table_name = $wpdb->prefix . "le_user_video_tracking";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id varchar(20) NOT NULL,
      lang_code varchar(7) NOT NULL,
		  date date NOT NULL,
      end_percentage float DEFAULT '0',
      time_watched float DEFAULT '0',
		  PRIMARY KEY  (user_id,post_id,date),
      KEY id (id),
      KEY user_id (user_id),
      KEY post_id (post_id),
      KEY date (date),
      KEY lang_code (lang_code)
		) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('let_db_version', $let_db_version);
  }

  private function create_user_resource_files_table()
  {
    global $wpdb, $let_db_version;
    $table_name = $wpdb->prefix . "le_user_resource_files";

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			post_id varchar(20) NOT NULL,
      filename varchar(255) NOT NULL,
		  last_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  deleted tinyint(1) NOT NULL DEFAULT 0,
		  PRIMARY KEY  (user_id,post_id,filename),
      KEY id (id),
      KEY user_id (user_id),
      KEY post_id (post_id),
      KEY last_modified (last_modified),
      KEY filename (filename),
      KEY deleted (deleted)
		) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option('let_db_version', $let_db_version);
  }
	
}
new Custom_Reporting_Beta();