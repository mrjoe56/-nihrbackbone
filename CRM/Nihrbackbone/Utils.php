<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Collection of generic NIHR BioResource functions.
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 25 Feb 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_Utils {

  /**
   * Method to get name column value for contact
   *
   * @param $contactId
   * @param $nameColumn
   * @return bool|string
   */
  public static function getContactName($contactId, $nameColumn) {
    $validNameColumns = ['sort_name', 'display_name', 'household_name', 'organization_name', 'first_name', 'last_name'];
    if (empty($nameColumn) || !in_array($nameColumn, $validNameColumns)) {
      Civi::log()->error(E::ts('Not able to retrieve column ') . $nameColumn . E::ts(' for contact as this is not a valid name column in ') . __METHOD__);
      return FALSE;
    }
    if (empty($contactId)) {
      return FALSE;
    }
    try {
      return (string) civicrm_api3('Contact', 'getvalue', [
        'id' => $contactId,
        'return' => $nameColumn,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to get the option value label with option value and option group name
   *
   * @param $optionValue
   * @param $optionGroupId
   * @return string
   */
  public static function getOptionValueLabel($optionValue, $optionGroupId) {
    try {
      return (string) civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => $optionGroupId,
        'value' => $optionValue,
        'return' => 'label',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

}
