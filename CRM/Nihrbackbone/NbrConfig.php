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
  private $_eligibleEligibilityStatusValue = NULL;
  private $_ethnicityEligibilityStatusValue = NULL;
  private $_genderEligibilityStatusValue = NULL;
  private $_maxEligibilityStatusValue = NULL;
  private $_otherStudyEligibilityStatusValue = NULL;
  private $_panelEligibilityStatusValue = NULL;
  private $_recallableEligibilityStatusValue = NULL;
  private $_travelEligibilityStatusValue = NULL;
  // tags
  private $_tempNonRecallTagId = NULL;

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

}
