<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource resourcer
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 10 Aug 2020
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrResourcer {

  private $_bioResourcersGroupName = NULL;

  /**
   * CRM_Nihrbackbone_NbrResourcer constructor.
   */
  public function __construct() {
    $this->_bioResourcersGroupName = "nbr_bioresourcers";
  }

  /**
   * Method to find a resourcer contact id with name
   *
   * @param $resourcerName
   * @return false
   */
  public function findWithName($resourcerName) {
    $query = "SELECT cc.id
        FROM civicrm_group_contact AS cgc
        JOIN civicrm_group AS cg ON cgc.group_id = cg.id
        JOIN civicrm_contact AS cc ON cgc.contact_id = cc.id
        WHERE cg.name = %1 AND cgc.status = %2 AND cc.display_name LIKE %3";
    $queryParams = [
      1 => [$this->_bioResourcersGroupName, "String"],
      2 => ["Added", "String"],
      3 => ["%" . $resourcerName . "%", "String"],
    ];
    $contactId = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($contactId) {
      return (int) $contactId;
    }
    else {
      return FALSE;
    }
  }
}

