<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
// require_once( plugin_dir_path(__FILE__).'util_functions.php' );

class IRB_External_Resources {

	private $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'resource-external';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;

	function __construct(){
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action('admin_init',array($this,'admin_init'));
		add_action( 'admin_menu', array($this, 'reporting_menu') );
 	}

 	function reporting_menu() {
		add_submenu_page( $this->menu_parent, 'External Resources', 'External Resources', $this->user_role_permission, $this->page_slug, array($this, 'resource_external') );
	}

	function admin_init(){
		if(isset($_GET['page']) && $_GET['page']==$this->page_slug && isset($_GET['download_csv'])){
			if ( !current_user_can( $this->user_role_permission ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			IRB_Util::output_csv_file('custom-external_resources', $this->create_externals_csv_data());   
		}
	}

	function resource_external() {
		if ( !current_user_can( $this->user_role_permission ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		require_once plugin_dir_path(__FILE__).'class_ir_external_resources_table.php';

	  $resourceTable = new IRB_External_Resources_Table($this->get_externals());


	  echo '<div class="wrap">';
			echo '<h3>External Resources</h3>';
			echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true').'" target="_blank" class="button" style="float:right;">Export CSV</a>';
			$resourceTable->prepare_items(); 
			echo '<form id="events-filter" method="get">';
			echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
			$resourceTable->display();
			echo '</form>';
			echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true').'" target="_blank" class="button">Export CSV</a>';
		echo '</div>';

	}

	private function get_post_ids_with_external_links(){
		global $wpdb, $user_ID;

		$partner_filter = '';
		if($this->is_partner){
			$partner_id = get_user_meta($user_ID, 'partner', true);
			$partner_filter = "AND pm2.meta_value={$partner_id}";
		}

		$results = $wpdb->get_results( 
			"SELECT pm.post_id
				FROM `{$wpdb->prefix}postmeta` as pm
				RIGHT JOIN {$wpdb->prefix}posts as p on pm.post_id=p.ID and p.post_status!='trash' AND p.post_type='post'
				LEFT JOIN {$wpdb->prefix}postmeta as pm2 on pm.post_id=pm2.post_id and pm2.meta_key='owning_partner_partner-id'
				WHERE pm.meta_key LIKE 'related_resources_external_%_external_url' AND pm.meta_value!='' {$partner_filter}
				GROUP BY pm.post_id 
				ORDER BY pm.post_id ASC"
			, 'ARRAY_A' );

		return $results;
	}

	private function get_externals(){
		$post_ids = $this->get_post_ids_with_external_links();

		$externals = array();

		foreach ($post_ids as $id) {
			$id = $id['post_id'];

			$title = get_the_title($id);

			// check if the repeater field has rows of data
			if( have_rows('related_resources_external', $id) ):
			 	// loop through the rows of data
			    while ( have_rows('related_resources_external', $id) ) : the_row();
			      $externals[] = array(
			      	'post_id' => $id,
			      	'post_title' => $title,
			      	'external_category' => get_sub_field('external_category'),
			      	'external_title' => get_sub_field('external_title'),
			      	'external_url' => get_sub_field('external_url'),
			      	'external_summary' => get_sub_field('external_summary')
			      );

			    endwhile;
			endif;

		}

		return $externals;
	}

	function create_externals_csv_data(){

		$separator = ',';
    $csv_output = '';                                           //Assigning the variable to store all future CSV file's data

    $header = array(
    	'Resource Id',
			'Resource Title',
			'External Category',
			'External Name',
			'External URL',
			'External Summary',
    );

    foreach($header as $col) {
        $csv_output = $csv_output . $col . $separator;
    }
    $csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

    $csv_output .= "\r\n";

    $values = $this->get_externals();       //This here

    foreach ($values as $row) {
        $fields = array(
        	$row['post_id'],
        	$row['post_title'],
        	$row['external_category'],
        	$row['external_title'],
        	$row['external_url'],
        	$row['external_summary']
        );                  //Getting rid of the keys and using numeric array to get values
        $csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
        $csv_output .= "\r\n";    //Yeah...
    }

    return $csv_output; //Back to constructor

  }

}