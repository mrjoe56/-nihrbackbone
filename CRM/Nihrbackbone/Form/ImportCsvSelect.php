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

  private $_projectId = NULL;

  /**
   * Overridden parent method to build the form
   */
  public function buildQuickForm() {
    // no action if delete
    if ($this->_action != CRM_Core_Action::DELETE) {
      $this->assign('project_id', $this->_projectId);
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
   * Overridden parent method to prepare form
   *
   * @throws Exception if no project id in request
   */
  public function preProcess() {
    $this->_projectId = CRM_Utils_Request::retrieveValue('pid', 'Integer');
    CRM_Utils_System::setTitle(E::ts('NIHR BioResouce - Select CSV File to Import'));
    parent::preProcess(); // TODO: Change the autogenerated stub
  }

  /**
   * Overridden parent method to process the submitted form
   *
   */
  public function postProcess() {
    $import = new CRM_Nihrbackbone_NihrImportCsv('participation', $this->_submitFiles['csv_file']['tmp_name'], $this->getSeparator($this->_submitValues['separator_id']), $this->_submitValues['first_row_headers']);
    if ($import->validImportData($this->_submitValues['project_id'])) {
      $import->processImport();
    }
    parent::postProcess();
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
