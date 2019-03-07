<?php
use CRM_Nihrprototype_ExtensionUtil as E;

/**
 * NihrProjectVolunteer.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_project_volunteer_Create_spec(&$spec) {
  $spec['project_id'] = array(
    'name' => 'project_id',
    'title' => 'project_id',
    'description' => 'Internal ID of the project',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
  $spec['contact_id'] = array(
    'name' => 'contact_id',
    'title' => 'contact_id',
    'description' => 'Internal ID of the Volunteer',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * NihrProjectVolunteer.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nihr_project_volunteer_Create($params) {
  $pv = new CRM_Nihrprototype_NihrProjectVolunteer($params['project_id']);
  return civicrm_api3_create_success($pv->createProjectVolunteer($params['contact_id']), $params, 'NihrProjectVolunteer', 'create');
}
