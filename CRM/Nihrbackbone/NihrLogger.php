<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource NIHR Logger for specific process logging
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Mar 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NihrLogger {

  private $_logFile = null;

  /**
   * CRM_Nihrbackbone_NihrLogger constructor.
   *
   * @param string $fileName
   *
   */
  function __construct($fileName) {
    $file = CRM_Core_Config::singleton()->configAndLogDir . $fileName .'.log';
    $this->_logFile = fopen($file, 'w');
  }

  /**
   * Method to add message to logger
   *
   * @param $message
   * @param $type (error, warning, info)
   */
  public function logMessage($message, $type = "info") {
    $this->addMessage($type, $message);
  }

  /**
   * Method to log the message
   *
   * @param $type
   * @param $message
   */
  private function addMessage($type, $message) {
    fputs($this->_logFile, date('Y-m-d h:i:s'));
    fputs($this->_logFile, ' ');
    fputs($this->_logFile, $type);
    fputs($this->_logFile, ' ');
    fputs($this->_logFile, $message);
    fputs($this->_logFile, "\n");
  }
}
