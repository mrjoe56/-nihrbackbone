<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrStudy.Generateids API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
function _civicrm_api3_nbr_study_Generateids_spec(&$spec) {
  $spec['study_number']['api.required'] = 1;
}

/**
 * NbrStudy.Generateids API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_study_Generateids($params) {
  if (array_key_exists('study_number', $params)) {
    $returnValues = CRM_Nihrbackbone_NbrVolunteerCase::generateIdForDataOnlyParticipants((string) $params['study_number']);
    return civicrm_api3_create_success($returnValues, $params, 'NbrStudy', 'Generateids');
  }
  else {
    throw new API_Exception('Param study_number not found');
  }
}
