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
  private $_outdatedVolunteerStatus = NULL;
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
  private $_exclOnlineEligibilityStatusValue = NULL;
  // encounter medium
  private $_emailMediumId = NULL;
  private $_inPersonMediumId = NULL;
  private $_letterMediumId = NULL;
  private $_phoneMediumId = NULL;
  private $_smsMediumId = NULL;
  // tags
  private $_tempNonRecallTagId = NULL;
  // activity types
  private $_activityTypeOptionGroupId = NULL;
  private $_bulkMailActivityTypeId = NULL;
  private $_consentActivityTypeId = NULL;
  private $_consentStage2ActivityTypeId = NULL;
  private $_assentActivityTypeId = NULL;
  private $_emailActivityTypeId = NULL;
  private $_followUpActivityTypeId = NULL;
  private $_incomingCommunicationActivityTypeId = NULL;
  private $_letterActivityTypeId = NULL;
  private $_meetingActivityTypeId = NULL;
  private $_notRecruitedActivityTypeId = NULL;
  private $_openCaseActivityTypeId = NULL;
  private $_phoneActivityTypeId = NULL;
  private $_redundantActivityTypeId = NULL;
  private $_smsActivityTypeId = NULL;
  private $_sampleReceivedActivityTypeId = NULL;
  private $_visitStage1ActivityTypeId = NULL;
  private $_visitStage2ActivityTypeId = NULL;
  private $_withdrawnActivityTypeId = NULL;
  // activity status
  private $_arrangeActivityStatusId = NULL;
  private $_completedActivityStatusId = NULL;
  private $_returnToSenderActivityStatusId = NULL;
  private $_scheduledActivityStatusId = NULL;
  // custom groups
  private $_contactIdentityCustomGroupId = NULL;
  private $_contactIdentityTableName = NULL;
  private $_consentStage2TableName = NULL;
  private $_consentTableName = NULL;
  private $_assentTableName = NULL;
  private $_diseaseTableName = NULL;
  private $_medicationTableName = NULL;
  private $_participationDataTableName = NULL;
  private $_siteAliasTableName = NULL;
  private $_visitTableName = NULL;
  private $_visitStage2TableName = NULL;
  private $_volunteerPanelTableName = NULL;
  private $_volunteerStatusTableName = NULL;
  private $_volunteerSelectionTableName = NULL;
  private $_redundantTableName = NULL;
  private $_withdawnTableName = NULL;

  // custom fields
  private $_attemptsCustomFieldId = NULL;
  private $_bleedDifficultiesCustomFieldId = NULL;
  private $_claimReceivedDateCustomFieldId = NULL;
  private $_claimSubmittedDateCustomFieldId = NULL;
  private $_collectedByCustomFieldId = NULL;
  private $_consentVersionColumnName = NULL;
  private $_consentVersionStage2CustomFieldId = NULL;
  private $_assentVersionColumnName = NULL;

  private $_assentStatusColumnName = NULL;


  private $_assentPisVersionColumnName = NULL;
  private $_diagnosisAgeColumnName = NULL;
  private $_diagnosisYearColumnName = NULL;
  private $_diseaseColumnName = NULL;
  private $_diseaseNotesColumnName = NULL;
  private $_drugFamilyColumnName = NULL;
  private $_expensesNotesCustomFieldId = NULL;
  private $_familyMemberColumnName = NULL;
  private $_identifierTypeCustomFieldId = NULL;
  private $_identifierTypeColumnName = NULL;
  private $_identifierColumnName = NULL;
  private $_incidentFormCustomFieldId = NULL;
  private $_leafletVersionColumnName = NULL;
  private $_medicationDateColumnName = NULL;
  private $_medicationNameColumnName = NULL;
  private $_mileageCustomFieldId =  NULL;
  private $_notRecruitedReasonCustomFieldId = NULL;
  private $_otherExpensesCustomFieldId = NULL;
  private $_parkingFeeCustomFieldId = NULL;
  private $_participationStudyIdColumnName = NULL;
  private $_questionnaireVersionStage2CustomFieldId = NULL;
  private $_redundantReasonCustomFieldId = NULL;
  private $_redundantDestroySamplesCustomFieldId = NULL;
  private $_redundantDestroyDataCustomFieldId = NULL;
  private $_redundantDestroyDataColumnName = NULL;
  private $_sampleSiteCustomFieldId = NULL;
  private $_siteAliasColumnName = NULL;
  private $_siteAliasTypeColumnName = NULL;
  private $_studyParticipationStatusColumnName = NULL;
  private $_studyPaymentCustomFieldId = NULL;
  private $_takingMedicationColumnName = NULL;
  private $_toLabDateCustomFieldId = NULL;
  private $_withdrawnReasonCustomFieldId = NULL;
  private $_withdrawnDestroySamplesCustomFieldId = NULL;
  private $_withdrawnDestroyDataCustomFieldId = NULL;
  private $_withdrawnDestroyDataColumnName = NULL;
  private $_volunteerCentreColumnName = NULL;
  private $_volunteerCentreCustomFieldId = NULL;
  private $_volunteerPanelColumnName = NULL;
  private $_volunteerPanelCustomFieldId = NULL;
  private $_volunteerSiteColumnName = NULL;
  private $_volunteerSiteCustomFieldId = NULL;
  private $_volunteerSourceColumnName = NULL;
  private $_volunteerSourceCustomFieldId = NULL;
  private $_volunteerStatusColumnName = NULL;
  private $_noOnlineStudiesColumnName = NULL;
  private $_noOnlineStudiesCustomFieldId = NULL;
   // study participation status
  private $_acceptedParticipationStatusValue = NULL;
  private $_declinedParticipationStatusValue = NULL;
  private $_excludedParticipationStatusValue = NULL;
  private $_invitationPendingParticipationStatusValue = NULL;
  private $_invitedParticipationStatusValue = NULL;
  private $_noResponseParticipationStatusValue = NULL;
  private $_notParticipatedParticipationStatusValue = NULL;
  private $_participatedParticipationStatusValue = NULL;
  private $_renegedParticipationStatusValue = NULL;
  private $_returnToSenderParticipationStatusValue = NULL;
  private $_selectedParticipationStatusValue = NULL;
  private $_withdrawnParticipationStatusValue = NULL;
  // option group ids
  private $_bleedDifficultiesOptionGroupId = NULL;
  private $_campaignStatusOptionGroupId = NULL;
  private $_participationConsentVersionOptionGroupId = NULL;
  private $_consentVersionOptionGroupId = NULL;
  private $_assentVersionOptionGroupId = NULL;
  private $_assentPisVersionOptionGroupId = NULL;
  private $_diseaseOptionGroupId = NULL;
  private $_drugFamilyOptionGroupId = NULL;
  private $_ethnicityOptionGroupId = NULL;
  private $_familyMemberOptionGroupId = NULL;
  private $_leafletVersionOptionGroupId = NULL;
  private $_medicationOptionGroupId = NULL;
  private $_notRecruitedReasonOptionGroupId = NULL;
  private $_questionnaireOptionGroupId = NULL;
  private $_redundantReasonOptionGroupId = NULL;
  private $_sampleSiteOptionGroupId = NULL;
  private $_studyPaymentOptionGroupId = NULL;
  private $_volunteerStatusOptionGroupId = NULL;
  private $_withdrawnReasonOptionGroupId = NULL;
  // default mailing parameters
  private $_autoResponderId = NULL;
  private $_mailingFooterId = NULL;
  private $_mailingHeaderId = NULL;
  private $_optOutId = NULL;
  private $_resubscribeId = NULL;
  private $_subscribeId = NULL;
  private $_unsubscribeId = NULL;
  private $_welcomeId = NULL;
    // others
  private $_participationCaseTypeName = NULL;
  private $_recruitmentCaseTypeName = NULL;
  private $_correctConsentStatusValue = NULL;
  private $_visitStage2Substring = NULL;
  private $_normalPriorityId = NULL;
  private $_otherBleedDifficultiesValue = NULL;
  private $_otherSampleSiteValue = NULL;
  private $_defaultNbrMailingType = NULL;
  private $_assigneeRecordTypeId = NULL;
  private $_sourceRecordTypeId = NULL;
  private $_targetRecordTypeId = NULL;
  private $_homeLocationTypeId = NULL;
  private $_otherLocationTypeId = NULL;
  private $_workLocationTypeId = NULL;
  private $_bioResourcersGroupId = NULL;
  private $_ukCountryId = NULL;
  private $_participantIdIdentifierType = NULL;
  private $_bioresourceIdIdentifierType = NULL;

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
  public function setOutdatedVolunteerStatus($status) {
    $this->_outdatedVolunteerStatus = $status;
  }

  /**
   * @return string
   */
  public function getOutdatedVolunteerStatus() {
    return $this->_outdatedVolunteerStatus;
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
  public function setExclOnlineEligibilityStatusValue($value) {
    $this->_exclOnlineEligibilityStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getExclOnlineEligibilityStatusValue() {
    return $this->_exclOnlineEligibilityStatusValue;
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
  public function setDeclinedParticipationStatusValue($value) {
    $this->_declinedParticipationStatusValue = $value;
  }

  /**
   * @return string
   */
  public function getDeclinedParticipationStatusValue() {
    return $this->_declinedParticipationStatusValue;
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

  public function setAssentActivityTypeId($id) {
    $this->_assentActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getConsentActivityTypeId() {
    return $this->_consentActivityTypeId;
  }

  public function getAssentActivityTypeId() {
    return $this->_assentActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setBulkMailActivityTypeId($id) {
    $this->_bulkMailActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getBulkMailActivityTypeId() {
    return $this->_bulkMailActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setConsentStage2ActivityTypeId($id) {
    $this->_consentStage2ActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getConsentStage2ActivityTypeId() {
    return $this->_consentStage2ActivityTypeId;
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
  public function setFollowUpActivityTypeId($id) {
    $this->_followUpActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getFollowUpActivityTypeId() {
    return $this->_followUpActivityTypeId;
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
  public function setNotRecruitedActivityTypeId($id) {
    $this->_notRecruitedActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getNotRecruitedActivityTypeId() {
    return $this->_notRecruitedActivityTypeId;
  }

  /**
   * @param int $id
   */
  public function setOpenCaseActivityTypeId($id) {
    $this->_openCaseActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getOpenCaseActivityTypeId() {
    return $this->_openCaseActivityTypeId;
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
  public function setRedundantActivityTypeId($id) {
    $this->_redundantActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getRedundantActivityTypeId() {
    return $this->_redundantActivityTypeId;
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
   * @param int $id
   */
  public function setWithdrawnActivityTypeId($id) {
    $this->_withdrawnActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getWithdrawnActivityTypeId() {
    return $this->_withdrawnActivityTypeId;
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
   * @param string
   */
  public function setContactIdentityTableName($name) {
    $this->_contactIdentityTableName = $name;
  }

  /**
   * @return string
   */
  public function getContactIdentityTableName() {
    return $this->_contactIdentityTableName;
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
   * @param string
   */
  public function setDiagnosisAgeColumnName($name) {
    $this->_diagnosisAgeColumnName = $name;
  }

  /**
   * @return string
   */
  public function getDiagnosisAgeColumnName() {
    return $this->_diagnosisAgeColumnName;
  }

  /**
   * @param string
   */
  public function setDiagnosisYearColumnName($name) {
    $this->_diagnosisYearColumnName = $name;
  }

  /**
   * @return string
   */
  public function getDiagnosisYearColumnName() {
    return $this->_diagnosisYearColumnName;
  }

  /**
   * @param string
   */
  public function setDiseaseColumnName($name) {
    $this->_diseaseColumnName = $name;
  }

  /**
   * @return string
   */
  public function getDiseaseColumnName() {
    return $this->_diseaseColumnName;
  }

  /**
   * @param string
   */
  public function setDiseaseNotesColumnName($name) {
    $this->_diseaseNotesColumnName = $name;
  }

  /**
   * @return string
   */
  public function getDiseaseNotesColumnName() {
    return $this->_diseaseNotesColumnName;
  }

  /**
   * @param string
   */
  public function setDrugFamilyColumnName($name) {
    $this->_drugFamilyColumnName = $name;
  }

  /**
   * @return string
   */
  public function getDrugFamilyColumnName() {
    return $this->_drugFamilyColumnName;
  }

  /**
   * @param string
   */
  public function setMedicationDateColumnName($name) {
    $this->_medicationDateColumnName = $name;
  }

  /**
   * @return string
   */
  public function getMedicationDateColumnName() {
    return $this->_medicationDateColumnName;
  }

  /**
   * @param string
   */
  public function setMedicationNameColumnName($name) {
    $this->_medicationNameColumnName = $name;
  }

  /**
   * @return string
   */
  public function getMedicationNameColumnName() {
    return $this->_medicationNameColumnName;
  }

  /**
   * @param string
   */
  public function setFamilyMemberColumnName($name) {
    $this->_familyMemberColumnName = $name;
  }

  /**
   * @return string
   */
  public function getFamilyMemberColumnName() {
    return $this->_familyMemberColumnName;
  }

  /**
   * @param string
   */
  public function setTakingMedicationColumnName($name) {
    $this->_takingMedicationColumnName = $name;
  }

  /**
   * @return string
   */
  public function getTakingMedicationColumnName() {
    return $this->_takingMedicationColumnName;
  }

  /**
   * @param string
   */
  public function setIdentifierColumnName($name) {
    $this->_identifierColumnName = $name;
  }

  /**
   * @return string
   */
  public function getIdentifierColumnName() {
    return $this->_identifierColumnName;
  }

  /**
   * @param string
   */
  public function setIdentifierTypeColumnName($name) {
    $this->_identifierTypeColumnName = $name;
  }

  /**
   * @return string
   */
  public function getIdentifierTypeColumnName() {
    return $this->_identifierTypeColumnName;
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

  /**
   * @param int
   */
  public function setArrangeActivityStatusId($id) {
    $this->_arrangeActivityStatusId = $id;
  }

  /**
   * @return int
   */
  public function getArrangeActivityStatusId() {
    return $this->_arrangeActivityStatusId;
  }

  /**
   * @param int
   */
  public function setCompletedActivityStatusId($id) {
    $this->_completedActivityStatusId = $id;
  }

  /**
   * @return int
   */
  public function getCompletedActivityStatusId() {
    return $this->_completedActivityStatusId;
  }

  /**
   * @param int
   */
  public function setReturnToSenderActivityStatusId($id) {
    $this->_returnToSenderActivityStatusId = $id;
  }

  /**
   * @return int
   */
  public function getReturnToSenderActivityStatusId() {
    return $this->_returnToSenderActivityStatusId;
  }

  /**
   * @param int
   */
  public function setScheduledActivityStatusId($id) {
    $this->_scheduledActivityStatusId = $id;
  }

  /**
   * @return int
   */
  public function getScheduledActivityStatusId() {
    return $this->_scheduledActivityStatusId;
  }

  /**
   * @param int
   */
  public function setNormalPriorityId($id) {
    $this->_normalPriorityId = $id;
  }

  /**
   * @return int
   */
  public function getNormalPriorityId() {
    return $this->_normalPriorityId;
  }

  /**
   * @param int
   */
  public function setEmailMediumId($id) {
    $this->_emailMediumId = $id;
  }

  /**
   * @return int
   */
  public function getEmailMediumId() {
    return $this->_emailMediumId;
  }

  /**
   * @param int
   */
  public function setInPersonMediumId($id) {
    $this->_inPersonMediumId = $id;
  }

  /**
   * @return int
   */
  public function getInPersonMediumId() {
    return $this->_inPersonMediumId;
  }

  /**
   * @param int
   */
  public function setLetterMediumId($id) {
    $this->_letterMediumId = $id;
  }

  /**
   * @return int
   */
  public function getLetterMediumId() {
    return $this->_letterMediumId;
  }

  /**
   * @param int
   */
  public function setPhoneMediumId($id) {
    $this->_phoneMediumId = $id;
  }

  /**
   * @return int
   */
  public function getPhoneMediumId() {
    return $this->_phoneMediumId;
  }

  /**
   * @param int
   */
  public function setSmsMediumId($id) {
    $this->_smsMediumId = $id;
  }

  /**
   * @return int
   */
  public function getSmsMediumId() {
    return $this->_smsMediumId;
  }

  /**
   * @param int
   */
  public function setSampleReceivedActivityTypeId($id) {
    $this->_sampleReceivedActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getSampleReceivedActivityTypeId() {
    return $this->_sampleReceivedActivityTypeId;
  }

  /**
   * @param int
   */
  public function setVisitStage1ActivityTypeId($id) {
    $this->_visitStage1ActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getVisitStage1ActivityTypeId() {
    return $this->_visitStage1ActivityTypeId;
  }

  /**
   * @param int
   */
  public function setVisitStage2ActivityTypeId($id) {
    $this->_visitStage2ActivityTypeId = $id;
  }

  /**
   * @return int
   */
  public function getVisitStage2ActivityTypeId() {
    return $this->_visitStage2ActivityTypeId;
  }

  /**
   * @param string
   */
  public function setVisitTableName($name) {
    $this->_visitTableName = $name;
  }

  /**
   * @return string
   */
  public function getVisitTableName() {
    return $this->_visitTableName;
  }

  /**
   * @param string
   */
  public function setVisitStage2TableName($name) {
    $this->_visitStage2TableName = $name;
  }

  /**
   * @return string
   */
  public function getVisitStage2TableName() {
    return $this->_visitStage2TableName;
  }

  /**
   * @param string
   */
  public function setConsentStage2TableName($name) {
    $this->_consentStage2TableName = $name;
  }

  /**
   * @return string
   */
  public function getConsentStage2TableName() {
    return $this->_consentStage2TableName;
  }

  /**
   * @param string
   */
  public function setSiteAliasTableName($name) {
    $this->_siteAliasTableName = $name;
  }

  /**
   * @return string
   */
  public function getSiteAliasTableName() {
    return $this->_siteAliasTableName;
  }

  /**
   * @param string
   */
  public function setVolunteerPanelTableName($name) {
    $this->_volunteerPanelTableName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerPanelTableName() {
    return $this->_volunteerPanelTableName;
  }

  /**
   * @param string
   */
  public function setVolunteerSelectionTableName($name) {
    $this->_volunteerSelectionTableName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerSelectionTableName() {
    return $this->_volunteerSelectionTableName;
  }

  /**
   * @param string
   */
  public function setVolunteerStatusTableName($name) {
    $this->_volunteerStatusTableName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerStatusTableName() {
    return $this->_volunteerStatusTableName;
  }

  /**
   * @param string
   */
  public function setSiteAliasColumnName($name) {
    $this->_siteAliasColumnName = $name;
  }

  /**
   * @return string
   */
  public function getSiteAliasColumnName() {
    return $this->_siteAliasColumnName;
  }

  /**
   * @param string
   */
  public function setSiteAliasTypeColumnName($name) {
    $this->_siteAliasTypeColumnName = $name;
  }

  /**
   * @return string
   */
  public function getSiteAliasTypeColumnName() {
    return $this->_siteAliasTypeColumnName;
  }

  /**
   * @param string
   */
  public function setVolunteerCentreColumnName($name) {
    $this->_volunteerCentreColumnName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerCentreColumnName() {
    return $this->_volunteerCentreColumnName;
  }

  /**
   * @param int
   */
  public function setVolunteerCentreCustomFieldId($id) {
    $this->_volunteerCentreCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getVolunteerCentreCustomFieldId() {
    return $this->_volunteerCentreCustomFieldId;
  }

  /**
   * @param string
   */
  public function setVolunteerPanelColumnName($name) {
    $this->_volunteerPanelColumnName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerPanelColumnName() {
    return $this->_volunteerPanelColumnName;
  }

  /**
   * @param int
   */
  public function setVolunteerPanelCustomFieldId($id) {
    $this->_volunteerPanelCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getVolunteerPanelCustomFieldId() {
    return $this->_volunteerPanelCustomFieldId;
  }

  /**
   * @param string
   */
  public function setVolunteerSiteColumnName($name) {
    $this->_volunteerSiteColumnName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerSiteColumnName() {
    return $this->_volunteerSiteColumnName;
  }

  /**
   * @param int
   */
  public function setVolunteerSiteCustomFieldId($id) {
    $this->_volunteerSiteCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getVolunteerSiteCustomFieldId() {
    return $this->_volunteerSiteCustomFieldId;
  }

  /**
   * @param string
   */
  public function setVolunteerSourceColumnName($name) {
    $this->_volunteerSourceColumnName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerSourceColumnName() {
    return $this->_volunteerSourceColumnName;
  }

  /**
   * @param int
   */
  public function setVolunteerSourceCustomFieldId($id) {
    $this->_volunteerSourceCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getVolunteerSourceCustomFieldId() {
    return $this->_volunteerSourceCustomFieldId;
  }

  /**
   * @param string
   */
  public function setNoOnlineStudiesColumnName($name) {
    $this->_noOnlineStudiesColumnName = $name;
  }

  /**
   * @return string
   */
  public function getNoOnlineStudiesColumnName() {
    return $this->_noOnlineStudiesColumnName;
  }

  /**
   * @param int
   */
  public function setNoOnlineStudiesCustomFieldId($id) {
    $this->_noOnlineStudiesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getNoOnlineStudiesCustomFieldId() {
    return $this->_noOnlineStudiesCustomFieldId;
  }

  /**
   * @param string
   */
  public function setVolunteerStatusColumnName($name) {
    $this->_volunteerStatusColumnName = $name;
  }

  /**
   * @return string
   */
  public function getVolunteerStatusColumnName() {
    return $this->_volunteerStatusColumnName;
  }

  /**
   * @param int
   */
  public function setAttemptsCustomFieldId($id) {
    $this->_attemptsCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getAttemptsCustomFieldId() {
    return $this->_attemptsCustomFieldId;
  }

  /**
   * @param int
   */
  public function setBleedDifficultiesCustomFieldId($id) {
    $this->_bleedDifficultiesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getBleedDifficultiesCustomFieldId() {
    return $this->_bleedDifficultiesCustomFieldId;
  }

  /**
   * @param int
   */
  public function setClaimReceivedDateCustomFieldId($id) {
    $this->_claimReceivedDateCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getClaimReceivedDateCustomFieldId() {
    return $this->_claimReceivedDateCustomFieldId;
  }

  /**
   * @param int
   */
  public function setClaimSubmittedDateCustomFieldId($id) {
    $this->_claimSubmittedDateCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getClaimSubmittedDateCustomFieldId() {
    return $this->_claimSubmittedDateCustomFieldId;
  }

  /**
   * @param int
   */
  public function setCollectedByCustomFieldId($id) {
    $this->_collectedByCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getCollectedByCustomFieldId() {
    return $this->_collectedByCustomFieldId;
  }

  /**
   * @param int
   */
  public function setExpensesNotesCustomFieldId($id) {
    $this->_expensesNotesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getExpensesNotesCustomFieldId() {
    return $this->_expensesNotesCustomFieldId;
  }

  /**
   * @param int
   */
  public function setIncidentFormCustomFieldId($id) {
    $this->_incidentFormCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getIncidentFormCustomFieldId() {
    return $this->_incidentFormCustomFieldId;
  }

  /**
   * @param int
   */
  public function setMileageCustomFieldId($id) {
    $this->_mileageCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getMileageCustomFieldId() {
    return $this->_mileageCustomFieldId;
  }

  /**
   * @param int
   */
  public function setNotRecruitedReasonCustomFieldId($id) {
    $this->_notRecruitedReasonCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getNotRecruitedReasonCustomFieldId() {
    return $this->_notRecruitedReasonCustomFieldId;
  }

  /**
   * @param int
   */
  public function setOtherExpensesCustomFieldId($id) {
    $this->_otherExpensesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getOtherExpensesCustomFieldId() {
    return $this->_otherExpensesCustomFieldId;
  }

  /**
   * @param int
   */
  public function setParkingFeeCustomFieldId($id) {
    $this->_parkingFeeCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getParkingFeeCustomFieldId() {
    return $this->_parkingFeeCustomFieldId;
  }

  /**
   * @param string
   */
  public function setParticipationStudyIdColumnName($name) {
    $this->_participationStudyIdColumnName = $name;
  }

  /**
   * @return string
   */
  public function getParticipationStudyIdColumnName() {
    return $this->_participationStudyIdColumnName;
  }

  /**
   * @param int
   */
  public function setRedundantReasonCustomFieldId($id) {
    $this->_redundantReasonCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getRedundantReasonCustomFieldId() {
    return $this->_redundantReasonCustomFieldId;
  }

  /**
   * @param int
   */
  public function setRedundantDestroyDataCustomFieldId($id) {
    $this->_redundantDestroyDataCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getRedundantDestroyDataCustomFieldId() {
    return $this->_redundantDestroyDataCustomFieldId;
  }

  /**
   * @param string
   */
  public function setRedundantDestroyDataColumnName(string $name) {
    $this->_redundantDestroyDataColumnName = $name;
  }

  /**
   * @return string
   */
  public function getRedundantDestroyDataColumnName() {
    return $this->_redundantDestroyDataColumnName;
  }

  /**
   * @param string
   */
  public function setWithdrawnDestroyDataColumnName(string $name) {
    $this->_withdrawnDestroyDataColumnName = $name;
  }

  /**
   * @return string
   */
  public function getWithdrawnDestroyDataColumnName() {
    return $this->_withdrawnDestroyDataColumnName;
  }

  /**
   * @param int
   */
  public function setRedundantDestroySamplesCustomFieldId($id) {
    $this->_redundantDestroySamplesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getRedundantDestroySamplesCustomFieldId() {
    return $this->_redundantDestroySamplesCustomFieldId;
  }

  /**
   * @param int
   */
  public function setWithdrawnReasonCustomFieldId($id) {
    $this->_withdrawnReasonCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getWithdrawnReasonCustomFieldId() {
    return $this->_withdrawnReasonCustomFieldId;
  }

  /**
   * @param int
   */
  public function setWithdrawnDestroyDataCustomFieldId($id) {
    $this->_withdrawnDestroyDataCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getWithdrawnDestroyDataCustomFieldId() {
    return $this->_withdrawnDestroyDataCustomFieldId;
  }

  /**
   * @param int
   */
  public function setWithdrawnDestroySamplesCustomFieldId($id) {
    $this->_withdrawnDestroySamplesCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getWithdrawnDestroySamplesCustomFieldId() {
    return $this->_withdrawnDestroySamplesCustomFieldId;
  }

  /**
   * @param int
   */
  public function setSampleSiteCustomFieldId($id) {
    $this->_sampleSiteCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getSampleSiteCustomFieldId() {
    return $this->_sampleSiteCustomFieldId;
  }

  /**
   * @param int
   */
  public function setToLabDateCustomFieldId($id) {
    $this->_toLabDateCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getToLabDateCustomFieldId() {
    return $this->_toLabDateCustomFieldId;
  }

  /**
   * @param int
   */
  public function setConsentVersionStage2CustomFieldId($id) {
    $this->_consentVersionStage2CustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getConsentVersionStage2CustomFieldId() {
    return $this->_consentVersionStage2CustomFieldId;
  }

  /**
   * @param int
   */
  public function setQuestionnaireVersionStage2CustomFieldId($id) {
    $this->_questionnaireVersionStage2CustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getQuestionnaireVersionStage2CustomFieldId() {
    return $this->_questionnaireVersionStage2CustomFieldId;
  }

  /**
   * @param int
   */
  public function setStudyPaymentCustomFieldId($id) {
    $this->_studyPaymentCustomFieldId = $id;
  }

  /**
   * @return int
   */
  public function getStudyPaymentCustomFieldId() {
    return $this->_studyPaymentCustomFieldId;
  }

  /**
   * @param int
   */
  public function setBleedDifficultiesOptionGroupId($id) {
    $this->_bleedDifficultiesOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getBleedDifficultiesOptionGroupId() {
    return $this->_bleedDifficultiesOptionGroupId;
  }

  /**
   * @param int
   */
  public function setParticipationConsentVersionOptionGroupId($id) {
    $this->_participationConsentVersionOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getParticipationConsentVersionOptionGroupId() {
    return $this->_participationConsentVersionOptionGroupId;
  }

  /**
   * @param int
   */
  public function setConsentVersionOptionGroupId($id) {
    $this->_consentVersionOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getConsentVersionOptionGroupId() {
    return $this->_consentVersionOptionGroupId;
  }

  public function setAssentVersionOptionGroupId($id) {
    $this->_assentVersionOptionGroupId = $id;
  }

  public function getAssentVersionOptionGroupId() {
    return $this->_assentVersionOptionGroupId;
  }

  public function setAssentPisVersionOptionGroupId($id) {
    $this->_assentPisVersionOptionGroupId = $id;
  }

  public function getAssentPisVersionOptionGroupId() {
    return $this->_assentPisVersionOptionGroupId;
  }


  /**
   * @param int
   */
  public function setDiseaseOptionGroupId($id) {
    $this->_diseaseOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getDiseaseOptionGroupId() {
    return $this->_diseaseOptionGroupId;
  }

  /**
   * @param int
   */
  public function setDrugFamilyOptionGroupId($id) {
    $this->_drugFamilyOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getDrugFamilyOptionGroupId() {
    return $this->_drugFamilyOptionGroupId;
  }

  /**
   * @param int
   */
  public function setEthnicityOptionGroupId($id) {
    $this->_ethnicityOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getEthnicityOptionGroupId() {
    return $this->_ethnicityOptionGroupId;
  }

  /**
   * @param int
   */
  public function setFamilyMemberOptionGroupId($id) {
    $this->_familyMemberOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getFamilyMemberOptionGroupId() {
    return $this->_familyMemberOptionGroupId;
  }

  /**
   * @param int
   */
  public function setLeafletVersionOptionGroupId($id) {
    $this->_leafletVersionOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getLeafletVersionOptionGroupId() {
    return $this->_leafletVersionOptionGroupId;
  }

  /**
   * @param int
   */
  public function setMedicationOptionGroupId($id) {
    $this->_medicationOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getMedicationOptionGroupId() {
    return $this->_medicationOptionGroupId;
  }

  /**
   * @param int
   */
  public function setNotRecruitedReasonOptionGroupId($id) {
    $this->_notRecruitedReasonOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getNotRecruitedReasonOptionGroupId() {
    return $this->_notRecruitedReasonOptionGroupId;
  }

  /**
   * @param int
   */
  public function setQuestionnaireVersionOptionGroupId($id) {
    $this->_questionnaireOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getQuestionnaireVersionOptionGroupId() {
    return $this->_questionnaireOptionGroupId;
  }

  /**
   * @param int
   */
  public function setRedundantReasonOptionGroupId($id) {
    $this->_redundantReasonOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getRedundantReasonOptionGroupId() {
    return $this->_redundantReasonOptionGroupId;
  }

  /**
   * @param int
   */
  public function setSampleSiteOptionGroupId($id) {
    $this->_sampleSiteOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getSampleSiteOptionGroupId() {
    return $this->_sampleSiteOptionGroupId;
  }

  /**
   * @param int
   */
  public function setStudyPaymentOptionGroupId($id) {
    $this->_studyPaymentOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getStudyPaymentOptionGroupId() {
    return $this->_studyPaymentOptionGroupId;
  }

  /**
   * @param int
   */
  public function setVolunteerStatusOptionGroupId($id) {
    $this->_volunteerStatusOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getVolunteerStatusOptionGroupId() {
    return $this->_volunteerStatusOptionGroupId;
  }

  /**
   * @param int
   */
  public function setWithdrawnReasonOptionGroupId($id) {
    $this->_withdrawnReasonOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getWithdrawnReasonOptionGroupId() {
    return $this->_withdrawnReasonOptionGroupId;
  }

  /**
   * @param int
   */
  public function setCampaignStatusOptionGroupId($id) {
    $this->_campaignStatusOptionGroupId = $id;
  }

  /**
   * @return int
   */
  public function getCampaignStatusOptionGroupId() {
    return $this->_campaignStatusOptionGroupId;
  }

  /**
   * @param string
   */
  public function setOtherBleedDifficultiesValue($value) {
    $this->_otherBleedDifficultiesValue = $value;
  }

  /**
   * @return string
   */
  public function getOtherBleedDifficultiesValue() {
    return $this->_otherBleedDifficultiesValue;
  }

  /**
   * @param string
   */
  public function setOtherSampleSiteValue($value) {
    $this->_otherSampleSiteValue = $value;
  }

  /**
   * @return string
   */
  public function getOtherSampleSiteValue() {
    return $this->_otherSampleSiteValue;
  }

  /**
   * @param string
   */
  public function setDefaultNbrMailingType($type) {
    $this->_defaultNbrMailingType = $type;
  }

  /**
   * @return string
   */
  public function getDefaultNbrMailingType() {
    return $this->_defaultNbrMailingType;
  }

  /**
   * @param int
   */
  public function setAutoResponderId($id) {
    $this->_autoResponderId = $id;
  }

  /**
   * @return int
   */
  public function getAutoResponderId() {
    return $this->_autoResponderId;
  }

  /**
   * @param int
   */
  public function setMailingFooterId($id) {
    $this->_mailingFooterId = $id;
  }

  /**
   * @return int
   */
  public function getMailingFooterId() {
    return $this->_mailingFooterId;
  }

  /**
   * @param int
   */
  public function setMailingHeaderId($id) {
    $this->_mailingHeaderId = $id;
  }

  /**
   * @return int
   */
  public function getMailingHeaderId() {
    return $this->_mailingHeaderId;
  }

  /**
   * @param int
   */
  public function setOptOutId($id) {
    $this->_optOutId = $id;
  }

  /**
   * @return int
   */
  public function getOptOutId() {
    return $this->_optOutId;
  }

  /**
   * @param int
   */
  public function setResubscribeId($id) {
    $this->_resubscribeId = $id;
  }

  /**
   * @return int
   */
  public function getResubscribeId() {
    return $this->_resubscribeId  ;
  }

  /**
   * @param int
   */
  public function setSubscribeId($id) {
    $this->_subscribeId = $id;
  }

  /**
   * @return int
   */
  public function getSubscribeId() {
    return $this->_subscribeId  ;
  }

  /**
   * @param int
   */
  public function setUnsubscribeId($id) {
    $this->_unsubscribeId = $id;
  }

  /**
   * @return int
   */
  public function getUnsubscribeId() {
    return $this->_unsubscribeId  ;
  }

  /**
   * @param int
   */
  public function setWelcomeId($id) {
    $this->_welcomeId = $id;
  }

  /**
   * @return int
   */
  public function getWelcomeId() {
    return $this->_welcomeId  ;
  }

  /**
   * @param int
   */
  public function setAssigneeRecordTypeId($id) {
    $this->_assigneeRecordTypeId = $id;
  }

  /**
   * @return int
   */
  public function getAssigneeRecordTypeId() {
    return $this->_assigneeRecordTypeId  ;
  }

  /**
   * @param int
   */
  public function setSourceRecordTypeId($id) {
    $this->_sourceRecordTypeId = $id;
  }

  /**
   * @return int
   */
  public function getSourceRecordTypeId() {
    return $this->_sourceRecordTypeId  ;
  }

  /**
   * @param int
   */
  public function setTargetRecordTypeId($id) {
    $this->_targetRecordTypeId = $id;
  }

  /**
   * @return int
   */
  public function getTargetRecordTypeId() {
    return $this->_targetRecordTypeId  ;
  }

  /**
   * @param int
   */
  public function setHomeLocationTypeId($id) {
    $this->_homeLocationTypeId = $id;
  }

  /**
   * @return int
   */
  public function getHomeLocationTypeId() {
    return $this->_homeLocationTypeId  ;
  }

  /**
   * @param int
   */
  public function setOtherLocationTypeId($id) {
    $this->_otherLocationTypeId = $id;
  }

  /**
   * @return int
   */
  public function getOtherLocationTypeId() {
    return $this->_otherLocationTypeId  ;
  }

  /**
   * @param int
   */
  public function setWorkLocationTypeId($id) {
    $this->_workLocationTypeId = $id;
  }

  /**
   * @return int
   */
  public function getWorkLocationTypeId() {
    return $this->_workLocationTypeId  ;
  }

  /**
   * @param int
   */
  public function setBioResourcersGroupId($id) {
    $this->_bioResourcersGroupId = $id;
  }

  /**
   * @return int
   */
  public function getBioResourcersGroupId() {
    return $this->_bioResourcersGroupId  ;
  }

  /**
   * @param int
   */
  public function setUkCountryId($id) {
    $this->_ukCountryId = $id;
  }

  /**
   * @return int
   */
  public function getUkCountryId() {
    return $this->_ukCountryId  ;
  }

  /**
   * @param string
   */
  public function setConsentTableName($name) {
    $this->_consentTableName = $name;
  }

  /**
   * @return string
   */
  public function getConsentTableName() {
    return $this->_consentTableName  ;
  }

  public function setAssentTableName($name) {
    $this->_assentTableName = $name;
  }

  public function getAssentTableName() {
    return $this->_assentTableName  ;
  }

  /**
   * @param string
   */
  public function setDiseaseTableName($name) {
    $this->_diseaseTableName = $name;
  }

  /**
   * @return string
   */
  public function getDiseaseTableName() {
    return $this->_diseaseTableName  ;
  }

  /**
   * @param string
   */
  public function setRedundantTableName($name) {
    $this->_redundantTableName = $name;
  }

  /**
   * @return string
   */
  public function getRedundantTableName() {
    return $this->_redundantTableName  ;
  }

  /**
   * @param string
   */
  public function setWithdrawnTableName($name) {
    $this->_withdawnTableName = $name;
  }

  /**
   * @return string
   */
  public function getWithdrawnTableName() {
    return $this->_withdawnTableName  ;
  }

  /**
   * @param string
   */
  public function setMedicationTableName($name) {
    $this->_medicationTableName = $name;
  }

  /**
   * @return string
   */
  public function getMedicationTableName() {
    return $this->_medicationTableName;
  }

  /**
   * @param string
   */
  public function setParticipationDataTableName($name) {
    $this->_participationDataTableName = $name;
  }

  /**
   * @return string
   */
  public function getParticipationDataTableName() {
    return $this->_participationDataTableName  ;
  }

  /**
   * @param string
   */
  public function setConsentVersionColumnName($name) {
    $this->_consentVersionColumnName = $name;
  }

  /**
   * @return string
   */
  public function getConsentVersionColumnName() {
    return $this->_consentVersionColumnName  ;
  }

  /**
   * @param string
   */
  public function setLeafletVersionColumnName($name) {
    $this->_leafletVersionColumnName = $name;
  }

  /**
   * @return string
   */
  public function getLeafletVersionColumnName() {
    return $this->_leafletVersionColumnName  ;
  }


  public function setAssentVersionColumnName($name) {
    $this->_assentVersionColumnName = $name;
  }

  public function getAssentVersionColumnName() {
    return $this->_assentVersionColumnName  ;
  }

  public function setAssentPisVersionColumnName($name) {
    $this->_assentPisVersionColumnName = $name;
  }

  public function getAssentPisVersionColumnName() {
    return $this->_assentPisVersionColumnName  ;
  }


  public function setAssentStatusColumnName($name) {
    $this->_assentStatusColumnName = $name;
  }

  public function getAssentStatusColumnName() {
    return $this->_assentStatusColumnName  ;
  }


  /**
   * @param string
   */
  public function setStudyParticipationStatusColumnName($name) {
    $this->_studyParticipationStatusColumnName = $name;
  }

  /**
   * @return string
   */
  public function getStudyParticipationStatusColumnName() {
    return $this->_studyParticipationStatusColumnName  ;
  }

  /**
   * @param string
   */
  public function setBioresourceIdIdentifierType($name) {
    $this->_bioresourceIdIdentifierType = $name;
  }

  /**
   * @return string
   */
  public function getBioresourceIdIdentifierType() {
    return $this->_bioresourceIdIdentifierType;
  }

  /**
   * @param string
   */
  public function setParticipantIdIdentifierType($name) {
    $this->_participantIdIdentifierType = $name;
  }

  /**
   * @return string
   */
  public function getParticipantIdIdentifierType() {
    return $this->_participantIdIdentifierType  ;
  }

  /**
   * @param string
   */
  public function setParticipationCaseTypeName($name) {
    $this->_participationCaseTypeName = $name;
  }

  /**
   * @return string
   */
  public function getParticipationCaseTypeName() {
    return $this->_participationCaseTypeName  ;
  }

  /**
   * @param string
   */
  public function setRecruitmentCaseTypeName($name) {
    $this->_recruitmentCaseTypeName = $name;
  }

  /**
   * @return string
   */
  public function getRecruitmentCaseTypeName() {
    return $this->_recruitmentCaseTypeName  ;
  }

  /**
   * Method to retreive a label from a value (explode on "_" and " ", uppercase first letter of each element
   * and implode with " "
   *
   * @param $value
   * @return string
   */
  public function generateLabelFromValue($value) {
    $result = [];
    $value = str_replace(" ", "_", $value);
    $parts = explode("_", $value);
    foreach ($parts as $part) {
      $result[] = ucfirst(strtolower($part));
    }
    return implode(" ", $result);
  }

  /**
   * Method to get the id of a group member contact with first name and last name
   *
   * @param $groupId
   * @param $firstName
   * @param $lastName
   * @return false|string
   */
  public function getGroupMemberContactIdWithName($groupId, $firstName, $lastName) {
    if (empty($groupId) || empty($firstName) || empty($lastName)) {
      return FALSE;
    }
    $query = "SELECT a.contact_id
      FROM civicrm_group_contact AS a JOIN civicrm_contact AS b ON a.contact_id = b.id
        WHERE a.group_id = %1 AND a.status = %2 AND b.first_name = %3 AND b.last_name = %4";
    $queryParams = [
      1 => [(int) $groupId, "Integer"],
      2 => ["Added", "String"],
      3 => [$firstName, "String"],
      4 => [$lastName, "String"],
    ];
    $contactId = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($contactId) {
      return $contactId;
    }
    return FALSE;
  }

}
