<?php
use CRM_Nihrprototype_ExtensionUtil as E;

/**
 * NbrVolunteerCase.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nbr_volunteer_case_Create_spec(&$spec) {
  $spec['project_id'] = array(
    'name' => 'project_id',
    'title' => 'project_id',
    'description' => 'Internal ID of the project',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['contact_id'] = array(
    'name' => 'contact_id',
    'title' => 'contact_id',
    'description' => 'Internal ID of the Volunteer',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['case_type'] = array(
    'name' => 'case_type',
    'title' => 'Case type',
    'description' => 'Case type (recruitment or participation)',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_STRING,
  );
  $spec['recall_group'] = array(
    'name' => 'recall_group',
    'title' => 'Recall group',
    'description' => 'Recall Group',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_STRING,
  );
}

/**
 * NbrVolunteerCase.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nbr_volunteer_case_Create($params) {
  $validTypes = ['participation', 'recruitment'];
  $type = strtolower($params['case_type']);
  if (!in_array($type, $validTypes)) {
    throw new API_Exception('invalid case type')     ;
  }
  if ($type == 'participation' && !isset($params['project_id'])){
    throw new API_Exception('project ID required for participation');
  }
  $pv = new CRM_Nihrbackbone_NbrVolunteerCase($params);
  $newCaseId = $pv->createRecruitmentVolunteerCase($params['contact_id']);
  return $newCaseId;
}
