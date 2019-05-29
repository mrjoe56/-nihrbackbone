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
  private $_eligibleStatusOptionGroupId = NULL;
  private $_genderOptionGroupId = NULL;
  private $_volunteerProjectStatusOptionGroupId = NULL;
  private $_ethnicityOptionGroupId = NULL;
  private $_consentStatusOptionGroupId = NULL;
  private $_consentVersionOptionGroupId = NULL;

  // property for project campaign type
  private $_projectCampaignTypeId = NULL;

  // properties for custom groups
  private $_projectDataCustomGroup = [];
  private $_participationDataCustomGroup = [];
  private $_volunteerDataCustomGroup = [];
  private $_sampleDataCustomGroup = [];

  // properties for case types ids
  private $_participationCaseTypeId = NULL;
  private $_recruitmentCaseTypeId = NULL;

  // properties for phone type ids
  private $_mobilePhoneTypeId = NULL;
  private $_phonePhoneTypeId = NULL;

  // properties for communication styles
  private $_defaultCommunicationStyleId = NULL;
  private $_defaultIndEmailGreetingId = NULL;
  private $_defaultIndPostalGreetingId = NULL;
  private $_defaultIndAddresseeId = NULL;

  // other properties
  private $_defaultLocationTypeId = NULL;
  private $_skypeProviderId = NULL;

  /**
   * CRM_Nihrbackbone_BackboneConfig constructor.
   */
  public function __construct() {
    $this->setOptionGroups();
    $this->setCampaignTypes();
    $this->setCaseTypes();
    $this->setCustomData();
    $this->setPhoneTypes();
    $this->setDefaultCommunicationStyles();
    try {
      $this->_defaultLocationTypeId = civicrm_api3('LocationType', 'getvalue', [
        'return' => "id",
        'is_default' => 1,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('No default location type id found in ') . __METHOD__);
    }
    try {
      $this->_skypeProviderId = civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "instant_messenger_service",
        'name' => "Skype",
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::s('No instant messenger with name Skype found in ') . __METHOD__);
    }
  }

  /**
   * Getter for consent version option group id
   * @return null
   */
  public function getConsentVersionOptionGroupId() {
    return $this->_consentVersionOptionGroupId;

  }
  /**
   * Getter for Skype provider id
   *
   * @return array|null
   */
  public function getSkypeProviderId() {
    return $this->_skypeProviderId;
  }

  /**
   * Getter for default location type id
   *
   * @return array|null
   */
  public function getDefaultLocationTypeId() {
    return $this->_defaultLocationTypeId;
  }
  /**
   * Getter for default communication style id
   *
   * @return null
   */
  public function getDefaultCommmunicationStyleId() {
    return $this->_defaultCommunicationStyleId;
  }

  /**
   * Getter for default individual addressee id
   *
   * @return null
   */
  public function getDefaultIndAddresseeId() {
    return $this->_defaultIndAddresseeId;
  }

  /**
   * Getter for default individual email greeting id
   *
   * @return null
   */
  public function getDefaultIndEmailGreetingId() {
    return $this->_defaultIndEmailGreetingId;
  }

  /**
   * Getter for default individual postal greeting id
   *
   * @return null
   */
  public function getDefaultIndPostalGreetingId() {
    return $this->_defaultIndPostalGreetingId;
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
   * Getter for volunteer data custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerDataCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerDataCustomGroup[$key])) {
      return $this->_volunteerDataCustomGroup[$key];
    }
    else {
      return $this->_volunteerDataCustomGroup;
    }
  }

  /**
   * Getter for participation data custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getParticipationDataCustomGroup($key = NULL) {
    if ($key && isset($this->_participationDataCustomGroup[$key])) {
      return $this->_participationDataCustomGroup[$key];
    }
    else {
      return $this->_participationDataCustomGroup;
    }
  }

  /**
   * Getter for sample data custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getSampleDataCustomGroup($key = NULL) {
    if ($key && isset($this->_sampleDataCustomGroup[$key])) {
      return $this->_sampleDataCustomGroup[$key];
    }
    else {
      return $this->_sampleDataCustomGroup;
    }
  }

  /**
   * Getter for mobile phone type id
   *
   * @return null
   */
  public function getMobilePhoneTypeId() {
    return $this->_mobilePhoneTypeId;
  }

  /**
   * Getter for phone phone type id
   *
   * @return null
   */
  public function getPhonePhoneTypeId() {
    return $this->_phonePhoneTypeId;
  }

  /**
   * Getter for project data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getProjectCustomField($customFieldName, $key = NULL) {
    foreach ($this->_projectDataCustomGroup['custom_fields'] as $customField) {
      if ($customField['name'] == $customFieldName) {
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
   * Getter for sample data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getSampleCustomField($customFieldName, $key = NULL) {
    foreach ($this->_sampleDataCustomGroup['custom_fields'] as $customField) {
      if ($customField['name'] == $customFieldName) {
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
   * Getter for volunteer data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getVolunteerCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerDataCustomGroup['custom_fields'] as $customField) {
      if ($customField['name'] == $customFieldName) {
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
   * Getter for participation data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getParticipationCustomField($customFieldName, $key = NULL) {
    foreach ($this->_participationDataCustomGroup['custom_fields'] as $customField) {
      if ($customField['name'] == $customFieldName) {
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
   * Getter for participation case type id
   */
  public function getParticipationCaseTypeId() {
    return $this->_participationCaseTypeId;
  }

  /**
   * Getter for recruitmnet case type id
   */
  public function getRecruitmentCaseTypeId() {
    return $this->_recruitmentCaseTypeId;
  }

  /**
   * Getter for consent status option group id
   *
   * @return null
   */
  public function getConsentStatusOptionGroupId() {
    return $this->_consentStatusOptionGroupId;
  }

  /**
   * Getter for volunteer project status option group id
   *
   * @return null
   */
  public function getVolunteerProjectStatusOptionGroupId() {
    return $this->_volunteerProjectStatusOptionGroupId;
  }

  /**
   * Getter for eligible status option group id
   *
   * @return null
   */
  public function getEligibleStatusOptionGroupId() {
    return $this->_eligibleStatusOptionGroupId;
  }

  /**
   * Getter for ethnicity option group id
   *
   * @return null
   */
  public function getEthnicityOptionGroupId() {
    return $this->_ethnicityOptionGroupId;
  }

  /**
   * Getter for ethics approved option group id
   * @return null
   */
  public function getEthicsApprovedOptionGroupId() {
    return $this->_ethicsApprovedOptionGroupId;
  }

  /**
   * Getter for gender option group id
   * @return null
   */
  public function getGenderOptionGroupId() {
    return $this->_genderOptionGroupId;
  }

  /**
   * Method to set the relevant option groups
   */
  private function setOptionGroups() {
    $optionGroupNames = [
      'nihr_study_status',
      'nihr_ethics_approved',
      'nihr_eligible_status',
      'nihr_volunteer_project_status',
      'nihr_ethnicity',
      'nihr_consent_status',
      'gender',
      'nbr_consent_version'
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
   * Method to set the relevant case types
   */
  private function setCaseTypes() {
    try {
      $caseTypes = civicrm_api3('CaseType', 'get', [
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($caseTypes['values'] as $caseTypeId => $caseType) {
        switch ($caseType['name']) {
          case 'nihr_participation':
            $this->_participationCaseTypeId = $caseTypeId;
            break;

          case 'nihr_recruitment':
            $this->_recruitmentCaseTypeId = $caseTypeId;
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find case_types in ') . __METHOD__);
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
    if ($parts[0] == 'nihr' || $parts[0] == 'nbr') {
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
    $relevantCustomGroups = [
      'nihr_project_data',
      'nihr_participation_data',
      'nihr_volunteer_data',
      'nihr_sample_data',
      ];
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
   * Method to set the phone type properties
   */
  private function setPhoneTypes() {
    try {
      $phoneTypes = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'phone_type',
        'is_active' => 1,
        'name' => ['IN' => ["Mobile", "Phone"]],
        'options' => ['limit' => 0],
      ]);
      foreach ($phoneTypes['values'] as $phoneType) {
        switch ($phoneType['name']) {
          case "Mobile":
            $this->_mobilePhoneTypeId = $phoneType['value'];
            break;
          case "Phone":
            $this->_phonePhoneTypeId = $phoneType['value'];
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }

  }

  /**
   * Method to set the default communication style and greetings
   */
  private function setDefaultCommunicationStyles() {
    try {
      $this->_defaultCommunicationStyleId = (int) civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "communication_style",
        'is_default' => 1,
      ]);
      $this->_defaultIndAddresseeId = civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "addressee",
        'is_default' => 1,
        'filter' => 1,
      ]);
      $this->_defaultIndEmailGreetingId = civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "email_greeting",
        'is_default' => 1,
        'filter' => 1,
      ]);
      $this->_defaultIndPostalGreetingId = civicrm_api3('OptionValue', 'getvalue', [
        'return' => "value",
        'option_group_id' => "postal_greeting",
        'is_default' => 1,
        'filter' => 1,
      ]);
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
