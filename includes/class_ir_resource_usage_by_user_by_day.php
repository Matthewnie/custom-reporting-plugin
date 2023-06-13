<?php

defined('ABSPATH') or die('Nope, not accessing this');
require_once(plugin_dir_path(__FILE__) . 'class_ir_resource_usage_by_user_by_day_table.php');

class IRB_Resource_Usage_By_User_By_Day
{

	public $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'resource-util-by-user-by-day';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;

	public $filters = array(
		'start_date' => null,
		'end_date' => null,
		'language' => '',
		'roles' => array('client')
	);

	public $start_date = null;
	public $end_date = null;
	public $language = '';
	public $roles = array('client');
	public $roleOptions = array();

	function __construct()
	{
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'reporting_menu'));

		$this->roleOptions = IP_Helpers::get_user_roles();
	}

	function reporting_menu()
	{
		add_submenu_page($this->menu_parent, 'Hours & Resource Report', 'Hours & Resource Report', $this->user_role_permission, $this->page_slug, array($this, 'resource_util_by_user_by_day'));
	}

	function admin_init()
	{
		if (isset($_GET['page']) && $_GET['page'] == $this->page_slug && isset($_GET['download_csv'])) {
			if (!current_user_can($this->user_role_permission)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}

			$this->set_filters();

			IRB_Util::output_csv_file('custom-utilization_hours_and_resources-' . $this->start_date . '_' . $this->end_date, $this->generate_user_usage_csv_data());
		}
	}

	function set_filters()
	{
		if (isset($_GET['start_date']) && $_GET['start_date']) {
			// Make sure supplied date is in necessary format
			$this->start_date = date('Y-m-d', strtotime($_GET['start_date']));
			$this->filters['start_date'] = $this->start_date;
		} else {
			$this->start_date = date('Y-m-d', strtotime('-30 days'));
		}

		if (isset($_GET['end_date']) && $_GET['end_date']) {
			$this->end_date = date('Y-m-d', strtotime($_GET['end_date']));
			$this->filters['end_date'] = $this->end_date;
		}
		else {
			$this->end_date = date('Y-m-d', strtotime($this->start_date . ' +30 days'));
		}

		if (isset($_GET['language']) && $_GET['language']) {
			$this->language = $_GET['language'];
			$this->filters['language'] = $this->language;
		}

		if (isset($_GET['roles']) && $_GET['roles']) {
			$this->roles = $_GET['roles'];
			$this->filters['roles'] = $this->roles;
		}
	}

	function resource_util_by_user_by_day()
	{
		if (!current_user_can($this->user_role_permission)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$this->set_filters();

		echo '<div class="wrap">';
		echo '<h3>Hours & Resource Report</h3>';
		echo '<a href="' . admin_url("admin.php?page={$this->page_slug}&download_csv=true&" . http_build_query($this->filters)) . '" target="_blank" class="button" style="float: right;">Export CSV</a>';

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
									echo '<option value="' . $code . '" ' . ($this->language == $code ? 'selected' : '') . '>' . $value . '</option>';
								}
								?>
					</select>
				</div>

				<div class="field">
					<h4>User Type</h4>
					<?php
							foreach ($this->roleOptions as $role => $title) {
								echo '<div>';
								echo '<input type="checkbox" name="roles[]" value="' . $role . '" 
											id="role-' . $role . '" ' . (in_array($role, $this->roles) ? 'checked' : '') . '/>';
								echo '<label for="role-' . $role . '">' . $title . '</label>';
								echo '</div>';
							}
							?>
				</div>

				<div class="field">
					<h4>Dates</h4>
					<div class="row">
						<div class="field">
							<label for="start_date">Start Date</label>
							<input type="date" name="start_date" id="start_date" value="<?php echo $this->start_date; ?>">
						</div>
					</div>
					<div class="row">
						<div class="field">
							<label for="end_date">End Date</label>
							<input type="date" name="end_date" id="end_date" value="<?php echo $this->end_date; ?>">
						</div>
					</div>
				</div>
			</div> <!-- end .row -->
			<div class="row">
				<div class="field submit-field">
					<input type="submit" class="button" value="Filter">
				</div>
			</div><!-- end .row -->

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

				$this->table = new IRB_Resource_Usage_By_User_By_Day_Table($this->get_user_resource_report(ARRAY_A), $this->is_partner);

				$this->table->prepare_items();

				echo '<form id="events-filter" method="get">';
				echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
				$this->table->display();
				echo '</form>';

				?>
		<style>
			table #user_id {
				width: 65px;
			}

			table #partner {
				width: 50px;
			}

			table #date {
				width: 100px;
			}

			table #hours {
				width: 50px;
			}

			table #resource_count {
				width: 80px;
			}

			table #resource_titles {
				width: 22%;
			}
		</style>
<?php
		echo '</div>';
	}

	function get_user_ids_by_role()
	{
		$roles = $this->roles;
		if (is_array($roles) && count($roles) == 1) {
			$roles = $roles[0];
		} else if (is_array($roles) && count($roles) == 0) {
			$roles = '';
		}
		return IRB_Util::get_user_ids_by_role($roles, $this->is_partner);
	}

	function get_user_resource_report($output = OBJECT)
	{
		global $wpdb;

		$orderby = (isset($_GET['orderby'])) ? esc_sql($_GET['orderby']) : 'date';
		$order = (isset($_GET['order'])) ? esc_sql($_GET['order']) : 'ASC';

		$userIds = $this->get_user_ids_by_role();
		if (!$userIds) {
			return array();
		}

		$language_filter = '';
		if ($this->language) {
			$language_filter .= " AND urt.lang_code='{$this->language}'";
		}

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT uht.user_id,
				ifnull(umta.meta_value, '') as tracking_account_id,
				ifnull(umtc.meta_value, '') as tracking_contact_id,
				CONCAT( umfn.meta_value, ' ', umln.meta_value) as full_name,
				ifnull(umc.meta_value, '') as company,
				-- ifnull(ump.meta_value, '') as partner,
				ifnull(part.post_title, '') as partner,
				ifnull(child_part.post_title, '') as child_partner,
				ifnull(u.user_email, '') as user_email,
				ifnull(umr.meta_value, '') as reporting_role,
				ifnull(uht.date, '') as date,
				ifnull(uht.hours, '') as hours,
				ifnull(resources.unique_resource_count, '') as unique_resource_count,
				ifnull(resources.resource_count, '') as resource_count,
				ifnull(resources.resource_titles, '') as resource_titles,
				languages.langs as languages
				FROM {$wpdb->prefix}le_user_hours_tracking as uht
				LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=uht.user_id AND umta.meta_key='tracking_account_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=uht.user_id AND umtc.meta_key='tracking_contact_id'
				LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=uht.user_id AND ump.meta_key='partner'
				LEFT JOIN {$wpdb->prefix}posts as part ON part.ID=ump.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as umcp ON umcp.user_id=uht.user_id AND umcp.meta_key='child_partner'
				LEFT JOIN {$wpdb->prefix}posts as child_part ON child_part.ID=umcp.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as umc ON umc.user_id=uht.user_id AND umc.meta_key='company'
				LEFT JOIN {$wpdb->prefix}usermeta as umfn ON umfn.user_id=uht.user_id AND umfn.meta_key='first_name'
				LEFT JOIN {$wpdb->prefix}usermeta as umln ON umln.user_id=uht.user_id AND umln.meta_key='last_name'
				LEFT JOIN {$wpdb->prefix}usermeta as umr ON umr.user_id=uht.user_id AND umr.meta_key='le_role'
				LEFT JOIN {$wpdb->prefix}users as u ON uht.user_id=u.ID
				LEFT JOIN (
					SELECT urt.user_id,
						DATE(urt.time) dateOnly,
						GROUP_CONCAT(DISTINCT p.post_title SEPARATOR ', ') as resource_titles,
						COUNT(DISTINCT p.post_title) as unique_resource_count,
						COUNT(p.post_title) as resource_count
						FROM {$wpdb->prefix}le_user_resource_tracking as urt
						LEFT JOIN {$wpdb->prefix}posts as p ON urt.post_id=p.ID
            WHERE 1=1 {$language_filter}
						GROUP BY urt.user_id, dateOnly
				) as resources ON resources.user_id=uht.user_id AND resources.dateOnly=uht.date
				LEFT JOIN (
					SELECT urt.user_id,
						DATE(urt.time) dateOnly,
						GROUP_CONCAT(DISTINCT urt.lang_code SEPARATOR ', ') AS langs
						FROM {$wpdb->prefix}le_user_resource_tracking AS urt
						WHERE urt.post_id!='cac' && urt.lang_code!='' {$language_filter}
						GROUP BY urt.user_id, dateOnly
					) AS languages ON languages.user_id=uht.user_id AND languages.dateOnly=uht.date

				WHERE resources.resource_count && (uht.used_resource=1 OR uht.legacy_data=1) && uht.hours > 0 && uht.date >= '{$this->start_date}' && uht.date <= '{$this->end_date}' && uht.user_id IN(" . implode(', ', array_fill(0, count($userIds), '%d')) . ")

				ORDER BY {$orderby} {$order}",
				$userIds
			),
			$output
		);

		return $results;
	}

	function generate_user_usage_csv_data()
	{

		$separator = ',';
		$csv_output = '';                                           //Assigning the variable to store all future CSV file's data

		$header = array(
			'Customer ID',
			'Account ID',
			'Contact ID',
			'Contact',
			'Company',
			'Email',
			'User Type',
			'Partner',
			'Date',
			'Hours',
			'Unique Resource Count',
			'Resource Count',
			'Language',
			'Resources'
		);

		if (IP_Helpers::is_shared_partner()) {
			$header = array(
				'Customer ID',
				'Account ID',
				'Contact ID',
				'Contact',
				'Company',
				'Email',
				'User Type',
				'Child Partner',
				'Date',
				'Hours',
				'Unique Resource Count',
				'Resource Count',
				'Language',
				'Resources'
			);
		}
		else if($this->is_partner){
			$header = array(
				'Customer ID',
				'Account ID',
				'Contact ID',
				'Contact',
				'Company',
				'Email',
				'User Type',
				'Date',
				'Hours',
				'Unique Resource Count',
				'Resource Count',
				'Language',
				'Resources'
			);
		}
		else {
			$header = array(
				'Customer ID',
				'Account ID',
				'Contact ID',
				'Contact',
				'Company',
				'Email',
				'User Type',
				'Partner',
				'Date',
				'Hours',
				'Unique Resource Count',
				'Resource Count',
				'Language',
				'Resources'
			);
		}

		foreach ($header as $col) {
			$csv_output = $csv_output . $col . $separator;
		}
		$csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";

		$values = $this->get_user_resource_report();       //This here

		if (IP_Helpers::is_shared_partner()) {
			foreach ($values as $row) {
				$fields = array(
					$row->user_id,
					$row->tracking_account_id,
					$row->tracking_contact_id,
					$row->full_name,
					$row->company,
					$row->user_email,
					IP_Helpers::get_user_role_title($row->reporting_role),
					$row->child_partner,
					$row->date,
					$row->hours,
					$row->unique_resource_count,
					$row->resource_count,
					$row->languages,
					$row->resource_titles
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}
		}
		else if($this->is_partner){
			foreach ($values as $row) {
				$fields = array(
					$row->user_id,
					$row->tracking_account_id,
					$row->tracking_contact_id,
					$row->full_name,
					$row->company,
					$row->user_email,
					IP_Helpers::get_user_role_title($row->reporting_role),
					$row->date,
					$row->hours,
					$row->unique_resource_count,
					$row->resource_count,
					$row->languages,
					$row->resource_titles
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}
		}
		else {
			foreach ($values as $row) {
				$fields = array(
					$row->user_id,
					$row->tracking_account_id,
					$row->tracking_contact_id,
					$row->full_name,
					$row->company,
					$row->user_email,
					IP_Helpers::get_user_role_title($row->reporting_role),
					$row->partner,
					$row->date,
					$row->hours,
					$row->unique_resource_count,
					$row->resource_count,
					$row->languages,
					$row->resource_titles
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}
		}

		

		return $csv_output; //Back to constructor

	}
}
