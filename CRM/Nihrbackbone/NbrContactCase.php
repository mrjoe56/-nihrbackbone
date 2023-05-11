<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource contact case processing
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 9 May 2023
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrContactCase {

  /**
   * Method to determine if the contact has an active case of a certain type
   *
   * @param int $contactId
   * @param int $caseTypeId
   * @return bool
   */
  public static function hasActiveCaseOfType(int $contactId, int $caseTypeId): bool {
    if ($contactId && $caseTypeId) {
      $query = "SELECT COUNT(*)
        FROM civicrm_case_contact a
            JOIN civicrm_case b ON a.case_id = b.id
            JOIN civicrm_option_value c ON b.status_id = c.value AND c.option_group_id = %1
        WHERE b.case_type_id = %2 AND b.is_deleted = FALSE AND a.contact_id = %3 AND c.grouping != %4";
      $queryParams = [
        1 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getCaseStatusOptionGroupId(), "Integer"],
        2 => [$caseTypeId, "Integer"],
        3 => [$contactId, "Integer"],
        4 => ["Closed", "String"],
      ];
      $count = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($count > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
