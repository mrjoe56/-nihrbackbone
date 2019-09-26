<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrVolunteerCase.Checkmaxpart API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_project_volunteer_Checkmaxpart_spec(&$spec) {
  $spec['project_id'] = array(
    'name' => 'project_id',
    'title' => 'project_id',
    'description' => 'Internal ID of the project for which the volunteers are checked for max participation in projects in period',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * NbrVolunteerCase.Checkmaxpart API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nihr_project_volunteer_Checkmaxpart($params) {
  // todo update with case statuses once decision has been made
  // get all volunteers that have an active volunteer case (for a project if parameter was not empty)
  if (isset($params['project_id']) && !empty($params['project_id'])) {
    $query = "SELECT DISTINCT(a.contact_id) 
        FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        LEFT JOIN civicrm_value_nihr_participation_data AS c on b.id = c.entity_id
        WHERE b.case_type_id = %1 AND b.is_deleted = %2 AND b.status_id != %3 AND c."
        . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name')
        . " = %4";
    $queryParams = [
      1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
      2 => [0, 'Integer'],
      3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), 'Integer'],
      4 => [$params['project_id'], 'Integer'],
    ];
  }
  else {
    $query = "SELECT DISTINCT(a.contact_id)
    FROM civicrm_case_contact AS a JOIN civicrm_case AS b ON a.case_id = b.id
    WHERE b.case_type_id = %1 AND b.is_deleted = %2 AND b.status_id != %3";
    $queryParams = [
      1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
      2 => [0, 'Integer'],
      3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), 'Integer'],
    ];
  }
  $volunteer = CRM_Core_DAO::executeQuery($query, $queryParams);
  while ($volunteer->fetch()) {
    // remove eligible status for no of project in xxx period if necessary
    if (!CRM_Nihrbackbone_NihrVolunteer::hasMaxParticipationsNow($volunteer->contact_id)) {
      CRM_Nihrbackbone_NbrVolunteerCase::unsetMaxStatus($volunteer->contact_id);
    }
  }
  return civicrm_api3_create_success([], $params, 'NbrVolunteerCase', 'seteligible');
}
