<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Distance.Calculate API specification (optional)  JB 18/12/19
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/

function _civicrm_api3_distance_Calculate_spec(&$spec) {
  $spec['magicword']['api.required'] = 1;
}
*/
function _civicrm_api3_distance_Calculate(&$spec) {

  $spec['postcode'] = [
    'api.required' =>1,
    'name' => 'postcode',
    'title' => 'postcode',
    'type' => CRM_Utils_Type::T_STRING,
  ];

}

/**
 * Distance.Calculate API
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
  $distance = round($data->route->distance);
  # it would seem that some postcodes are unknown to mapquest - if so drop last char of $from and try again ..
  if ($distance == 0) {
    $from1 = substr($from, 0, -1);
    $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from1.'&to='.$to.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
    $data = file_get_contents($url);
    $data = json_decode($data);
    $distance = round($data->route->distance);
  }
  # if that does not work - drop last char of $to and try again ..
  if ($distance == 0) {
    $to1 = substr($to, 0, -1);
    $url = 'http://www.mapquestapi.com/directions/v2/route?key=ge1sXrGxbNAcYEreGTxWFV8PAT0m7UWA&from='.$from.'&to='.$to1.'&outFormat=json&unit=Miles&routeType=shortest&locale=en_GB';
    $data = file_get_contents($url);
    $data = json_decode($data);
    $distance = round($data->route->distance);
  }

  return $distance;

}


