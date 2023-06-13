<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class IRB_Salesforce_Report {

	public $user_ids = null;
	public $user_role_permission = 'le_reports';
	public $is_partner = false;
	public $page_slug = 'sf';

	//Filters for admin view
	private $partner_id = false;
	private $start_date;
	private $end_date;
	public $filters = array(
		'start_date' =>null,
		'end_date' => null,
		'partner_id' => null
	);

	function __construct(){
		$this->page_slug = IRB_Util::add_menu_item_prefix($this->page_slug);
		
		add_action( 'admin_menu', array($this, 'reporting_menu') );

		add_action( 'daily_salesforce_report_email',  array($this, 'salesforce_report_email') );
 	}

 	function reporting_menu() {
		add_menu_page( 'Salesforce', 'Salesforce', $this->user_role_permission, $this->page_slug, array($this, 'admin_view'), 'dashicons-chart-area' );
	}

	function salesforce_report_email(){
		$hour = date("G");
		if($hour < 6 || $hour > 9){
			return false;
		}

		$queue = $this->build_queue();
		$result = $this->send_queue($queue);
		return $result;
	}

	function build_queue(){
		$queue = array();

		// get partners scheduled to send today
		$today = date('Y-m-d', strtotime('today'));

		$partners = get_posts(array(
			'numberposts'	=> 1,
			'post_type'		=> 'partner',
			'meta_query'	=> array(
				'relation'		=> 'AND',
				array(
					'key'	 	=> 'recieve_salesforce_emails',
					'value'	  	=> '1',
					'compare' 	=> '=',
				),
				array(
					'key'	  	=> 'salesforce_reports_email',
					'value'	  	=> array(''),
					'compare' 	=> 'NOT IN',
				),
				array(
					'relation'	=> 'OR',
					array(
						'key'			=> 'salesforce_notification_next_date',
						'value'		=> $today,
						'compare'	=> '=',
						'type' => 'DATE'
					),
					array(
						'key'			=> 'salesforce_notification_next_date',
						'value'		=> 'daily-2021-05',
						'compare'	=> '='
					),
					array(
						'key'			=> 'salesforce_notification_next_date',
						'compare'	=> 'NOT EXISTS'
					)
				)
			),
		));

		if($partners){
			foreach ($partners as $partner) {
				$partner_id = $partner->ID;
				$partner_email = get_field('salesforce_reports_email', $partner_id);
				if(!$partner_email){
					continue;
				}

				// daily, weekly, monthly
				$interval = get_field('salesforce_notification_interval', $partner_id);
				// next date to send
				$nextDate = '';
				// first day to pull records for
				$startDate = '';
				// last day to pull records for
				$endDate = '';

				if(stripos($interval, 'daily-') !== false){
					$date = str_replace('daily-', '', $interval);
					$nextDate = date('Y-m-d', strtotime('tomorrow'));
					$startDate = date('Y-m-d', strtotime($date));
					$endDate = date('Y-m-d', strtotime('-2 days'));

					update_post_meta($partner_id, 'salesforce_notification_interval', 'daily');
				}
				else {
					switch($interval){
						case 'daily':
							$nextDate = date('Y-m-d', strtotime('tomorrow'));
							$startDate = date('Y-m-d', strtotime('yesterday'));
							$endDate = $startDate;
							break;
						case 'weekly':
							$dayOfWeek = get_field('salesforce_day_of_week', $partner_id);
							$nextDate = date('Y-m-d', strtotime('next '.$dayOfWeek));
							$startDate = date('Y-m-d', strtotime('last week'));
							$endDate = date('Y-m-d', strtotime('yesterday'));
							break;
						case 'firstofmonth':
							$nextDate = date('Y-m-d', strtotime('first day of next month'));
							$startDate = date('Y-m-d', strtotime('first day of last month'));
							$endDate = date('Y-m-d', strtotime('last day of last month'));
							break;
	
						default:
							// daily
							$nextDate = date('Y-m-d', strtotime('tomorrow'));
							$startDate = date('Y-m-d', strtotime('yesterday'));
							$endDate = $startDate;
							break;
					}
				}

				update_post_meta($partner_id, 'salesforce_notification_next_date', $nextDate);

				$message = $this->build_xml($partner_id, $startDate, $endDate);
				if(!$message){
					continue;
				}

				$subject = 'Daily User Report';

				$queue[] = array(
					'partnerID' => $partner_id,
					'to' 				=> $partner_email,
					'message' 	=> $message,
					'nextDate' 	=> $nextDate,
					'subject'		=> $subject
				);
			}
		}

		return $queue;
	}

	function send_queue($queue){
		if($queue){
			foreach($queue as $email){
				$sent = wp_mail($email['to'], $email['subject'], $email['message']);

				if($sent){
					// Set next interval for partner
					update_post_meta($email['partnerID'], 'salesforce_notification_next_date', $email['nextDate']);
				}
			}
			return true;
		}

		return false;
	}

	function build_xml($partner_id, $startDate, $endDate, $returnObject = false) {
		// Get Client IDs for partner
		$userIds = IRB_Util::get_user_ids_by_role(array('client'), true, $partner_id);

		$users = $this->get_users($startDate, $endDate, $userIds);

		if($users){
			$usersXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><users></users>');
			foreach ($users as $key => $user) {

				$resources = $this->get_users_resources($user->user_id, $user->date);
				$resource_count = $this->get_users_total_resource_count($user->user_id, $user->date);

				$userXML = $usersXML->addChild('user');
				$userXML->addChild('user_id', $user->user_id);
				$userXML->addChild('tracking_account_id', $user->tracking_account_id ? $user->tracking_account_id : " ");
				$userXML->addChild('tracking_contact_id', $user->tracking_contact_id ? $user->tracking_contact_id : " ");
				$userXML->addChild('hours', $user->hours ? $user->hours : 0);
				$userXML->addChild('total_resources', $resource_count);
				$userXML->addChild('unique_resources', count($resources));
				$userXML->addChild('date', $user->date);
				$userXML->addChild('resources');

				if($resources){
					foreach ($resources as $key => $resource) {
						$resourceXML = $userXML->resources->addChild('resource');
						$resourceXML->addChild('post_title', htmlspecialchars($resource->post_title));
						$resourceXML->addChild('category', $resource->category);
						$resourceXML->addChild('sub_category', $resource->sub_category);
						$resourceXML->addChild('resource_type', $resource->resource_type);
						$resourceXML->addChild('language', $resource->language);
					}
				}
			}
			if($returnObject){
				return $usersXML;
			}

			return $usersXML->asXML();
		}
		else {
			return false;
		}
	}

	function get_users($startDate, $endDate, $userIds){
		global $wpdb;

		$results = $wpdb->get_results( 
			"SELECT uht.*,
				ifnull(umta.meta_value, '') as tracking_account_id,
				ifnull(umtc.meta_value, '') as tracking_contact_id
				FROM {$wpdb->prefix}le_user_hours_tracking as uht
				LEFT JOIN {$wpdb->prefix}usermeta as umta ON umta.user_id=uht.user_id AND umta.meta_key='tracking_account_id'
				LEFT JOIN {$wpdb->prefix}usermeta as umtc ON umtc.user_id=uht.user_id AND umtc.meta_key='tracking_contact_id'
				LEFT JOIN {$wpdb->prefix}usermeta as ump ON ump.user_id=uht.user_id AND ump.meta_key='partner'

				WHERE uht.date >= '{$startDate}' && uht.date <= '{$endDate}' && uht.used_resource=1 && uht.user_id IN(".implode(',', $userIds).")
				ORDER BY uht.date"
			, OBJECT );

			return $results;
	}

	function get_users_total_resource_count($user_id, $date){
		global $wpdb;

		$resource_count = $wpdb->get_var(
			"SELECT count(*)
				FROM {$wpdb->prefix}le_user_resource_tracking as urt
				WHERE urt.user_id = {$user_id} && urt.time >= '{$date} 00:00:00' && urt.time <= '{$date} 23:59:59'"
		);

		return $resource_count;
	}

	function get_users_resources($user_id, $date){
		global $wpdb;

		$results = $wpdb->get_results( 
			"SELECT DISTINCT p.post_title,
				t2.name as category, 
				t.name as sub_category,
				t3.name as resource_type,
				trans.language_code as language
				FROM {$wpdb->prefix}le_user_resource_tracking as urt
				LEFT JOIN {$wpdb->prefix}posts as p ON urt.post_id=p.ID
				LEFT JOIN {$wpdb->prefix}term_relationships as tr ON tr.object_id=p.ID
				INNER JOIN {$wpdb->prefix}term_taxonomy as tt ON tr.term_taxonomy_id=tt.term_taxonomy_id AND tt.taxonomy='category' AND tt.parent!=0
				INNER JOIN {$wpdb->prefix}terms as t ON t.term_id=tt.term_id
				INNER JOIN {$wpdb->prefix}terms as t2 ON t2.term_id=tt.parent
				LEFT JOIN {$wpdb->prefix}term_relationships as tr2 ON tr2.object_id=p.ID
				INNER JOIN {$wpdb->prefix}term_taxonomy as tt2 ON tr2.term_taxonomy_id=tt2.term_taxonomy_id AND tt2.taxonomy='resource_format'
				INNER JOIN {$wpdb->prefix}terms as t3 ON t3.term_id=tt2.term_id
				LEFT JOIN {$wpdb->prefix}icl_translations as trans ON trans.element_id=p.ID && trans.element_type='post_post'
				WHERE urt.user_id = {$user_id} && urt.time >= '{$date} 00:00:00' && urt.time <= '{$date} 23:59:59'"
			, OBJECT );

			return $results;
	}

	function set_filters(){
		if(isset($_GET['start_date']) && $_GET['start_date']){
			$this->start_date = date('Y-m-d', strtotime($_GET['start_date']));
		}
		else {
			$this->start_date = date('Y-m-d', strtotime('-1 day'));
		}
		$this->filters['start_date'] = $this->start_date;

		if(isset($_GET['end_date']) && $_GET['end_date']){
			$this->end_date = date('Y-m-d', strtotime($_GET['end_date']));
		}
		else {
			$this->end_date = date('Y-m-d', strtotime('-1 day'));
		}
		$this->filters['end_date'] = $this->end_date;

		if(isset($_GET['partner_id']) && $_GET['partner_id']){
			$this->partner_id = $_GET['partner_id'];
		}
		$this->filters['partner_id'] = $this->partner_id;
	}

	function admin_view(){
		echo '<h2>Salesforce Export View</h2>';
		echo '<p>Build XML for every active user of the day, output hours, and resources visited by user.</p>';
		echo '<p>Select Partner ID and Dates</p>';

		$this->set_filters();
		?>
			<form action="<?php echo admin_url('admin.php'); ?>">
				<input type="hidden" name="page" value="<?php echo $this->page_slug; ?>" />
				<h3>Options</h3>
				<div class="row">
				<?php 
					$partners = get_posts(array(
						'numberposts'	=> -1,
						'post_type'		=> 'partner',
						'meta_query'	=> array(
							'relation'		=> 'AND',
							array(
								'key'	 	=> 'recieve_salesforce_emails',
								'value'	  	=> '1',
								'compare' 	=> '=',
							),
							array(
								'key'	  	=> 'salesforce_reports_email',
								'value'	  	=> array(''),
								'compare' 	=> 'NOT IN',
							)
						),
						'suppress_filters' => false
					));
				?>
					<div class="field">
						<h4><label for="language">Partner</label></h4>
						<select name="partner_id" id="partner_id">
							<?php 
								foreach ($partners as $partner) {
									echo '<option value="'.$partner->ID.'" '.($this->partner_id==$partner->ID ? 'selected' : '').'>'.$partner->post_title.' - '.get_field('salesforce_notification_interval', $partner->ID).'</option>';
								}
							?>
						</select>
					</div>
					<div class="field">
						<h4>Dates Accessed</h4>
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

		if($this->partner_id){
			$message = $this->build_xml($this->partner_id, $this->start_date, $this->end_date, true);
			if(!$message){
				echo '<p>No results</p>';
			}
			elseif(isset($_GET['send_email_notification']) && $_GET['send_email_notification']=='true'){
				$subject = 'Daily User Report';

				$email = array(
					'to' 				=> get_field('salesforce_reports_email', $this->partner_id),
					'message' 	=> $message->asXML(),
					'subject'		=> $subject
				);

				$sent = wp_mail($email['to'], $email['subject'], $email['message']);

				if($sent){
					echo '<p>XML Email Sent to Partner</p>';
				}
				else {
					echo '<p>XML Email not sent, there was an error.</p>';
				}
			}
			else {
				echo '<pre>'. htmlspecialchars($this->formatXml($message)). '</pre>';
				echo '<a href="'.admin_url('admin.php?page='.$this->page_slug.'&send_email_notification=true&'.http_build_query($this->filters)).'" class="button">Send XML to Partner</a>';
			}
		}
		else {
			echo '<p>Please select a partner</p>';
		}
	}

	function formatXml($simpleXMLElement)
	{
			$xmlDocument = new DOMDocument('1.0');
			$xmlDocument->preserveWhiteSpace = false;
			$xmlDocument->formatOutput = true;
			$xmlDocument->loadXML($simpleXMLElement->asXML());

			return $xmlDocument->saveXML();
	}

}