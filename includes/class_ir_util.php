<?php 

class IRB_Util {

  public static $menu_item_prefix = 'irb_';

  public static function add_menu_item_prefix($slug){
    return IRB_Util::$menu_item_prefix . $slug;
  }

  public static function get_user_partner_id(){
    global $user_ID;

    $partner_id = get_user_meta($user_ID, 'partner', true);

    return $partner_id;
  }

  // Get User IDs by role
  // Supply an empty sting for all Users
  // Supply a single role as a sting, or multiple roles an array
  // If the users need to be limited by a partner bool
  // partner_id can be supplied, else false
  public static function get_user_ids_by_role_legacy($role = array('client'), $partner = false, $partner_id = false){
    global $user_ID;
    
    $args = array(
      'fields' => 'ID',
      'orderby'=> 'ID'
      );
    
    if($partner){
      // If no partner_id was supplied, get id based on user.
      if(!$partner_id){
        $partner_id = get_user_meta($user_ID, 'partner', true);
      }
      $partner_ids = IP_Helpers::get_all_translation_ids_for_partner($partner_id);
      $args['meta_key'] = 'partner';
      $args['meta_value'] = $partner_ids;
      $args['meta_compare'] = 'IN';
    }
    else {
      $partner_ids = IRB_Util::get_partner_ids_to_not_include();

      $args['meta_key'] = 'partner';
      $args['meta_value'] = $partner_ids;
      $args['meta_compare'] = 'NOT IN';
    }

    if(is_array($role)){
      $args['role__in'] = $role;
    }
    else if( $role ) {
      $args['role__in'] = array($role);
    }

    $additional_roles = array();
    if(isset($args['role__in'])){
      foreach ($args['role__in'] as $key => $role) {
        if($role == 'partner_owner'){
          $additional_roles[] = 'partner_owner_no_content';
        }
        else if($role == 'partner_admin'){
          $additional_roles[] = 'partner_admin_no_content';
        }
      }
      $args['role__in'] = array_merge($args['role__in'], $additional_roles);
    }

    $users = get_users( $args );


    return $users;
  }

  public static function get_user_ids_by_role($roles = array('client'), $partner = false, $partner_id = false){
    global $user_ID;
    
    if(!is_array($roles)){
      $roles = array($roles);
    }

    $args = array(
      'fields' => 'ID',
      'orderby'=> 'ID'
      );

    $args['meta_query'] = array(
      'relation', 'AND',
      array(
        'key' => 'le_role',
        'compare' => 'IN',
        'value' => $roles
      )
    );
    
    if($partner){
      // If no partner_id was supplied, get id based on user.
      if(!$partner_id){
        $partner_id = get_user_meta($user_ID, 'partner', true);
      }
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

  

    $users = get_users( $args );

    return $users;
  }

  public static function get_partner_ids_to_not_include(){
    $partner_ids = [];
    //filter out partners that are not supposed to be in reporting.
    $partners_to_not_include = get_posts(array(
      'posts_per_page' => -1,
      'post_type' => 'partner',
      'meta_key' => 'show_in_reporting',
      'meta_value' => 0,
      'fields' => 'ID'
    ));

    if($partners_to_not_include){
      foreach ($partners_to_not_include as $key => $partner) {
        $partner_ids[] = $partner->ID;
      }
    }
    return $partner_ids;
  }

  // Remove WPML capabilities from the list for clean output
  public static function removeWPML($array){
    foreach( $array as $key => $value ) {
      if( strpos( $key, 'wpml_' ) === 0 ) {
        unset( $array[ $key ] );
      }
    }
    return $array;
  }


  public static function output_csv_file($filename='csv_file', $data) {		
    $generatedDate = date('d-m-Y_His');                         //Date will be part of file name. I dont like to see ...(23).csv downloaded

    $csvFile = $data;                                          //Getting the text generated to download
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);                    //Forces the browser to download
    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=\"" . $filename . "_" . $generatedDate . ".csv\";" );
    header("Content-Transfer-Encoding: binary");

    echo $csvFile;                                              //Whatever is echoed here will be in the csv file
    exit;

  }
    
  public static function arrayToCsvLine(array $values) {
    $line = '';

    $values = array_map(function ($v) {
        return '"' . str_replace('"', '""', $v) . '"';
    }, $values);

    $line .= implode(',', $values);

    return $line;
  }

  public static function csvstr(array $fields): string
  {
    $f = fopen('php://memory', 'r+');
    if (fputcsv($f, $fields) === false) {
      return false;
    }
    rewind($f);
    $csv_line = stream_get_contents($f);
    return rtrim($csv_line);
  }

  public static function floorToFraction($number, $denominator = 1)
  {
    $x = $number * $denominator;
    $x = floor($x);
    $x = $x / $denominator;
    return $x;
  }

  // If the time passed into = 0 return 0
  // Else format into the fraction desired
  // If the fraction returns 0 round up to a quarter
  public static function formatTimeInPortal($time){
    if($time == 0) {
      return 0;
    }

    $fraction = IRB_Util::floorToFraction( $time, 4);
    if($fraction == 0){
      return '0.25';
    }
    else {
      return $fraction;
    }
  }
}