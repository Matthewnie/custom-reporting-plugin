<?php

defined('ABSPATH') or die('Nope, not accessing this');

class IRB_Resource_Usage
{

	private $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'resource_util';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $start_date = null;
	public $end_date = null;
	public $roles = array('client');
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
		add_submenu_page($this->menu_parent, 'Resource List', 'Resource List', $this->user_role_permission, $this->page_slug, array($this, 'resource_util'));
	}

	function admin_init()
	{
		if (isset($_GET['page']) && $_GET['page'] == $this->page_slug && isset($_GET['download_csv'])) {
			if (!current_user_can($this->user_role_permission)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			$this->set_filters();
			IRB_Util::output_csv_file('custom-resources-utilization', $this->generate_csv());
		}
	}

	function set_filters()
	{
		if (isset($_GET['start_date']) && $_GET['start_date']) {
			// Make sure supplied date is in necessary format
			$this->filters['start_date'] = date('Y-m-d', strtotime($_GET['start_date']));
		} else {
			// $start_date = date('Y-m-d', strtotime('-60 days'));
		}

		if (isset($_GET['end_date']) && $_GET['end_date']) {
			$this->filters['end_date'] = date('Y-m-d', strtotime($_GET['end_date']));
		}

		if (isset($_GET['roles']) && $_GET['roles']) {
			$this->filters['roles'] = $_GET['roles'];
		}
		if (isset($_GET['lang']) && $_GET['lang']) {
			$this->filters['lang'] = $_GET['lang'];
		}
	}

	function resource_util()
	{
		if (!current_user_can($this->user_role_permission)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$this->set_filters();

		require_once plugin_dir_path(__FILE__) . 'class_ir_resource_usage_table.php';

		$results = $this->get_resources_utilization('array');

		$resourceTable = new IRB_Resource_Usage_Table($results, $this->is_partner);


		echo '<div class="wrap">';
		echo '<h3>Resource Usage</h3>';
		echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&download_csv=true&' . http_build_query($this->filters)) . '" target="_blank" class="button" style="float: right;">Export CSV</a>';
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
						echo '<input type="checkbox" name="roles[]" value="' . $role . '" 
										id="role-' . $role . '" ' . (in_array($role, $this->filters['roles']) ? 'checked' : '') . '/>';
						echo '<label for="role-' . $role . '">' . $title . '</label>';
						echo '</div>';
					}
					?>
					<!-- </select> -->
				</div>
				<div class="field">
					<h4>Language</h4>
					<select name="lang" id="lnag">
						<?php
						foreach ($this->langOptions as $lang => $title) {
							echo '<option value="' . $lang . '" ' . ($lang == $this->filters['lang'] ? 'selected' : '') . '>' . $title . '</option>';
						}
						?>
					</select>
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
		$resourceTable->prepare_items();
		echo '<form id="events-filter" method="get">';
		echo '<input type="hidden" name="page" value="' . $_REQUEST['page'] . '" />';
		$resourceTable->display();
		echo '</form>';
		echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&download_csv=true&' . http_build_query($this->filters)) . '" target="_blank" class="button">Export CSV</a>';
		echo '</div>';
	}

	public function get_user_ids_by_role()
	{
		return IRB_Util::get_user_ids_by_role($this->filters['roles'], $this->is_partner);
	}

	private function get_resources_utilization($output = 'object')
	{
		global $wpdb, $user_ID;

		$userIds = $this->get_user_ids_by_role();

		$partner_filter = '';
		if ($this->is_partner) {
			$partner_id = get_user_meta($user_ID, 'partner', true);
			$partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
			$partner_filter = "AND (Partner.partner_id IS NULL OR Partner.partner_id IN (0," . implode(',', $partner_ids) . "))";
		}
		else {
			$partner_ids_exclude = IRB_Util::get_partner_ids_to_not_include();
			$partner_filter = "AND (Partner.partner_id IS NULL OR Partner.partner_id NOT IN (" . implode(',', $partner_ids_exclude) . "))";
		}

		$filterBy = '';
		if ($this->filters['start_date'] && $this->filters['end_date']) {
			$filterBy .= " AND (urt.time >= '" . $this->filters['start_date'] . "' AND urt.time <= '" . $this->filters['end_date'] . "')";
		} else if ($this->filters['start_date']) {
			$filterBy .= " AND (urt.time >= '" . $this->filters['start_date'] . "')";
		} else if ($this->filters['end_date']) {
			$filterBy .= " AND (urt.time <= '" . $this->filters['end_date'] . "')";
		}

		if ($this->filters['lang']) {
			$filterBy .= " AND (urt.lang_code= '" . $this->filters['lang'] . "')";
		}

		if (!$userIds) {
			$query = 
				"SELECT 
					p.ID as resource_id, 
					t2.name as category, 
					t.name as sub_category, 
					p.post_title, 
					p.post_excerpt, 
					0 as views, 
					B.name,
					p.post_date,
					p.post_modified,
					ifnull(Partner.partner_title, 'Partner') as partner_title,
					trans.language_code as language_code,
					ifnull(ptv.meta_value, '0') as training_views
				FROM {$wpdb->prefix}posts as p 
				LEFT JOIN {$wpdb->prefix}postmeta as ptv on ptv.post_id=p.ID and ptv.meta_key='resource_training_views'
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
					GROUP BY tr.object_id ) as B ON B.object_id=p.ID
				LEFT JOIN (
				  SELECT pm.meta_value as partner_id, posts.post_title as partner_title , pm.post_id as post_id
				  FROM {$wpdb->prefix}posts as posts
				  LEFT JOIN {$wpdb->prefix}postmeta as pm ON posts.ID=pm.meta_value
					WHERE pm.meta_key='owning_partner_partner-id' ) as Partner ON Partner.post_id=p.ID
				INNER JOIN {$wpdb->prefix}icl_translations as trans on trans.element_id=p.ID

				WHERE p.post_status='publish' AND tr.term_taxonomy_id!=1 {$filterBy} {$partner_filter}

				GROUP BY p.id
				ORDER BY p.post_modified DESC";
		}
		else {
			$query = $wpdb->prepare(
				"SELECT 
					p.ID as resource_id, 
					t2.name as category, 
					t.name as sub_category, 
					p.post_title, 
					p.post_excerpt, 
					CAST(count(urt.post_id) as UNSIGNED) as views, 
					B.name,
					p.post_date,
					p.post_modified,
					ifnull(Partner.partner_title, 'Partner') as partner_title,
					trans.language_code as language_code,
					ifnull(ptv.meta_value, '0') as training_views
				FROM {$wpdb->prefix}posts as p 
				LEFT JOIN {$wpdb->prefix}le_user_resource_tracking as urt ON urt.post_id=p.ID AND urt.user_id IN(" . implode(', ', array_fill(0, count($userIds), '%d')) . ")
				LEFT JOIN {$wpdb->prefix}postmeta as ptv on ptv.post_id=p.ID and ptv.meta_key='resource_training_views'
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
					GROUP BY tr.object_id ) as B ON B.object_id=p.ID
				LEFT JOIN (
				  SELECT pm.meta_value as partner_id, posts.post_title as partner_title , pm.post_id as post_id
				  FROM {$wpdb->prefix}posts as posts
				  LEFT JOIN {$wpdb->prefix}postmeta as pm ON posts.ID=pm.meta_value
					WHERE pm.meta_key='owning_partner_partner-id' ) as Partner ON Partner.post_id=p.ID
				INNER JOIN {$wpdb->prefix}icl_translations as trans on trans.element_id=p.ID

				WHERE p.post_status='publish' AND tr.term_taxonomy_id!=1 {$filterBy} {$partner_filter}

				GROUP BY p.id
				ORDER BY p.post_modified DESC",
				$userIds
			);
		}

		$results = $wpdb->get_results($query, ($output === 'array' ? 'ARRAY_A' : OBJECT));

		return $results;
	}

	function generate_csv()
	{

		$separator = ',';
		$csv_output = '';                                           //Assigning the variable to store all future CSV file's data

			$header = array(
				'Resource Id',
				'Category',
				'Sub Category',
				'Resource Name',
				'Excerpt',
				'Type of Resource',
				'# Views',
				'Partner',
				'Created',
				'Modified',
				'Language',
				'Training Views'
			);
		

		foreach ($header as $col) {
			$csv_output = $csv_output . $col . $separator;
		}
		$csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";

		$values = $this->get_resources_utilization();       //This here

			foreach ($values as $row) {
				$fields = array(
					$row->resource_id,
					$row->category,
					$row->sub_category,
					$row->post_title,
					$row->post_excerpt,
					$row->name,
					$row->views,
					$row->partner_title,
					date('Y-m-d', strtotime($row->post_date)),
					date('Y-m-d', strtotime($row->post_modified)),
					$row->language_code,
					$row->training_views
				);                  //Getting rid of the keys and using numeric array to get values
				$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
				$csv_output .= "\r\n";    //Yeah...
			}


		return $csv_output; //Back to constructor

	}
}
