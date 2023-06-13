<?php

defined('ABSPATH') or die('Nope, not accessing this');
require_once(plugin_dir_path(__FILE__) . 'class_ir_resource_usage_by_user_table.php');

class IRB_Resource_Usage_By_User
{

	public $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'resource-util-by-user';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $start_date = null;
	public $end_date = null;
	// public $roles = array('client');
	public $roleOptions = array();
	public $langOptions = array(
		'' => 'All',
		'en' => 'English',
		'es' => 'Spanish'
	);
	public $filters = array(
		'start_date' => null,
		'end_date' => null,
		'roles' => array('client'),
		'lang' => ''
	);

	function __construct()
	{
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'reporting_menu'));

		$this->roleOptions = IP_Helpers::get_user_roles();
	}

	function reporting_menu()
	{
		add_submenu_page($this->menu_parent, 'Resource Usage by User', 'Resource Usage by User', $this->user_role_permission, $this->page_slug, array($this, 'resource_util_by_user'));
	}

	function admin_init()
	{
		if (isset($_GET['page']) && $_GET['page'] == $this->page_slug && isset($_GET['download_csv'])) {
			if (!current_user_can($this->user_role_permission)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			IRB_Util::output_csv_file('custom-resources-utilization-by-user', $this->generate_user_usage_csv_data());
		}
	}

	function set_filters()
	{
		if (isset($_GET['start_date']) && $_GET['start_date']) {
			// Make sure supplied date is in necessary format
			$this->filters['start_date'] = date('Y-m-d', strtotime($_GET['start_date']));
		} else {
			$this->filters['start_date'] = date('Y-m-d', strtotime('-60 days'));
		}

		if (isset($_GET['end_date']) && $_GET['end_date']) {
			$this->filters['end_date'] = date('Y-m-d', strtotime($_GET['end_date']));
		}

		if (isset($_GET['language']) && $_GET['language']) {
			$this->filters['language'] = $_GET['language'];
		}

		if (isset($_GET['roles']) && $_GET['roles']) {
			$this->filters['roles'] = $_GET['roles'];
		}
		if (isset($_GET['lang']) && $_GET['lang']) {
			$this->filters['lang'] = $_GET['lang'];
		}
	}

	function resource_util_by_user()
	{
		if (!current_user_can($this->user_role_permission)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$this->set_filters();

		echo '<div class="wrap">';
		echo '<h3>Resource Usage by User</h3>';
		echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&download_csv=true&' . http_build_query($this->filters)) . '" target="_blank" class="button" style="float: right;">Export CSV</a>';
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
						echo '<input type="checkbox" name="roles[]" value="' . $role . '" 
										id="role-' . $role . '" ' . (in_array($role, $this->filters['roles']) ? 'checked' : '') . '/>';
						echo '<label for="role-' . $role . '">' . $title . '</label>';
						echo '</div>';
					}
					?>
				</div>
				<div class="field">
					<h4>Language</h4>
					<select name="lang" id="lnag">
					<?php
					foreach ($this->langOptions as $lang => $title) {
						echo '<option value="' . $lang . '" ' . ($lang == $this->filters['lang'] ? 'selected' : '') . '>'.$title.'</option>';
					}
					?>
					</select>
				</div>
				<div class="field">
					<h4>Date Range</h4>
					<div class="row">
						<div class="field">
							<label for="start_date">Start Date</label>
							<input type="date" name="start_date" id="start_date" value="<?php echo $this->filters['start_date']; ?>">
						</div>
					</div>

					<div class="row">
						<div class="field">
							<label for="end_date">End Date</label>
							<input type="date" name="end_date" id="end_date" value="<?php echo $this->filters['end_date']; ?>">
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

		$this->table = new IRB_Resource_Usage_By_User_Table($this->get_resources_utilization_by_user(ARRAY_A), $this->is_partner);

		$this->table->prepare_items();

		echo '<form id="events-filter" method="get">';
		echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
		$this->table->display();
		echo '</form>';

		echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&download_csv=true&' . http_build_query($this->filters)) . '" target="_blank" class="button">Export CSV</a>';
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

	function get_resources_utilization_by_user($output = OBJECT)
	{
		global $wpdb;

		$userIds = $this->get_user_ids_by_role();
		if (!$userIds) {
			return array();
		}

		$filterBy = '';
		if ($this->filters['start_date'] && $this->filters['end_date']) {
			$filterBy .= " AND (urt.time >= '" . $this->filters['start_date'] . "' AND urt.time <= '" . $this->filters['end_date'] . " 23:59:59')";
		} else if ($this->filters['start_date']) {
			$filterBy .= " AND (urt.time >= '" . $this->filters['start_date'] . "')";
		} else if ($this->filters['end_date']) {
			$filterBy .= " AND (urt.time <= '" . $this->filters['end_date'] . " 23:59:59')";
		}

		if ($this->filters['lang']) {
			$filterBy .= " AND (urt.lang_code= '" . $this->filters['lang'] . "')";
		}
		
		// Get resources tracked for users for days with hours.
		$tracked = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT urt.post_id as resource_id,
				urt.user_id as user_id,
				urt.time,
				urt.lang_code
				FROM {$wpdb->prefix}le_user_hours_tracking as uht
				LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt on urt.user_id=uht.user_id AND uht.date=DATE(urt.time)
				WHERE uht.hours>0 AND urt.user_id IN(" . implode(', ', array_fill(0, count($userIds), '%d')) . ") {$filterBy}
				ORDER BY urt.time DESC",
				$userIds
			),
			OBJECT
		);

		// get resource ids for the resource query
		$resource_ids = array();
		foreach ($tracked as $key => $item) {
			$resource_ids[] = $item->resource_id;
		}
		$resource_ids = array_unique($resource_ids);
		
		
		$users = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT u.ID as user_id, 
					u.user_email, 
					umr.meta_value as reporting_role,
					um.meta_value as 'first_name',
					um2.meta_value as 'last_name', 
					CONCAT( um.meta_value, ' ', um2.meta_value) as full_name,
					um3.meta_value as 'company', 
					ifnull(umta.meta_value, '') as tracking_account_id,
					ifnull(umtc.meta_value, '') as tracking_contact_id,
					ifnull(umcp.meta_value, '') as child_partner,
					ifnull(child_part.post_title, '') as child_partner_title,
					ifnull(part.post_title, '') as partner				

				FROM {$wpdb->prefix}users as u		  
				LEFT JOIN {$wpdb->prefix}usermeta as um ON um.user_id=u.ID AND um.meta_key='first_name'
				LEFT JOIN {$wpdb->prefix}usermeta as um2 ON um2.user_id=u.ID AND um2.meta_key='last_name'
				LEFT JOIN {$wpdb->prefix}usermeta as um3 ON um3.user_id=u.ID AND um3.meta_key='company'
				LEFT JOIN {$wpdb->prefix}usermeta as umr ON umr.user_id=u.ID AND umr.meta_key='le_role'

				LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=u.ID AND umta.meta_key='tracking_account_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=u.ID AND umtc.meta_key='tracking_contact_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umcp ON umcp.user_id=u.ID AND umcp.meta_key='child_partner'
				LEFT JOIN {$wpdb->prefix}posts as child_part ON child_part.ID=umcp.meta_value
				LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=u.ID AND ump.meta_key='partner'
				LEFT JOIN {$wpdb->prefix}posts as part ON part.ID=ump.meta_value

				WHERE u.ID IN(" . implode(', ', array_fill(0, count($userIds), '%d')) . ")",
				$userIds
			),
			OBJECT_K
		);

		$posts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID as post_id,
					p.post_title,
					t2.name as category, 
					t.name as sub_category,
					format.name

				FROM {$wpdb->prefix}posts as p		  
				INNER JOIN {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID
				INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy='category' AND tt.parent!=0
				INNER JOIN {$wpdb->prefix}terms as t ON t.term_id=tt.term_id
				INNER JOIN {$wpdb->prefix}terms as t2 ON t2.term_id=tt.parent

				LEFT JOIN (
				  SELECT tr.object_id, t.name 
				  FROM {$wpdb->prefix}term_relationships as tr
				  LEFT JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id=tt.term_taxonomy_id
				  LEFT JOIN {$wpdb->prefix}terms as t ON t.term_id=tt.term_id
				  WHERE tt.taxonomy='resource_format'
				  GROUP BY tr.object_id ) as format ON format.object_id=p.ID

				WHERE p.post_status='publish' AND p.ID IN(" . implode(', ', array_fill(0, count($resource_ids), '%d')) . ")",
				$resource_ids
			),
			OBJECT_K
		);

		$results = array();
		foreach ($tracked as $key => $item) {
			// do not add result if user or post information does not exist
			if(!isset($users[$item->user_id])){
				continue;
				$users[$item->user_id] = (object)[];
				$users[$item->user_id]->user_email = '';
				$users[$item->user_id]->reporting_role = '';
				$users[$item->user_id]->first_name = '';
				$users[$item->user_id]->last_name = '';
				$users[$item->user_id]->full_name = '';
				$users[$item->user_id]->company = '';
				$users[$item->user_id]->tracking_account_id = '';
				$users[$item->user_id]->tracking_contact_id = '';
				$users[$item->user_id]->child_partner = '';
				$users[$item->user_id]->child_partner_title = '';
				$users[$item->user_id]->partner = '';
			}

			if(!isset($posts[$item->resource_id])){
				continue;
				$posts[$item->resource_id] = (object)[];
				$posts[$item->resource_id]->category = '';
				$posts[$item->resource_id]->sub_category = '';
				$posts[$item->resource_id]->post_title = $item->resource_id;
				$posts[$item->resource_id]->name = '';
			}

			$results[] = array(
				'resource_id' => $item->resource_id,
				'category' => $posts[$item->resource_id]->category,
				'sub_category' => $posts[$item->resource_id]->sub_category,
				'post_title' => $posts[$item->resource_id]->post_title,
				'name' => $posts[$item->resource_id]->name,
				'user_id' => $item->user_id,
				'user_email' => $users[$item->user_id]->user_email,
				'reporting_role' => $users[$item->user_id]->reporting_role,
				'first_name' => $users[$item->user_id]->first_name,
				'last_name' => $users[$item->user_id]->last_name,
				'full_name' => $users[$item->user_id]->full_name,
				'company' => $users[$item->user_id]->company,
				'time' => $item->time,
				'lang_code' => $item->lang_code,
				'tracking_account_id' => $users[$item->user_id]->tracking_account_id,
				'tracking_contact_id' => $users[$item->user_id]->tracking_contact_id,
				'child_partner' => $users[$item->user_id]->child_partner,
				'child_partner_title' => $users[$item->user_id]->child_partner_title,
				'partner' => $users[$item->user_id]->partner
			);
		}


		return $results;
	}

	function generate_user_usage_csv_data()
	{

		$separator = ',';
		$csv_output = '';                                           //Assigning the variable to store all future CSV file's data

		if (IP_Helpers::is_shared_partner()) {
			$header = array(
				'User ID',
				'Account ID',
				'Contact ID',
				'Company',
				'Contact',
				'Email',
				'Role',
				'Date',
				'Language',
				'Category',
				'Sub-Category',
				'Resource ID',
				'Resource Name',
				'Type of Resource',
				// 'Child Partner'
			);
		} else {
			$header = array(
				'User ID',
				'Account ID',
				'Contact ID',
				'Company',
				'Contact',
				'Email',
				'Role',
				'Date',
				'Language',
				'Category',
				'Sub-Category',
				'Resource ID',
				'Resource Name',
				'Type of Resource',
				// 'Partner'
			);
		}

		foreach ($header as $col) {
			$csv_output = $csv_output . $col . $separator;
		}
		$csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";

		$this->set_filters();

		$values = $this->get_resources_utilization_by_user();       //This here

		if (IP_Helpers::is_shared_partner()) {
			foreach ($values as $row) {
				$fields = array(
					$row['user_id'],
					$row['tracking_account_id'],
					$row['tracking_contact_id'],
					$row['company'],
					$row['full_name'],
					$row['user_email'],
					IP_Helpers::get_user_role_title($row['reporting_role']),
					$row['time'],
					$row['lang_code'],
					$row['category'],
					$row['sub_category'],
					$row['resource_id'],
					$row['post_title'],
					$row['name'],
					// $row->child_partner_title
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}
		} else {
			foreach ($values as $row) {
				$fields = array(
					$row['user_id'],
					$row['tracking_account_id'],
					$row['tracking_contact_id'],
					$row['company'],
					$row['full_name'],
					$row['user_email'],
					IP_Helpers::get_user_role_title($row['reporting_role']),
					$row['time'],
					$row['lang_code'],
					$row['category'],
					$row['sub_category'],
					$row['resource_id'],
					$row['post_title'],
					$row['name'],
					// $row->partner
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}
		}

		return $csv_output; //Back to constructor

	}
}
