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
      CRM_Nihrbackbone_NbrInvitation::addInviteActivity($recipient->case_id, $recipient->contact_id, $nbrMailing['study_id'], "bulk invite");
    }
  }
}
