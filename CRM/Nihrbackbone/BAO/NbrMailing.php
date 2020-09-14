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
   * Method to process the postMailing hook
   * - get nbr mailing data
   * - remove temporary group
   * - add invite activity to all recipients
   *
   * @param $mailingId
   */
  public static function postMailing($mailingId) {
    // retrieve nbr_mailing data
    try {
      $nbrMailing = civicrm_api3('NbrMailing', 'getsingle', ['id' => $mailingId]);
      // delete temporary group for mailing
      if ($nbrMailing['group_id']) {
        civicrm_api3('Group', 'delete', ['id' => $nbrMailing['group_id']]);
      }
      // get all mailing recipients with their participation cases on study
      $query = "SELECT a.contact_id, b.case_id
        FROM civicrm_mailing_recipients AS a
            JOIN civicrm_case_contact AS b ON a.contact_id = b.contact_id
            JOIN civicrm_case AS c ON b.case_id = c.id
            JOIN civicrm_value_nbr_participation_data AS d ON c.id = d.entity_id
        WHERE mailing_id = 45 AND c.is_deleted = 0 AND c.case_type_id = 3 AND d.nvpd_study_id = 2";
      $queryParams = [
        1 => [(int) $mailingId, "Integer"],
        2 => [0, "Integer"],
        3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        4 => [(int) $nbrMailing['study_id'], "Integer"],
      ];
      $recipient = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($recipient->fetch()) {
        // add invite activity to all recipients
        CRM_Nihrbackbone_NbrInvitation::addInviteActivity();
      }


    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error("Could not find NBR Mailing data for mailing with ID " . $mailingId . ", no invite activities added, invite dates set or participant status updated! Error from API NbrMailing getsingle: "
        . $ex->getMessage());
    }
  }

}
