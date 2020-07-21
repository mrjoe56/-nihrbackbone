<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for Nbr Configuration (with container usage)
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 9 apr 2020
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NbrConfig {
  /**
   * @var CRM_Nihrbackbone_NbrConfig
   */
  protected static $singleton;
  // volunteer status
  private $_activeVolunteerStatus = NULL;
  private $_deceasedVolunteerStatus = NULL;
  private $_notRecruitedVolunteerStatus = NULL;
  private $_pendingVolunteerStatus = NULL;
  private $_redundantVolunteerStatus = NULL;
  private $_withdrawnVolunteerStatus = NULL;
  // eligibility
  private $_activeEligibilityStatusValue = NULL;
  private $_ageEligibilityStatusValue = NULL;
  private $_bloodEligibilityStatusValue = NULL;
  private $_bmiEligibilityStatusValue = NULL;
  private $_commercialEligibilityStatusValue = NULL;
  private $_drugsEligibilityStatusValue = NULL;
  private $_eligibleEligibilityStatusValue = NULL;
  private $_ethnicityEligibilityStatusValue = NULL;
  private $_genderEligibilityStatusValue = NULL;
  private $_mriEligibilityStatusValue = NULL;
  private $_maxEligibilityStatusValue = NULL;
  private $_onlineOnlyEligibilityStatusValue = NULL;
  private $_otherStudyEligibilityStatusValue = NULL;
  private $_panelEligibilityStatusValue = NULL;
  private $_recallableEligibilityStatusValue = NULL;
  private $_travelEligibilityStatusValue = NULL;
  // tags
  private $_tempNonRecallTagId = NULL;
  // activity types
  private $_activityTypeOptionGroupId = NULL;
  private $_emailActivityTypeId = NULL;
  private $_consentActivityTypeId = NULL;
  private $_incomingCommunicationActivityTypeId = NULL;
  private $_letterActivityTypeId = NULL;
  private $_meetingActivityTypeId = NULL;
  private $_phoneActivityTypeId = NULL;
  private $_smsActivityTypeId = NULL;
  // custom group and field ids
  private $_contactIdentityCustomGroupId = NULL;
  private $_identifierTypeCustomFieldId = NULL;
  // study participation status
  private $_acceptedParticipationStatusValue = NULL;
  private $_excludedParticipationStatusValue = NULL;
  private $_invitationPendingParticipationStatusValue = NULL;
  private $_invitedParticipationStatusValue = NULL;
  private $_noResponseParticipationStatusValue = NULL;
  private $_notParticipatedParticipationStatusValue = NULL;
  private $_participatedParticipationStatusValue = NULL;
  private $_refusedParticipationStatusValue = NULL;
  private $_renegedParticipationStatusValue = NULL;
  private $_returnToSenderParticipationStatusValue = NULL;
  private $_selectedParticipationStatusValue = NULL;
  private $_withdrawnParticipationStatusValue = NULL;
  // others
  private $_correctConsentStatusValue = NULL;
  private $_visitStage2Substring = NULL;

  /**
   * CRM_Nihrbackbone_NbrConfig constructor.
   */
  public function __construct() {
    if (!self::$singleton) {
      self::$singleton = $this;
    }
  }

  /**
   * @return CRM_Nihrbackbone_NbrConfig
   */
  public static function getInstance() {
    if (!self::$singleton) {
      self::$singleton = new CRM_Nihrbackbone_NbrConfig();
    }
    return self::$singleton;
  }

  /**
   * @param string $status
   */
  public function setActiveVolunteerStatus($status) {
    $this->_activeVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getActiveVolunteerStatus() {
    return $this->_activeVolunteerStatus;
  }

  /**
   * @param string $status
   */
  public function setDeceasedVolunteerStatus($status) {
    $this->_deceasedVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getDeceasedVolunteerStatus() {
    return $this->_deceasedVolunteerStatus;
  }

  /**
   * @param string $status
   */
  public function setNotRecruitedVolunteerStatus($status) {
    $this->_notRecruitedVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getNotRecruitedVolunteerStatus() {
    return $this->_notRecruitedVolunteerStatus;
  }

  /**
   * @param string $status
   */
  public function setPendingVolunteerStatus($status) {
    $this->_pendingVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getPendingVolunteerStatus() {
    return $this->_pendingVolunteerStatus;
  }

  /**
   * @param string $status
   */
  public function setRedundantVolunteerStatus($status) {
    $this->_redundantVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getRedundantVolunteerStatus() {
    return $this->_redundantVolunteerStatus;
  }

  /**
   * @param string $status
   */
  public function setWithdrawnVolunteerStatus($status) {
    $this->_withdrawnVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getWithdrawnVolunteerStatus() {
    return $this->_withdrawnVolunteerStatus;
  }

  /**
   * @param int $id
   */
  public function setTempNonRecallTagId($id) {
    $this->_tempNonRecallTagId = $id;
  }

  /**
   * @return int
   */
  public function getTempNonRecallTagId() {
    return $this->_tempNonRecallTagId;
  }

  /**
   * @param string $value
   */
  public function setActiveEligibilityStatusValue($value) {
    $this->_activeEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getActiveEligibilityStatusValue() {
    return $this->_activeEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setAgeEligibilityStatusValue($value) {
    $this->_ageEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getAgeEligibilityStatusValue() {
    return $this->_ageEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setBloodEligibilityStatusValue($value) {
    $this->_bloodEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getBloodEligibilityStatusValue() {
    return $this->_bloodEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setBmiEligibilityStatusValue($value) {
    $this->_bmiEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getBmiEligibilityStatusValue() {
    return $this->_bmiEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setCommercialEligibilityStatusValue($value) {
    $this->_commercialEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getCommercialEligibilityStatusValue() {
    return $this->_commercialEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setDrugsEligibilityStatusValue($value) {
    $this->_drugsEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getDrugsEligibilityStatusValue() {
    return $this->_drugsEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setEligibleEligibilityStatusValue($value) {
    $this->_eligibleEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getEligibleEligibilityStatusValue() {
    return $this->_eligibleEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setEthnicityEligibilityStatusValue($value) {
    $this->_ethnicityEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getEthnicityEligibilityStatusValue() {
    return $this->_ethnicityEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setGenderEligibilityStatusValue($value) {
    $this->_genderEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getGenderEligibilityStatusValue() {
    return $this->_genderEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setMriEligibilityStatusValue($value) {
    $this->_mriEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getMriEligibilityStatusValue() {
    return $this->_mriEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setMaxEligibilityStatusValue($value) {
    $this->_maxEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getMaxEligibilityStatusValue() {
    return $this->_maxEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setOnlineOnlyEligibilityStatusValue($value) {
    $this->_onlineOnlyEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getOnlineOnlyEligibilityStatusValue() {
    return $this->_onlineOnlyEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setOtherEligibilityStatusValue($value) {
    $this->_otherStudyEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getOtherEligibilityStatusValue() {
    return $this->_otherStudyEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setPanelEligibilityStatusValue($value) {
    $this->_panelEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getPanelEligibilityStatusValue() {
    return $this->_panelEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setRecallableEligibilityStatusValue($value) {
    $this->_recallableEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getRecallableEligibilityStatusValue() {
    return $this->_recallableEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setTravelEligibilityStatusValue($value) {
    $this->_travelEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getTravelEligibilityStatusValue() {
    return $this->_travelEligibilityStatusValue;
  }

  /**
   * @param string $value
   */
  public function setAcceptedParticipationStatusValue($value) {
    $this->_acceptedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getAcceptedParticipationStatusValue() {
    return $this->_acceptedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setExcludedParticipationStatusValue($value) {
    $this->_excludedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getExcludedParticipationStatusValue() {
    return $this->_excludedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setInvitationPendingParticipationStatusValue($value) {
    $this->_invitationPendingParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getInvitationPendingParticipationStatusValue() {
    return $this->_invitationPendingParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setInvitedParticipationStatusValue($value) {
    $this->_invitedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getInvitedParticipationStatusValue() {
    return $this->_invitedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setNoResponseParticipationStatusValue($value) {
    $this->_noResponseParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getNoResponseParticipationStatusValue() {
    return $this->_noResponseParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setNotParticipatedParticipationStatusValue($value) {
    $this->_notParticipatedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getNotParticipatedParticipationStatusValue() {
    return $this->_notParticipatedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setParticipatedParticipationStatusValue($value) {
    $this->_participatedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getParticipatedParticipationStatusValue() {
    return $this->_participatedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setRefusedParticipationStatusValue($value) {
    $this->_refusedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getRefusedParticipationStatusValue() {
    return $this->_refusedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setRenegedParticipationStatusValue($value) {
    $this->_renegedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getRenegedParticipationStatusValue() {
    return $this->_renegedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setReturnToSenderParticipationStatusValue($value) {
    $this->_returnToSenderParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getReturnToSenderParticipationStatusValue() {
    return $this->_returnToSenderParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setSelectedParticipationStatusValue($value) {
    $this->_selectedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getSelectedParticipationStatusValue() {
    return $this->_selectedParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setWithdrawnParticipationStatusValue($value) {
    $this->_withdrawnParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getWithdrawnParticipationStatusValue() {
    return $this->_withdrawnParticipationStatusValue;
  }

  /**
   * @param string $value
   */
  public function setCorrectConsentStatusValue($value) {
    $this->_correctConsentStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getCorrectConsentStatusValue() {
    return $this->_correctConsentStatusValue;
  }

  /**
   * @param int $id
   */
  public function setConsentActivityTypeId($id) {
    $this->_consentActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getConsentActivityTypeId() {
    return $this->_consentActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setEmailActivityTypeId($id) {
    $this->_emailActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getEmailActivityTypeId() {
    return $this->_emailActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setIncomingCommunicationActivityTypeId($id) {
    $this->_incomingCommunicationActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getIncomingCommunicationActivityTypeId() {
    return $this->_incomingCommunicationActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setLetterActivityTypeId($id) {
    $this->_letterActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getLetterActivityTypeId() {
    return $this->_letterActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setMeetingActivityTypeId($id) {
    $this->_meetingActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getMeetingActivityTypeId() {
    return $this->_meetingActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setPhoneActivityTypeId($id) {
    $this->_phoneActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getPhoneActivityTypeId() {
    return $this->_phoneActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setSmsActivityTypeId($id) {
    $this->_smsActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getSmsActivityTypeId() {
    return $this->_smsActivityTypeId;
  }

  /**
   * @param string
   */
  public function setVisitStage2Substring($string) {
    $this->_visitStage2Substring = $string;
  }

  /**
   * @return string
   */
  public function getVisitStage2Substring() {
    return $this->_visitStage2Substring;
  }

  /**
   * @param int
   */
  public function setActivityTypeOptionGroupId($id) {
    $this->_activityTypeOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getActivityTypeOptionGroupId() {
    return $this->_activityTypeOptionGroupId;
  }

  /**
   * @param int
   */
  public function setContactIdentityCustomGroupId($id) {
    $this->_contactIdentityCustomGroupId = $id;
  }

  /**
   * @return int
   */
  public function getContactIdentityCustomGroupId() {
    return $this->_contactIdentityCustomGroupId;
  }

  /**
   * @param int
   */
  public function setIdentifierTypeCustomFieldId($id) {
    $this->_identifierTypeCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getIdentifierTypeCustomFieldId() {
    return $this->_identifierTypeCustomFieldId;
  }

}
