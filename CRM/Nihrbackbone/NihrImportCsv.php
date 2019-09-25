<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource CSV Importer (generic)
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Mar 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NihrImportCsv {

  private $_csvFile = NULL;
  private $_separator = NULL;
  private $_firstRowHeaders = NULL;
  private $_type = NULL;
  private $_logger = NULL;
  private $_csv = NULL;
  private $_projectId = NULL;
  private $_mapping = [];

  /**
   * CRM_Nihrbackbone_NihrImportCsv constructor.
   *
   * @param string $type
   * @param string $csvFileName
   * @param string $separator
   * @param bool $firstRowHeaders
   */
  public function __construct($type, $csvFileName, $separator = ';', $firstRowHeaders = FALSE) {
    $this->_logger = new CRM_Nihrbackbone_NihrLogger('nbrcsvimport_' . date('Ymdhis'));
    $validTypes = ['participation'];
    if (in_array($type, $validTypes)) {
      $this->_type = $type;
    }
    else {
      $this->_logger->logMessage(E::ts('Invalid type ') . $type . E::ts(' of csv import in parameters.'), 'error');
    }
    if (!empty($csvFileName)) {
      $this->_csvFile = $csvFileName;
    }
    else {
      $this->_logger->logMessage(E::ts('Empty parameter for name of csv file to be imported.'), 'error');
    }
    $this->_separator = $separator;
    $this->_firstRowHeaders = $firstRowHeaders;
  }

  /**
   * Method to check if the import data is valid (depending on type)
   *
   * @param null $projectId
   * @return bool
   */
  public function validImportData($projectId = NULL) {
    // project id required with participation
    if ($projectId) {
      $project = new CRM_Nihrbackbone_NihrProject();
      if (!$project->projectExists($projectId)) {
        $this->_logger->logMessage(E::ts('No project with ID ') . $projectId . E::ts(' found, csv import aborted.'), 'error');
        return FALSE;
      }
      else {
        $this->_projectId = $projectId;
      }
    }
    else {
      if ($this->_type == 'participation') {
        $this->_logger->logMessage(E::ts('No projectID parameter passed for participation type of csv import, is mandatory. Import aborted'), 'error');
        return FALSE;
      }
    }
    // does the file exist
    if (!file_exists($this->_csvFile)) {
      $this->_logger->logMessage(E::ts('Could not find csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__
        . E::ts(', import aborted.'), 'error');
      return FALSE;
    }
    // can i open
    $this->_csv = fopen($this->_csvFile, 'r');
    if (!$this->_csv) {
      $this->_logger->logMessage(E::ts('Could not open csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__
        . E::ts(', import aborted.'), 'error');
      return FALSE;
    }
    // is there any data?
    $data = fgetcsv($this->_csv, 0, $this->_separator);
    if (!$data || empty($data)) {
      $this->_logger->logMessage(E::ts('No data in csv file ') . $this->_csvFile . E::ts(', no data imported'), 'warning');
      fclose($this->_csv);
      return FALSE;
    }
    // we only expect 1 field in the csv file (for participation)
    if ($this->_type == 'participation' && count($data) > 1) {
      $this->_logger->logMessage(E::ts('CSV Import of type participation expects only 1 column with participantID, more columns detected. First column will be used as participantID, all other columns will be ignored in ') . __METHOD__, 'warning');
    }
    // if we have column headers we can ignore the first line else rewind to start of file
    if (!$this->_firstRowHeaders) {
      rewind($this->_csv);
    }
    return TRUE;
  }

  /**
   * Process import depending on type
   */
  public function processImport() {
    // get mapping
    $this->getMapping();
    switch ($this->_type) {
      case 'participation':
        $this->importParticipation();
        fclose($this->_csv);
        break;
      default:
        $this->_logger->logMessage(E::ts('No valid type of csv import found, no function to process import.'), 'error');
    }
  }

  /**
   * Method to get the data mapping based on the name of the csv file
   */
  private function getMapping() {
    // retrieve first part of the file name (expecting pattern like ucl_12sept2019.csv)
    $nameParts = explode("_", $this->_csvFile);
    $container = CRM_Extension_System::singleton()->getFullContainer();
    $resourcePath = $container->getPath('nihrbackbone') . '/resources/';
    $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $nameParts[0] . $this->_type . "_mapping.json";
    if (!file_exists($mappingFile)) {
      $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $this->_type . "_default_mapping.json";
    }
    $mappingJson = file_get_contents($mappingFile);
    $this->_mapping = json_decode($mappingJson, TRUE);
  }

  /**
   * Method to process the participation import (participation id)
   */
  private function importParticipation() {
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);
      if ($data){
        // map data based on filename
        $data = $this->applyMapping($data);
        $contactId = $volunteer->findVolunteerByIdentity($data[0], 'alias_type_participant_id');
        if (!$contactId) {
          $this->_logger->logMessage(E::ts('Could not find a volunteer with participantID ') . $data[0] . E::ts(', not imported to project in ') . __METHOD__, 'error');
        }
        else {
          try {
            civicrm_api3('NihrProjectVolunteer', 'create', [
              'project_id' => $this->_projectId,
              'contact_id' => $contactId,
            ]);
            $this->_logger->logMessage(E::ts('Volunteer with participantID ') . $data[0] . E::ts(' succesfully added to projectID ') . $this->_projectId);
          }
          catch (CiviCRM_API3_Exception $ex) {
            $this->_logger->logMessage(E::ts('Error message when adding volunteer with contactID ') . $contactId
              . E::ts(' to project ID ') . $this->_projectId . E::ts(' from API NihrProjectVolunteer create :')
              . $ex->getMessage(), 'error');
          }
        }
      }
    }
  }

  /**
   * Method to map data according to loaded mapping
   *
   * @param $preMappingData
   * @return array
   */
  private function applyMapping($preMappingData) {
    $mappedData = [];
    foreach ($preMappingData as $key => $value) {
      if (isset($this->_mapping[$key])) {
        $newKey = $this->_mapping[$key];
      } else {
        $newKey = $key;
      }
      $mappedData[$newKey] = $value;
    }
    return $mappedData;
  }

}

