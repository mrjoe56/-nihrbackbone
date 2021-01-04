<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrVolunteer.CalculateEligibility API
 * Nightly re-calculates eligibility for all selected volunteers on recruiting studies
 * (might have changed because they are older or have reached/not reached max studies)
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 30 Apr 2020
 * @license AGPL-3.0
 */
function civicrm_api3_nbr_volunteer_Calceligibility($params) {
  $query = "";
  $queryParams = [];
  $mode = "default";
  if (isset($params['mode']) && $params['mode'] == "full") {
    $mode = "full";
  }
  CRM_Nihrbackbone_NbrVolunteerCase::getQueryForCalculationMode($query, $queryParams, $mode);
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  while ($dao->fetch()) {
    $eligibilities = CRM_Nihrbackbone_NbrVolunteerCase::calculateEligibility($dao->study_id, $dao->contact_id);
    CRM_Nihrbackbone_NbrVolunteerCase::setEligibilityStatus($eligibilities, $dao->case_id, TRUE);
  }
  $message = "Eligibility for all selected volunteers on recruiting studies recalculated";
  if (isset($params['mode']) && $params['mode'] == "full") {
    $message = "Eligibility for all selected/invited/invitation pending/accepted volunteers on recruiting studies recalculated";
  }
  return civicrm_api3_create_success([$message], $params, 'NbrVolunteer', 'Calceligibility');
}
