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
  $spec['id'] = [
    'name' => 'id',
    'title' => 'NIHR Study ID',
    'description' => 'Unique ID for NIHR Study',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['study_number'] = [
    'name' => 'study_number',
    'title' => 'Study Number',
    'description' => 'Study Number',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['investigator_id'] = [
    'name' => 'investigator_id',
    'title' => 'Investigator ID',
    'description' => 'Contact ID of the Principal Investigator',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['short_name'] = [
    'name' => 'short_name',
    'title' => 'Short Name',
    'description' => 'Study Short Name',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['long_name'] = [
    'name' => 'long_name',
    'title' => 'Long Name',
    'description' => 'Study Long Name',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['description'] = [
    'name' => 'description',
    'title' => 'Description',
    'description' => 'Study Description',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['ethics_number'] = [
    'name' => 'ethics_number',
    'title' => 'Ethics Number',
    'description' => 'Study Ethics Number',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['ethics_approved_id'] = [
    'name' => 'ethics_approved_id',
    'title' => 'Ethics Approved',
    'description' => 'Study Ethics Approved',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['ethics_approved_date'] = [
    'name' => 'ethics_approved_date',
    'title' => 'Ethics Approved Date',
    'description' => 'Study Ethics Approved Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['requirements'] = [
    'name' => 'requirements',
    'title' => 'Requirements',
    'description' => 'Study Requirements',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['valid_start_date'] = [
    'name' => 'valid_start_date',
    'title' => 'Valid Start Date',
    'description' => 'Valid Start Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['valid_end_date'] = [
    'name' => 'valid_end_date',
    'title' => 'Valid End Date',
    'description' => 'Valid End Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['centre_study_origin_id'] = [
    'name' => 'centre_study_origin_id',
    'title' => 'Centre Study Origin',
    'description' => 'Centre where Study Originated from',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['notes'] = [
    'name' => 'notes',
    'title' => 'Notes',
    'description' => 'Study Notes',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['status_id'] = [
    'name' => 'status_id',
    'title' => 'Study Status',
    'description' => 'Study Status',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
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
 * NihrStudy.delete API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_study_delete_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'NIHR Study ID',
    'description' => 'Unique ID for NIHR Study',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  ];
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
 * NihrStudy.get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_study_get_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'NIHR Study ID',
    'description' => 'Unique ID for NIHR Study',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['investigator_id'] = [
    'name' => 'investigator_id',
    'title' => 'Investigator ID',
    'description' => 'Contact ID of the Principal Investigator',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['short_name'] = [
    'name' => 'short_name',
    'title' => 'Short Name',
    'description' => 'Study Short Name',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['long_name'] = [
    'name' => 'long_name',
    'title' => 'Long Name',
    'description' => 'Study Long Name',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['description'] = [
    'name' => 'description',
    'title' => 'Description',
    'description' => 'Study Description',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['ethics_number'] = [
    'name' => 'ethics_number',
    'title' => 'Ethics Number',
    'description' => 'Study Ethics Number',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['ethics_approved_id'] = [
    'name' => 'ethics_approved_id',
    'title' => 'Ethics Approved',
    'description' => 'Study Ethics Approved',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['ethics_approved_date'] = [
    'name' => 'ethics_approved_date',
    'title' => 'Ethics Approved Date',
    'description' => 'Study Ethics Approved Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['requirements'] = [
    'name' => 'requirements',
    'title' => 'Requirements',
    'description' => 'Study Requirements',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['valid_start_date'] = [
    'name' => 'valid_start_date',
    'title' => 'Valid Start Date',
    'description' => 'Valid Start Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['valid_end_date'] = [
    'name' => 'valid_end_date',
    'title' => 'Valid End Date',
    'description' => 'Valid End Date',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['centre_study_origin_id'] = [
    'name' => 'centre_study_origin_id',
    'title' => 'Centre Study Origin',
    'description' => 'Centre where Study Originated from',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['notes'] = [
    'name' => 'notes',
    'title' => 'Notes',
    'description' => 'Study Notes',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['status_id'] = [
    'name' => 'status_id',
    'title' => 'Study Status',
    'description' => 'Study Status',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['created_date'] = [
    'name' => 'created_date',
    'title' => 'Created Date',
    'description' => 'Date Study Created',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['created_by_id'] = [
    'name' => 'created_by_id',
    'title' => 'Created By',
    'description' => 'Study Created By',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['modified_date'] = [
    'name' => 'modified_date',
    'title' => 'Modified Date',
    'description' => 'Date Study Modified',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['modified_by_id'] = [
    'name' => 'modified_by_id',
    'title' => 'Modified By',
    'description' => 'Study Modified By',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  ];
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
