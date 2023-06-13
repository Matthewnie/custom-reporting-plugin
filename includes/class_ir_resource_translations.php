<?php

defined('ABSPATH') or die('Nope, not accessing this');
// require_once(plugin_dir_path(__FILE__) . 'util_functions.php');

class IRB_Resource_Translations
{

	private $all_users = null;
	public $user_role_permission = 'le_reports';
	public $page_slug = 'resource_translations';
	public $menu_parent = 'reporting-2';
	public $is_partner = false;
	public $langOptions = array(
		'en' => 'English',
		'es' => 'Spanish'
	);
	public $filters = array(
		'lang' => 'en'
	);

	function __construct()
	{
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action('admin_init', array($this, 'admin_init'));
		add_action('admin_menu', array($this, 'reporting_menu'));
	}

	function reporting_menu()
	{
		add_submenu_page($this->menu_parent, 'Resource Translations', 'Resource Translations', $this->user_role_permission, $this->page_slug, array($this, 'resource_translations'));
	}

	function admin_init()
	{
		if (isset($_GET['page']) && $_GET['page'] == $this->page_slug && isset($_GET['download_csv'])) {
			if (!current_user_can($this->user_role_permission)) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
			$this->set_filters();
			IRB_Util::output_csv_file('custom-resource-translations-'.$this->filters['lang'], $this->generate_csv());
		}
	}

	function set_filters()
	{
		if (isset($_GET['lang']) && $_GET['lang']) {
			$this->filters['lang'] = $_GET['lang'];
		}
	}

	function resource_translations()
	{
		if (!current_user_can($this->user_role_permission)) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$this->set_filters();

		require_once plugin_dir_path(__FILE__) . 'class_ir_resource_translations_table.php';

		$results = $this->get_resource_translations('array');

		$resourceTable = new IRB_Resource_Translations_Table($results);


		echo '<div class="wrap">';
		echo '<h3>Resource Translations</h3>';
		echo '<a href="' . admin_url('admin.php?page=' . $this->page_slug . '&download_csv=true&' . http_build_query($this->filters)) . '" target="_blank" class="button" style="float: right;">Export CSV</a>';
?>
		<form action="<?php echo admin_url('admin.php'); ?>">
			<input type="hidden" name="page" value="<?php echo $this->page_slug; ?>" />
			<h3>Filter Results</h3>
			<div class="row">
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

	private function get_resource_translations($output = 'object')
	{
		global $wpdb, $user_ID;

			$query = 
			"SELECT p.ID as resource_id, p.post_title, p.post_modified, t.language_code as language, tt.element_id as translation_post_id, pt.post_title as translation_title, pt.post_modified as translation_modified, pt.post_status as translation_status
				FROM {$wpdb->prefix}posts as p
				LEFT JOIN {$wpdb->prefix}icl_translations as t ON t.element_id=p.ID && t.element_type='post_post'
				LEFT JOIN {$wpdb->prefix}icl_translations tt ON t.trid = tt.trid && t.language_code!=tt.language_code && tt.element_type='post_post'
				LEFT JOIN {$wpdb->prefix}posts as pt ON pt.ID=tt.element_id
				WHERE p.post_type='post' && p.post_status='publish' && t.language_code='{$this->filters['lang']}'";

		$results = $wpdb->get_results($query, ($output === 'array' ? 'ARRAY_A' : OBJECT));

		return $results;
	}

	function generate_csv()
	{

		$separator = ',';
		$csv_output = '';                                           //Assigning the variable to store all future CSV file's data

		$header = array(
			'Resource Id',
			'Title',
			'Language',
			'Modified',
			'Translation ID',
			'Translation Title',
			'Modified',
			'Status'
		);

		foreach ($header as $col) {
			$csv_output = $csv_output . $col . $separator;
		}
		$csv_output = substr($csv_output, 0, -1);               //Removing the last separator, because thats how CSVs work

		$csv_output .= "\r\n";

		$values = $this->get_resource_translations();       //This here

		foreach ($values as $row) {
			$fields = array(
				$row->resource_id,
				$row->post_title,
				$row->language,
				$row->post_modified ? date('Y-m-d', strtotime($row->post_modified)) : '',
				$row->translation_post_id,
				$row->translation_title,
				$row->translation_modified ? date('Y-m-d', strtotime($row->translation_modified)) : '',
				$row->translation_status,
			);                  //Getting rid of the keys and using numeric array to get values
			$csv_output .= IRB_Util::arrayToCsvLine($fields);      //Generating string with field separator
			$csv_output .= "\r\n";    //Yeah...
		}

		return $csv_output; //Back to constructor

	}
}
