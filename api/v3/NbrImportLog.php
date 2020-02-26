<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrImportLog.create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nbr_import_log_create_spec(&$spec) {
  $spec['import_id'] = [
    'name' => 'import_d',
    'title' => 'Import ID',
    'description' => 'Unique Import ID',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['filename'] = [
    'name' => 'filename',
    'title' => 'Import file',
    'description' => 'Import file',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['filename'] = [
    'name' => 'filename',
    'title' => 'Import file',
    'description' => 'Import file',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['message'] = [
    'name' => 'message',
    'title' => 'Message',
    'description' => 'Message',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['logged_date'] = [
    'name' => 'logged_date',
    'title' => 'Date Logged',
    'description' => 'Date Logged',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_DATE,
  ];
}

/**
 * NbrImportLog.create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nbr_import_log_create($params) {
  return _civicrm_api3_basic_create(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NbrImportLog.delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nbr_import_log_delete($params) {
  return _civicrm_api3_basic_delete(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NbrImportLog.get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_nbr_import_log_get($params) {
  return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

/**
 * NbrImportLog.clear API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws
 */
function civicrm_api3_nbr_import_log_clear($params) {
  // compute date 1 month ago
  $oneMonthAgo = new \DateTime('1 month ago');
  $query = "DELETE FROM civicrm_nbr_import_log WHERE logged_date < %1";
  CRM_Core_DAO::executeQuery($query, [1 => [$oneMonthAgo->format('Y-m-d'), 'String']]);
  return civicrm_api3_create_success([], $params, 'NbrImportLog', 'clear');
}
