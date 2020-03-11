<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource CSV Importer (generic)
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Mar 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NihrImportCsv
{

  private $_csvFile = NULL;
  private $_importId = NULL;
  private $_separator = NULL;
  private $_firstRowHeaders = NULL;
  private $_type = NULL;
  private $_csv = NULL;
  private $_studyId = NULL;
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

  public function __construct($type, $csvFileName, $additional_parameter = [])
  {
    if (isset($additional_parameter['separator'])) {
      $this->_separator = $additional_parameter['separator'];
    } else {
      $this->_separator = ';';
    }

    if (isset($additional_parameter['firstRowHeaders'])) {
      $this->_firstRowHeaders = $additional_parameter['firstRowHeaders'];
    } else {
      $this->_firstRowHeaders = 'TRUE';
    }

    if (isset($additional_parameter['context'])) {
      $this->_context = $additional_parameter['context'];
    } else {
      $this->_context = "job";
    }

    if (isset($additional_parameter['dataSource'])) {
      $this->_dataSource = $additional_parameter['dataSource'];
    } else {
      $this->_dataSource = "";
    }

    $this->_logger = new CRM_Nihrbackbone_NihrLogger('nbrcsvimport_' . date('Ymdhis'));
    $this->_failed = 0;
    $this->_imported = 0;
    $this->_read = 0;
    $this->_importId = uniqid(rand());
    if (isset($additional_parameter['originalFileName'])) {
      $this->_originalFileName = $additional_parameter['originalFileName'];
    } else {
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
  }

  /**
   * Method to check if the import data is valid (depending on type)
   *
   * @param null $studyId
   * @return bool
   * @throws
   */
  public function validImportData($studyId = NULL)
  {
    // study id required with participation
    if ($studyId) {
      $study = new CRM_Nihrbackbone_NbrStudy();
      if (!$study->studyExists($studyId)) {
        $message = E::ts('No study with ID ') . $studyId . E::ts(' found, csv import aborted.');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        return FALSE;
      }
      else {
        $this->_studyId = $studyId;
      }
    } else {
      if ($this->_type == 'participation') {
        $message = E::ts('No studyID parameter passed for participation type of csv import, is mandatory. Import aborted');
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
  public function processImport($recallGroup = NULL)
  {
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
  private function setJobReturnValues()
  {
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
    } catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to get the data mapping based on the name of the csv file
   */
  private function getMapping()
  {
    if ($this->_type != 'participation') {
      $container = CRM_Extension_System::singleton()->getFullContainer();
      $resourcePath = $container->getPath('nihrbackbone') . '/resources/';
      $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $this->_dataSource . "_mapping.json";
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
  private function importParticipation($recallGroup = NULL)
  {
    //todo do NOT import if volunteer deceased, withdrawn or redundant IF is NOT a data only or sample only study
    $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNameWithId($this->_studyId);
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);
      if ($data) {
        $this->_read++;
        $contactId = $volunteer->findVolunteerByIdentity($data[0], 'cih_type_participant_id');
        if (!$contactId) {
          $this->_failed++;
          $message = E::ts('Could not find a volunteer with participantID ') . $data[0] . E::ts(', not imported to study.');
          CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        }
        else {
          // only if not already on study
          if (CRM_Nihrbackbone_NbrVolunteerCase::isAlreadyOnStudy($contactId, $this->_studyId)) {
            $this->_failed++;
            $message = E::ts('Volunteer with participantID ') . $data[0] . E::ts(' is already on study ') . $studyNumber . E::ts(', not imported again.');
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
          }
          else {
            $volunteerCaseParams = [
              'study_id' => $this->_studyId,
              'contact_id' => $contactId,
              'case_type' => 'participation',
            ];
            if ($recallGroup) {
              $volunteerCaseParams['recall_group'] = $recallGroup;
            }
            try {
              civicrm_api3('NbrVolunteerCase', 'create', $volunteerCaseParams);
              $this->_imported++;
              $message = E::ts('Volunteer with participantID ') . $data[0] . E::ts(' succesfully added to study ') . $studyNumber;
              CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
            }
            catch (CiviCRM_API3_Exception $ex) {
              $this->_failed++;
              $message = E::ts('Error message when adding volunteer with contactID ') . $contactId
                . E::ts(' to study ') . $studyNumber . E::ts(' from API NbrVolunteerCase create: ')
                . $ex->getMessage();
              CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
            }
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
        // format data (e.g. mixed case, trim...)
        $data = $this->formatData($data);

        list($contactId, $new_volunteer) = $this->addContact($data);
        $this->addEmail($contactId, $data);
        $this->addAddress($contactId, $data);
        $this->addPhone($contactId, $data, 'phone_home', 'Home', 'Phone');
        $this->addPhone($contactId, $data, 'phone_work', 'Work', 'Phone');
        $this->addPhone($contactId, $data, 'phone_mobile', 'Main', 'Mobile');

        $this->addAlias($contactId, 'alias_type_nhs_number', $data['nhs_number'], 0);
        $this->addAlias($contactId, 'alias_type_packid', $data['pack_id'], 0);

        // *** add recruitment case, if volunteer record newly created
        $caseID = '';
        if ($new_volunteer) {
          try {
            $caseID = civicrm_api3('NbrVolunteerCase', 'create', [
              'contact_id' => $contactId,
              'case_type' => 'recruitment'
            ]);
            $message = E::ts('Recruitment case for volunteer ' . $contactId . '  added');
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
          } catch (CiviCRM_API3_Exception $ex) {
            $message = E::ts('Error when creating recruitment case for volunteer ') . $contactId
              . E::ts(' from API NbrVolunteerCase create : ') . $ex->getMessage();
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
          }

          if ($data['nihr_paper_questionnaire'] == 'yes') {
              // add action
            try {
              civicrm_api3('Activity', 'create', [
                'source_contact_id' => $contactId,
                'case_id' => $caseID,
                'activity_type_id' => "nihr_paper_questionnaire",
              ]);
            } catch (CiviCRM_API3_Exception $ex) {
               $message = E::ts('Error when adding paper questionnaire activity to recruitment case for volunteer ') . $contactId
                . E::ts(' from API Activity create : ') . $ex->getMessage();
             CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
            }
          }
        }

        // add consent to recruitment case
        $nbrConsent = new CRM_Nihrbackbone_NbrConsent();
        $nbrConsent->addConsent($contactId, $caseID, 'consent_form_status_not_valid', $data);
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

      // NOTE: keep names for aliases, need to be added to the database separately


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
        // todo use new fct from Erik $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nva_ucl_br_local', 'id');
        $mappedData[$newKey] = $value;
        // mapping ID is entered twice, once for insert (custom ID) and once for mapping (local_ucl_id)
        $newKey = 'custom_253';
        // todo $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_ucl_br_local', 'id');

      }
      // todo don't use hardcoded
      if ($newKey == 'pack_id') {
        // todo $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_ucl_br_local', 'id');
        $mappedData['nva_alias_type'] = 'alias_type_packid';
        $mappedData['nva_external_id'] = $value;

      /*  $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('alias_type_ibd_id', 'id');
        $mappedData[$newKey] = $value;
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_external_id', 'id');
        $mappedData[$newKey] = $value;
        // mapping ID is entered twice, once for insert (custom ID) and once for mapping (local_ucl_id)
        $newKey = 'custom_'.CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomField('nva_alias_type', 'id');
        ; */
      }

      $mappedData[$newKey] = $value;
    }
    return $mappedData;
  }

  private function formatData($xData)
  {
    $this->formatDataItem($xData['first_name']);
    $this->formatDataItem($xData['last_name']);
    $xData['email'] = strtolower($xData['email']);
    $this->formatDataItem($xData['address_1']);
    $this->formatDataItem($xData['address_2']);
    $this->formatDataItem($xData['address_3']);
    $this->formatDataItem($xData['address_4']);
    return $xData;
  }

  private function formatDataItem(&$dataItem)
  {
    $dataItem = ucwords(strtolower($dataItem), '- ');
    $dataItem = trim($dataItem);
  }


    /**
   * @param $data
   * @return int
   * @throws Exception
   */
  private function addContact($data)
  {
    // *** add or update volunteer

    $new_volunteer = 1;

    // todo move to volunteer class (?)

    $data['contact_type'] = 'Individual';
    $data['contact_sub_type'] = 'nihr_volunteer';

    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();

    $mappingID = NULL;
    $identifierType = NULL;
    switch ($this->_dataSource) {
      case "ucl":
        $mappingID = "local_ucl_id";
        $identifierType = 'alias_type_ucl_br_local';
        break;
      case "ibd":
        $mappingID = "pack_id";
        $identifierType = 'alias_type_packid';
        break;
      default:
        $this->_logger->logMessage('ERROR: no default mapping for ' . $this->_dataSource, 'error');
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
      return array((int)$result['id'], $new_volunteer);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_logger->logMessage('Error message when adding volunteer ' . $data['last_name'] . " " . $ex->getMessage() . 'error');
    }
  }


  private function addEmail($contactID, $data)
  {
    // *** add or update volunteer email address

    if (isset ($data['email']) && $data['email'] <> '') {
      // --- only add if not already on database
      $result = civicrm_api3('Email', 'get', [
        'sequential' => 1,
        'email' => $data['email'],
        'contact_id' => $contactID,
      ]);
      if ($result['count'] == 0) {

        // --- only add if not former email address either
        $sql = "select count(*)
          from civicrm_value_fcd_former_comm_data
          where entity_id = %1
          and fcd_communication_type = 'email'
          and fcd_details like %2";

        $queryParams = [
          1 => [$contactID, 'Integer'],
          2 => ['%' . $data['email'] . '%', 'String'],
        ];

        try {
          $count = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
        } catch (CiviCRM_API3_Exception $ex) {
        }

        if ($count == 0) {
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
    }
  }

  private function addAddress($contactID, $data)
  {
    // *** add or update volunteer home address

    if (isset($data['address_1']) && isset($data['postcode']) &&
      $data['address_1'] <> '' && $data['postcode'] <> '') {

      // --- only add if not already on database
      $result = civicrm_api3('Address', 'get', [
        'sequential' => 1,
        'contact_id' => $contactID,
        'street_address' => $data['address_1'],
        'postal_code' => $data['postcode'],
      ]);
      if ($result['count'] == 0) {

        // --- only add if not former address either
        $sql = "select count(*)
          from civicrm_value_fcd_former_comm_data
          where entity_id = %1
          and fcd_communication_type = 'address'
          and fcd_details like %2";

        $queryParams = [
          1 => [$contactID, 'Integer'],
          2 => ['%' . $data['address_1'] . '%' . $data['postcode'] . '%', 'String'],
        ];

        try {
          $count = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
        } catch (CiviCRM_API3_Exception $ex) {
        }

        if ($count == 0) {
          $fields = [
            'contact_id' => $contactID,
            'location_type_id' => "Home",
            'street_address' => $data['address_1'],
            'city' => $data['address_4'],
            'postal_code' => $data['postcode'],
          ];
          // optional fields, only add if there is data
          if (isset($data['address_2'])) {
            $fields['supplemental_address_1'] = $data['address_2'];
          }
          if (isset($data['address_3'])) {
            $fields['supplemental_address_2'] = $data['address_3'];
          }

          try {
            $result = civicrm_api3('Address', 'create', $fields);

            //$this->_logger->logMessage('Volunteer ' . $data["last_name"] . ' ' . $data["first_name"] . ' succesfully loaded');
            $this->_logger->logMessage('Volunteer address succesfully loaded/updated');
          } catch (CiviCRM_API3_Exception $ex) {
            $this->_logger->logMessage('Error message when adding volunteer address ' . $contactID . $ex->getMessage(), 'error');
          }
        }
      }
    }
  }

  private function addPhone($contactID, $data, $fieldName, $phoneLocation, $phoneType)
  {
    // *** add or update volunteer phone

    if (isset($data[$fieldName]) && $data[$fieldName] <> '') {
      $phoneNumber = $data[$fieldName];

      // only add if not already on database (do ignore type and location)
      $result = civicrm_api3('Phone', 'get', [
        'sequential' => 1,
        'contact_id' => $contactID,
        'phone' => $phoneNumber,
      ]);
      if ($result['count'] == 0) {

        // only add if not former phone number either (do ignore type and location)
        // todo other phone types
        $sql = "select count(*)
          from civicrm_value_fcd_former_comm_data
          where entity_id = %1
          and fcd_communication_type = 'phone'
          and fcd_details like %2";

        $queryParams = [
          1 => [$contactID, 'Integer'],
          2 => ['%' . $phoneNumber . '%', 'String'],
        ];

        try {
          $count = CRM_Core_DAO::singleValueQuery($sql, $queryParams);
        } catch (CiviCRM_API3_Exception $ex) {
        }

        if ($count == 0) {
          try {
            $result = civicrm_api3('Phone', 'create', [
              'contact_id' => $contactID,
              'phone' => $phoneNumber,
              'location_type_id' => $phoneLocation,
              'phone_type_id' => $phoneType,
            ]);
            $this->_logger->logMessage('Volunteer phone succesfully loaded/updated');
          } catch (CiviCRM_API3_Exception $ex) {
            $this->_logger->logMessage('Error message when adding volunteer phone ' . $contactID . $ex->getMessage(), 'error');
          }
        }
      }
    }
  }

  private function addAlias($contactID, $aliasType, $externalID, $update)
  {
    // *** add alias

    if (isset($aliasType))
      // todo and check if aliasType exists
    {
      if (isset($externalID)) {
        $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomGroup('table_name');

        // --- check if alias already exists ---------------------------------------------------------------------

        // todo compare on strings removing blanks and special chars
        $query = "SELECT nva_external_id
                    from $table
                    where entity_id = %1
                    and nva_alias_type = %2";
        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$aliasType, "String"],
        ];
        $dbExternalID = CRM_Core_DAO::singleValueQuery($query, $queryParams);

        if (!isset($dbExternalID)) {
          // --- insert --------------------------------------------------------------------------------------------

          if ($aliasType == 'alias_type_nhs_number') {
            // todo check if nhs number format is correct (subroutine to be written by JB)
            // reformat NHS number
            $externalID = substr($externalID, 0, 3) . ' ' . substr($externalID, 3, 3) . ' ' . substr($externalID, 6, 4);
          }


          $query = "insert into $table (entity_id,nva_alias_type, nva_external_id)
                            values (%1,%2,%3)";
          $queryParams = [
            1 => [$contactID, "Integer"],
            2 => [$aliasType, "String"],
            3 => [$externalID, "String"],
          ];
          CRM_Core_DAO::executeQuery($query, $queryParams);

        }
        else {
          // only update if update flag is set
          if($dbExternalID <> $externalID and $update == 1)
          {
            // todo update alias
          }
        }
      }
    }
  }
}
