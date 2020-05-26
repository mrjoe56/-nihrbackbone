<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Page NbrImportLog to show messages after a csv import in the UI
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 21 Oct 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_Page_NbrImportLog extends CRM_Core_Page {

  private $_type = NULL;
  private $_read = NULL;
  private $_imported = NULL;
  private $_failed = NULL;
  private $_importId = NULL;
  private $_fileName = NULL;
  private $_loggedDate = NULL;

  /**
   * Overridden parent method to run the page
   *
   * @return void|null
   * @throws CRM_Core_Exception
   */
  public function run() {
    $this->setPageConfiguration();
    if ($this->_importId) {
      $this->assign('messages', $this->getImportLog());
      CRM_Utils_System::setTitle('header_txt', E::ts('Import results for file ') . $this->_fileName . E::ts(' imported on ') . $this->_loggedDate);
      if ($this->_read) {
        $this->assign('record_count', $this->_read);
      }
      if ($this->_imported) {
        $this->assign('imported_count', $this->_imported);
      }
      if ($this->_failed) {
        $this->assign('failed_count', $this->_failed);
      }
      $this->assign('done_url', CRM_Utils_System::url('studies-list', 'reset=1', TRUE));
      parent::run();
    }
    else {
      CRM_Core_Session::setStatus(E::ts('No import ID found, not able to show import results'), E::ts('Error showing import results'), 'error');
      return;
    }
  }

  /**
   * Method to get the relevant import log messages
   *
   * @return array
   */
  private function getImportLog() {
    $messages = [];
    try {
      $apiMessages = civicrm_api3('NbrImportLog', 'get', [
        'import_id' => $this->_importId,
        'sequential' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiMessages['values'] as $apiMessage) {
        $messages[] = [
          'message_type' => ucfirst($apiMessage['message_type']),
          'message' => $apiMessage['message'],
        ];
        $this->_fileName = $apiMessage['filename'];
        $this->_loggedDate = date('d-m-Y', strtotime($apiMessage['logged_date']));
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $messages;
  }

  /**
   * Method to prepare the page
   *
   * @throws CRM_Core_Exception
   */
  private function setPageConfiguration() {
    // read parameters from request
    $type = CRM_Utils_Request::retrieveValue("type", "String");
    if ($type) {
      $this->_type = $type;
    }
    $read = CRM_Utils_Request::retrieveValue("r", "Integer");
    if ($read) {
      $this->_read = $read;
    }
    $imported = CRM_Utils_Request::retrieveValue("i", "Integer");
    if ($imported) {
      $this->_imported = $imported;
    }
    $failed = CRM_Utils_Request::retrieveValue("f", "Integer");
    if ($failed) {
      $this->_failed = $failed;
    }
    $importId = CRM_Utils_Request::retrieveValue("iid", "String");
    if ($importId) {
      $this->_importId = $importId;
    }
  }

}
