<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class IRB_New_User_Report {

	public $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'new-user-report';
	public $hours_page_slug = 'user-hours';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $filters = array(
		'start_date' =>null,
		'end_date' => null,
		'language' => '',
		'roles' => array(),
		'usrlck' => array('no'),
		'showall' => false
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
			$this->filters['usrlck'] = $_GET['usrlck'];
		}
		if(isset($_GET['showall']) && $_GET['showall']=='1'){
			$this->filters['showall'] = true;
		}
		else {
			$this->filters['showall'] = false;
		}
	}

	function resource_util_by_user_table() {
		require_once plugin_dir_path(__FILE__).'class_ir_new_user_report_table.php';

		$this->set_filters();

	  
	  echo '<div class="wrap">';
	  echo '<h2>User Report</h2>'; 
		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button" style="float: right;">Export CSV</a>';
		// echo ' <a href="'.admin_url('admin.php?page=user-report&download_hours_csv=true').'" target="_blank" class="button">Export Raw Hours CSV</a>';
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
						<h4>User Type</h4>
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
											id="locked-no" '.(in_array('no', $this->filters['usrlck']) ? 'checked' : '').'/>';
										echo '<label for="locked-no">No</label>';
									echo '</div>';
									echo '<div>';
										echo '<input type="checkbox" name="usrlck[]" value="yes" 
											id="locked-yes" '.(in_array('yes', $this->filters['usrlck']) ? 'checked' : '').'/>';
										echo '<label for="locked-yes">Yes</label>';
									echo '</div>';
							?>
					</div>

					<div class="field">
						<h4>Resource Usage</h4>
							<?php 
									echo '<div>';
										echo '<input type="radio" name="showall" value="1" 
											id="showall-yes" '.($this->filters['showall'] ? 'checked' : '').'/>';
										echo '<label for="showall-yes">All Users</label>';
									echo '</div>';
									echo '<div>';
										echo '<input type="radio" name="showall" value="no" 
											id="showall-no" '.(!$this->filters['showall'] ? 'checked' : '').'/>';
										echo '<label for="showall-no">Users with resource usage</label>';
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

		//place this before any script you want to calculate time
		$time_start = microtime(true); 
		$userData = $this->get_resources_utilization_by_user('array');
		$time_end = microtime(true);
		$execution_time = ($time_end - $time_start)/60;
		// echo '<b>Total Execution Time:</b> '.$execution_time.' Mins';

		$userTable = new IRB_New_User_Report_Table($userData, $this->is_partner);
		$userTable->prepare_items();
		echo '<form id="events-filter" method="get">';
		echo '<input type="hidden" name="page" value="'.$_REQUEST['page'].'" />';
		$userTable->display(); 
		echo '</form>';
		echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&download_csv=true&'.http_build_query($this->filters)).'" target="_blank" class="button">Export CSV</a>';
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


		$orderby = 'full_name';
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
				$orderbySQL = 'umln.meta_value '.$order.', umfn.meta_value '.$order;
				break;
			case 'company':
				$orderbySQL = 'umc.meta_value '.$order;
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
				$orderbySQL = 'umln.meta_value '.$order.', umfn.meta_value '.$order;
				$orderby = 'full_name';
				break;
		}

		$filterBy = '';
		$uniFilterBy = '';
		$resourceTrackingFilterBy = '';
		$hoursFilterBy = '';
		$filter_title = '';
		if($this->start_date && $this->end_date){
			// $filterBy .= " AND urt.time >= '{$this->start_date} 00:00:00' AND urt.time <= '{$this->end_date} 23:59:59'";
			$hoursFilterBy .= "date >= '{$this->start_date}' AND date <= '{$this->end_date}' AND";
			$resourceTrackingFilterBy .= " AND time >= '{$this->start_date} 00:00:00' AND time <= '{$this->end_date} 23:59:59'";
			$filter_title .= '_'.strtotime($this->start_date).strtotime($this->end_date);
		}
		else if($this->start_date){
			// $filterBy .= " AND (urt.time >= '{$this->start_date} 00:00:00')";
			$hoursFilterBy .= "date >= '{$this->start_date}' AND";
			$resourceTrackingFilterBy .= " AND time >= '{$this->start_date} 00:00:00'";
			$filter_title .= '_start'.strtotime($this->start_date);
		}
		else if($this->end_date){
			// $filterBy .= " AND (urt.time <= '{$this->end_date} 23:59:59')";
			$hoursFilterBy .= "date <= '{$this->end_date}' AND";
			$resourceTrackingFilterBy .= " AND time <= '{$this->end_date} 23:59:59'";
			$filter_title .= '_end'.strtotime($this->end_date);
		}

		if($this->language){
			// $filterBy .= " AND urt.lang_code='{$this->language}'";
			$uniFilterBy .= " AND lang_code='{$this->language}'";
			$filter_title .= '_language_'.$this->language;
		}

		if($this->filters['roles']){
			$filter_title .= '_roles_'.(is_array($this->filters['roles']) ? implode('_', $this->filters['roles']) : $this->filters['roles']);
		}

		if(count($this->filters['usrlck']) > 0){
			if(count($this->filters['usrlck']) == 1 && in_array('no', $this->filters['usrlck'])){
				$filterBy .= " AND (uml.meta_value IS NULL OR uml.meta_value='')";
			}
			else if(count($this->filters['usrlck']) == 1 && in_array('yes', $this->filters['usrlck'])){
				$filterBy .= " AND uml.meta_value='yes'";
			}
			$filter_title .= '_locked_'.(is_array($this->filters['usrlck']) ? implode('_', $this->filters['usrlck']) : $this->filters['usrlck']);
		}

		$transient_id = 'resources_utilization_by_user_'.strtolower($orderby).'_'.strtolower($order).strtolower($filter_title);
		if($this->is_partner){
			$transient_id = $transient_id.'_p_'.IRB_Util::get_user_partner_id();
		}
		
		// if ( false === ( $results = get_transient( $transient_id ) ) ) {
			$results = $wpdb->get_results(
			// $results = $wpdb->prepare(
			"SELECT u.ID as user_id, 
				ifnull(umta.meta_value, '') as tracking_account_id,
				ifnull(umtc.meta_value, '') as tracking_contact_id,
				ifnull(umc.meta_value, '') as company, 
				CONCAT( umfn.meta_value, ' ', umln.meta_value) as full_name,
				ifnull(uma.meta_value, '') as address,
				u.user_email, 
				umr.meta_value as reporting_role,
				ifnull(accesscode.post_title, '') as access_code,
				ifnull(umexp.meta_value, '') as expiration_date,
				DATE_FORMAT(u.user_registered, '%m/%d/%Y') as user_registered, 
				ifnull(cac.count, 0) as contact_a_coach_count,
				ifnull(umcf.meta_value, '') as custom_fields,
				ump.meta_value as partner_id


				FROM {$wpdb->prefix}users as u 
				LEFT JOIN {$wpdb->prefix}usermeta as umc ON umc.user_id=u.ID AND umc.meta_key='company'
				LEFT JOIN {$wpdb->prefix}usermeta as umr ON umr.user_id=u.ID AND umr.meta_key='le_role'


				LEFT JOIN {$wpdb->prefix}usermeta as umfn ON umfn.user_id=u.ID AND umfn.meta_key='first_name'
				LEFT JOIN {$wpdb->prefix}usermeta as umln ON umln.user_id=u.ID AND umln.meta_key='last_name'


				LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=u.ID AND umta.meta_key='tracking_account_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=u.ID AND umtc.meta_key='tracking_contact_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umcf ON umcf.user_id=u.ID AND umcf.meta_key='custom_fields'
				LEFT JOIN {$wpdb->prefix}usermeta as uma ON uma.user_id=u.ID AND uma.meta_key='address'
				LEFT JOIN {$wpdb->prefix}usermeta as umexp ON umexp.user_id=u.ID AND umexp.meta_key='le_expiration_date'
				LEFT JOIN {$wpdb->prefix}usermeta as umaccess ON umaccess.user_id=u.ID AND umaccess.meta_key='access_code'
				LEFT JOIN {$wpdb->prefix}posts as accesscode ON accesscode.ID=umaccess.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as uml ON uml.user_id=u.ID AND uml.meta_key='baba_user_locked'
				LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=u.ID AND ump.meta_key='partner'

				LEFT JOIN (
				SELECT user_id, count(*) as count
				FROM {$wpdb->prefix}le_user_resource_tracking

				WHERE post_id='cac' AND user_id IN (".implode(', ', $userIds).")
				GROUP BY user_id
				) as cac on cac.user_id=u.ID
				WHERE u.ID IN (".implode(', ', $userIds).") {$filterBy}
				GROUP BY u.ID
				ORDER BY ".$orderbySQL
			, ($output==='array' ? 'ARRAY_A' : OBJECT) );



			// 60 * 60 is the expiration in seconds - in this case, 3600 seconds (1 hour)
			set_transient( $transient_id, $results, 60 * 10 ); 
		// }

		// Get unique views
		// Of non template resources
		$unique_views = $wpdb->get_results(
			"SELECT urt.user_id, count(DISTINCT urt.user_id,urt.post_id) as unique_views, count(post_id) as total_views
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			LEFT JOIN {$wpdb->prefix}term_relationships on post_id=object_id
			WHERE uht.hours>0 AND urt.post_id!='cac' AND urt.user_id IN (".implode(', ', $userIds).") AND term_taxonomy_id IN (26,28,29,172,170,169) {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );
		
		// Get unique views/downloads of all resources
		$unique_views_downloads = $wpdb->get_results(
			"SELECT urt.user_id, count(DISTINCT urt.user_id,urt.post_id) as unique_views, count(post_id) as total_views
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			LEFT JOIN {$wpdb->prefix}term_relationships on post_id=object_id
			WHERE uht.hours>0 AND urt.post_id!='cac' AND urt.user_id IN (".implode(', ', $userIds).") AND term_taxonomy_id IN (26,28,29,172,170,169, 27,171) {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );

		$cac_count_for_dates = $wpdb->get_results(
			"SELECT urt.user_id, count(post_id) as total_views
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			WHERE uht.hours>0 AND urt.post_id='cac' AND urt.user_id IN (".implode(', ', $userIds).") {$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );

		// Get total downloads
		// Of template resources
		$total_downloads = $wpdb->get_results(
			"SELECT urt.user_id, count(DISTINCT urt.user_id,urt.post_id) as unique_downloads, count(post_id) as total_downloads
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			LEFT JOIN {$wpdb->prefix}term_relationships on post_id=object_id
			WHERE uht.hours>0 AND post_id!='cac' AND urt.user_id IN (".implode(', ', $userIds).") AND term_taxonomy_id IN (27,171) {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );

		// Get date of first resource use
		$first_use = $wpdb->get_results(
			"SELECT user_id, DATE_FORMAT(date, '%m/%d/%Y') as date
			FROM {$wpdb->prefix}le_user_hours_tracking
			WHERE hours>0 AND (legacy_data OR used_resource) AND user_id IN (".implode(', ', $userIds).") AND used_resource {$uniFilterBy}
			GROUP BY user_id;"
		, 'OBJECT_K' );

		// Get Unique Post Titles
		// Set group concat length to much larger to allow the post titles field...
		$wpdb->query('SET @@group_concat_max_len = 1000000;');
		$post_titles = $wpdb->get_results(
			"SELECT uht.user_id,
			GROUP_CONCAT(DISTINCT p.post_title SEPARATOR';') as posts 
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			LEFT JOIN {$wpdb->prefix}posts as p on urt.post_id=p.ID
			LEFT JOIN {$wpdb->prefix}term_relationships on urt.post_id=object_id
			WHERE uht.hours>0 AND uht.user_id IN (".implode(', ', $userIds).") and urt.post_id != 'cac' AND term_taxonomy_id IN (26,28,29,172,170,169) {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );

		$partner_titles = $wpdb->get_results(
			"SELECT p.ID, p.post_title as partner_title
			FROM {$wpdb->prefix}posts as p
			WHERE p.post_type='partner'
			"
		, 'OBJECT_K' );

		$download_titles = $wpdb->get_results(
			"SELECT uht.user_id,
			GROUP_CONCAT(DISTINCT p.post_title SEPARATOR';') as posts 
			FROM {$wpdb->prefix}le_user_hours_tracking as uht
			LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
			LEFT JOIN {$wpdb->prefix}posts as p on urt.post_id=p.ID
			LEFT JOIN {$wpdb->prefix}term_relationships on urt.post_id=object_id
			WHERE uht.hours>0 AND urt.user_id IN (".implode(', ', $userIds).") and urt.post_id != 'cac' AND term_taxonomy_id IN (27,171) {$uniFilterBy}{$resourceTrackingFilterBy}
			GROUP BY urt.user_id;"
		, 'OBJECT_K' );

		// Get Days Accessed
		// Do not include days with 0 hours
		$days_hours = $wpdb->get_results(
			"SELECT user_id, count(DISTINCT user_id,date) as days_accessed, sum(hours) as hours
			FROM {$wpdb->prefix}le_user_hours_tracking
			WHERE {$hoursFilterBy} hours >0 AND user_id IN (".implode(', ', $userIds).")
			GROUP BY user_id;"
		, 'OBJECT_K' );

		// Get hours for days resources used
		$hours = $wpdb->get_results(
			"SELECT user_id, count(DISTINCT user_id,date) as days_accessed, sum(hours) as hours
			FROM {$wpdb->prefix}le_user_hours_tracking
			WHERE {$hoursFilterBy} hours >0 AND (legacy_data=1 OR used_resource=1) AND user_id IN (".implode(', ', $userIds).")
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

		foreach ($results as $key => $result) {
			$results[$key]['unique_views_downloads'] = isset($unique_views_downloads[$result['user_id']]) ? $unique_views_downloads[$result['user_id']]->unique_views : 0; 
			$results[$key]['views_downloads'] = isset($unique_views_downloads[$result['user_id']]) ? $unique_views_downloads[$result['user_id']]->total_views : 0; 
			$results[$key]['unique_views'] = isset($unique_views[$result['user_id']]) ? $unique_views[$result['user_id']]->unique_views : 0; 
			// $results[$key]['views'] = isset($unique_views[$result['user_id']]) ? $unique_views[$result['user_id']]->total_views : 0; 
			$results[$key]['days_accessed'] = isset($hours[$result['user_id']]) ? $hours[$result['user_id']]->days_accessed : 0; 
			$results[$key]['hours'] = isset($hours[$result['user_id']]) ? $hours[$result['user_id']]->hours : 0; 
			$results[$key]['lang_views'] = isset($formattedLangs[$result['user_id']]) ? $formattedLangs[$result['user_id']] : 0; 
			$results[$key]['posts'] = isset($post_titles[$result['user_id']]) ? $post_titles[$result['user_id']]->posts : ''; 
			$results[$key]['downloads'] = isset($total_downloads[$result['user_id']]) ? $total_downloads[$result['user_id']]->unique_downloads : 0; 
			$results[$key]['download_titles'] = isset($download_titles[$result['user_id']]) ? $download_titles[$result['user_id']]->posts : ''; 
			$results[$key]['first_use'] = isset($first_use[$result['user_id']]) ? $first_use[$result['user_id']]->date : ''; 
			$results[$key]['cac_count'] = isset($cac_count_for_dates[$result['user_id']]) ? $cac_count_for_dates[$result['user_id']]->total_views : 0; 
			$results[$key]['partner_title'] = isset($partner_titles[$result['partner_id']]) ? $partner_titles[$result['partner_id']]->partner_title : ''; 


			

			// remove users with 0 hours/resources used if showall is no
			if(!$this->filters['showall']){
				// if($results[$key]['views']==0 && $results[$key]['downloads']==0){
				if($results[$key]['views_downloads']==0 && $results[$key]['cac_count']==0){
					unset($results[$key]);
				}
			}
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

		$header1 = array(
			'User ID',
			'Account ID',
			'Contact ID',
	    'Company',
	    'Contact',
		);

		if(!$this->is_partner){
			array_push($header1, 'Partner');
		}

		$header2 = array(
	    'Email',
	    'User Type',
			'Access Code',
	    'Date Account Created',
			'Exp. Date',
	    'Hours',
	    'Total Views/Downloads',
	    'Unique Views/Downloads',
	    'Resources Viewed',
			'List of Resources Viewed',
			'Resources Downloaded',
			'List of Resources Downloaded',
	    'Languages',
	    'Days Accessed',
	    // 'Date Last Accessed',
	    'Contacted a Coach'
		);
		$addressFields = array(
			'Address Line 1',
			'Line 2',
			'City',
			'State',
			'Zipcode',
		);

		$header = array_merge($header1, $addressFields, $header2);

		// merge custom fields
		$header = array_merge($header, $custom_field_headers);
		$csv_output = IRB_Util::csvstr($header);
		$csv_output .= "\r\n";
		
		$this->set_filters();

    $values = $this->get_resources_utilization_by_user();       //This here

		foreach ($values as $row) {
			$fields = array(
				$row['user_id'],
				$row['tracking_account_id'],
				$row['tracking_contact_id'],
				$row['company'],
				$row['full_name'],
			);

			if(!$this->is_partner){
				array_push($fields, $row['partner_title']);
			}

			$address_fields = array('line1', 'line2', 'city', 'state', 'zip');
			$address = unserialize($row['address']);
			foreach ($address_fields as $key => $field_idx) {
				array_push($fields, isset($address[$field_idx]) ? $address[$field_idx] : '');
			}

			


			$fields2 = array(
				$row['user_email'],
				IP_Helpers::get_user_role_title($row['reporting_role']),
				$row['access_code'],
				$row['user_registered'],
				$row['expiration_date'] ? Date('m/d/Y', $row['expiration_date']) : '',
				$row['hours'],
				$row['views_downloads'],
				$row['unique_views_downloads'],
				$row['unique_views'],
				$row['posts'],
				$row['downloads'],
				$row['download_titles'],
				$row['lang_views'],
				$row['days_accessed'] ? $row['days_accessed'] : 0,
				// $row['date_last_accessed'] ? Date('m/d/Y', $row['date_last_accessed']) : '',
				($row['cac_count'] ? $row['cac_count'] : 0),
			);

			$fields = array_merge($fields, $fields2);

			// if (IP_Helpers::is_shared_partner()) {
			// 	array_push($fields, $row['child_partner_title']);
			// }
			// else if(!$this->is_partner){
			// 	array_push($fields, $row['partner_title']);
			// }

			

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