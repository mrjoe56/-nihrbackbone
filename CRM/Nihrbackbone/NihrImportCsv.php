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
  private $_importId = NULL;
  private $_separator = NULL;
  private $_firstRowHeaders = NULL;
  private $_type = NULL;
  private $_csv = NULL;
  private $_projectId = NULL;
  private $_mapping = [];
  private $_columnHeaders = [];
  private $_dataSource = NULL;
  private $_imported = NULL;
  private $_failed = NULL;
  private $_read = NULL;
  private $_context = NULL;
  private $_originalFileName = NULL;

  /**
   * CRM_Nihrbackbone_NihrImportCsv constructor.
   *
   * @param string $type
   * @param string $csvFileName
   * @param string $separator
   * @param bool $firstRowHeaders
   * @param string $originalFileName
   * @param string $context
   *
   * @throws Exception when error in logMessage
   */

  public function __construct($type, $csvFileName, $separator = ';', $firstRowHeaders = TRUE, $originalFileName = NULL, $context = "job", $dataSource = '') {
    $this->_logger = new CRM_Nihrbackbone_NihrLogger('nbrcsvimport_' . date('Ymdhis'));
    $this->_failed = 0;
    $this->_imported = 0;
    $this->_read = 0;
    $this->_importId = uniqid(rand());
    $this->_context = $context;
    if ($originalFileName) {
      $this->_originalFileName = $originalFileName;
    }
    else {
      $this->_originalFileName = $csvFileName;
    }

    $validTypes = ['participation', 'demographics'];
    if (in_array($type, $validTypes)) {
      $this->_type = $type;
    } else {
      $message = E::ts('Invalid type ') . $type . E::ts(' of csv import in parameters.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
    }
    if (!empty($csvFileName)) {
      $this->_csvFile = $csvFileName;
    } else {
      $message = E::ts('Empty parameter for name of csv file to be imported.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
    }
    $this->_separator = $separator;
    $this->_firstRowHeaders = $firstRowHeaders;
    $this->_dataSource = $dataSource;
  }

  /**
   * Method to check if the import data is valid (depending on type)
   *
   * @param null $projectId
   * @return bool
   * @throws
   */
  public function validImportData($projectId = NULL) {
    // project id required with participation
    if ($projectId) {
      $project = new CRM_Nihrbackbone_NihrProject();
      if (!$project->projectExists($projectId)) {
        $message = E::ts('No project with ID ') . $projectId . E::ts(' found, csv import aborted.');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        return FALSE;
      } else {
        $this->_projectId = $projectId;
      }
    } else {
      if ($this->_type == 'participation') {
        $message = E::ts('No projectID parameter passed for participation type of csv import, is mandatory. Import aborted');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        return FALSE;
      }
    }
    // does the file exist
    if (!file_exists($this->_csvFile)) {
      $message = E::ts('Could not find csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__ . E::ts(', import aborted.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      return FALSE;
    }
    // can i open
    $this->_csv = fopen($this->_csvFile, 'r');
    if (!$this->_csv) {
      $message = E::ts('Could not open csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__ . E::ts(', import aborted.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      return FALSE;
    }
    // is there any data?
    $data = fgetcsv($this->_csv, 0, $this->_separator);
    if (!$data || empty($data)) {
      $message = E::ts('No data in csv file ') . $this->_csvFile . E::ts(', no data imported');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
      fclose($this->_csv);
      return FALSE;
    }
    // we only expect 1 field in the csv file (for participation)
    if ($this->_type == 'participation' && count($data) > 1) {
      $message = E::ts('CSV Import of type participation expects only 1 column with participantID, more columns detected. First column will be used as participantID, all other columns will be ignored.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
    }
    // if we have column headers we can ignore the first line else rewind to start of file
    if (!$this->_firstRowHeaders) {
      rewind($this->_csv);
    } else {
      foreach ($data as $key => $value) {
        $this->_columnHeaders[$key] = $value;
        // todo validate if i have all headers
      }
    }
    return TRUE;
  }

  /**
   * Method to process import
   *
   * @param string $recallGroup
   * @return array|void
   * @throws Exception
   */
  public function processImport($recallGroup = NULL) {
    // get mapping
    $this->getMapping();
    switch ($this->_type) {
      case 'participation':
        $this->importParticipation($recallGroup);
        fclose($this->_csv);
        break;
      case 'demographics':
        $this->importDemographics();
        fclose($this->_csv);
        break;
      default:
        $message = E::ts('No valid type of csv import found, no function to process import.');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
    }
    // return messages in return values if ran as scheduled job
    if ($this->_context == "job") {
      return $this->setJobReturnValues();
    }
  }

  /**
   * Method  to fill an array with the messages of the job (so they can be shown as returnValues in the scheduled job log)

   * @return array
   */
  private function setJobReturnValues() {
    $result = [];
    try {
      $apiMessages = civicrm_api3('NbrImportLog', 'get', [
        'import_id' => $this->_importId,
        'sequential' => 1,
        'options' => ['limit' => 0],
      ]);
      foreach ($apiMessages['values'] as $message) {
        $result[] = $message['message_type'] . ": " . $message['message'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to get the data mapping based on the name of the csv file
   */
  private function getMapping() {
    if ($this->_type != 'participation') {
      // todo : $this->_dataSource needs to hold the param!!
      $container = CRM_Extension_System::singleton()->getFullContainer();
      $resourcePath = $container->getPath('nihrbackbone') . '/resources/';
      // todo $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $this->_dataSource . $this->_type . "_mapping.json";
      $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . "ucl" . "_mapping.json";
      if (!file_exists($mappingFile)) {
        $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $this->_type . "_default_mapping.json";
      }
      $mappingJson = file_get_contents($mappingFile);
      $this->_mapping = json_decode($mappingJson, TRUE);
    }
  }

  /**
   * Method to process the participation import (participation id)
   *
   * @throws
   */
  private function importParticipation($recallGroup = NULL) {
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);
      if ($data) {
        $this->_read++;
        $contactId = $volunteer->findVolunteerByIdentity($data[0], 'cih_type_participant_id');
        if (!$contactId) {
          $this->_failed++;
          $message = E::ts('Could not find a volunteer with participantID ') . $data[0] . E::ts(', not imported to project.');
          CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        } else {
          $volunteerCaseParams = [
            'project_id' => $this->_projectId,
            'contact_id' => $contactId,
            'case_type' => 'participation',
          ];
          if ($recallGroup) {
            $volunteerCaseParams['recall_group'] = $recallGroup;
          }
          try {
            civicrm_api3('NbrVolunteerCase', 'create', $volunteerCaseParams);
            $this->_imported++;
            $message = E::ts('Volunteer with participantID ') . $data[0] . E::ts(' succesfully added to projectID ') . $this->_projectId;
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
          } catch (CiviCRM_API3_Exception $ex) {
            $this->_failed++;
            $message = E::ts('Error message when adding volunteer with contactID ') . $contactId
              . E::ts(' to project ID ') . $this->_projectId . E::ts(' from API NbrVolunteerCase create: ')
              . $ex->getMessage();
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
          }
        }
      }
    }
    // set user context to page with import results when called from UI
    if ($this->_context == "ui") {
      CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url("civicrm/nihrbackbone/page/nbrimportlog", "reset=1&type=participation&r=" . $this->_read . "&i=" . $this->_imported . "&f=" . $this->_failed . "&iid=" . $this->_importId, TRUE));
    }
  }

  /**
   * Method to process the participation import (participation id)
   *
   * @throws
   */
  private function importDemographics()
  {
    $this->_logger->logMessage('dataSource: ' . $this->_dataSource . '; separator: ' . $this->_separator);
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);

      if ($data) {
        // map data based on filename
        $data = $this->applyMapping($data);

        list($contactId, $new_volunteer) = $this->addContact($data);
        $this->addEmail($data, $contactId);
        $this->addAddress($data, $contactId);
        $this->addPhone($data, $contactId); // todo home phone only at the moment, add mobile and work

        // *** add recruitment case, if volunteer record newly created
        if ($new_volunteer) {
          try {
            civicrm_api3('NbrVolunteerCase', 'create', [
              'contact_id' => $contactId,
              'case_type' => 'recruitment'
            ]);
            $message = E::ts('Recruitment case for volunteer ' . $contactId . '  added');
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
          } catch (CiviCRM_API3_Exception $ex) {
            $message = E::ts('Error message when creating recruitment case for volunteer ') . $contactId
              . E::ts(' from API NbrVolunteerCase create : ') . $ex->getMessage();
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
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
  private function applyMapping($preMappingData)
  {
    $mappedData = [];
    foreach ($preMappingData as $key => $value) {
      $header = $this->_columnHeaders[$key];
      if (isset($this->_mapping[$header])) {
        $newKey = $this->_mapping[$header];
      } else {
        // todo add to logfile
        $newKey = $key;
      }

      if ($newKey == 'ethnicity') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'id');
      }
      if ($newKey == 'weight_kg') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_weight_kg', 'id');
      }
      if ($newKey == 'height_m') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_height_m', 'id');
      }
      if ($newKey == 'local_ucl_id') {
        // todo $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nva_ucl_br_local', 'id');
        $mappedData[$newKey] = $value;
        // mapping ID is entered twice, once for insert (custom ID) and once for mapping (local_ucl_id)
        $newKey = 'custom_218';
      }
      $mappedData[$newKey] = $value;
    }
    return $mappedData;
  }

  /**
   * @param $data
   * @return int
   * @throws Exception
   */
  private function addContact($data) {
    // *** add or update volunteer

    $new_volunteer = 1;

    // todo replace with param $dataSource
    $xdataSource = 'ucl';

    // todo move to volunteer class (?)

    $data['contact_type'] = 'Individual';
    $data['contact_sub_type'] = 'nihr_volunteer';

    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();

    $mappingID = NULL;
    $identifierType = NULL;
    switch ($xdataSource) {
      case "ucl": $mappingID = "local_ucl_id";  $identifierType = 'alias_type_ucl_br_local'; break;
      case "ibd": $mappingID = "ibd_id";        $identifierType = 'alias_type_ibd_id';       break;
      default: $this->_logger->logMessage('ERROR: no default mapping for ' . $xdataSource, 'error');
    }

    $contactId = $volunteer->findVolunteerByIdentity($data[$mappingID], $identifierType);
    if ($contactId) {
      // volunteer already exists
      $data['id'] = $contactId;
      $new_volunteer = 0;
    }
    try {
      $result = civicrm_api3("Contact", "create", $data);
      $this->_logger->logMessage('Volunteer succesfully loaded/updated');
    }
    catch (CiviCRM_API3_Exception $ex) {
      $this->_logger->logMessage('Error message when adding volunteer ' . $data['last_name'] . " " . $ex->getMessage(). 'error');
    }
    return array ((int) $result['id'], $new_volunteer);
  }


  private function addEmail($data, $contactID) {
    // *** add or update volunteer email address

    // only add if not already on database
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'email' => $data['email'],
      'contact_id' => $contactID,
    ]);
    if ($result['count'] == 0) {
      // todo check if not former email address
      try {
        $result = civicrm_api3('Email', 'create', [
          'contact_id' => $contactID,
          'email' => $data['email'],
        ]);
        $this->_logger->logMessage('Volunteer email succesfully loaded/updated');
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error message when adding volunteer email ' . $contactID . $ex->getMessage(), 'error');
      }
    }
  }

  private function addAddress($data, $contactID) {
    // *** add or update volunteer home address

    // only add if not already on database
    $result = civicrm_api3('Address', 'get', [
      'sequential' => 1,
      'contact_id' => $contactID,
      'street_address' => $data['address_1'],
      'postal_code' => $data['postcode'],
    ]);
    if ($result['count'] == 0) {
      // todo check if not former address
      try {
        $result = civicrm_api3('Address', 'create', [
          'contact_id' => $contactID,
          'location_type_id' => "Home",
          'street_address' => $data['address_1'],
          'supplemental_address_1' => $data['address_2'], // todo only add this line, if there is data
          'supplemental_address_2' => $data['address_3'], // todo only add this line, if there is data
          'city' => $data['address_4'],
          'postal_code' => $data['postcode'],
        ]);

        //$this->_logger->logMessage('Volunteer ' . $data["last_name"] . ' ' . $data["first_name"] . ' succesfully loaded');
        $this->_logger->logMessage('Volunteer address succesfully loaded/updated');
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error message when adding volunteer address ' . $contactID . $ex->getMessage(), 'error');
      }
    }
  }

  private function addPhone($data, $contactID) {
    // *** add or update volunteer home address

    // only add if not already on database
    $result = civicrm_api3('Phone', 'get', [
      'sequential' => 1,
      'contact_id' => $contactID,
      'phone' => $data['phone_home']
    ]);
    if ($result['count'] == 0) {
      // todo check if not former phone
      try {
        $result = civicrm_api3('Phone', 'create', [
          'contact_id' => $contactID,
          'phone' => $data['phone_home'],
          'phone_type_id' => "Phone"
        ]);
        // todo for Mobile and work phone (add new phone type, if possible)

        $this->_logger->logMessage('Volunteer phone succesfully loaded/updated');
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error message when adding volunteer phone ' . $contactID . $ex->getMessage(), 'error');
      }
    }
  }

}
