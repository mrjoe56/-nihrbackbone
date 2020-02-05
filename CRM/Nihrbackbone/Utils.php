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

  /**
   * Method om dao in array te stoppen en de 'overbodige' data er uit te slopen
   *
   * @param  $dao
   * @return array
   */
  public static function moveDaoToArray($dao) {
    $ignores = array('N', 'id', 'entity_id');
    $columns = get_object_vars($dao);
    // first remove all columns starting with _
    foreach ($columns as $key => $value) {
      if (substr($key, 0, 1) == '_') {
        unset($columns[$key]);
      }
      if (in_array($key, $ignores)) {
        unset($columns[$key]);
      }
    }
    return $columns;
  }

  /**
   * Method to put comma separated list of values in a string into an array
   *
   * @param string $csList
   * @return array
   */
  public static function moveCommaSeparatedListToArray($csList) {
    $parts = explode(',', $csList);
    foreach ($parts  as $partKey => $partValue) {
      trim($partValue);
    }
    return $parts;
  }

  /**
   * Method to log a message in the table civicrm_nbr_import_log
   *
   * @param $importId
   * @param $message
   * @param null $fileName
   * @param string $messageType
   * @throws Exception
   */
  public static function logMessage($importId, $message, $fileName = NULL, $messageType = "info") {
    $logParams = [
      'import_id' => $importId,
      'message' => trim(strip_tags($message)),
      'message_type' => $messageType,
      'logged_date' => date('Ymd'),
    ];
    if ($fileName) {
      $logParams['filename'] = $fileName;
    }
    try {
      civicrm_api3('NbrImportLog', 'create' , $logParams);
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(E::ts('Could not log message of type ') . $messageType . E::ts(' to logging table with API NbrImportLog create in  ')
        . __METHOD__ . E::ts(', error message from API: ') . $ex->getMessage());
    }
  }

}
