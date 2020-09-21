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
      ($this->_dataSource == 'ibd' && !isset($this->_mapping['pack_id']) && !isset($this->_mapping['ibd_id'])) ||
      ($this->_dataSource == 'starfish' && !isset($this->_mapping['participant_id']))) {
      // todo : log error
    }
    elseif (!isset($this->_mapping['panel'])) {
      // todo check on panel, centre and site &&&&&&&&
      $this->_logger->logMessage('ERROR: panel missing for xxx data not loaded', 'error');
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
        $this->addPhone($contactId, $data, 'phone_mobile', 'Home', 'Mobile');
        $this->addNote($contactId, $data['notes']);

        if ($data['panel'] <> '' || $data['site'] <> '' || $data['centre'] <> '') {
          $this->addPanel($contactId, $this->_dataSource, $data['panel'], $data['site'], $data['centre'], $data['source']);
        }

        // *** Aliases ***
        if (!empty($data['nhs_number'])) {
          $this->addAlias($contactId, 'cih_type_nhs_number', $data['nhs_number'], 1);
        }
        if (!empty($data['pack_id'])) {
          $this->addAlias($contactId, 'cih_type_pack_id', $data['pack_id'], 2);
        }
        if (!empty($data['ibd_id'])) {
          $this->addAlias($contactId, 'cih_type_ibd_id', $data['ibd_id'], 2);
        }

        if (isset($data['alias_type_former_surname']) && !empty($data['alias_type_former_surname'])) {
          $this->addAlias($contactId, 'cih_type_former_surname', $data['alias_type_former_surname'], 2);
        }

        // ^^^ starfish migration
        $aliases = array(
            'cih_type_anon_project_id',
            'cih_type_blood_donor_id',
            'cih_type_cardio_id',
            'cih_type_cbr',
            'cih_type_duplicate_invalid',
            'cih_type_cbr_withdrawal_form_id',
            'cih_type_cdb_id',
            'cih_type_interval_id',
            'cih_type_nhs_number',
            'cih_type_pack_id_din',
            'cih_type_strides_pid',
            'cih_type_sample_destruction_form_id',
            'cih_type_duplicate_live_entry',
            'cih_type_compare_id',
            'cih_type_retired_national_id',
            'cih_type_duplicate_deleted',
            'cih_type_nb_id',
            'cih_type_gstt',
            'cih_type_imperial',
            'cih_type_oxford',
            'cih_type_leicester',
            'cih_type_newcastle',
            'cih_type_ucl',
            'cih_type_ucl_local',
            'cih_type_ibd_id',
            'cih_type_ibdgc_number',
            'cih_type_nbr_withdrawal_form_id',
            'cih_type_slam',
            'cih_type_packid',
            'cih_type_glad_id',
            'cih_type_nafld_br',
            'cih_type_covid_id',
            'cih_type_hospital_number',
            'cih_type_nspn_id',
            'cih_type_rare_diseases_id',
            'cih_type_dil_withdrawal_form_id',
            'cih_type_replaced_sid'
        );

        foreach ($aliases as &$alias) {
          if (isset($data[$alias]) && !empty($data[$alias])) {
            $this->addAlias($contactId, $alias, $data[$alias], 2);
          }
        }

        // *** Diseases ***
        $this->addDisease($contactId, $data['family_member'], $data['disease'], $data['diagnosis_year'], $data['diagnosis_age'], $data['disease_notes'], $data['taking_medication']);

        // *** add source specific identifiers and data *********************************************************
        switch ($this->_dataSource) {
          case "ibd":
            if ($data['diagnosis'] <> '') {
              $this->addDisease($contactId, 'family_member_self', $data['diagnosis'], '', '', '', '');
            }
            if ($data['nva_ibdgc_number'] <> '') {
              $this->addAlias($contactId, 'cih_type_ibdgc_number', $data['nva_ibdgc_number'], 1);
            }
            break;

          case "ucl":
            $this->addAlias($contactId, 'cih_type_ucl_br_local', $data['local_id'], 0);
            if (!empty($data['national_id'])) {
              $this->addAlias($contactId, 'cih_type_ucl_br', $data['national_id'], 0);
            }
            break;
        }
        /*
        if (!empty($data['ucl_id'])) {
          $this->addAlias($contactId, 'alias_type_local_ucl_id', $data['ucl_id'], 0);
        }
        // todo add all other aliases
        */


      // &&&&&  $this->addGeneralObservations($contactId, $data);



        // *** add recruitment case, if volunteer record newly created *************************
        // (unless migration -> datasource 'Starfish')
        $caseID = '';

        if ($new_volunteer && $this->_dataSource <> 'starfish') {
          // use consent date as case started date
          $consentDate = date('Y-m-d',strtotime($data['consent_date']));

          try {
            $result = civicrm_api3('NbrVolunteerCase', 'create', [
              'contact_id' => $contactId,
              'case_type' => 'recruitment',
              'start_date' => $consentDate
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
          $nbrConsent->addConsent($contactId, $caseID, 'consent_form_status_not_valid', 'Consent', $data, $this->_logger);
        }

        // *** Starfish migration only
        if ($this->_dataSource == 'starfish') {
          if ($new_volunteer or $data['consent_version'] <> '') {
            // consents all migrated into one recruitment case
            $this->migrationAddConsent($contactId, $data);
          }
          // migrate volunteer status and fields linked to the status
          $this->migrationVolunteerStatus($contactId, $data);

          // migrate paper questionnaire flag
          if (isset($data['nihr_paper_questionnaire']) && $data['nihr_paper_questionnaire'] == 'yes') {
            $this->addActivity($contactId, 'nihr_paper_questionnaire', '');
          }
        }

        // gdpr request - very likely only used for migration
        if (isset($data['gdpr_request_received']) && $data['gdpr_request_received'] <> '') {
          $this->addActivity($contactId, 'nihr_gdpr_request_received', $data['gdpr_request_received']);
        }
        if (isset($data['gdpr_sent_to_nbr']) && $data['gdpr_sent_to_nbr'] <> '') {
          $this->addActivity($contactId, 'nihr_gdpr_sent_to_nbr', $data['gdpr_sent_to_nbr']);
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

    // *** initialise with all fields
    foreach($this->_mapping as $item) {
      $mappedData[$item] = '';
    }

    foreach ($preMappingData as $key => $value) {
      $header = $this->_columnHeaders[$key];
      if (isset($this->_mapping[$header])) {
        $newKey = $this->_mapping[$header];
      } else {
        // todo add to logfile
        $newKey = $key;
      }

      // NOTE: keep names for aliases, need to be added to the database separately

      // *** custom group 'general observations'
      if ($newKey == 'ethnicity') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'id');
      }
      if ($newKey == 'weight_kg') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_weight_kg', 'id');
      }
      if ($newKey == 'height_m') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_height_m', 'id');
      }
      if ($newKey == 'hand_preference') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_hand_preference', 'id');
      }
      if ($newKey == 'abo_group') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_abo_group', 'id');
      }
      if ($newKey == 'rhesus_factor') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_rhesus_factor', 'id');
      }

      // *** custom group 'Lifestyle'
      if ($newKey == 'alcohol') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_alcohol', 'id');
      }
      if ($newKey == 'alcohol_amount') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_alcohol_amount', 'id');
      }
      if ($newKey == 'alcohol_notes') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_alcohol_notes', 'id');
      }

      if ($newKey == 'smoker') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker', 'id');
      }
      if ($newKey == 'smoker_amount') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_amount', 'id');
      }
      if ($newKey == 'smoker_years') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_years', 'id');
      }
      if ($newKey == 'smoker_past') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_past', 'id');
      }
      if ($newKey == 'smoker_past_amount') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_past_amount', 'id');
      }
      if ($newKey == 'smoker_past_years') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_past_years', 'id');
      }
      if ($newKey == 'smoker_gave_up') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoker_gave_up', 'id');
      }
      if ($newKey == 'smoker_notes') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_smoking_notes', 'id');
      }
      if ($newKey == 'diet') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_diet', 'id');
      }
      if ($newKey == 'diet_notes') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getLifestyleCustomField('nvl_diet_notes', 'id');
      }

      // *** selection eligibility
      if ($newKey == 'unable_to_travel') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_unable_to_travel', 'id');
      }
      if ($newKey == 'exclude_from_blood_studies') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_no_blood_studies', 'id');
      }
      if ($newKey == 'exclude_from_commercial_studies') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_no_commercial_studies', 'id');
      }
      if ($newKey == 'exclude_from_drug_studies') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_no_drug_studies', 'id');
      }
      if ($newKey == 'exclude_from_studies_with_mri') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_no_mri', 'id');
      }
      if ($newKey == 'unable_to_travel') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_unable_to_travel', 'id');
      }
      if ($newKey == 'unable_to_travel') {
        $newKey = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomField('nvse_unable_to_travel', 'id');
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
    if (isset($xData['email'])) { $xData['email'] = strtolower($xData['email']); }
    $this->formatDataItem($xData['address_1']);
    $this->formatDataItem($xData['address_2']);
    $this->formatDataItem($xData['address_3']);
    $this->formatDataItem($xData['address_4']);
    return $xData;
  }

  private function formatDataItem(&$dataItem)
  {
    if($dataItem <> '') {
      $dataItem = ucwords(strtolower($dataItem), '- ');
      $dataItem = trim($dataItem);
    }
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
    if(isset($data['participant_id']) && $data['participant_id'] <> '') {
      $participant_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id')['id'];
      $data[$participant_custom_id] = $data['participant_id'];
    }
    if(isset($data['bioresource_id']) && $data['bioresource_id'] <> '') {
      $bioresource_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id')['id'];
      $data[$bioresource_custom_id] = $data['bioresource_id'];
    }

    // prepare data for 'preferred contact' (multiple options)
    if ($data['preferred_communication_method'] <> '') {
      $xpref = explode('-', $data['preferred_communication_method']);
      $data['preferred_communication_method'] = CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $xpref);
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
      case "starfish":
        $contactId = $volunteer->findVolunteerByAlias($data['participant_id'], 'cih_type_participant_id');
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
      if ($data['first_name'] == '') {
        $data['first_name'] = 'x';
      }
      if ($data['last_name'] == '') {
        $data['last_name'] = 'x';
      }

      // set volunteer status to 'pending'
      $volunteerStatusCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerStatusCustomField('nvs_volunteer_status', 'id');
      $data[$volunteerStatusCustomField] = 'volunteer_status_pending';
    }

    foreach ($data as $key => $value) {
      if (!empty($value)) {
        $params[$key] = $value;
      }
    }

    try {
      // create/update volunteer record
      $result = civicrm_api3("Contact", "create", $params);
      $this->_logger->logMessage('Volunteer ' . $data['participant_id'] . ' ' .(int)$result['id'].' successfully loaded/updated. New volunteer: '. $new_volunteer);
      return array((int)$result['id'], $new_volunteer);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_logger->logMessage('Error message when adding volunteer ' . $data['last_name'] . " " . $ex->getMessage() . 'error');
    }

    // **** if no name is available ('x' inserted) - TODO: use participant ID instead
  }


  private function addEmail($contactID, $data)
  {
    // *** add or update volunteer email address

    if ($data['email'] <> '') {
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

    if ($data['address_1'] <> '' && $data['postcode'] <> '') {

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
          if ($data['address_2'] <> '') {
            $fields['supplemental_address_1'] = $data['address_2'];
          }
          if ($data['address_3'] <> '') {
            $fields['supplemental_address_2'] = $data['address_3'];
          }

          try {
            $result = civicrm_api3('Address', 'create', $fields);

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

    if ($data[$fieldName] <> '') {
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

          } catch (CiviCRM_API3_Exception $ex) {
            $this->_logger->logMessage('Error message when adding volunteer phone ' . $contactID . $ex->getMessage(), 'error');
          }
        }
      }
    }
  }

  private function addNote($contactID, $note)
  {
    if (isset($note) && $note <> '') {
      try {
        civicrm_api3('Note', 'create', [
          'entity_id' => $contactID,
          'note' => $note,
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error message when adding volunteer notes ' . $contactID . $ex->getMessage(), 'error');
      }
    }
  }

  private function addAlias($contactID, $aliasType, $externalID, $update)
  {
    // *** add alias aka contact_id_history_type

    // *** update=0 - do not update if alias already set
    // *** update=1 - update, if alias exists
    // *** update=2 - multiple aliases of this type possible, always add

    if (isset($aliasType) && $aliasType <> '')
      // todo add check if aliasType exists
    {
      if (isset($externalID) && $externalID <> '') {
        $table = 'civicrm_value_contact_id_history';

        // --- check if alias already exists ---------------------------------------------------------------------
        // todo compare on strings removing blanks and special chars
        $query = "SELECT identifier
                    FROM $table
                    where entity_id = %1
                    and identifier_type = %2";
        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$aliasType, "String"],
        ];

        // todo: &&& need to check for multiple results
        $dbExternalID = CRM_Core_DAO::singleValueQuery($query, $queryParams);

        if (!isset($dbExternalID) || ($update == 2 and $dbExternalID <> $externalID)) {
          // --- no alias of this type exists, insert -------------------------------------------
          // --- OR update

          if ($aliasType == 'cih_type_nhs_number') {
            // todo check if nhs number format is correct (subroutine to be written by JB)
          }
          try {
            $query = "insert into $table (entity_id, identifier_type, identifier)
                              values (%1,%2,%3)";
            $queryParams = [
              1 => [$contactID, "Integer"],
              2 => [$aliasType, "String"],
              3 => [$externalID, "String"],
            ];
            CRM_Core_DAO::executeQuery($query, $queryParams);
          }
          catch (Exception $ex) {}
        }
        elseif (isset($dbExternalID) && $dbExternalID <> $externalID)
          {
            if($update == 0) {
              $this->_logger->logMessage("Contact ID $contactID: different identifier for $aliasType provided, not updated.", 'warning');
            }
            elseif ($update == 1) {
              try {
                $query = "update $table
                        set identifier = %1
                        where entity_id = %2
                        and identifier_type = %3";
                $queryParams = [
                  1 => [$externalID, "String"],
                  2 => [$contactID, "Integer"],
                  3 => [$aliasType, "String"],
                ];
                CRM_Core_DAO::executeQuery($query, $queryParams);
              }
              catch (Exception $ex) {}
            }
          }
        }
      }
    }



  private function addDisease($contactID, $familyMember, $disease, $diagnosisYear, $diagnosisAge, $diseaseNotes, $takingMedication)
  {
    // *** add disease/conditions

    if ($familyMember <> '' and $disease <> '')
    {
      // todo check if disease and family member exists

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
        $query = "insert into $table (entity_id, nvdi_family_member, nvdi_disease, nvdi_disease_notes ";
        if ($diagnosisYear <> '') {
          $query .= ", nvdi_diagnosis_year ";
        }
        if ($diagnosisAge <> '') {
          $query .= ", nvdi_diagnosis_age ";
        }
        if ($takingMedication <> '') {
          $query .= ", nvdi_taking_medication ";
        }
        $query .= ") values (%1,%2,%3,%4";
        $i=5;
        if ($diagnosisYear <> '') {
          $query .= ",%$i";
          $i++;
        }
        if ($diagnosisAge <> '') {
          $query .= ",%$i";
          $i++;
        }
        if ($takingMedication <> '') {
          $query .= ",%$i";
        }
        $query .= ")";

        $queryParams = [
          1 => [$contactID, "Integer"],
          2 => [$familyMember, "String"],
          3 => [$disease, "String"],
          4 => [$diseaseNotes, "String"]
        ];

        if ($diagnosisYear <> '') {
          $ref = [$diagnosisYear, "Integer"];
          array_push($queryParams, $ref);
        }
        if ($diagnosisAge <> '') {
          $ref = [$diagnosisAge, "Integer"];
          array_push($queryParams, $ref);
        }
        if ($takingMedication <> '') {
          $ref = [$takingMedication, "Integer"];
          array_push($queryParams, $ref);
        }
        CRM_Core_DAO::executeQuery($query, $queryParams);
      }
    }
  }

  private function addPanel($contactID, $dataSource, $panel, $site, $centre, $source)
  {
    // TODO $dataSource is ignored at the moment as IBD now split in 2 panels

    // TODO add centre

    $table = 'civicrm_value_nihr_volunteer_panel';

    $volunteerPanelCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomField('nvp_panel', 'id');
    $volunteerCentreCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomField('nvp_centre', 'id');
    $volunteerSiteCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomField('nvp_site', 'id');
    $volunteerSourceCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomField('nvp_source', 'id');

    // ---
    $siteAliasTypeCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getSiteAliasCustomField('nsa_alias_type', 'id');
    $siteAliasCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getSiteAliasCustomField('nsa_alias', 'id');
    $siteAliasTypeValue = "nbr_site_alias_type_".strtolower($dataSource);

    // migration
    if ($dataSource == 'starfish') {
      if ($panel == 'IBD Main' || $panel == 'Inception') {
        $siteAliasTypeValue = "nbr_site_alias_type_ibd";
      }
      elseif ($panel == 'STRIDES') {
        $siteAliasTypeValue = "nbr_site_alias_type_strides";
      }
    }

    // *** centre/panel/site: usually two of each are set per record, all of them are contact organisation
    // *** records; check if given values are on the - if any is missing do not insert any 'panel' data
    $centreID = 0;
    $panelID = 0;
    $siteID = 0;

    $xdata = [];
    $xdata['id'] = $contactID;

    // *** check if panel exists and if so, select ID
    if (isset($panel) && $panel <> '') {
      try {
        $result = civicrm_api3('Contact', 'get', [
          'contact_sub_type' => "nbr_panel",
          'display_name' => $panel,
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error selecting panel ' . $panel . ': ' . $ex->getMessage(), 'error');
      }

      if ($result['count'] <> 1) {
        // error, panel does not exist, exit
        $this->_logger->logMessage('Panel does not exist on database: ' . $panel, 'error');
        return;
      } else {
        $xdata[$volunteerPanelCustomField] = $result['id'];
        //  &&& $panelID = $result['id'];
      }
    }

    // *** check if site exists and if so, select ID
    // (site code can be sic code or site alias (IBD and STRIDES at the moment)
    if (isset($site) && $site <> '') {
      try {
        // sic code first
        $result = civicrm_api3('Contact', 'get', [
          'sequential' => 1,
          'contact_type' => "Organization",
          'contact_sub_type' => "nbr_site",
          'sic_code' => $site,
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error selecting site ' . $site . ': ' . $ex->getMessage(), 'error');
      }

      if ($result['count'] == 0) {
        try {
          // site alias (STRIDES and IBD)
          $result = civicrm_api3('Contact', 'get', [
            'sequential' => 1,
            'contact_type' => "Organization",
            'contact_sub_type' => "nbr_site",
            $siteAliasTypeCustomField => $siteAliasTypeValue,
            $siteAliasCustomField => $site,
          ]);
          } catch (CiviCRM_API3_Exception $ex) {
            $this->_logger->logMessage('Error selecting site(2) ' . $site . ': ' . $ex->getMessage(), 'error');
          }
      }

      if ($result['count'] <> 1) {
        // error, site does not exist, exit
        $this->_logger->logMessage('Site does not exist on database: ' . $site, 'error');
        return;
      } else {
        // &&& $siteID = $result['id'];
        $xdata[$volunteerSiteCustomField] = $result['id'];
      }
    }

    // *** check if centre exists
    if (isset($centre) && $centre <> '') {
      try {
        $result = civicrm_api3('Contact', 'get', [
          'contact_sub_type' => "nbr_centre",
          'display_name' => $centre,
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error selecting centre ' . $centre . ': ' . $ex->getMessage(), 'error');
      }

      if ($result['count'] <> 1) {
        // error, centre does not exist, exit
        $this->_logger->logMessage('Centre does not exist on database: ' . $centre, 'error');
        return;
      } else {
        // &&&  $centreID = $result['id'];
        $xdata[$volunteerCentreCustomField] = $result['id'];
      }
    }

    // --- check that data was provided
    if(count($xdata) < 3) {
      $this->_logger->logMessage('No panel information provided for : ' . $contactID, 'error');
      return;
    }

    // --- check if panel/site/centre combination is already linked to volunteer ------------------
    try {
      $result = civicrm_api3('Contact', 'get', $xdata);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_logger->logMessage('Error selecting volunteer with panel ' . $contactID . ': '. $ex->getMessage(), 'error');
    }

    if ($result['count'] == 0) {
      // --- panel not yet linked to volunteer, insert -----------------------------------
      // *** add source, if given
      if(isset($source) && !empty($source)) {
        $xdata[$volunteerSourceCustomField] = $source;
      }

      try {
        $result = civicrm_api3("Contact", "create", $xdata);
      } catch (CiviCRM_API3_Exception $ex) {
        $this->_logger->logMessage('Error inserting panel for volunteer ' . $contactID . ': '. $ex->getMessage(), 'error');
      }
    }
  }

  private function addActivity ($contactId, $activityType, $dateTime)
  {
    // &&& todo: only insert if not already in place
    try {
      $result = civicrm_api3('Activity', 'create', [
        'activity_type_id' => $activityType,
        'activity_date_time' => $dateTime,
        'target_id' => $contactId,
      ]);
    } catch (CiviCRM_API3_Exception $ex) {
      $this->_logger->logMessage('Error inserting $activityType activity for volunteer ' . $contactId . ': ' . $ex->getMessage(), 'error');
    }
  }

  private function migrationAddConsent($contactId, $data)
  {
    // only to be used for starfish data migration: migrate all consents to the same recruitment case

    $caseId = '';
    // check if recruitment case already exists
    $params = [1 => [$contactId, 'Integer'],];
    $sql = "select cc.case_id
            from civicrm_case_contact cc, civicrm_case cas, civicrm_case_type cct
            where cc.case_id = cas.id
            and cas.case_type_id  = cct.id
            and cct.name = 'nihr_recruitment'
            and contact_id = %1";

    try {
      $caseId = CRM_Core_DAO::singleValueQuery($sql, $params);
    } catch (Exception $ex) {
    }

    if (!isset($caseId)) {
      // create recruitment case, use date of first consent
      $consentDate = date('Y-m-d', strtotime($data['consent_date']));
      try {
        $result = civicrm_api3('NbrVolunteerCase', 'create', [
          'contact_id' => $contactId,
          'case_type' => 'recruitment',
          'start_date' => $consentDate
        ]);
        $caseId = $result['case_id'];
        $message = E::ts('Recruitment case for volunteer ' . $contactId . '  added');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
      } catch (CiviCRM_API3_Exception $ex) {
        $message = E::ts('Error when creating recruitment case for volunteer ') . $contactId
          . E::ts(' from API NbrVolunteerCase create : ') . $ex->getMessage();
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      }
    }
    // add consent information
    if ($data['consent_version'] <> '' or $data['consent_date'] <> '') {
      $nbrConsent = new CRM_Nihrbackbone_NbrConsent();
      $nbrConsent->addConsent($contactId, $caseId, $data['consent_status'], 'Consent (Starfish migration)', $data, $this->_logger);
    }
  }

  /**
   * Method to migrate the volunteer status, add activities for not recruited, redundant
   * and withdrawn if required and process death information
   *
   * @param $contactId
   * @param $data
   * @throws Exception
   */
  private function migrationVolunteerStatus($contactId, $data)
  {
    // only to be used for starfish data migration:
    if ($this->_dataSource == "starfish") {
      // only if contactId
      if ($contactId) {
        // first set the volunteer status
        $status = CRM_Nihrbackbone_NihrVolunteer::setVolunteerStatus($contactId, $data['volunteer_status']);
        if (!$status) {
          $message = "Invalid status " . $data['volunteer_status'] . ", default status pending used.";
          CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
        }
        // process not recruited, redundant or withdrawn if required
        if (!empty($data['not_recruited_date']) || !empty($data['not_recruited_reason'])) {
          $this->migrateStatusActivity('not_recruited', $contactId, $data['not_recruited_date'], $data['not_recruited_reason'], $data['not_recruited_by']);
        }
        if (!empty($data['redundant_date']) || !empty($data['redundant_reason'])) {
          $this->migrateStatusActivity('redundant', $contactId, $data['redundant_date'], $data['redundant_reason'], $data['redundant_by'], $data['request_to_destroy']);
        }
        if (!empty($data['withdrawn_date']) || !empty($data['withdrawn_reason'])) {
          $this->migrateStatusActivity('withdrawn', $contactId, $data['withdrawn_date'], $data['withdrawn_reason'], $data['withdrawn_by'], $data['request_to_destroy']);
        }
        // process deceased if required
        if (!empty($data['death_reported_date'])) {
          $deceased = CRM_Nihrbackbone_NihrVolunteer::processDeceased($contactId, $data['death_reported_date']);
          // set volunteer status to deceased
          $this->setVolunteerStatus($contactId, Civi::service('nbrBackbone')->getDeceasedVolunteerStatus());
          if (!$deceased) {
            $message = "Error trying to set contact ID " . $contactId . " to deceased with deceased date: " . $data['death_reported_date'] . ". Migrated but no deceased processing.";
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
          }
        }
      }
    }
    else {
      $message = "Call to method migrationVolunteerStatus with invalid datasource " . $this->_dataSource . ", method skipped.";
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
    }
  }

  /**
   * Method to set the volunteer status of a volunteer
   *
   * @param $volunteerId
   * @param $sourceStatus
   * @return bool
   */
  private function setVolunteerStatus($volunteerId, $sourceStatus) {
    $sourceStatus = strtolower($sourceStatus);
    // first check if status exists, use pending if not
    $query = "SELECT COUNT(*) FROM civicrm_option_value WHERE option_group_id = %1 AND value = %2";
    $queryParams = [
      1 => [Civi::service('nbrBackbone')->getVolunteerStatusOptionGroupId(), "Integer"],
      2 => [$sourceStatus, "String"],
    ];
    $count = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($count == 0) {
      $sourceStatus = Civi::service('nbrBackbone')->getPendingVolunteerStatus();
    }
    $update = "UPDATE " . Civi::service('nbrBackbone')->getVolunteerStatusTableName() . " SET "
      . Civi::service('nbrBackbone')->getVolunteerStatusColumnName() . " = %1 WHERE entity_id = %2";
    $updateParams = [
      1 => [$sourceStatus, "String"],
      2 => [(int) $volunteerId, "Integer"],
    ];
    CRM_Core_DAO::executeQuery($update, $updateParams);
    return TRUE;
  }


  /**
   * Method to create activity for not recruited, redundant or withdrawn
   *
   * @param $type
   * @param $contactId
   * @param $sourceDate
   * @param $sourceReason
   * @param $sourceBy
   * @param $sourceDestroy
   */
  private function migrateStatusActivity($type, $contactId, $sourceDate, $sourceReason, $sourceBy, $sourceDestroy = "") {
    $activity = new CRM_Nihrbackbone_NbrActivity();
    $activityParams = ['target_contact_id' => $contactId];
    switch ($type) {
      case "not_recruited":
        $activityParams['activity_type_id'] = Civi::service('nbrBackbone')->getNotRecruitedActivityTypeId();
        $reasonCustomField = "custom_" . Civi::service('nbrBackbone')->getNotRecruitedReasonCustomFieldId();
        if (!empty($sourceReason)) {
          $activityParams[$reasonCustomField] = $activity->findOrCreateStatusReasonValue("not_recruited", $sourceReason);
        }
        break;
      case "redundant":
        $activityParams['activity_type_id'] = Civi::service('nbrBackbone')->getRedundantActivityTypeId();
        $reasonCustomField = "custom_" . Civi::service('nbrBackbone')->getRedundantReasonCustomFieldId();
        $destroyDataCustomField = "custom_" . Civi::service('nbrBackbone')->getRedundantDestroyDataCustomFieldId();
        $destroySamplesCustomField = "custom_" . Civi::service('nbrBackbone')->getRedundantDestroySamplesCustomFieldId();
        if (!empty($sourceReason)) {
          $activityParams[$reasonCustomField] = $activity->findOrCreateStatusReasonValue("redundant", $sourceReason);
        }
        $activityParams[$destroyDataCustomField] = 0;
        $activityParams[$destroySamplesCustomField] = 0;
        if ($sourceDestroy == TRUE) {
          $activityParams[$destroyDataCustomField] = 1;
          $activityParams[$destroySamplesCustomField] = 1;
        }
        break;
      case "withdrawn":
        $activityParams['activity_type_id'] = Civi::service('nbrBackbone')->getWithdrawnActivityTypeId();
        $reasonCustomField = "custom_" . Civi::service('nbrBackbone')->getWithdrawnReasonCustomFieldId();
        $destroyDataCustomField = "custom_" . Civi::service('nbrBackbone')->getWithdrawnDestroyDataCustomFieldId();
        $destroySamplesCustomField = "custom_" . Civi::service('nbrBackbone')->getWithdrawnDestroySamplesCustomFieldId();
        if (!empty($sourceReason)) {
          $activityParams[$reasonCustomField] = $activity->findOrCreateStatusReasonValue("withdrawn", $sourceReason);
        }
        if ($sourceDestroy == '') {
          $message = "No request to destroy flag found for volunteer " . $contactId . ", assumed FALSE.";
          CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
        }
        $activityParams[$destroyDataCustomField] = 0;
        $activityParams[$destroySamplesCustomField] = 0;
        if ($sourceDestroy == TRUE) {
          $activityParams[$destroyDataCustomField] = 1;
          $activityParams[$destroySamplesCustomField] = 1;
        }
        break;
      default:
        return FALSE;
    }
    $activityParams['activity_date_time'] = $this->formatMigrateActivityDate($sourceDate);
    if (!empty($sourceBy)) {
      $resourcer = new CRM_Nihrbackbone_NbrResourcer();
      $sourceContactId = $resourcer->findWithName($sourceBy);
      if ($sourceContactId) {
        $activityParams['source_contact_id'] = $sourceContactId;
      }
      else {
        $activityParams['details'] = "By: " . $sourceBy;
      }
    }
    if (!empty($activityParams)) {
      $activityParams['priority_id'] = Civi::service('nbrBackbone')->getNormalPriorityId();
      $activityParams['subject'] = Civi::service('nbrBackbone')->generateLabelFromValue($type) . " (Starfish migration)";
      $new = $activity->createActivity($activityParams);
      if ($new != TRUE) {
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $new, $this->_originalFileName, 'warning');
      }
    }
  }

  /**
   * Format migration activity date
   *
   * @param $sourceDate
   * @return string
   * @throws Exception
   */
  private function formatMigrateActivityDate($sourceDate) {
    $activityDate = new DateTime();
    if (!empty($sourceDate)) {
      try {
        $activityDate = new DateTime($sourceDate);
      }
      catch (Exception $ex) {
        $message = "Could not transfer date " . $sourceDate . " to a valid DateTime in " . __METHOD__ . ", defaulted to today";
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
      }
    }
    return $activityDate->format("Y-m-d");
  }
}

