<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );
require_once( plugin_dir_path(__FILE__).'class_ir_user_hours_table.php' );

class IRB_User_Hours {

	public $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'user-hours';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $filters = array(
		'start_date' =>null,
		'end_date' => null,
		'roles' => array('')
	);
	public $start_date = null;
	public $end_date = null;
	public $language = '';
	public $roleOptions = array();

	function __construct(){
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action('admin_init',array($this,'admin_init'));
		add_action( 'admin_menu', array($this, 'reporting_menu') );

		$this->roleOptions = IP_Helpers::get_user_roles();
 	}

 	function reporting_menu() {
		add_submenu_page( $this->menu_parent, 'User Hours', 'User Hours', $this->user_role_permission, $this->page_slug, array($this, 'user_hours') );
	}

	function admin_init(){
		if(isset($_GET['page']) && $_GET['page']==$this->page_slug && isset($_GET['download_csv'])){
			if ( !current_user_can( $this->user_role_permission ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			IRB_Util::output_csv_file('custom-hours-export', $this->generate_hours_csv());  
		}
		if(isset($_GET['page']) && $_GET['page']==$this->page_slug && isset($_GET['download_full_csv'])){
			if ( !current_user_can( $this->user_role_permission ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			IRB_Util::output_csv_file('custom-full-hours-export', $this->generate_full_hours_csv());  
		}
	}

	function user_hours(){
		if ( !current_user_can( $this->user_role_permission ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if(isset($_GET['download_csv'])){
			return false;
		}
		else {
			$this->user_hours_output();
		}
	}

	function set_filters(){
		if(isset($_GET['start_date']) && $_GET['start_date']){
			// Make sure supplied date is in necessary format
			$this->start_date = date('Y-m-d', strtotime($_GET['start_date']));
			$this->filters['start_date'] = date('Y-m-d', strtotime($_GET['start_date']));
		}
		else {
			// $start_date = date('Y-m-d', strtotime('-60 days'));
		}

		if(isset($_GET['end_date']) && $_GET['end_date']){
			$this->end_date = date('Y-m-d', strtotime($_GET['end_date']));
			$this->filters['end_date'] = date('Y-m-d', strtotime($_GET['end_date']));
		}

		if(isset($_GET['roles']) && $_GET['roles']){
			$this->filters['roles'] = $_GET['roles'];
		}
	}

	function user_hours_output() {
		$this->set_filters();

	  
	  echo '<div class="wrap">';
	  echo '<h2>User Hours Report</h2>'; 

		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button" style="float: right;">Export CSV</a>';

		if(!$this->is_partner){
			echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_full_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button" style="float: right; clear: both;margin-top:10px;">Export Full Hours CSV</a>';
		}

		?>
			<form action="<?php echo admin_url('admin.php'); ?>">
				<input type="hidden" name="page" value="<?php echo $this->page_slug; ?>" />
				<h3>Filter Results</h3>
				<div class="row">
					<div class="field">
						<h4>Role</h4>
						<!-- <select name="roles[]" id="roles" multiple> -->
							<?php 
								foreach ($this->roleOptions as $role => $title) {
									echo '<div>';
										echo '<input type="checkbox" name="roles[]" value="'.$role.'" 
											id="role-'.$role.'" '.(in_array($role, $this->filters['roles']) ? 'checked' : '').'/>';
										echo '<label for="role-'.$role.'">'.$title.'</label>';
									echo '</div>';
								}
							?>
						<!-- </select> -->
					</div>
					<div class="field">
								<h4>Date Last Accessed</h4>
								<div class="row">
									<div class="field">
										<label for="start_date">Start Date</label>
										<input type="date" name="start_date" id="start_date" value="<?php echo $this->start_date;?>">
									</div>
								</div>

								<div class="row">
									<div class="field">
										<label for="end_date">End Date</label>
										<input type="date" name="end_date" id="end_date" value="<?php echo $this->end_date;?>">
									</div>
								</div>
							</div>

					</div>
				</div>
				<div class="row">
					<div class="field submit-field">
						<input type="submit" class="button" value="Filter">
					</div>
				</div>
			</form>

			<style>
				.field {
					display: inline-block;
					margin-right: 10px;
					vertical-align: top;
					margin-right: 15px;
				}
				.row {
					margin: 5px 0;
				}
			</style>
		<?php
		$userTable = new IRB_User_Hours_Table($this->get_hours('array'), $this->is_partner);
		$userTable->prepare_items(); 
		echo '<form id="events-filter" method="get">';
		echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		$userTable->display(); 
		echo '</form>';
		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button">Export CSV</a>';
	  echo '</div>'; 
	}

	// Special case, need to get all roles
	function get_user_ids_by_role(){
		$roles = $this->filters['roles'];
		if(is_array($roles) && count($roles) == 1){
			$roles = $roles[0];
		}
		else if(is_array($roles) && count($roles) ==0){
			$roles = '';
		}
		return IRB_Util::get_user_ids_by_role($roles, $this->is_partner);
	}

	function generate_hours_csv(){

		$separator = ',';
    $csv_output = '';                                           //Assigning the variable to store all future CSV file's data

    $header = array(
			'Customer ID',
			'Account ID',
			'Contact ID',
			'Customer Name',
			'Company',
			'Email',
			'Date',
			'Hours',
			'Role',
			// 'Partner'
			// 'Minutes'
    );

    foreach($header as $col) {
        $csv_output = $csv_output . $col . $separator;
    }
    $csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";
		
		$this->set_filters();

    $values = $this->get_hours();       //This here

    foreach ($values as $row) {
        $fields = array(
					$row->user_id,
					$row->tracking_account_id,
        	$row->tracking_contact_id,
					$row->full_name,
					$row->company,
					$row->user_email,
					$row->date,
					$row->hours,
					$row->capabilities,
					// $row->partner_title
        );                  //Getting rid of the keys and using numeric array to get values
        $csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
        $csv_output .= "\r\n";    //Yeah...
    }

    return $csv_output; //Back to constructor

	}

  function get_hours($output = 'object'){
		global $wpdb;

			$userIds = $this->get_user_ids_by_role();
			if(!$userIds){
				return array();
			}

			$filterBy = '';
			if($this->filters['start_date'] && $this->filters['end_date']){
				$filterBy .= " AND (h.date >= '".$this->filters['start_date']."' AND h.date <= '".$this->filters['end_date']."')";
			}
			else if($this->filters['start_date']){
				$filterBy .= " AND (h.date >= '".$this->filters['start_date']."')";
			}
			else if($this->filters['end_date']){
				$filterBy .= " AND (h.date <= '".$this->filters['end_date']."')";
			}

			$results = $wpdb->get_results(
				"SELECT 
					h.id, 
					h.user_id,
					CONCAT( um.meta_value, ' ', um2.meta_value) as full_name,
					ifnull(um3.meta_value, '') as company,
					u.user_email,
					um6.meta_value as capabilities,
					h.date, 
					h.hours,
					ifnull(umta.meta_value, '') as tracking_account_id,
					ifnull(umtc.meta_value, '') as tracking_contact_id,
					ifnull(part.post_title, '') as partner_title

						FROM {$wpdb->prefix}le_user_hours_tracking as h
						LEFT JOIN {$wpdb->prefix}users as u on u.ID=h.user_id

						LEFT JOIN {$wpdb->prefix}usermeta as um ON um.user_id=u.ID AND um.meta_key='first_name'
						LEFT JOIN {$wpdb->prefix}usermeta as um2 ON um2.user_id=u.ID AND um2.meta_key='last_name'
						LEFT JOIN {$wpdb->prefix}usermeta as um3 ON um3.user_id=u.ID AND um3.meta_key='company'
						LEFT JOIN {$wpdb->prefix}usermeta as um6 ON um6.user_id=u.ID AND um6.meta_key='le_role'

						LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=u.ID AND ump.meta_key='partner'
						LEFT JOIN {$wpdb->prefix}posts as part ON part.ID=ump.meta_value

						LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=u.ID AND umta.meta_key='tracking_account_id'
						LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=u.ID AND umtc.meta_key='tracking_contact_id'

						WHERE h.user_id IN (".implode(', ', $userIds).") AND (h.legacy_data=1 OR h.used_resource=1)  {$filterBy}
						ORDER BY h.date DESC
				"
				, ($output==='array' ? 'ARRAY_A' : OBJECT) );

		return $results;
	}

	function generate_full_hours_csv(){

		$separator = ',';
    $csv_output = '';                                           //Assigning the variable to store all future CSV file's data

    $header = array(
			'Customer ID',
			'Account ID',
			'Contact ID',
			'Customer Name',
			'Company',
			'Email',
			'Date',
			'Hours',
			'Seconds',
			'Last Location',
			'Location Timestamp',
			'Legacy Data',
			'Used Resource',
			'Role',
			'Partner ID'
			// 'Minutes'
    );

    foreach($header as $col) {
        $csv_output = $csv_output . $col . $separator;
    }
    $csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";
		
		$this->set_filters();

    $values = $this->get_raw_hours();       //This here

    foreach ($values as $row) {
        $fields = array(
					$row->user_id,
					$row->tracking_account_id,
        	$row->tracking_contact_id,
					$row->full_name,
					$row->company,
					$row->user_email,
					$row->date,
					$row->hours,
					$row->seconds,
					$row->location,
					$row->location_timestamp,
					$row->legacy_data,
					$row->used_resource,
					$row->capabilities,
					$row->partner_id,
					// $row->partner_title
        );                  //Getting rid of the keys and using numeric array to get values
        $csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
        $csv_output .= "\r\n";    //Yeah...
    }

    return $csv_output; //Back to constructor

	}

	function get_raw_hours($output = 'object'){
		global $wpdb;

			$userIds = $this->get_user_ids_by_role();
			if(!$userIds){
				return array();
			}

			$filterBy = '';
			// $filter_title = '';
			if($this->filters['start_date'] && $this->filters['end_date']){
				$filterBy .= " AND (h.date >= '".$this->filters['start_date']."' AND h.date <= '".$this->filters['end_date']."')";
				// $filter_title .= '_'.strtotime($this->start_date).strtotime($this->end_date);
			}
			else if($this->filters['start_date']){
				$filterBy .= " AND (h.date >= '".$this->filters['start_date']."')";
				// $filter_title .= '_start'.strtotime($this->start_date);
			}
			else if($this->filters['end_date']){
				$filterBy .= " AND (h.date <= '".$this->filters['end_date']."')";
				// $filter_title .= '_end'.strtotime($this->end_date);
			}

			$results = $wpdb->get_results(
				"SELECT 
					h.id, 
					h.user_id,
					CONCAT( um.meta_value, ' ', um2.meta_value) as full_name,
					ifnull(um3.meta_value, '') as company,
					u.user_email,
					um6.meta_value as capabilities,
					h.date, 
					h.hours,
					h.seconds,
					h.location,
					h.location_timestamp,
					h.legacy_data,
					h.used_resource,
					ifnull(umta.meta_value, '') as tracking_account_id,
					ifnull(umtc.meta_value, '') as tracking_contact_id,
					ifnull(part.post_title, '') as partner_title,
					ump.meta_value as partner_id

						FROM {$wpdb->prefix}le_user_hours_tracking as h
						LEFT JOIN {$wpdb->prefix}users as u on u.ID=h.user_id

						LEFT JOIN {$wpdb->prefix}usermeta as um ON um.user_id=u.ID AND um.meta_key='first_name'
						LEFT JOIN {$wpdb->prefix}usermeta as um2 ON um2.user_id=u.ID AND um2.meta_key='last_name'
						LEFT JOIN {$wpdb->prefix}usermeta as um3 ON um3.user_id=u.ID AND um3.meta_key='company'
						LEFT JOIN {$wpdb->prefix}usermeta as um6 ON um6.user_id=u.ID AND um6.meta_key='wp_capabilities'

						LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=u.ID AND ump.meta_key='partner'
						LEFT JOIN {$wpdb->prefix}posts as part ON part.ID=ump.meta_value

						LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=u.ID AND umta.meta_key='tracking_account_id'
						LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=u.ID AND umtc.meta_key='tracking_contact_id'

						WHERE h.user_id IN (".implode(', ', $userIds).") {$filterBy}
						ORDER BY h.date DESC
				"
				, ($output==='array' ? 'ARRAY_A' : OBJECT) );

		return $results;
	}

}