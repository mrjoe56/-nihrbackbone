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
  $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
  $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
  $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
  $query = "SELECT DISTINCT(a.contact_id), c.entity_id AS case_id, d.id AS study_id
    FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        JOIN " . $tableName . " AS c on a.case_id = c.entity_id
        JOIN civicrm_campaign AS d ON c." . $studyIdColumn . " = d.id
    WHERE d.campaign_type_id = %1 AND d.status_id = %2 AND b.is_deleted = %3 AND c." . $statusColumn . " = %4";
  $queryParams = [
    1 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId(), "Integer"],
    2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), "Integer"],
    3 => [0, "Integer"],
    4 => [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"],
  ];
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  while ($dao->fetch()) {
    $eligibilities = CRM_Nihrbackbone_NbrVolunteerCase::calculateEligibility($dao->study_id, $dao->contact_id);
    CRM_Nihrbackbone_NbrVolunteerCase::setEligibilityStatus($eligibilities, $dao->case_id, TRUE);
  }
  return civicrm_api3_create_success(["Eligibility for all selected volunteers on recruiting studies recalculated"], $params, 'NbrVolunteer', 'Calceligibility');
}
