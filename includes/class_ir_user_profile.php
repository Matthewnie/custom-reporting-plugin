<?php 

defined( 'ABSPATH' ) or die( 'Nope, not accessing this' );

class IRB_User_Profile {

	function __construct(){		
		add_action( 'show_user_profile', array($this, 'user_profile_tracking'), 100 );
		add_action( 'edit_user_profile', array($this, 'user_profile_tracking'), 100 );
 	}

 	public function user_profile_tracking( $user ) {
 		if ( !current_user_can( 'le_reports' ) && !current_user_can( 'le_partner_reports' ) )  {
			return false;
		}

		$data = $this->get_resources_utilization_for_user($user);
		echo '<h3>User Summary</h3>';
		echo '<table class="user-profile-reporting striped">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>Total Hours</th>';
					echo '<th># of Unique Resources Used</th>';
					echo '<th># Resource Views</th>';
					echo '<th>Days Logged In</th>';
					echo '<th>Date Account Created</th>';
					echo '<th>Date Last Accessed</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				echo '<tr>';
					echo '<td style="text-align: center;">'.get_user_meta($user->ID, 'time_in_portal', true).' hours</td>';
					echo '<td style="text-align: center;">'.$this->get_number_of_resources_utilized_by_user($user).'</td>';
					echo '<td style="text-align: center;">'.$this->get_number_of_resource_views_by_user($user).'</td>';
					echo '<td style="text-align: center;">'.get_user_meta($user->ID, 'le_days_accessed', true).' days</td>';
					echo '<td style="text-align: center;">'.date('m/d/Y', strtotime($user->user_registered)).'</td>';
					echo '<td style="text-align: center;">'.(get_user_meta($user->ID, 'le_date_last_accessed', true) ? date('m/d/Y',get_user_meta($user->ID, 'le_date_last_accessed', true)) : '00/00/0000').'</td>';
				echo '</tr>';
			echo '</tbody>';
		echo '</table>';

    echo '<h3>Last 25 Resources Utilized</h3>';
		echo '<table class="user-profile-reporting striped">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>Date</th>';
					echo '<th>Category</th>';
					echo '<th>Sub-Category</th>';
					echo '<th>Resource Name</th>';
					echo '<th>Type of Resource</th>';
					echo '<th>Language</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
				if(!$data){
					echo '<tr><td colspan="5">No resources found</td></tr>';
				}
				else {
					foreach ($data as $key => $row) {
						echo '<tr>';
							echo '<td>'.$row->time.'</td>';
							echo '<td>'.$row->category.'</td>';
							echo '<td>'.$row->sub_category.'</td>';
							echo '<td>'.$row->post_title.'</td>';
							echo '<td>'.$row->type.'</td>';
							echo '<td>'.$row->language.'</td>';
						echo '</tr>';
					}
				}
			echo '</tbody>';
		echo '</table>';
		echo '<style>.user-profile-reporting { border: 1px solid black; max-width: 700px; width: 100%; background-color: #fff;}.user-profile-reporting th, .user-profile-reporting td {padding: 5px;}</style>';
	}

	private function get_resources_utilization_for_user($user){
		global $wpdb;
		$results = $wpdb->get_results( 
			"SELECT DATE_FORMAT(urt.time, '%m/%d/%Y') as time,
        t2.name as category, 
				t.name as sub_category, 
				p.post_title, 
				format.name as type,
				urt.lang_code as language

				FROM {$wpdb->prefix}le_user_resource_tracking as urt
				RIGHT JOIN {$wpdb->prefix}posts as p ON urt.post_id=p.ID
				INNER JOIN {$wpdb->prefix}users as u ON u.ID=urt.user_id

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

				WHERE p.post_status='publish' AND urt.user_id = ".$user->ID."
				ORDER BY urt.time DESC
				LIMIT 25"
			, OBJECT );

		return $results;
	}
	
	private function get_number_of_resources_utilized_by_user($user){
		global $wpdb;
		$results = $wpdb->get_results( 
			"SELECT post_id
				FRO{$wpdb->prefix}le_user_resource_tracking
				WHERE user_id=".$user->ID." AND post_id<>'cac'
				GROUP BY post_id"
			, 'ARRAY_A' );

		return count($results);
	}

	private function get_number_of_resource_views_by_user($user){
		global $wpdb;
		$results = $wpdb->get_results( 
			"SELECT post_id
				FROM {$wpdb->prefix}le_user_resource_tracking
				WHERE user_id=".$user->ID." AND post_id<>'cac'"
			, 'ARRAY_A' );

		return count($results);
	}

}