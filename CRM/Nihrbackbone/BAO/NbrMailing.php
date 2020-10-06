<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NbrMailing extends CRM_Nihrbackbone_DAO_NbrMailing {

  /**
   * Check if the mailing is an Nbr Mailing
   *
   * @param int $mailingId
   * @return bool
   */
  public static function isNbrMailing($mailingId) {
    if (!empty($mailingId)) {
      $query = "SELECT COUNT(*) FROM civicrm_nbr_mailing WHERE mailing_id = %1";
      $count = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $mailingId, "Integer"]]);
      if ($count > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Method to get nbr mailing with mailing id
   *
   * @param $mailingId
   * @return array
   */
  public static function getByMailingId($mailingId) {
    if (empty($mailingId)) {
      return [];
    }
    $query = "SELECT * FROM civicrm_nbr_mailing WHERE mailing_id = %1";
    $nbrMailing = CRM_Core_DAO::executeQuery($query, [1=> [(int) $mailingId, "Integer"]]);
    if ($nbrMailing->fetch()) {
      return CRM_Nihrbackbone_Utils::moveDaoToArray($nbrMailing);
    }
    return [];
  }

  /**
   * Method to process the postMailing hook
   * - get nbr mailing data
   * - remove temporary group
   * - add invite activity to all recipients
   *
   * @param $mailingId
   */
  public static function postMailing($mailingId) {
    // retrieve nbr_mailing data
    $nbrMailing = CRM_Nihrbackbone_BAO_NbrMailing::getByMailingId($mailingId);
    // delete temporary group for mailing
    if ($nbrMailing['group_id']) {
      try {
        civicrm_api3('Group', 'delete', ['id' => $nbrMailing['group_id']]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    // get all mailing recipients with their participation cases on study
    $query = "SELECT a.contact_id, b.case_id
      FROM civicrm_mailing_recipients AS a
          JOIN civicrm_case_contact AS b ON a.contact_id = b.contact_id
          JOIN civicrm_case AS c ON b.case_id = c.id
          JOIN civicrm_value_nbr_participation_data AS d ON c.id = d.entity_id
      WHERE mailing_id = %1 AND c.is_deleted = %2 AND c.case_type_id = %3 AND d.nvpd_study_id = %4";
    $queryParams = [
      1 => [(int) $mailingId, "Integer"],
      2 => [0, "Integer"],
      3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      4 => [(int) $nbrMailing['study_id'], "Integer"],
    ];
    $recipient = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($recipient->fetch()) {
      // add invite activity to all recipients
      $mailingSubject = "";
      try {
        $mailingSubject = civicrm_api3('Mailing', 'getvalue' , [
          'id' => $mailingId,
          'return' => 'subject'
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
      CRM_Nihrbackbone_NbrInvitation::addInviteActivity($recipient->case_id, $recipient->contact_id, $nbrMailing['study_id'], "bulk invite (" . $mailingSubject . ")");
    }
  }

  /**
   * Method to reset invitation pending status to selected status for mailing
   *
   * @param $mailingId
   * @throws API_Exception
   */
  public static function resetInvitationPending($mailingId) {
    // if mailing is NBR mailing
    if (!empty($mailingId) && self::isNbrMailing($mailingId)) {
      $selected = Civi::service('nbrBackbone')->getSelectedParticipationStatusValue();
      // get all volunteers with status invitation pending and reset to selected
      $table = Civi::service('nbrBackbone')->getParticipationDataTableName();
      $statusColumn = Civi::service('nbrBackbone')->getStudyParticipationStatusColumnName();
      $query = "SELECT c.contact_id, d.case_id
        FROM civicrm_nbr_mailing AS a JOIN civicrm_mailing AS b ON a.mailing_id = b.id
            JOIN civicrm_mailing_recipients AS c on a.mailing_id = c.mailing_id
            JOIN civicrm_case_contact AS d ON c.contact_id = d.contact_id
            JOIN civicrm_case AS e ON d.case_id = e.id
            LEFT JOIN " . $table . " AS f ON e.id = f.entity_id
        WHERE b.is_completed IS NULL AND a.mailing_id = %1 AND e.is_deleted = %2 AND e.case_type_id = %3
            AND f." . $statusColumn . " = %4";
      $queryParams = [
        1 => [(int) $mailingId, "Integer"],
        2 => [0, "Integer"],
        3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        4 => [Civi::service('nbrBackbone')->getInvitationPendingParticipationStatusValue(), "String"],
      ];
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($dao->fetch()) {
        CRM_Nihrbackbone_NbrVolunteerCase::updateStudyStatus($dao->case_id, $dao->contact_id, $selected);
      }
    }
  }
}
