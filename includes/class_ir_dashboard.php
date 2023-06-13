<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class IRB_Dashboard {

	public $user_ids = null;
	public $user_role_permission = 'le_reports';
	public $is_partner = false;
	public $menu_parent = 'reporting-2';
	public $page_slug = 'reporting-2';
	public $filters = array(
		'start_date' =>null,
		'end_date' => null,
		'roles' => array('client')
	);
	public $roles = array();
	public $roleOptions = array();

	function __construct(){
		add_action( 'admin_enqueue_scripts',  array($this, 'reporting_scripts') );
		add_action( 'admin_menu', array($this, 'reporting_menu') );

		$this->roleOptions = IP_Helpers::get_user_roles();
 	}

 	function reporting_menu() {
		add_menu_page( 'Admin Reports', 'Admin Reports', $this->user_role_permission, $this->menu_parent, array($this, 'dashboard'), 'dashicons-chart-area', 30 );
		add_submenu_page($this->menu_parent, 'Summary', 'Summary', $this->user_role_permission, $this->menu_parent);
	}

	function reporting_scripts() {
    // Register the script like this for a plugin: 
		wp_register_script( 'd3', '//cdnjs.cloudflare.com/ajax/libs/d3/4.7.2/d3.min.js', array(), null, false );
		wp_register_script( 'd3pie', plugins_url( '../js/d3pie.min.js', __FILE__ ), array('d3') );
    // For either a plugin or a theme, you can then enqueue the script:
    wp_enqueue_script( 'd3' );
    wp_enqueue_script( 'd3pie' );
	}

	function set_filters(){
		if(isset($_GET['start_date']) && $_GET['start_date']){
			// Make sure supplied date is in necessary format
			$start_date = date('Y-m-d', strtotime($_GET['start_date']));
			$this->filters['start_date'] = $start_date;
		}
		else {
			$start_date = date('Y-m-d', strtotime('-1 month'));
			$this->filters['start_date'] = $start_date;
		}

		if(isset($_GET['end_date']) && $_GET['end_date']){
			$end_date = date('Y-m-d', strtotime($_GET['end_date']));
			$this->filters['end_date'] = $end_date;
		}

		if(isset($_GET['roles']) && $_GET['roles']){
			$this->filters['roles'] = $_GET['roles'];
		}
	}

	function dashboard() {
		if ( !current_user_can( $this->user_role_permission ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}
		echo '<div class="wrap">';
		echo '<h2>Summary</h2>';

		$this->set_filters();

		if($this->is_partner){
			echo '<p style="font-size: 18px; margin-top: 0;"><b>User Accounts:</b> '.$this->get_licenses_utilized_count().' Used / '.$this->get_licenses_remaining_count().' Available</p>';
		}

		?>

		<form action="<?php echo admin_url('admin.php'); ?>">
				<input type="hidden" name="page" value="<?php echo $this->page_slug; ?>" />
				<h3>Filter Results</h3>
				<div class="row">
					<div class="field">
						<h4>User Type</h4>
							<?php 
								foreach ($this->roleOptions as $role => $title) {
									echo '<div>';
										echo '<input type="checkbox" name="roles[]" value="'.$role.'" 
											id="role-'.$role.'" '.(in_array($role, $this->filters['roles']) ? 'checked' : '').'/>';
										echo '<label for="role-'.$role.'">'.$title.'</label>';
									echo '</div>';
								}
							?>
					</div>
					<div class="field">
						<h4>Dates</h4>
						<div class="row">
							<div class="field">
								<label for="start_date">Start Date</label>
								<input type="date" name="start_date" id="start_date" value="<?php echo $this->filters['start_date'];?>">
							</div>
						</div>

						<div class="row">
							<div class="field">
								<label for="end_date">End Date</label>
								<input type="date" name="end_date" id="end_date" value="<?php echo $this->filters['end_date'];?>">
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

		$this->user_ids = IRB_Util::get_user_ids_by_role( $this->filters['roles'], $this->is_partner);
		
		
		echo '<table class="wp-list-table widefat fixed striped" style="max-width: 975px; margin-top: 25px;">';
			echo '<tbody>';

				echo '<tr>';
					echo '<th>Accounts Created</th>';
					echo '<td>'.$this->get_new_users_count().'</td>';
				echo '</tr>';

				echo '<tr>';
					echo '<th>Active Users</th>';
					echo '<td>'.$this->get_active_users_count().'</td>';
				echo '</tr>';

				// echo '<tr>';
				// 	echo '<th>Total Users</th>';
				// 	echo '<td>'.$this->get_user_count().'</td>';
				// echo '</tr>';
				
				if($this->is_partner){
					echo '<tr>';
						echo '<th>Hours of Training</th>';
						echo '<td>'.$this->get_hours_of_training().'</td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th>Login Frequency</th>';
					$loginFreq = $this->get_user_login_frequency();
					echo '<td>';
						echo '<div id="pieChart"></div>';
					echo '</td>';
				echo '</tr>';

				

				// echo '<tr>';
				// 	echo '<th>% of Resources Utilized</th>';
				// 	echo '<td>'.$this->get_resource_utilization_percentage().'%</td>';
				// echo '</tr>';
				$english_count = $this->get_cumulative_count_of_resources_viewed('en');
				$spanish_count = $this->get_cumulative_count_of_resources_viewed('es');
				$total_utilized_count = $english_count+$spanish_count;
				echo '<tr>';
					echo '<th>Total Views / Downloads</th>';
					echo '<td><span style="text-align: center; display: inline-block;">'.
						$total_utilized_count.
						// ' (Avg. '.$this->get_cumulative_count_of_resources_viewed_avg().'/user)</span>'.
						'<br>'.
						$english_count.' EN'.
						'<br>'.
						$spanish_count.' ES '
						.'</td>';
				echo '</tr>';

				// echo '<tr>';
				// 	echo '<th>Unique Resources Utilized (by user)</th>';
				// 	echo '<td><span style="text-align: center; display: inline-block;">'.$this->get_resources_viewed_count().'<br>(Avg. '.$this->get_resources_viewed_avg().'/user)</span></td>';
				// echo '</tr>';

				echo '<tr>';
					echo '<th>Contact a Coach</th>';
					echo '<td>'.$this->get_cac_count().'</td>';
				echo '</tr>';

				echo '<tr>';
					echo '<th>Utilization by Category</th>';
					echo '<td>'.
					'<h3 style="margin-bottom: 0;">English</h3>'.		
						$this->get_utilization_by_category().
					'<h3 style="margin-bottom: 0;">Spanish</h3>'.
						$this->get_utilization_by_category('es').
					'</td>';
				echo '</tr>';

				// echo '<tr>';
				// 	echo '<th>Utilization by Type</th>';
				// 	echo '<td>'.$this->get_utilization_by_format().'</td>';
				// echo '</tr>';

				echo '<tr>';
					echo '<th>Resources Utilized</th>';
					echo '<td>'.
					'<h3 style="margin-bottom: 0;">English</h3>'.		
						$this->get_top_utilized_resources().
					'<h3 style="margin-bottom: 0;">Spanish</h3>'.
						$this->get_top_utilized_resources('es').
					'</td>';
				echo '</tr>';

			echo '</tbody>';
		echo '</table>';


		echo '<pre>';
		echo '</pre>';

		echo "<style>@media print{ #adminmenumain {display: none; } #wpcontent {margin-left: 0} .update-nag {display: none;}}</style>";

		echo '<script>
					var pie = new d3pie("pieChart", {"header":{"title":{"text":"","fontSize":22,"font":"verdana"},"subtitle":{"color":"#999999","fontSize":10,"font":"verdana"},"location":"top-left","titleSubtitlePadding":12},"footer":{"color":"#999999","fontSize":11,"font":"open sans","location":"bottom-center"},
						"size":{"canvasHeight":250,"canvasWidth":300,"pieInnerRadius":"6%","pieOuterRadius":"88%"},"data":{"sortOrder":"value-asc",
						"content":[
							{"label":"1","value":'.$loginFreq['1'].',"color":"#0066cc"},
							{"label":"2-4","value":'.$loginFreq['2-4'].',"color":"#003366"},
							{"label":"5-9","value":'.$loginFreq['5-9'].',"color":"#336600"},
							{"label":"10-15","value":'.$loginFreq['10-19'].',"color":"#669966"},
							{"label":"20+","value":'.$loginFreq['20+'].',"color":"#cc6600"}
						]},"labels":{"outer":{"pieDistance":20},"mainLabel":{"font":"verdana"},"percentage":{"color":"#e1e1e1","font":"verdana","decimalPlaces":0},"value":{"color":"#e1e1e1","font":"verdana"},"lines":{"enabled":true},"truncation":{"enabled":true}},"tooltips":{"enabled":true,"type":"placeholder","string":"{label}: {value}, {percentage}%","styles":{"backgroundOpacity":0.66}},"effects":{"pullOutSegmentOnClick":{"effect":"linear","speed":400,"size":8}}});
					</script>';
	}

	// Timeframe in days
	function get_user_count(){
		if($this->user_ids){
			return count($this->user_ids);
		}
		else {
			return 0;
		}
	}

	// Timeframe in days
	// Limit to only new users that have logged in
	function get_new_users_count(){
		global $user_ID;
		$args = array(
			'meta_query' => array(
				'relation' => 'AND',
				// array(
				// 	'key' => 'le_login_count',
				// 	'value' => 1,
				// 	'compare' => '>='
				// )
			)
		);

		$args['meta_query'][] = array(
        'key' => 'le_role',
        'compare' => 'IN',
        'value' => $this->filters['roles']
    );

		if($this->filters['start_date'] && $this->filters['end_date']){
			$args['date_query'] = array(
				'inclusive' => true,
				'before' => $this->filters['end_date'],
				'after' => $this->filters['start_date']
			);
		}
		elseif($this->filters['start_date']){
			$args['date_query'] = array(
				'inclusive' => true,
				'after' => $this->filters['start_date']
			);
		}
		elseif($this->filters['end_date']){
			$args['date_query'] = array(
				'inclusive' => true,
				'before' => $this->filters['end_date']
			);
		}
		
		if($this->is_partner){
			$partner_id = get_user_meta($user_ID, 'partner', true);
			$partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
			$args['meta_query'][] = array(
				'key' => 'partner',
				'value' => $partner_ids,
				'compare' => 'IN'
			);
		}
		else {
			$partner_ids = IRB_Util::get_partner_ids_to_not_include();

			$args['meta_query'][] = array(
				'key' => 'partner',
				'value' => $partner_ids,
				'compare' => 'NOT IN'
			);
		}
		$query = new WP_User_Query( $args );

		if($query){
			return count($query->results);
		}
		else {
			return false;
		}
	}

	// Timeframe in days
	function get_active_users_count($resources_used = 2){
		global $wpdb;

		if(empty($this->user_ids)){
			return 0;
		}


		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}

		$results = $wpdb->get_results( 
			$wpdb->prepare( "SELECT urt.user_id, count(urt.post_id) as count
				FROM ".$wpdb->prefix."le_user_resource_tracking as urt
				WHERE urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).") {$resourceTrackingFilterBy}
				GROUP BY urt.user_id
				ORDER BY count", $this->user_ids)
			, OBJECT );

		$count = 0;
		foreach ($results as $key => $result) {
			if($result->count >= 1){
				$count++;
			}
		}
		return $count;

		

		return '';
	}

	// Timeframe in days
	function get_user_login_frequency(){
		global $user_ID, $wpdb;

		$loginCounts = array(
			'1' => 0,
			'2-4' => 0,
			'5-9' => 0,
			'10-19' => 0,
			'20+'	=> 0
		);

		$hoursTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$hoursTrackingFilterBy .= " AND date >= '{$this->filters['start_date']}' AND date <= '{$this->filters['end_date']}' ";
		}
		else if($this->filters['start_date']){
			$hoursTrackingFilterBy .= " AND date >= '{$this->filters['start_date']}' ";
		}
		else if($this->filters['end_date']){
			$hoursTrackingFilterBy .= " AND date <= '{$this->filters['end_date']}' ";
		}

		// Get Days Accessed
		// Do not include days with 0 hours
		$days_hours = $wpdb->get_results(
			$wpdb->prepare(
			"SELECT user_id, count(DISTINCT user_id,date) as days_accessed
			FROM {$wpdb->prefix}le_user_hours_tracking
			WHERE hours >0 AND user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).") {$hoursTrackingFilterBy}
			GROUP BY user_id;", $this->user_ids)
		, 'OBJECT_K' );

		foreach ($days_hours as $key => $user) {
			if($user->days_accessed == 1){
				$loginCounts['1']++;
			}
			else if($user->days_accessed >= 2 && $user->days_accessed <= 4){
				$loginCounts['2-4']++;
			}
			else if($user->days_accessed >= 5 && $user->days_accessed <= 9){
				$loginCounts['5-9']++;
			}
			else if($user->days_accessed >= 10 && $user->days_accessed <= 19){
				$loginCounts['10-19']++;
			}
			else if($user->days_accessed > 20){
				$loginCounts['20+']++;
			}
		}

		
		return $loginCounts;
	}

	function get_resource_ids($lang = 'en'){
		global $user_ID, $sitepress;

		$pre_lang = false;
		if($lang !== ICL_LANGUAGE_CODE){
			$pre_lang = ICL_LANGUAGE_CODE;
			$sitepress->switch_lang($lang);
		}

		if($this->is_partner){
			$partner_id = get_user_meta($user_ID, 'partner', true);

			// 0 is the default ID
			$partner_ids = array(0);
			if($partner_id){
				// $partner_ids[] = $partner_id;
				$partner_ids = array_merge($partner_ids, IP_Helpers::get_all_translation_ids_for_partner($partner_id));
			}
			
			$post_ids = get_posts(array(
				'post_type'     => 'post',
				'numberposts'   => -1, // get all posts.
				'order'         => 'ASC',
				'orderby'       => 'menu_order',
				'suppress_filters' => 0,
				'post_status' => 'publish',
				'category__not_in' => array(1),
				'meta_query'    => array(
					'relation'  => 'OR',
					array(
						'key' => 'owning_partner_partner-id',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'owning_partner_partner-id',
						'value' => $partner_ids,
						'compare' => 'IN'
						)
					),
					'fields'        => 'ids', // Only get post IDs
				));
		}
		else {
			$partner_ids_exclude = IRB_Util::get_partner_ids_to_not_include();
			$post_ids = get_posts(array(
				'post_type'     => 'post',
				'numberposts'   => -1, // get all posts.
				'order'         => 'ASC',
				'orderby'       => 'menu_order',
				'suppress_filters' => 0,
				'post_status' => 'publish',
				'category__not_in' => array(1),
				'meta_query'    => array(
					'relation'  => 'OR',
					array(
						'key' => 'owning_partner_partner-id',
						'compare' => 'NOT EXISTS'
					),
					array(
						'key' => 'owning_partner_partner-id',
						'value' => $partner_ids_exclude,
						'compare' => 'NOT IN'
						)
					),
					'fields'        => 'ids', // Only get post IDs
				));
			}

			// switch back to the previous language
			if($pre_lang){
				$sitepress->switch_lang($pre_lang);
			}
			return $post_ids;
	}

	function get_licenses_utilized_count(){
		global $user_ID;
		$partner_id = get_user_meta($user_ID, 'partner', true);
		return Custom_Reporting::get_not_locked_user_count_for_partner($partner_id);
	}
	function get_licenses_remaining_count(){
		global $user_ID;
		$partner_id = IP_Helpers::get_partner_id($user_ID, 'en');

		$limit = get_metadata('post', $partner_id, 'user_limit', true);

		if($limit){
			return $limit - $this->get_licenses_utilized_count();
		}
		else {
			return 'Unlimited';
		}

		return Custom_Reporting::get_not_locked_user_count_for_partner($partner_id);
	}
	
	function get_hours_of_training(){
		global $wpdb;

		if (empty($this->user_ids)) {
			return 0;
		}

		$hoursTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$hoursTrackingFilterBy .= " AND date >= '{$this->filters['start_date']}' AND date <= '{$this->filters['end_date']}' ";
		}
		else if($this->filters['start_date']){
			$hoursTrackingFilterBy .= " AND date >= '{$this->filters['start_date']}' ";
		}
		else if($this->filters['end_date']){
			$hoursTrackingFilterBy .= " AND date <= '{$this->filters['end_date']}' ";
		}

		$time_in_portal_query = $wpdb->prepare(
			"SELECT sum(hours) as total_hours
        FROM {$wpdb->prefix}le_user_hours_tracking
        WHERE user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).") AND (legacy_data=1 OR used_resource=1) {$hoursTrackingFilterBy}
      ", $this->user_ids
		);

		$time_in_portal = $wpdb->get_row($time_in_portal_query, OBJECT);

		if($time_in_portal->total_hours){
			return $time_in_portal->total_hours;
		}
		
		return 0;
	}

	function get_resource_count($lang = 'en'){
		$post_ids = $this->get_resource_ids($lang);
		return count($post_ids);
	}

	function get_resources_not_viewed_count(){
		global $wpdb;

		if(empty($this->user_ids)){
			return 0;
		}

		$resource_ids = $this->get_resource_ids();

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(DISTINCT p.ID) as not_used_count
				FROM ".$wpdb->prefix."posts as p
				LEFT JOIN ".$wpdb->prefix."le_user_resource_tracking as urt ON p.ID=urt.post_id
				WHERE urt.post_id IS NULL 
					AND p.post_type=%s 
					AND p.post_status='publish' 
					AND p.post_id IN(".implode(', ', array_fill(0, count($resource_ids), '%d')).")
					OR urt.user_id NOT IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")",
				'post', $resource_ids, $this->user_ids), OBJECT );
		return $results[0]->not_used_count;
	}

	function get_resources_viewed_count(){
		global $wpdb;

		if(empty($this->user_ids)){
			return 0;
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(DISTINCT urt.post_id) as viewed_count
				FROM ".$wpdb->prefix."le_user_resource_tracking as urt
				WHERE urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")", $this->user_ids)
			, OBJECT );
		return $results[0]->viewed_count;
	}

	function get_resources_viewed_avg(){
		global $wpdb;

		if(empty($this->user_ids)){
			return 0;
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(DISTINCT urt.post_id)/count(DISTINCT urt.user_id) as viewed_count
				FROM ".$wpdb->prefix."le_user_resource_tracking as urt
				WHERE urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")", $this->user_ids)
			, OBJECT );
		return number_format($results[0]->viewed_count, 0);
	}

	function get_resource_utilization_percentage(){
		return number_format(($this->get_resources_viewed_count()/$this->get_resource_count()) * 100, 2);
	}

	function get_cumulative_count_of_resources_viewed($lang_code='en'){
		global $wpdb;

		if(empty($this->user_ids)){
			return 0;
		}

		$args = array_merge(array($lang_code), $this->user_ids);

		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(urt.post_id) as viewed_count
				FROM ".$wpdb->prefix."le_user_resource_tracking as urt
				WHERE urt.post_id!='cac' AND urt.lang_code='%s' AND urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).") {$resourceTrackingFilterBy}", 
				$args)
			, OBJECT );
		return $results[0]->viewed_count;
	}

	function get_cumulative_count_of_resources_viewed_avg(){
		global $wpdb;
		
		if(empty($this->user_ids)){
			return 0;
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(urt.post_id)/count(DISTINCT urt.user_id) as viewed_count
				FROM ".$wpdb->prefix."le_user_resource_tracking as urt
				WHERE urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")", $this->user_ids)
			, OBJECT );
		return number_format($results[0]->viewed_count, 0);
	}

	function get_cac_count(){
		global $wpdb;
		
		if(empty($this->user_ids)){
			return 0;
		}

		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT count(urt.post_id) as viewed_count
				FROM {$wpdb->prefix}le_user_resource_tracking as urt
				WHERE urt.post_id='cac' AND urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).") {$resourceTrackingFilterBy}",
				$this->user_ids)
			, OBJECT );
		return number_format($results[0]->viewed_count, 0);
	}

	function get_utilization_by_category($lang_code='en'){
		global $wpdb;
		
		if(empty($this->user_ids)){
			return '';
		}

		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}

		$arguments = $this->user_ids;
		array_push($arguments, $lang_code);
		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT tt.term_id, t.name, count(urt.post_id) as count
				FROM ".$wpdb->prefix."term_taxonomy as tt
				LEFT JOIN ".$wpdb->prefix."terms as t ON t.term_id=tt.term_id
				LEFT JOIN ".$wpdb->prefix."term_relationships as tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
				LEFT JOIN ".$wpdb->prefix."le_user_resource_tracking as urt ON urt.post_id=tr.object_id AND tt.parent!=0 AND urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")
				WHERE urt.lang_code=%s AND tt.taxonomy='category' {$resourceTrackingFilterBy}
				GROUP BY tt.term_id
				ORDER BY count DESC", $arguments)
			, OBJECT );

		$i = 0;
		$output = '';
		while ($i <= 9) {
			if(!isset($results[$i])) break;
			$output .= '<b>'.$results[$i]->name . '</b> ('.$results[$i]->count.')<br>';

			$i++;
		}
		return $output;
	}

	function get_utilization_by_format(){
		global $wpdb;
		
		if(empty($this->user_ids)){
			return '';
		}

		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT tt.term_id, t.name, count(urt.post_id) as count
				FROM ".$wpdb->prefix."term_taxonomy as tt
				LEFT JOIN ".$wpdb->prefix."terms as t ON t.term_id=tt.term_id
				LEFT JOIN ".$wpdb->prefix."term_relationships as tr ON tr.term_taxonomy_id=tt.term_taxonomy_id
				LEFT JOIN ".$wpdb->prefix."le_user_resource_tracking as urt ON urt.post_id=tr.object_id AND urt.user_id IN(".implode(', ', array_fill(0, count($this->user_ids), '%d')).")
				WHERE tt.taxonomy='resource_format' {$resourceTrackingFilterBy}
				GROUP BY tt.term_id
				ORDER BY count DESC", $this->user_ids)
			, OBJECT );

		$output = '';
		foreach ($results as $key => $result) {
			$output .= '<b>'.$result->name . '</b> ('.$result->count.')<br>';
		}

		return $output;
	}

	function get_top_utilized_resources($lang_code='en')
	{
		global $wpdb;

		if (empty($this->user_ids)) {
			return '';
		}

		$resourceTrackingFilterBy = '';
		if($this->filters['start_date'] && $this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00' AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		else if($this->filters['start_date']){
			$resourceTrackingFilterBy .= " AND urt.time >= '{$this->filters['start_date']} 00:00:00'";
		}
		else if($this->filters['end_date']){
			$resourceTrackingFilterBy .= " AND urt.time <= '{$this->filters['end_date']} 23:59:59'";
		}
		$arguments = $this->user_ids;
		array_push($arguments, $lang_code);
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.post_title, count(urt.post_id) as count
				FROM " . $wpdb->prefix . "posts as p
				INNER JOIN " . $wpdb->prefix . "le_user_resource_tracking as urt ON urt.post_id=p.ID AND urt.user_id IN(" . implode(', ', array_fill(0, count($this->user_ids), '%d')) . ")
				WHERE urt.lang_code=%s {$resourceTrackingFilterBy}
				GROUP BY p.ID
				ORDER BY count DESC
				LIMIT 20",
				$arguments
			),
			OBJECT
		);

		$output = '';
		foreach ($results as $key => $result) {
			$output .= '<b>' . $result->post_title . '</b> (' . $result->count . ')<br>';
		}

		return $output;
	}

}