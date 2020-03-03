<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource CSV Importer, demographics import
 *
 * @author Carola Kanz
 * @date 11/02/2020
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NihrImportDemographicsCsv
{

  private $_csvFile = NULL;
  private $_importId = NULL;
  private $_separator = NULL;
  private $_csv = NULL;
  private $_columnHeaders = [];
  private $_dataSource = NULL;
  private $_imported = NULL;
  private $_failed = NULL;
  private $_read = NULL;
  private $_originalFileName = NULL;

  /**
   * CRM_Nihrbackbone_NihrImportDemographicsCsv constructor.
   *
   * @param string $csvFileName
   * @param string $separator
   * @param bool $firstRowHeaders
   * @param string $originalFileName
   * @param string $context
   *
   * @throws Exception when error in logMessage
   */

  public function __construct($csvFileName, $additional_parameter = [])
  {
    if (isset($additional_parameter['separator'])) {
      $this->_separator = $additional_parameter['separator'];
    } else {
      $this->_separator = ';';
    }

    if (isset($additional_parameter['dataSource'])) {
      $this->_dataSource = $additional_parameter['dataSource'];
    } else {
      $this->_dataSource = "";
    }

    $this->_logger = new CRM_Nihrbackbone_NihrLogger('nbrcsvimport_' . $this->_dataSource . '_' . date('Ymdhis'));
    $this->_failed = 0;
    $this->_imported = 0;
    $this->_read = 0;
    $this->_importId = uniqid(rand());
    $this->_csvFile = $csvFileName;
  }


  /**
   * Method to check if the import data is valid
   *
   * @return bool
   * @throws
   */
  public function validImportData()
  {
    // already checked that file exists

    // open
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

    // read headers
    foreach ($data as $key => $value) {
      $this->_columnHeaders[$key] = $value;
      // todo validate if i have all headers
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

    // check if mapping contains mandatory columns according to source given
    if (($this->_dataSource == 'ucl' && !isset($this->_mapping['local_id'])) ||
      ($this->_dataSource == 'ibd' && !isset($this->_mapping['pack_id']) && !isset($this->_mapping['ibd_id']))) {
      // todo : log error
    }
    else {
      $this->importDemographics();
    }
    fclose($this->_csv);

    // return messages in return values
    return $this->setJobReturnValues();
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
    $container = CRM_Extension_System::singleton()->getFullContainer();
    $resourcePath = $container->getPath('nihrbackbone') . '/resources/';
    $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . $this->_dataSource . "_mapping.json";
    if (!file_exists($mappingFile)) {
      $mappingFile = $resourcePath . DIRECTORY_SEPARATOR . "default_mapping.json";
    }
    $mappingJson = file_get_contents($mappingFile);
    $this->_mapping = json_decode($mappingJson, TRUE);
  }

  /**
   * Method to process the participation import (participation id)
   *
   * @throws
   */
  private function importDemographics()
  {
    $this->_logger->logMessage('file: ' .$this->_csvFile . '; dataSource: ' . $this->_dataSource . '; separator: ' . $this->_separator);

    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);

      if ($data) {
        // map data based on filename
        $data = $this->applyMapping($data);
        // format data (e.g. mixed case, trim...)
        $data = $this->formatData($data);

        // add volunteer or update data of existing volunteer
        list($contactId, $new_volunteer) = $this->addContact($data);
        $this->addEmail($contactId, $data);
        $this->addAddress($contactId, $data);
        $this->addPhone($contactId, $data, 'phone_home', 'Home', 'Phone');
        $this->addPhone($contactId, $data, 'phone_work', 'Work', 'Phone');
        $this->addPhone($contactId, $data, 'phone_mobile', 'Main', 'Mobile');

        if (!empty($data['nhs_number'])) {
          // &&& $this->addAlias($contactId, 'alias_type_nhs_number', $data['nhs_number'], 0);
          $this->addAlias($contactId, 'cih_type_nhs_number', $data['nhs_number'], 0);

        }
        if (!empty($data['pack_id'])) {
          $this->addAlias($contactId, 'cih_type_pack_id_din', $data['pack_id'], 0);
          // &&&&           $this->addAlias($contactId, 'alias_type_packid', $data['pack_id'], 0);
        }
        if (!empty($data['ibd_id'])) {
          $this->addAlias($contactId, 'cih_type_ibd_id', $data['ibd_id'], 0);
          // &&&          $this->addAlias($contactId, 'alias_type_ibd_id', $data['ibd_id'], 0);
        }

        // *** add source specific identifiers and data *********************************************************
        switch ($this->_dataSource) {
          case "ibd":
            if (!empty($data['diagnosis'])) {
              $this->addDisease($contactId, 'family_member_self', $data['diagnosis']);
            }
            if (!empty($data['site'])) {
              $this->addPanel($contactId, $this->_dataSource, $data['cohort'], $data['site']);
            }
            else {
              $this->_logger->logMessage('ERROR: panel missing for ' . $contactId, 'error');
            }
            break;
          case "ucl":
            $this->addAlias($contactId, 'cih_type_ucl_br_local', $data['local_id'], 0);
            // &&& $this->addAlias($contactId, 'alias_type_ucl_br_local', $data['local_id'], 0);
            if (!empty($data['national_id'])) {
              $this->addAlias($contactId, 'cih_type_ucl_br', $data['national_id'], 0);
              // &&&              $this->addAlias($contactId, 'alias_type_ucl_br', $data['national_id'], 0);
            }
            break;
        }
        /*
        if (!empty($data['ucl_id'])) {
          $this->addAlias($contactId, 'alias_type_local_ucl_id', $data['ucl_id'], 0);
        }
        // todo add all other aliases
        */

        // *** add recruitment case, if volunteer record newly created *************************
        $caseID = '';
        if ($new_volunteer) {
          try {
            $result = civicrm_api3('NbrVolunteerCase', 'create', [
              'contact_id' => $contactId,
              'case_type' => 'recruitment'
            ]);
            $caseID = $result['case_id'];
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

          // TODO &&& CONSENT ONLY ADDED FOR NEW VOLUNTEERS; FOR EXISTING VOLUNTEERS: CHECK IF THIS SPECIFIC
          // TODO     CONSENT ALREADY EXISTS AND IF NOT ADD (TO NEW CASE? TO ACTIVITIES?)
          // add consent to recruitment case
          $nbrConsent = new CRM_Nihrbackbone_NbrConsent();
          $nbrConsent->addConsent($contactId, $caseID, 'consent_form_status_not_valid', $data, $this->_logger);
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
        // &&& $mappedData['nva_alias_type'] = 'alias_type_packid';
        // &&& $mappedData['nva_external_id'] = $value;


        $mappedData['identifier_type'] = 'cih_type_packid';
        $mappedData['identifier'] = $value;


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
   *
   * *** add new volunteer or update data of existing volunteer
   */
  private function addContact($data)
  {
    $new_volunteer = 1;

    // todo move to volunteer class (?)

    $data['contact_type'] = 'Individual';
    $data['contact_sub_type'] = 'nihr_volunteer';

    // NOTE: these two settings are only used for migration and only have any effect if the numbergenerator
    // is disabled when the data is loaded!
    if(isset($data['participant_id'])) {
      $participant_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id')['id'];
      $data[$participant_custom_id] = $data['participant_id'];
    }
    if(isset($data['bioresource_id'])) {
      $bioresource_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id')['id'];
      $data[$bioresource_custom_id] = $data['bioresource_id'];
    }

    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    $contactId = '';

    switch ($this->_dataSource) {
      case "ucl":
        // todo check if ID is empty, if so, do not load record
        $contactId = $volunteer->findVolunteerByAlias($data['local_id'], 'cih_type_ucl_br_local');
        break;
      case "ibd":
        if(!empty($data['pack_id'])) {
          $contactId = $volunteer->findVolunteerByAlias($data['pack_id'], 'cih_type_packid');
        }
        // older entries have IBD IDs attached instead
        if (!$contactId && !empty($data['ibd_id'])) {
          $contactId = $volunteer->findVolunteerByAlias($data['ibd_id'], 'cih_type_ibd_id');
        }
        break;
      default:
        $this->_logger->logMessage('ERROR: no default mapping for ' . $this->_dataSource, 'error');
    }

    if (!$contactId) {
      // check if volunteer is already on Civi under a different panel/without the given ID
      // TODO &&&
    }

    if ($contactId) {
      // volunteer already exists
      $data['id'] = $contactId;
      $new_volunteer = 0;
    }

    else { // new record
      // for records with missing names (e.g. loading from sample receipts) a fake first name and surname needs to be added
      if (!isset($data['first_name'] )|| $data['first_name'] == '') {
        $data['first_name'] = 'x';
      }
      if (!isset($data['last_name'] )|| $data['last_name'] == '') {
        $data['last_name'] = 'x';
      }
    }

    try {
      // create/update volunteer record
      $result = civicrm_api3("Contact", "create", $data);
      $this->_logger->logMessage('Volunteer '.(int)$result['id'].' succesfully loaded/updated. New volunteer: '. $new_volunteer);
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

    // todo for different alias types allow multiples/updates etc...

    if (isset($aliasType))
      // todo and check if aliasType exists
    {
      if (isset($externalID)) {
        // &&&& $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerAliasCustomGroup('table_name');
        $table = 'civicrm_value_contact_id_history';

        // --- check if alias already exists ---------------------------------------------------------------------

        // todo compare on strings removing blanks and special chars
        /* $query = "SELECT nva_external_id
                    from $table
                    where entity_id = %1
                    and nva_alias_type = %2";
        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$aliasType, "String"],
        ]; */

        $query = "SELECT identifier
                    FROM $table
                    where entity_id = %1
                    and identifier_type = %2";
        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$aliasType, "String"],
        ];


        $dbExternalID = CRM_Core_DAO::singleValueQuery($query, $queryParams);

        if (!isset($dbExternalID)) {
          // --- insert --------------------------------------------------------------------------------------------

          if ($aliasType == 'cih_type_nhs_number') {
            // todo check if nhs number format is correct (subroutine to be written by JB)
            // reformat NHS number
            $externalID = substr($externalID, 0, 3) . ' ' . substr($externalID, 3, 3) . ' ' . substr($externalID, 6, 4);
          }


          /* $query = "insert into $table (entity_id,nva_alias_type, nva_external_id)
                            values (%1,%2,%3)";
          $queryParams = [
            1 => [$contactID, "Integer"],
            2 => [$aliasType, "String"],
            3 => [$externalID, "String"],
          ];
          CRM_Core_DAO::executeQuery($query, $queryParams); */

          $query = "insert into $table (entity_id, identifier_type, identifier)
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


  private function addDisease($contactID, $familyMember, $disease)
  {
    // *** add disease/conditions

    if (isset($familyMember))
    {
      if (isset($disease)) {
        // todo check if disease exists

        // todo $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerDisease('table_name');
        $table = 'civicrm_value_nihr_volunteer_disease';
        // --- check if disease already exists ---------------------------------------------------------------------

        // todo: add more fields; only one brother, sister etc possible per disease!!!!
        $query = "SELECT count(*)
                    from $table
                    where entity_id = %1
                    and nvdi_family_member = %2
                    and nvdi_disease = %3";
        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$familyMember, "String"],
          3 => [$disease, "String"],
        ];
        $cnt = CRM_Core_DAO::singleValueQuery($query, $queryParams);

        if ($cnt == 0) {
          // --- insert --------------------------------------------------------------------------------------------

          $query = "insert into $table (entity_id, nvdi_family_member, nvdi_disease) values (%1,%2,%3)";
          $queryParams = [
            1 => [$contactID, "Integer"],
            2 => [$familyMember, "String"],
            3 => [$disease, "String"],
          ];
          CRM_Core_DAO::executeQuery($query, $queryParams);
        }
      }
    }
  }


  private function addPanel($contactID, $dataSource, $cohort, $site)
  {
    // TODO &&& for the time being, the IBD site code is in 'nickname' this is going to change

    if (isset($site)) {

      // todo $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanel('table_name');
      $table = 'civicrm_value_nihr_volunteer_panel';


      $panel = $dataSource;
      if ($cohort <> '') {
        // IBD only
        $panel .=  ' ' . $cohort;
      }

      // *** check if panel exists and if so, select ID
      try {
        $result = civicrm_api3('Contact', 'get', [
          'contact_sub_type' => "nbr_panel",
          'organization_name' => $panel,
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error selecting panel ' . $panel . ': '. $ex->getMessage(), 'error');
      }

      if ($result['count'] <> 1) {
        // error, panel does not exist, exit
        $this->_logger->logMessage('Panel does not exist on database: ' . $panel, 'error');
      }
      else {

        $panelID = $result['id'];

        // *** check if site exists and if so, select ID
        try {
          $result = civicrm_api3('Contact', 'get', [
            'contact_sub_type' => "nbr_site",
            'nick_name' => $site,
          ]);
        } catch (CiviCRM_API3_Exception $ex) {
          $this->_logger->logMessage('Error selecting site ' . $site . ': '. $ex->getMessage(), 'error');
        }

        if ($result['count'] <> 1) {
          // error, site does not exist, exit
          $this->_logger->logMessage('Site does not exist on database: ' . $site, 'error');
        }
        else {

          $siteID = $result['id'];

          // --- check if panel is already linked to volunteer ------------------------------------
          $query = "SELECT count(*)
                    FROM cividev_drupal.civicrm_value_nihr_volunteer_panel p, cividev_drupal.civicrm_contact cp, cividev_drupal.civicrm_contact cs
                    where p.entity_id = %1
                    and p.nvp_panel = cp.id
                    and p.nvp_site = cs.id
                    and cp.organization_name = %2
                    and cs.nick_name = %3";

          $queryParams = [
            1 => [$contactID, "Integer"],
            2 => [$panel, "String"],
            3 => [$site, "String"],
          ];
          $cnt = CRM_Core_DAO::singleValueQuery($query, $queryParams);

          if ($cnt == 0) {
            // --- panel not yet linked to volunteer, insert -----------------------------------
            // todo &&& multiple IBD panels with different sites can be added; is this wanted????

            $query = "insert into $table (entity_id, nvp_panel, nvp_site) values (%1,%2,%3)";
            $queryParams = [
              1 => [$contactID, "Integer"],
              2 => [$panelID, "String"],
              3 => [$siteID, "String"],
            ];
            CRM_Core_DAO::executeQuery($query, $queryParams);
          }
        }
      }
    }
  }
}

