<?php
use CRM_Nihrbackbone_ExtensionUtil as E;


/**
 * NbrStudy.Cleanup API
 * Will remove all participant cases of a study with study status not progressed where the stduy participant status is selected
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
function civicrm_api3_nbr_study_Cleanup($params) {
  $returnValues = CRM_Nihrbackbone_NbrStudy::deleteParticipationNotProgressed();
  return civicrm_api3_create_success($returnValues, $params, 'NbrStudy', 'cleanup');
}
