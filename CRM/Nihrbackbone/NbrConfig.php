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

}
