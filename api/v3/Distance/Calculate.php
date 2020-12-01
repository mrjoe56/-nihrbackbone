<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/* Distance.Calculate API specification (optional)  JB 18/12/19 */
function _civicrm_api3_distance_Calculate(&$spec) {
  $spec['postcode'] = [
    'api.required' =>1,
    'name' => 'postcode',
    'title' => 'postcode',
    'type' => CRM_Utils_Type::T_STRING,
  ];
}

/**
 * Distance.Calculate API  JB 01/12/20
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_distance_Calculate($params) {
  $from = str_replace(' ', '', $params['postcode_from']);
  $to = str_replace(' ', '', $params['postcode_to']);
  $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from.'&to='.$to.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
  $data = file_get_contents($url);
  $data = json_decode($data);
  $max_uk = 700;

  $distance = round($data->route->distance);
  $distance = ($distance>$max_uk?0:$distance);
  $m = 'from pcode '.$from.' to pcode '.$to;

  // it would seem that some postcodes are unknown to mapquest - if so try with general area of 'from' postcode ..
  if ($distance == 0) {
    $from_area = getPostCodeArea($from);
    $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from_area.'&to='.$to.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
    $data = file_get_contents($url);
    $data = json_decode($data);
    $distance = round($data->route->distance);
    $distance = ($distance>$max_uk?0:$distance);
    $m = 'from area '.$from_area.' to pcode '.$to;
  }
  // if that does not work - try general area of 'to' postcode ..
  if ($distance == 0) {
    $to_area = getPostCodeArea($to);
    $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from.'&to='.$to_area.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
    $data = file_get_contents($url);
    $data = json_decode($data);
    $distance = round($data->route->distance);
    $distance = ($distance>$max_uk?0:$distance);
    $m = 'from pcode '.$from.' to area '.$to_area;
  }
  // finally - try general area of 'from'  and 'to' postcodes ..
  if ($distance == 0) {
    $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from_area.'&to='.$to_area.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
    $data = file_get_contents($url);
    $data = json_decode($data);
    $distance = round($data->route->distance);
    $distance = ($distance>$max_uk?0:$distance);
    $m = 'from area '.$from_area.' to area '.$to_area;
  }
  #Civi::log()->debug('dist calc by method : '.$m);
  return $distance;
}

/* return general area of a postcode */
function getPostCodeArea($pcode){
  $pcode = str_replace(' ', '', $pcode);
  if(strlen($pcode) > 4){
    if(is_numeric($pcode{strlen($pcode)-1})){
      $pcode = substr($pcode, 0, 4);
    }else{
      $pcode = substr($pcode, 0, strlen($pcode)-3);
    }
    return $pcode;
  }else{
    return $pcode;
  }
}

?>

