<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource activity
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 10 Aug 2020
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrActivity {

  /**
   * Method to create the activity
   *
   * @param $activityData
   * @return bool
   */
  public function createActivity($activityData) {
    // only if we have an activity type id
    if (isset($activityData['activity_type_id']) && !empty($activityData['activity_type_id'])) {
      if (!isset($activityData['activity_date_time']) || empty($activityData['activity_date_time'])) {
        $activityDateTime = new DateTime();
        $activityData['activity_date_time'] = $activityDateTime->format("Y-m-d");
      }
      if (!isset($activityData['source_contact_id']) || empty($activityData['source_contact_id'])) {
        $activityData['source_contact_id'] = 'user_contact_id';
      }
      try {
        civicrm_api3('Activity', 'create', $activityData);
        return TRUE;
      }
      catch (CiviCRM_API3_Exception $ex) {
        return "Could not create activity with data " . json_encode($activityData)
          . ", error from API Activity create: " . $ex->getMessage();
      }
    }
    else {
      return "Trying to create activity but activityTypeId is empty in data: " . json_encode($activityData) . " in " . __METHOD__;
    }
  }

  /**
   * Method to check if the reason status activity option value exists and create it if it does not
   *
   * @param $type
   * @param $sourceValue
   * @return false
   */
  public function findOrCreateStatusReasonValue($type, $sourceValue) {
    if (empty($sourceValue)) {
      return FALSE;
    }
    switch ($type) {
      case "not_recruited":
        $optionGroupId = Civi::service('nbrBackbone')->getNotRecruitedReasonOptionGroupId();
        break;
      case "redundant":
        $optionGroupId = Civi::service('nbrBackbone')->getRedundantReasonOptionGroupId();
        break;
      case "withdrawn":
        $optionGroupId = Civi::service('nbrBackbone')->getWithdrawnReasonOptionGroupId();
        break;
      default:
        return FALSE;
    }
    try {
      $foundValue = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => $optionGroupId,
        'value' => strtolower($sourceValue),
      ]);
      if ($foundValue == 0) {
        civicrm_api3('OptionValue', 'create', [
          'option_group_id' => $optionGroupId,
          'value' => strtolower($sourceValue),
          'name' => strtolower($sourceValue),
          'label' =>  Civi::service('nbrBackbone')->generateLabelFromValue($sourceValue),
          'is_active' => 1,
          'is_reserved' => 1,
        ]);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error('Error from API OptionValue getcount or create in ' . __METHOD__ . ', error message: ' . $ex->getMessage());
    }
    return strtolower($sourceValue);
  }

}

