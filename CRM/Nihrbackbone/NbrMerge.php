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
      try {
        CRM_Core_DAO::executeQuery($query);
      }
      catch (Exception $ex) {
        Civi::log()->error("Error in merge process with query: " . $query . " in " . __METHOD__ . ", error message: " . $ex->getMessage());
      }
      unset ($queries[$queryId]);
    }
  }
}


