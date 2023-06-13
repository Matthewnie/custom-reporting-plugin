<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class IRB_User_Report {

	public $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'user-report';
	public $hours_page_slug = 'user-hours';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $filters = array(
		'start_date' =>null,
		'end_date' => null,
		'language' => '',
		'roles' => array(),
		'locked' => array('no')
	);
	public $start_date = null;
	public $end_date = null;
	public $language = '';
	public $roles = array();
	public $roleOptions = array();

	function __construct(){
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		 
		add_action('admin_init',array($this,'admin_init'));
		add_action( 'admin_menu', array($this, 'reporting_menu') );

		$this->roleOptions = IP_Helpers::get_user_roles();
 	}

 	function reporting_menu() {
		add_submenu_page( $this->menu_parent, 'User Report', 'User Report', $this->user_role_permission, $this->page_slug, array($this, 'resource_util_by_user'), 5 );
	}

	function admin_init(){
		if(isset($_GET['page']) && $_GET['page']==$this->page_slug && isset($_GET['download_csv'])){
			if ( !current_user_can( $this->user_role_permission ) )  {
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			IRB_Util::output_csv_file('custom-user-report', $this->generate_user_report_csv_data());  
		}
	}

	function resource_util_by_user(){
		if ( !current_user_can( $this->user_role_permission ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		if(isset($_GET['download_csv'])){
			return false;
		}
		else {
			$this->resource_util_by_user_table();
		}
	}

	function set_filters(){
		if(isset($_GET['start_date']) && $_GET['start_date']){
			// Make sure supplied date is in necessary format
			$this->start_date = date('Y-m-d', strtotime($_GET['start_date']));
			$this->filters['start_date'] = $this->start_date;
		}
		else {
			// $start_date = date('Y-m-d', strtotime('-60 days'));
		}

		if(isset($_GET['end_date']) && $_GET['end_date']){
			$this->end_date = date('Y-m-d', strtotime($_GET['end_date']));
			$this->filters['end_date'] = $this->end_date;
		}

		if(isset($_GET['language']) && $_GET['language']){
			$this->language = $_GET['language'];
			$this->filters['language'] = $this->language;
		}

		if(isset($_GET['roles']) && $_GET['roles']){
			$this->filters['roles'] = $_GET['roles'];
		}

		if(isset($_GET['usrlck']) && $_GET['usrlck']){
			$this->filters['locked'] = $_GET['usrlck'];
		}
	}

	function resource_util_by_user_table() {
		require_once plugin_dir_path(__FILE__).'class_ir_user_report_table.php';

		$this->set_filters();

	  
	  echo '<div class="wrap">';
	  echo '<h2>User Report</h2>'; 
		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button" style="float: right;">Export CSV</a>';
		?>
			<form action="<?php echo admin_url('admin.php'); ?>">
				<input type="hidden" name="page" value="<?php echo $this->page_slug; ?>" />
				<h3>Filter Results</h3>
				<div class="row">
				<?php 
					$languages = array(
						'' => 'All',
						'en' => 'English',
						'es' => 'Spanish'
					);
				?>
					<div class="field">
						<h4><label for="language">Language</label></h4>
						<select name="language" id="language">
							<?php 
								foreach ($languages as $code => $value) {
									echo '<option value="'.$code.'" '.($this->language==$code ? 'selected' : '').'>'.$value.'</option>';
								}
							?>
						</select>
					</div>

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
						<h4>Dates</h4>
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
								
					<div class="field">
						<h4>Locked</h4>
							<?php 
									echo '<div>';
										echo '<input type="checkbox" name="usrlck[]" value="no" 
											id="locked-no" '.(in_array('no', $this->filters['locked']) ? 'checked' : '').'/>';
										echo '<label for="locked-no">No</label>';
									echo '</div>';
									echo '<div>';
										echo '<input type="checkbox" name="usrlck[]" value="yes" 
											id="locked-yes" '.(in_array('yes', $this->filters['locked']) ? 'checked' : '').'/>';
										echo '<label for="locked-yes">Yes</label>';
									echo '</div>';
							?>
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
				.field label {
					/* font-weight: bold; */
				}
				.row {
					margin: 5px 0;
				}
			</style>
		<?php
		$userTable = new IRB_User_Report_Table($this->get_resources_utilization_by_user('array'), $this->is_partner);
		$userTable->prepare_items();
		echo '<form id="events-filter" method="get">';
		echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		$userTable->display(); 
		echo '</form>';
		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button">Export CSV</a>';
		// echo ' <a href="'.admin_url('admin.php?page=user-report&download_hours_csv=true').'" target="_blank" class="button">Export Raw Hours CSV</a>';
	  echo '</div>'; 
	}

	// Special case, need to get all roles
	function get_user_ids_by_role()
	{
		$roles = $this->filters['roles'];
		if (is_array($roles) && count($roles) == 0) {
			$roles = '';
		}
		return IRB_Util::get_user_ids_by_role($roles, $this->is_partner);
	}

	function get_resources_utilization_by_user($output = 'array'){
		global $wpdb;

		$userIds = $this->get_user_ids_by_role();

		if(!$userIds){
			return array();
		}

		// print_r($userIds);

		$orderby = 'last_login';
		$order = 'DESC';
		if(!empty($_GET['orderby']))
    {
        $orderby = $_GET['orderby'];
    }
    // If order is set use this as the order
    if(!empty($_GET['order']))
    {
        $order = $_GET['order'];
		}

		switch ($orderby) {
			case 'full_name':
				$orderbySQL = 'um2.meta_value '.$order.', um.meta_value '.$order;
				break;
			case 'company':
				$orderbySQL = 'um3.meta_value '.$order;
				break;
			case 'last_login':
				$orderbySQL = 'um4.meta_value '.$order;
				break;
			case 'locked':
				$orderbySQL = 'uml.meta_value '.$order;
				break;
			case 'user_registered':
				$orderbySQL = 'u.user_registered '.$order;
				break;
			// case 'login_count':
			// 	$orderbySQL = 'login_count * 1 '.$order;
			// 	break;
			// case 'resources_count':
			// 	$orderbySQL = 'count(urt.post_id) * 1 '.$order;
			// 	break;
			case 'contact_a_coach_count':
				$orderbySQL = 'contact_a_coach_count * 1 '.$order;
				break;
			case 'capabilities':
				$orderbySQL = 'capabilities '.$order;
				break;
			case 'partner_title':
				$orderbySQL = 'partner_title '.$order;
				break;
			// case 'time_in_portal':
			// 	$orderbySQL = 'time_in_portal * 1 '.$order;
			// 	break;
			
			default:
				// $orderbySQL = 'u.ID '.$order;
				$order = 'ASC';
				$orderbySQL = 'um2.meta_value '.$order.', um.meta_value '.$order;
				$orderby = 'full_name';
				break;
		}

		$filterBy = '';
		$uniFilterBy = '';
		$resourceTrackingFilterBy = '';
		$hoursFilterBy = '';
		$filter_title = '';
		if($this->start_date && $this->end_date){
			$filterBy .= " AND urt.time >= '{$this->start_date} 00:00:00' AND urt.time <= '{$this->end_date} 23:59:59'";
			$hoursFilterBy .= "date >= '{$this->start_date}' AND date <= '{$this->end_date}' AND";
			$resourceTrackingFilterBy .= " AND time >= '{$this->start_date} 00:00:00' AND time <= '{$this->end_date} 23:59:59'";
			$filter_title .= '_'.strtotime($this->start_date).strtotime($this->end_date);
		}
		else if($this->start_date){
			$filterBy .= " AND (urt.time >= '{$this->start_date} 00:00:00')";
			$hoursFilterBy .= "date >= '{$this->start_date}' AND";
			$resourceTrackingFilterBy .= " AND time >= '{$this->start_date} 00:00:00'";
			$filter_title .= '_start'.strtotime($this->start_date);
		}
		else if($this->end_date){
			$filterBy .= " AND (urt.time <= '{$this->end_date} 23:59:59')";
			$hoursFilterBy .= "date <= '{$this->end_date}' AND";
			$resourceTrackingFilterBy .= " AND time <= '{$this->end_date} 23:59:59'";
			$filter_title .= '_end'.strtotime($this->end_date);
		}

		if($this->language){
			$filterBy .= " AND urt.lang_code='{$this->language}'";
			$uniFilterBy .= " AND lang_code='{$this->language}'";
			$filter_title .= '_language_'.$this->language;
		}

		if($this->filters['roles']){
			$filter_title .= '_roles_'.(is_array($this->filters['roles']) ? implode('_', $this->filters['roles']) : $this->filters['roles']);
		}

		if(count($this->filters['locked']) > 0){
			if(count($this->filters['locked']) == 1 && in_array('no', $this->filters['locked'])){
				$filterBy .= " AND (uml.meta_value IS NULL OR uml.meta_value='')";
			}
			else if(count($this->filters['locked']) == 1 && in_array('yes', $this->filters['locked'])){
				$filterBy .= " AND uml.meta_value='yes'";
			}
			$filter_title .= '_locked_'.(is_array($this->filters['locked']) ? implode('_', $this->filters['locked']) : $this->filters['locked']);
		}

		$transient_id = 'resources_utilization_by_user_'.strtolower($orderby).'_'.strtolower($order).strtolower($filter_title);
		if($this->is_partner){
			$transient_id = $transient_id.'_p_'.IRB_Util::get_user_partner_id();
		}
		
		// if ( false === ( $results = get_transient( $transient_id ) ) ) {
			$results = $wpdb->get_results(
			"SELECT u.ID as user_id, 
				ifnull(um3.meta_value, '') as company, 
				u.user_email, 
				um.meta_value as first_name, 
				um2.meta_value as last_name,
				CONCAT( um.meta_value, ' ', um2.meta_value) as full_name,
				ifnull(um4.meta_value, 0) as last_login, 
				-- ifnull(um5.meta_value, 0) as login_count,
				um6.meta_value as reporting_role,
				# um7.meta_value as partner_id,
				-- ifnull(um8.meta_value, 0) as time_in_portal,
				ifnull(p.post_title, '') as partner_title,
				ifnull(cp.post_title, '') as child_partner_title,
				DATE_FORMAT(u.user_registered, '%m/%d/%Y') as user_registered, 
				count(urt.post_id) as resources_count, 
				ifnull(cac.count, 0) as contact_a_coach_count,
				ifnull(umta.meta_value, '') as tracking_account_id,
				ifnull(umtc.meta_value, '') as tracking_contact_id,
				ifnull(umcf.meta_value, '') as custom_fields,
				ifnull(uma.meta_value, '') as address,
				ifnull(umexp.meta_value, '') as expiration_date,
				ifnull(accesscode.post_title, '') as access_code,
				ifnull(uml.meta_value, '') as locked

				FROM {$wpdb->prefix}users as u 
				LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt ON u.ID=urt.user_id AND urt.post_id!='cac'

				LEFT JOIN {$wpdb->prefix}usermeta as um ON um.user_id=u.ID AND um.meta_key='first_name'
				LEFT JOIN {$wpdb->prefix}usermeta as um2 ON um2.user_id=u.ID AND um2.meta_key='last_name'
				LEFT JOIN {$wpdb->prefix}usermeta as um3 ON um3.user_id=u.ID AND um3.meta_key='company'
				LEFT JOIN {$wpdb->prefix}usermeta as um4 ON um4.user_id=u.ID AND um4.meta_key='le_date_last_accessed'
				-- LEFT JOIN {$wpdb->prefix}usermeta as um5 ON um5.user_id=u.ID AND um5.meta_key='le_days_accessed'
				LEFT JOIN {$wpdb->prefix}usermeta as um6 ON um6.user_id=u.ID AND um6.meta_key='le_role'
				LEFT JOIN {$wpdb->prefix}usermeta as um7 ON um7.user_id=u.ID AND um7.meta_key='partner'
				LEFT JOIN {$wpdb->prefix}posts as p ON p.ID=um7.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as umcp ON umcp.user_id=u.ID AND umcp.meta_key='child_partner'
				LEFT JOIN {$wpdb->prefix}posts as cp ON cp.ID=umcp.meta_value
				-- LEFT JOIN {$wpdb->prefix}usermeta as um8 ON um8.user_id=u.ID AND um8.meta_key='time_in_portal'

				LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=u.ID AND umta.meta_key='tracking_account_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=u.ID AND umtc.meta_key='tracking_contact_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umcf ON umcf.user_id=u.ID AND umcf.meta_key='custom_fields'
				LEFT JOIN {$wpdb->prefix}usermeta as uma ON uma.user_id=u.ID AND uma.meta_key='address'
				LEFT JOIN {$wpdb->prefix}usermeta as umexp ON umexp.user_id=u.ID AND umexp.meta_key='le_expiration_date'
				LEFT JOIN {$wpdb->prefix}usermeta as umaccess ON umaccess.user_id=u.ID AND umaccess.meta_key='access_code'
				LEFT JOIN {$wpdb->prefix}posts as accesscode ON accesscode.ID=umaccess.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as uml ON uml.user_id=u.ID AND uml.meta_key='baba_user_locked'

				LEFT JOIN (
				SELECT user_id, count(*) as count
				FROM {$wpdb->prefix}le_user_resource_tracking
				WHERE post_id='cac' AND user_id IN (".implode(', ', $userIds).") {$resourceTrackingFilterBy}
				GROUP BY user_id
				) as cac on cac.user_id=u.ID
				WHERE u.ID IN (".implode(', ', $userIds).") {$filterBy}
				GROUP BY u.ID
				ORDER BY ".$orderbySQL
			, ($output==='array' ? 'ARRAY_A' : OBJECT) );

			// print_r($results);


			// 60 * 60 is the expiration in seconds - in this case, 3600 seconds (1 hour)
			set_transient( $transient_id, $results, 60 * 10 ); 
		// }

		// Get unique views
		$unique_views = $wpdb->get_results(
			"SELECT user_id, count(DISTINCT user_id,post_id) as unique_views
			FRO{$wpdb->prefix}le_user_resource_tracking 
			WHERE post_id!='cac' AND user_id IN (".implode(', ', $userIds).") {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY user_id;"
		, 'OBJECT_K' );

		// Get Days Accessed
		// Do not include days with 0 hours
		$days_hours = $wpdb->get_results(
			"SELECT user_id, count(DISTINCT user_id,date) as days_accessed, sum(hours) as hours
			FROM {$wpdb->prefix}le_user_hours_tracking 
			WHERE {$hoursFilterBy} hours >0 AND user_id IN (".implode(', ', $userIds).")
			GROUP BY user_id;"
		, 'OBJECT_K' );
	
		// Get language views breakdown
		$lang_views = $wpdb->get_results(
			"SELECT user_id, count(post_id) as views, lang_code
			FROM {$wpdb->prefix}le_user_resource_tracking 
			WHERE post_id!='cac' AND lang_code!='' AND user_id IN (".implode(', ', $userIds).") {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY user_id, lang_code;"
		, 'ARRAY_A' );

		$formattedLangs = array();
		foreach ($lang_views as $key => $value) {
			if(isset($formattedLangs[$value['user_id']])){
				$formattedLangs[$value['user_id']] .= ', ' . $value['views']. ' '. strtoupper($value['lang_code']);
			}
			else {
				$formattedLangs[$value['user_id']] = $value['views']. ' '. strtoupper($value['lang_code']);
			}
		}

		// print_r($lang_views);
		foreach ($results as $key => $result) {
			$results[$key]['unique_views'] = isset($unique_views[$result['user_id']]) ? $unique_views[$result['user_id']]->unique_views : 0; 
			$results[$key]['login_count'] = isset($days_hours[$result['user_id']]) ? $days_hours[$result['user_id']]->days_accessed : 0; 
			$results[$key]['time_in_portal'] = isset($days_hours[$result['user_id']]) ? $days_hours[$result['user_id']]->hours : 0; 
			$results[$key]['lang_views'] = isset($formattedLangs[$result['user_id']]) ? $formattedLangs[$result['user_id']] : 0; 
		}

		return $results;
	}

	function generate_user_report_csv_data(){
		global $user_ID;

		$separator = ',';
    $csv_output = '';                                           //Assigning the variable to store all future CSV file's data

		//  Only show custom user fields on the partner view
		$custom_field_headers = array();
		if($this->is_partner){
			$partner_id = get_user_meta($user_ID, 'partner', true);
			// $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
			$use_custom_fields = get_post_meta($partner_id, 'use_custom_fields', true);
			$custom_fields_details =  get_field('custom_fields', $partner_id);

			if($use_custom_fields && $custom_fields_details){
				foreach ($custom_fields_details as $key => $field) {
					$custom_field_headers[] = $field['label'];
				}
			}
		}

		$header = array(
			'Customer ID',
			'Account ID',
			'Contact ID',
			'Company',
			'Contact',
			'Email',
			'Date Account Created',
			'Date Last Accessed',
			'Expiration Date',
			'Days Accessed',
			'Hours',
			'Unique Resource Views',
			'Resource Views',
			'Resource Views by Language',
			'Contacted a Coach',
			'Role',
			'Access Code',
			'Locked'
		);
		if (IP_Helpers::is_shared_partner()) {
			array_push($header, 'Child Partner');
		}
		else if(!$this->is_partner){
			array_push($header, 'Partner');
		}

		$header = array_merge($header, array(
			'Address Line 1',
			'Address Line 2',
			'City',
			'State',
			'Zipcode',
			));

		// merge custom fields
		$header = array_merge($header, $custom_field_headers);
    

    // foreach($header as $col) {
    //     $csv_output = $csv_output . $col . $separator;
    // }
		$csv_output = IRB_Util::csvstr($header);

    // $csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";
		
		$this->set_filters();

    $values = $this->get_resources_utilization_by_user();       //This here

		foreach ($values as $row) {
			$fields = array(
				$row['user_id'],
				$row['tracking_account_id'],
				$row['tracking_contact_id'],
				$row['company'],
				$row['first_name'] . ' ' . $row['last_name'],
				$row['user_email'],
				Date('m/d/Y', strtotime($row['user_registered'])),
				$row['last_login'] ? Date('m/d/Y', $row['last_login']) : '',
				$row['expiration_date'] ? Date('m/d/Y', $row['expiration_date']) : '',
				$row['login_count'],
				$row['time_in_portal'],
				$row['unique_views'],
				$row['resources_count'],
				$row['lang_views'],
				($row['contact_a_coach_count'] ? $row['contact_a_coach_count'] : 0),
				$row['reporting_role'],
				$row['access_code'],
				$row['locked']
			);

			if (IP_Helpers::is_shared_partner()) {
				array_push($fields, $row['child_partner_title']);
			}
			else if(!$this->is_partner){
				array_push($fields, $row['partner_title']);
			}

			$address_fields = array('line1', 'line2', 'city', 'state', 'zip');
			$address = unserialize($row['address']);
			foreach ($address_fields as $key => $field_idx) {
				array_push($fields, $address[$field_idx]);
			}

			if ($this->is_partner && count($custom_field_headers)) {
				$idx = 0;
				$custom_fields = unserialize($row['custom_fields']);
				while ($idx < count($custom_field_headers)) {
					array_push($fields, isset($custom_fields[$idx]) ? (is_array($custom_fields[$idx]) ? implode(', ', $custom_fields[$idx])  : $custom_fields[$idx]) : '');
					$idx++;
				}
			}


			//Getting rid of the keys and using numeric array to get values
			$csv_output .= IRB_Util::csvstr($fields);      //Generating string with field separator
			$csv_output .= "\r\n";    //Yeah...
		}

    return $csv_output; //Back to constructor

  }

}