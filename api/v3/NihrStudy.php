<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NihrStudy.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_study_create_spec(&$spec) {
  // $spec['some_parameter']['api.required'] = 1;
}

/**
 * NihrStudy.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nihr_study_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NihrStudy.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nihr_study_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NihrStudy.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nihr_study_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}
