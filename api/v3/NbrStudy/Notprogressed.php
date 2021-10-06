<?php
use CRM_Nihrbackbone_ExtensionUtil as E;


/**
 * NbrStudy.Notprogressed API
 *
 * - will delete all participation cases if the study they belong to has status not progressed
 *
 * @param array $params
 * @link https://www.wrike.com/open.htm?id=753789895
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_nbr_study_Notprogressed($params) {
  $returnValues = CRM_Nihrbackbone_NbrStudy::deleteParticipationNotProgressed();
  return civicrm_api3_create_success($returnValues, $params, 'NbrStudy', 'Notprogressed');
}
