<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Config class for Nihr BioResource Backbone
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 25 Feb 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_BackboneConfig {

  // property for singleton pattern
  private static $_singleton = NULL;

  // properties for option group ids
  private $_studyStatusOptionGroupId = NULL;
  private $_ethicsApprovedOptionGroupId = NULL;

  // property for project campaign type
  private $_projectCampaignTypeId = NULL;

  // properties for custom groups
  private $_projectDataCustomGroup = [];

  /**
   * CRM_Nihrbackbone_BackboneConfig constructor.
   */
  public function __construct() {
    $this->setOptionGroups();
    $this->setCampaignTypes();
    $this->setCustomData();
  }

  /**
   * Getter for project data custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getProjectDataCustomGroup($key = NULL) {
    if ($key && isset($this->_projectDataCustomGroup[$key])) {
      return $this->_projectDataCustomGroup[$key];
    }
    else {
      return $this->_projectDataCustomGroup;
    }
  }

  /**
   * Getter for project study custom field
   *
   * @param null $key
   * @return bool
   */
  public function getProjectStudyCustomField($key = NULL) {
    foreach ($this->_projectDataCustomGroup['custom_fields'] as $customFieldId => $customField) {
      if ($customField['name'] == 'npd_study_id') {
        if ($key && isset($customField[$key])) {
          return $customField[$key];
        }
        else {
          return $customField;
        }
      }
    }
    return FALSE;
  }

  /**
   * Getter for project campaign type id
   *
   * @return null
   */
  public function getProjectCampaignTypeId() {
    return $this->_projectCampaignTypeId;
  }

  /**
   * Getter for study status option group id
   *
   * @return null
   */
  public function getStudyStatusOptionGroupId() {
    return $this->_studyStatusOptionGroupId;
  }

  /**
   * Getter for ethics approved option group id
   * @return null
   */
  public function getEthicsApprovedOptionGroupId() {
    return $this->_ethicsApprovedOptionGroupId;
  }

  /**
   * Method to set the relevant option groups
   */
  private function setOptionGroups() {
    $optionGroupNames = [
      'nihr_study_status',
      'nihr_ethics_approved',
    ];
    try {
      $foundOptionGroups = civicrm_api3('OptionGroup', 'get', [
        'return' => ["id", "name"],
        'name' => ['IN' => $optionGroupNames],
        'options' => ['limit' => 0],
      ])['values'];
      foreach ($foundOptionGroups as $foundOptionGroup) {
        $property = $this->getPropertyFromName($foundOptionGroup['name']) . 'OptionGroupId';
        $this->$property = $foundOptionGroup['id'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find a unique option group with name gender in ') . __METHOD__);
    }
  }

  /**
   * Method to set the relevant campaign types
   */
  private function setCampaignTypes() {
    try {
      $this->_projectCampaignTypeId = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'campaign_type',
        'return' => "value",
        'name' => 'nihr_project',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find a unique option group with name campaign_type in ') . __METHOD__);
    }
  }

  /**
   * Method to set the property from the custom group name
   * @param $customGroupName
   * @return bool|string|null
   */
  private function getPropertyFromName($customGroupName) {
    if (empty($customGroupName)) {
      return FALSE;
    }
    $property = NULL;
    $parts = explode('_', $customGroupName);
    $count = count($parts);
    if ($parts[0] == 'nihr') {
      $start = 1;
    }
    else {
      $start = 0;
    }
    for ($x = $start; $x < $count; $x++) {
      if (!$property) {
        $property = '_' . strtolower($parts[$x]);
      }
      else {
        $property .= ucfirst(strtolower($parts[$x]));
      }
    }
    return $property;
  }

  /**
   * Method to set the custom data
   */
  private function setCustomData() {
    $relevantCustomGroups = ['nihr_project_data'];
    try {
      $customGroups = civicrm_api3('CustomGroup', 'get', [
        'options' => ['limit' => 0],
      ]);
      foreach ($customGroups['values'] as $customGroupId => $customGroup) {
        if (in_array($customGroup['name'], $relevantCustomGroups)) {
          $name = str_replace('nihr_', '', $customGroup['name']);
          $parts = explode('_', $name);
          foreach ($parts as $partId => $part) {
            if ($partId == 0) {
              $parts[$partId] = strtolower($part);
            }
            else {
              $parts[$partId] = ucfirst(strtolower($part));
            }
          }
          $property = '_' . implode('', $parts) . 'CustomGroup';
          // add custom fields
          $customFields = civicrm_api3('CustomField', 'get', [
            'custom_group_id' => $customGroup['id'],
            'options' => ['limit' => 0],
          ]);
          $customGroup['custom_fields'] = $customFields['values'];
          $this->$property = $customGroup;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to return the singleton object or instantiate
   *
   * @return CRM_Nihrbackbone_BackboneConfig|null
   * @throws
   */
  public static function &singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Nihrbackbone_BackboneConfig();
    }
    return self::$_singleton;
  }

}
