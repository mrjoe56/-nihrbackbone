<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/* Distance.Calculate API specification (optional)  JB 17/10/22 */
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
  if (postcode_valid($from) && postcode_valid($to)) {
    $mapApiKey = (string) Civi::settings()->get('nbr_distance_lookup_api_key');
    $geoloc_from = getGeolocation($from, $mapApiKey);
    $geoloc_to = getGeolocation($to, $mapApiKey);
    $distance = round(getDistance($geoloc_from, $geoloc_to, $mapApiKey));
  }
  else {
    $distance = '0';
  }
  return $distance;
}

function postcode_valid($postcode) {
  return preg_match('/^([A-Za-z][A-Ha-hJ-Yj-y]?[0-9][A-Za-z0-9]? ?[0-9][A-Za-z]{2}|[Gg][Ii][Rr] ?0[Aa]{2})$/', $postcode);
}

function getDistance($geoloc_from, $geoloc_to, $mapApiKey) {
  $url = 'http://dev.virtualearth.net/REST/v1/Routes/DistanceMatrix?origins='.implode(',',$geoloc_from).'&destinations='.implode(',',$geoloc_to).'&travelMode=driving&DistanceUnit=mile&key='.$mapApiKey;
  $apidata = file_get_contents($url);
  $dataArray = json_decode($apidata, true);
  $distance = $dataArray['resourceSets'][0]['resources'][0]['results'][0]['travelDistance'];
  return($distance);
}

function getGeolocation($pcode, $mapApiKey) {
  $pcode = str_replace(' ', '', $pcode);
  $url = 'http://dev.virtualearth.net/REST/v1/locations?countryRegion=UK&postalcode='.$pcode.'&maxResults=1&key='.$mapApiKey;
  $apidata = file_get_contents($url);
  $dataArray = json_decode($apidata, true);
  $coords = $dataArray['resourceSets'][0]['resources'][0]['point']['coordinates'];
  return($coords);
}

?>

