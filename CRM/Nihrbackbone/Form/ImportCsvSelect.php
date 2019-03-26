<?php

use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Form to select csv file to import volunteers from
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 18 Mar 2019
 * @license AGPL-3.0
 * @errorrange 1000-1099
 */
class CRM_Nihrbackbone_Form_ImportCsvSelect extends CRM_Core_Form {

  private $_csvFileName = NULL;
  private $_separator = NULL;
  private $_firstRowHeaders = NULL;
  private $_columnHeaders = [];
  private $_tempTableName = NULL;
  private $_projectId = NULL;
  /**
   * Overridden parent method to build the form
   */
  public function buildQuickForm() {
    // get project id from request
    $this->_projectId = CRM_Utils_Request::retrieveValue('pid', 'Integer');
    // set user context to return to when page and all related actions done
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/nihrbackbone/page/nihrprojectvolunteer', 'reset=1&pid=' . $this->_projectId, TRUE));
    CRM_Utils_System::setTitle(E::ts('NIHR BioResouce - Select CSV File to Import'));
    // no action if delete
    if ($this->_action != CRM_Core_Action::DELETE) {
      $this->add('hidden', 'project_id');
      $this->add('file', 'csv_file', E::ts('File to import'), [], TRUE);
      $this->addRule('csv_file', E::ts('Input file must be in CSV format'), 'utf8File');
      $this->addRule('csv_file', E::ts('A valid file must be uploaded.'), 'uploadedfile');
      $this->addYesNo('first_row_headers', E::ts('First row contains headers?'), FALSE, TRUE);
      $this->add('select', 'separator_id', E::ts('Column separator'), ['Comma', 'Semi-colon'], TRUE);
      $this->addButtons([
        ['type' => 'next', 'name' => E::ts('Next'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => E::ts('Cancel')],
      ]);
      parent::buildQuickForm();
    }
  }

  /**
   * Overridden parent method to set default values
   *
   * @return array|NULL
   */
  public function setDefaultValues() {
    $defaults = [];
    if (isset($this->_projectId) && !empty($this->_projectId)) {
      $defaults['project_id'] = $this->_projectId;
    }
    return $defaults;
  }

  /**
   * Overridden parent method to process the submitted form
   *
   */
  public function postProcess() {
    $values = $this->exportValues();
    $this->_csvFileName = $this->_submitFiles['csv_file']['tmp_name'];
    $this->_firstRowHeaders = $values['first_row_headers'];
    $this->_separator = $this->getSeparator($values['separator_id']);
    $this->storeCsvInTempTable();
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/nihrbackbone/form/importcsvmap', 'pid=' . $values['project_id'] . '&csv=' . $this->_tempTableName, TRUE));
    parent::postProcess();
  }

  /**
   * Method to store csv data in temporary table
   *
   * @throws Exception
   */
  private function storeCsvInTempTable() {
    if ($this->_csvFileName) {
      // open file
      $csv = fopen($this->_csvFileName, 'r');
      if (!$csv) {
        throw new Exception(E::ts('Could not find or open the csv file'));
      }
      else {
        $this->createTempTable($csv);
        // rewind to start of file if no headers
        if (!$this->_firstRowHeaders) {
          rewind($csv);
        }
        while (!feof($csv)) {
          $data = fgetcsv($csv, 0, $this->_separator);
          if (!empty($data)) {
            $values = [];
            $insertParams = [];
            foreach ($data as $dataId => $dataValue) {
              $x = $dataId + 1;
              $values[] = '%' . $x;
              $insertParams[$x] = [$dataValue, 'String'];
            }
            $insert = "INSERT INTO " . $this->_tempTableName . " (" . implode(', ', $this->_columnHeaders)
              . ") VALUES(" . implode(', ', $values) . ")";
            CRM_Core_DAO::executeQuery($insert, $insertParams);
          }
        }
      }
    }
  }

  /**
   * Method to create the temporary table
   *
   * @param $csv
   */
  private function createTempTable($csv) {
    $this->_columnHeaders = [];
    $data = fgetcsv($csv, 0, $this->_separator);
    if ($this->_firstRowHeaders) {
      foreach ($data as $rowId => $fieldName) {
        $this->_columnHeaders[] = strtolower(stripslashes(trim($fieldName)));
      }
    }
    else {
      $noFields = count($data);
      for ($x=1; $x<= $noFields; $x++) {
        $this->_columnHeaders[] = 'field_' . $x;
      }
    }
    $this->_tempTableName = 'nihr_csv_import' . md5(uniqid(rand()));
    $query = "CREATE TABLE " . $this->_tempTableName . " (" . implode(' VARCHAR(256),', $this->_columnHeaders) . " VARCHAR(256))";
    CRM_Core_DAO::executeQuery($query);
  }

  /**
   * Method to get the csv separator
   *
   * @param $separatorId
   * @return string
   */
  private function getSeparator($separatorId) {
    if ($separatorId == 1) {
      return ';';
    }
    else {
      return ',';
    }
  }

}
