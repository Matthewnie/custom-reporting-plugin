<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
// require_once( plugin_dir_path(__FILE__).'util_functions.php' );

class IRB_Internal_Resources {

	public $page_slug = 'resource-internal';
	public $menu_parent = 'reporting-2';

	function __construct(){
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);

		add_action('admin_init',array($this,'admin_init'));
		add_action( 'admin_menu', array($this, 'reporting_menu') );
 	}

 	function reporting_menu() {
		add_submenu_page( $this->menu_parent, 'Internal Resources', 'Internal Resources', 'le_reports', $this->page_slug, array($this, 'resource_internal') );
	}

	function admin_init(){
		if(isset($_GET['page']) && $_GET['page']==$this->page_slug && isset($_GET['download_csv'])){
			if ( !current_user_can( 'le_reports' ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			IRB_Util::output_csv_file('custom-internal_resources', $this->create_internals_csv_data());   
		}
	}

	function resource_internal() {
		if ( !current_user_can( 'le_reports' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		require_once plugin_dir_path(__FILE__).'class_ir_internal_resources_table.php';

	  $resourceTable = new IRB_Internal_Resources_Table($this->get_internals());


	  echo '<div class="wrap">';
			echo '<h3>Resource Usage</h3>';
			echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true').'" target="_blank" class="button">Export CSV</a>';
			$resourceTable->prepare_items(); 
			echo '<form id="events-filter" method="get">';
			echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
			$resourceTable->display();
			echo '</form>';
			echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true').'" target="_blank" class="button">Export CSV</a>';
		echo '</div>';

	}

	private function get_post_ids_with_internal_links(){
		global $wpdb;
		$results = $wpdb->get_results( 
			"SELECT post_id, meta_value
				FROM `{$wpdb->prefix}postmeta` as pm
				RIGHT JOIN {$wpdb->prefix}posts as p on pm.post_id=p.ID and p.post_status='publish' and p.post_type='post'
				WHERE `meta_key` LIKE 'related_resources_internal' AND `meta_value`!=''  
				GROUP BY post_id 
				ORDER BY `post_id` ASC"
			, 'ARRAY_A' );

		return $results;
	}

	private function get_internals(){
		$posts_with_internals = $this->get_post_ids_with_internal_links();

		$internals = array();

		foreach ($posts_with_internals as $post) {
			$id = $post['post_id'];
			$related = unserialize($post['meta_value']);
			if(empty($related)){
				continue;
			}

			$title = get_the_title($id);
			foreach ($related as $key => $related_id) {
				// Make sure the post exists and is not a revision
				$related = get_post($related_id);
				$partner_owner = IP_Helpers::get_partner_id_for_post($id);
				$related_partner_owner = IP_Helpers::get_partner_id_for_post($related_id);
				// print_r($related);
				if($related && $related->post_type!=='revision' && $related->post_status='publish'){
					$internals[] = array(
								'post_id' => $id,
								'post_title' => $title ,
								'post_owner' => ($partner_owner ? get_the_title($partner_owner) : '') ,
								'related_resource' => get_the_title($related_id),
								'related_status' => get_post_status($related_id),
								'related_owner' => ($related_partner_owner ? get_the_title($related_partner_owner) : '')
							);
				}
			}
		}

		return $internals;
	}

	function create_internals_csv_data(){

		$separator = ',';
    $csv_output = '';                                           //Assigning the variable to store all future CSV file's data

    $header = array(
    	'Resource Id',
			'Resource Title',
			'Resource Owner',
			'Related Resource',
			'Related Status',
			'Related Owner'
    );

    foreach($header as $col) {
        $csv_output = $csv_output . $col . $separator;
    }
    $csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

    $csv_output .= "\r\n";

    $values = $this->get_internals();       //This here

    foreach ($values as $row) {
        $fields = array(
        	$row['post_id'],
        	$row['post_title'],
        	$row['post_owner'],
        	$row['related_resource'],
        	$row['related_status'],
        	$row['related_owner']
        );                  //Getting rid of the keys and using numeric array to get values
        $csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
        $csv_output .= "\r\n";    //Yeah...
    }

    return $csv_output; //Back to constructor

  }

}