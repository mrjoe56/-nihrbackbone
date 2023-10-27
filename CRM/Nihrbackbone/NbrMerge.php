<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource merge processing
 * see https://www.wrike.com/open.htm?id=1167119117
 *
 * @author Erik Hommel (erik.hommel@civicoop.org)
 * @date 5 Oct 2023
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NbrMerge {

  /**
   * Process on hook merge
   *
   * @param array $queries
   * @param int $remainingId
   * @param int $removeId
   * @return void
   */
  public static function merge(array &$queries, int $remainingId, int $removeId): void {
    foreach ($queries as $queryId => $query) {
      if (stripos($query, "UPDATE civicrm_acl_contact_cache") !== FALSE) {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_acl_contact_cache WHERE contact_id = %1", [1 => [$removeId, 'Integer']]);
        unset ($queries[$queryId]);
      }
      elseif (stripos($query, "UPDATE civicrm_acl_cache") !== FALSE) {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_acl_cache WHERE contact_id = %1", [1 => [$removeId, 'Integer']]);
        unset ($queries[$queryId]);
      }
      elseif (stripos($query, "UPDATE civicrm_group_contact_cache") !== FALSE) {
        CRM_Core_DAO::executeQuery("DELETE FROM civicrm_group_contact_cache WHERE contact_id = %1", [1 => [$removeId, 'Integer']]);
        unset ($queries[$queryId]);
      }
      elseif (stripos($query, "UPDATE civicrm_value_nihr_volunteer_general_observations") !== FALSE) {
        // make sure the record does not already exist!
        $countQry = "SELECT COUNT(*) FROM civicrm_value_nihr_volunteer_general_observations WHERE entity_id = %1";
        $count = CRM_Core_DAO::singleValueQuery($countQry, [1 => [$remainingId, 'Integer']]);
        if ($count > 0) {
          unset ($queries[$queryId]);
        }
      }
    }
  }
}


