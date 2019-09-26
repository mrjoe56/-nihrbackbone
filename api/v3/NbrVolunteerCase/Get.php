<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrVolunteerCase.Get API specification (optional)
 *
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nbr_volunteer_case_Get_spec(&$spec) {
  $spec['project_id'] = array(
    'name' => 'project_id',
    'title' => 'project_id',
    'description' => 'Internal ID of the project',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );/**

  }

 * NbrVolunteerCase.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nbr_volunteer_case_Get($params) {
  $pv = new CRM_Nihrbackbone_NbrVolunteerCase($params['project_id']);
  return civicrm_api3_create_success($pv->getVolunteers(), $params, 'NbrVolunteerCase', 'get');
}
