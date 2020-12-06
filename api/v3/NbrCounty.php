<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrCounty.create API specification (optional).
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_nbr_county_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * NbrCounty.create API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_county_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params, 'NbrCounty');
}

/**
 * NbrCounty.delete API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_county_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NbrCounty.get API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_county_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params, TRUE, 'NbrCounty');
}

/**
 * NbrCounty.migrate API.
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_county_migrate($params) {
  $returnValues = CRM_Nihrbackbone_BAO_NbrCounty::migrate();
  return civicrm_api3_create_success($returnValues, $params, 'NbrCounty', 'migrate');

}
