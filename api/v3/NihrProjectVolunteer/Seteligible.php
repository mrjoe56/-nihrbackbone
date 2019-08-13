<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NihrProjectVolunteer.Seteligible API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_project_volunteer_Seteligible_spec(&$spec) {
  $spec['project_id'] = array(
    'name' => 'project_id',
    'title' => 'project_id',
    'description' => 'Internal ID of the project that elibility has to be checked for',
    'api.required' => 0,
    'type' => CRM_Utils_Type::T_INT,
  );
}

/**
 * NihrProjectVolunteer.Seteligible API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nihr_project_volunteer_Seteligible($params) {
  // todo update with case statuses once decision has been made
  // todo check if we need to check more eligible statuses than max number of projects
  // get all volunteers that have an eligible status too many projects
  $query = "SELECT DISTINCT(b.contact_id)
FROM civicrm_value_nihr_participation_data AS a
LEFT JOIN civicrm_case_contact AS b ON a.entity_id = b.case_id
JOIN civicrm_case AS c ON a.entity_id = c.id
WHERE a.nvpd_eligible_status_id LIKE \"%10%\" AND c.is_deleted = 0 AND c.status_id IN (1,2,3)";
  $partQryParams = [
    1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
    2 => [0, 'Integer'],
    3 => [1, 'Integer'],
    4 => [2, 'Integer'],
    5 => [3, 'Integer'],
  ];
  $part = CRM_Core_DAO::executeQuery($partQry, $partQryParams);
  while ($part->fetch()) {
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    if ($volunteer->hasMaxParticipationsNow($part->contact_id)) {
      // add eligible status to all cases of volunteer
      $caseQry = "SELECT DISTINCT(a.case_id) FROM civicrm_case_contact AS a 
        LEFT JOIN civicrm_case AS b ON a.case_id = b.id WHERE a.contact_id = %1 AND b.case_type_id = %2
        AND b.is_deleted = %3";

    }

  }
  return civicrm_api3_create_success([], $params, 'NihrProjectVolunteer', 'seteligible');
}
