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
  private $_ethicsApprovedOptionGroupId = NULL;
  private $_eligibleStatusOptionGroupId = NULL;
  private $_genderOptionGroupId = NULL;
  private $_studyParticipationStatusOptionGroupId = NULL;
  private $_ethnicityOptionGroupId = NULL;
  private $_consentStatusOptionGroupId = NULL;
  private $_consentVersionOptionGroupId = NULL;
  private $_caseStatusOptionGroupId = NULL;

  // property for study campaign type
  private $_studyCampaignTypeId = NULL;

  // property for study participant status
  private $_studyParticipationStatusInvitationPendingOptionValue = NULL;
  private $_studyParticipationStatusInvitedOptionValue = NULL;

  // properties for study (campaign) status
  private $_recruitingStudyStatus = NULL;
  private $_completedStudyStatus = NULL;
  private $_declinedStudyStatus = NULL;
  private $_closedStudyStatus = NULL;
  private $_pendingStudyStatus = NULL;

  // properties for custom groups
  private $_studyDataCustomGroup = [];
  private $_participationDataCustomGroup = [];
  private $_volunteerStatusCustomGroup = [];
  private $_volunteerGeneralObservationsCustomGroup = [];
  private $_volunteerLifestyleCustomGroup = [];
  private $_volunteerSelectionEligibilityCustomGroup = [];
  private $_volunteerLifeQualityCustomGroup = [];
  private $_selectionCriteriaCustomGroup = [];
  private $_volunteerIdsCustomGroup = [];
  private $_volunteerPanelCustomGroup = [];
  private $_volunteerConsentCustomGroup = [];
  private $_siteAliasCustomGroup = [];

  // properties for case types ids
  private $_participationCaseTypeId = NULL;
  private $_recruitmentCaseTypeId = NULL;

  // propterties for activity type ids
  private $_changeStudyStatusActivityTypeId = NULL;
  private $_inviteProjectActivityTypeId = NULL;
  private $_exportExternalActivityTypeId = NULL;

  // properties for case status ids
  private $_closedCaseStatusId = NULL;

  // properties for phone type ids
  private $_mobilePhoneTypeId = NULL;
  private $_phonePhoneTypeId = NULL;

  // properties for location types
  private $_homeLocationTypeId = NULL;

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
    $this->setOptionValues();
    $this->setCampaignTypes();
    $this->setCampaignStatus();
    $this->setCaseTypes();
    $this->setActivityTypes();
    $this->setCaseStatus();
    $this->setCustomData();
    $this->setPhoneTypes();
    $this->setLocationTypes();
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
   * Getter for home location type id
   * @return null
   */
  public function getHomeLocationTypeId() {
    return $this->_homeLocationTypeId;
  }

  /**
   * Getter for case status closed
   * @return null
   */
  public function getClosedCaseStatusId() {
    return $this->_closedCaseStatusId;
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
   * Getter for study data custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getStudyDataCustomGroup($key = NULL) {
    if ($key && isset($this->_studyDataCustomGroup[$key])) {
      return $this->_studyDataCustomGroup[$key];
    }
    else {
      return $this->_studyDataCustomGroup;
    }
  }


  /**
   * Getter for volunteer ids custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerIdsCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerIdsCustomGroup[$key])) {
      return $this->_volunteerIdsCustomGroup[$key];
    }
    else {
      return $this->_volunteerIdsCustomGroup;
    }
  }

  /**
   * Getter for selection critiera custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getSelectionCriteriaCustomGroup($key = NULL) {
    if ($key && isset($this->_selectionCriteriaCustomGroup[$key])) {
      return $this->_selectionCriteriaCustomGroup[$key];
    }
    else {
      return $this->_selectionCriteriaCustomGroup;
    }
  }

  /**
   * Getter for volunteer general observation custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerGeneralObservationsCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerGeneralObservationsCustomGroup[$key])) {
      return $this->_volunteerGeneralObservationsCustomGroup[$key];
    }
    else {
      return $this->_volunteerGeneralObservationsCustomGroup;
    }
  }

  /**
   * Getter for volunteer lifestyle custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerLifestyleCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerLifestyleCustomGroup[$key])) {
      return $this->_volunteerLifestyleCustomGroup[$key];
    }
    else {
      return $this->_volunteerLifestyleCustomGroup;
    }
  }

  /**
   * Getter for site alias custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getSiteAliasCustomGroup($key = NULL) {
    if ($key && isset($this->_siteAliasCustomGroup[$key])) {
      return $this->_siteAliasCustomGroup[$key];
    }
    else {
      return $this->_siteAliasCustomGroup;
    }
  }

  /**
   * Getter for volunteer selection eligibility custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerSelectionEligibilityCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerSelectionEligibilityCustomGroup[$key])) {
      return $this->_volunteerSelectionEligibilityCustomGroup[$key];
    }
    else {
      return $this->_volunteerSelectionEligibilityCustomGroup;
    }
  }

  public function getVolunteerLifeQualityCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerLifeQualityCustomGroup[$key])) {
      return $this->_volunteerLifeQualityCustomGroup[$key];
    }
    else {
      return $this->_volunteerLifeQualityCustomGroup;
    }
  }

  /**
   * Getter for volunteer panel custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerPanelCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerPanelCustomGroup[$key])) {
      return $this->_volunteerPanelCustomGroup[$key];
    }
    else {
      return $this->_volunteerPanelCustomGroup;
    }
  }

  /**
   * Getter for volunteer consent custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerConsentCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerConsentCustomGroup[$key])) {
      return $this->_volunteerConsentCustomGroup[$key];
    }
    else {
      return $this->_volunteerConsentCustomGroup;
    }
  }

  /**
   * Getter for volunteer status custom group
   *
   * @param null $key
   * @return array|mixed
   */
  public function getVolunteerStatusCustomGroup($key = NULL) {
    if ($key && isset($this->_volunteerStatusCustomGroup[$key])) {
      return $this->_volunteerStatusCustomGroup[$key];
    }
    else {
      return $this->_volunteerStatusCustomGroup;
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
   * Getter for study data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getStudyCustomField($customFieldName, $key = NULL) {
    foreach ($this->_studyDataCustomGroup['custom_fields'] as $customField) {
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
   * Getter for volunteer panel custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getVolunteerPanelCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerPanelCustomGroup['custom_fields'] as $customField) {
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
   * Getter for volunteer consent custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getVolunteerConsentCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerConsentCustomGroup['custom_fields'] as $customField) {
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
   * Getter for volunteer ids custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getVolunteerIdsCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerIdsCustomGroup['custom_fields'] as $customField) {
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
   * Getter for volunteer status custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getVolunteerStatusCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerStatusCustomGroup['custom_fields'] as $customField) {
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
   * Getter for general observation data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getGeneralObservationCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerGeneralObservationsCustomGroup['custom_fields'] as $customField) {
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
   * Getter for lifestyle data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getLifestyleCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerLifestyleCustomGroup['custom_fields'] as $customField) {
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

  public function getVolunteerSelectionEligibilityCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerSelectionEligibilityCustomGroup['custom_fields'] as $customField) {
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

  public function getVolunteerLifeQualityCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerLifeQualityCustomGroup['custom_fields'] as $customField) {
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
   * Getter for site alias data custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getSiteAliasCustomField($customFieldName, $key = NULL) {
    foreach ($this->_siteAliasCustomGroup['custom_fields'] as $customField) {
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
   * Getter for selection eligibility custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getSelectionEligibilityCustomField($customFieldName, $key = NULL) {
    foreach ($this->_volunteerSelectionEligibilityCustomGroup['custom_fields'] as $customField) {
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
   * Getter for selection criteria custom field
   *
   * @param $customFieldName
   * @param $key
   * @return mixed
   */
  public function getSelectionCriteriaCustomField($customFieldName, $key = NULL) {
    foreach ($this->_selectionCriteriaCustomGroup['custom_fields'] as $customField) {
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
  public function getStudyCampaignTypeId() {
    return $this->_studyCampaignTypeId;
  }

  /**
   * Getter for closed study status
   *
   * @return null
   */
  public function getClosedStudyStatus() {
    return $this->_closedStudyStatus;
  }

  /**
   * Getter for completed study status
   * @return null
   */
  public function getCompletedStudyStatus() {
    return $this->_completedStudyStatus;
  }

  /**
   * Getter for declined study status
   *
   * @return null
   */
  public function getDeclinedStudyStatus() {
    return $this->_declinedStudyStatus;
  }

  /**
   * Getter for pending study status
   *
   * @return null
   */
  public function getPendingStudyStatus() {
    return $this->_pendingStudyStatus;
  }

  /**
   * Getter for recruiting study status
   *
   * @return mixed
   */
  public function getRecruitingStudyStatus() {
    return $this->_recruitingStudyStatus;
  }

  /**
   * Getter for pending invitation study participant status.
   *
   * @return mixed
   */
  public function getStudyParticipationStatusInvitationPending() {
    return $this->_studyParticipationStatusInvitationPendingOptionValue;
  }

  /**
   * Getter for pending invitation study participant status.
   *
   * @return mixed
   */
  public function getStudyParticipationStatusInvited() {
    return $this->_studyParticipationStatusInvitedOptionValue;
  }

  /**
   * Getter for case status option group id
   *
   * @return null
   */
  public function getCaseStatusOptionGroupId() {
    return $this->_caseStatusOptionGroupId;
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
   * Getter for volunteer study status option group id
   *
   * @return null
   */
  public function getStudyParticipationStatusOptionGroupId() {
    return $this->_studyParticipationStatusOptionGroupId;
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
   * Getter for change study status activity type id
   * @return null
   */
  public function getChangedStudyStatusActivityTypeId() {
    return $this->_changeStudyStatusActivityTypeId;
  }

  /**
   * Getter for invite to project activity type id
   * @return null
   */
  public function getInviteProjectActivityTypeId() {
    return $this->_inviteProjectActivityTypeId;
  }

  /**
   * Getter for export to external researcher activity type id
   * @return null
   */
  public function getExportExternalActivityTypeId() {
    return $this->_exportExternalActivityTypeId;
  }

  /**
   * Method to set the relevant option groups
   */
  private function setOptionGroups() {
    $optionGroupNames = [
      'nihr_ethics_approved',
      'nihr_eligible_status',
      'nbr_study_participation_status',
      'nihr_ethnicity',
      'nbr_consent_status',
      'gender',
      'nbr_consent_version',
      'case_status',
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
   * Method to set the relevant option values
   */
  private function setOptionValues() {
    $optionValueNames['nbr_study_participation_status'] = [
      'study_participation_status_invitation_pending',
      'study_participation_status_invited'
    ];
    foreach($optionValueNames as $optionGroupName => $optionValues) {
      try {
        $foundOptionValues = civicrm_api3('OptionValue', 'get', [
          'return' => ["value", "name"],
          'name' => ['IN' => $optionValues],
          'option_group_id' => $optionGroupName,
          'options' => ['limit' => 0],
        ])['values'];
        foreach ($foundOptionValues as $foundOptionGroup) {
          $property = $this->getPropertyFromName($foundOptionGroup['name']) . 'OptionValue';
          $this->$property = $foundOptionGroup['value'];
        }
      } catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->error(E::ts('Could not find a unique option values in group '.$optionGroupName.' in ') . __METHOD__);
      }
    }
  }

  /**
   * Method to set the relevant campaign types
   */
  private function setCampaignTypes() {
    try {
      $this->_studyCampaignTypeId = civicrm_api3('OptionValue', 'getvalue', [
        'option_group_id' => 'campaign_type',
        'return' => "value",
        'name' => 'nbr_study',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find a unique option group with name campaign_type in ') . __METHOD__);
    }
  }

  /**
   * Method to set the relevant campaign (project) status
   */
  private function setCampaignStatus() {
    try {
      $apiResult = civicrm_api3('OptionValue', 'get', [
        'sequential' => 1,
        'return' => ["value", "name"],
        'option_group_id' => "campaign_status",
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiResult['values'] as $apiValue) {
        $property = "_" . strtolower($apiValue['name']) . "StudyStatus";
        $this->$property = $apiValue['value'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
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
   * Method to check if change study status activity exists and create if not
   */
  private function checkChangeStudyStatus() {
    try {
      $checkCount = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => 'activity_type',
        'name' => 'nbr_change_study_status',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error("Unexpected error in " . __METHOD__ . ", error message from API OptionValue getcount: " . $ex->getMessage());
    }
    if ($checkCount == 0) {
      try {
        civicrm_api3('OptionValue', 'create', [
          'option_group_id' => 'activity_type',
          'name' => 'nbr_change_study_status',
          'label' => 'Change Study Status',
          'component' => 'CiviCase',
          'is_active' => 1,
          'is_reserved' => 1,
        ]);
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new API_Exception('Could not create required activity type Change Study Status in '
          . __METHOD__ . ', contact system administration citing error message: ' . $ex->getMessage());
      }
    }
  }

  /**
   * Method to set the relevant activity type ids
   */
  private function setActivityTypes() {
    // double check - if change study status does not exist, create
    $this->checkChangeStudyStatus();
    $validTypes = ['nbr_change_study_status', 'nbr_project_invite', 'nbr_export_external'];
    try {
      $apiTypes = civicrm_api3('OptionValue', 'get', [
        'options' => ['limit' => 0],
        'name' => ['IN' => $validTypes],
        'return' => ['value', 'name'],
        'sequential' => 1,
      ]);
      foreach ($apiTypes['values'] as $apiType) {
        switch ($apiType['name']) {
          case 'nbr_change_study_status':
            $this->_changeStudyStatusActivityTypeId = $apiType['value'];
            break;
          case 'nbr_project_invite':
            $this->_inviteProjectActivityTypeId = $apiType['value'];
            break;
          case 'nbr_export_external':
            $this->_exportExternalActivityTypeId = $apiType['value'];
            break;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
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
      'nbr_study_data',
      'nbr_participation_data',
      'nihr_volunteer_data',
      'nihr_volunteer_status',
      'nihr_volunteer_general_observations',
      'nihr_volunteer_lifestyle',
      'nihr_volunteer_selection_eligibility',
      'nbr_selection_criteria',
      'nihr_volunteer_alias',
      'nihr_volunteer_ids',
      'nihr_volunteer_panel',
      'nihr_volunteer_consent',
      'nihr_volunteer_disease',
      'nbr_site_alias'
      ];
    try {
      $customGroups = civicrm_api3('CustomGroup', 'get', [
        'options' => ['limit' => 0],
      ]);
      foreach ($customGroups['values'] as $customGroupId => $customGroup) {
        if (in_array($customGroup['name'], $relevantCustomGroups)) {
          $parts = explode('_', $customGroup['name']);
          foreach ($parts as $partId => $part) {
            if ($part == "nihr" || $part == "nbr") {
              unset($parts[$partId]);
            }
            else {
              if ($partId == 1) {
                $parts[$partId] = strtolower($part);
              }
              else {
                $parts[$partId] = ucfirst(strtolower($part));
              }
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
   * Method to set the location type properties
   */
  private function setLocationTypes() {
    try {
      $this->_homeLocationTypeId = civicrm_api3('LocationType', 'getvalue', [
        'return' => 'id',
        'name' => 'Home',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to set the case status properties
   */
  private function setCaseStatus() {
    try {
      $caseStatuses = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'case_status',
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($caseStatuses['values'] as $caseStatus) {
        switch ($caseStatus['name']) {
          case "Closed":
            $this->_closedCaseStatusId = $caseStatus['value'];
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
