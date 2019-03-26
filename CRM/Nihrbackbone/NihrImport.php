<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource NIHR BioResource Import (to process importing processes)
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Mar 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NihrImport {

  private $_importParams = [];
  private $_importLog = NULL;
  private $_projectId = NULL;
  private $_tempTable = NULL;
  private $_importNumbers = [];
  private $_sourceColumns = [];
  private $_contactColumns = [];
  private $_volunteerColumns = [];
  private $_phoneColumns = [];
  private $_addressColumns = [];
  private $_emailColumns = [];
  private $_participationColumns = [];
  private $_imColumns = [];
  private $_bloodDonorIds = [];
  private $_sampleIds = [];

  /**
   * CRM_Nihrbackbone_NihrImport constructor.
   */
  public function __construct($importParams) {
    $this->_importParams = $importParams;
    // create import log
    if (isset($this->_importParams['temp_table_name']) && !empty($this->_importParams['temp_table_name'])) {
      $logName = $this->_importParams['temp_table_name'];
    }
    else {
      $logName = 'nihr_import_' . md5(uniqid(rand(0,999999)));
    }
    $this->_importLog = new CRM_Nihrbackbone_NihrLogger($logName);
    // set params for contact api with volunteer data array key
  }

  /**
   * Method to import a project volunteer csv file
   *
   * @throws API_Exception
   */
  public function importPVCsvFile() {
    // check if we can import (is there a project_id, do we have a temp file, do we have data)
    if ($this->canImportPVCsvFile()) {
      // retrieve columns and values to import from -> into
      $this->retrievePVCsvSourcesAndValues();
      $selectQuery = $this->buildPVSelectQuery();
      if ($selectQuery) {
        $dao = CRM_Core_DAO::executeQuery($selectQuery);
        // process all records from temp table with csv file data
        while ($dao->fetch()) {
          $contactId = $this->getOrCreateVolunteer($dao);
          // if volunteer is not already in project, add to project
          $vp = new CRM_Nihrbackbone_NihrProjectVolunteer($this->_projectId);
          if (!$vp->isAlreadyOnProject($contactId)) {
            $this->addVolunteerToProject($contactId);
          }
        }
      }
    }
  }
  private function addVolunteerToProject($contactId) {
  }

  /**
   * Method to try and find a volunteer using the contact identities
   *
   * @param $dao
   * @return bool|int
   */
  private function findVolunteer($dao) {
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    // check if we have nhs_id and if so, use to get contact
    $nhsColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_nhs_id', 'column_name');
    if (in_array($nhsColumn, $this->_volunteerColumns)) {
      foreach ($this->_volunteerColumns as $importNumber => $volunteerColumn) {
        if ($volunteerColumn == $nhsColumn) {
          $nhsIdValue = $this->_sourceColumns[$importNumber];
          $contactId = $volunteer->findVolunteerByIdentity($dao->$nhsIdValue, 'nihr_nhs_id');
          if ($contactId) {
            return $contactId;
          }
        }
      }
    }
    // check if we have sample Ids and if so, use to get contact
    foreach ($this->_sampleIds as $importNumber) {
      $sampleIdValue = $this->_sourceColumns[$importNumber];
      $contactId = $volunteer->findVolunteerByIdentity($dao->$sampleIdValue, 'nihr_sample_id');
      if ($contactId) {
        return $contactId;
      }
    }
    // check if we have blood donor Ids and if so, use to get contact
    foreach ($this->_bloodDonorIds as $importNumber) {
      $bloodDonorIdValue = $this->_sourceColumns[$importNumber];
      $contactId = $volunteer->findVolunteerByIdentity($dao->$bloodDonorIdValue, 'nihr_blood_donor_id');
      if ($contactId) {
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to create volunteer with all relevant data
   *
   * @param $dao
   */
  private function getOrCreateVolunteer($dao) {
    // check if we already know this contact with identities
    $contactId = $this->findVolunteer($dao);
    if (!$contactId) {
      // create volunteer
      $insertContact = $this->buildContactInsert($dao);
      $contact = CRM_Core_DAO::executeQuery($insertContact);
      CRM_Core_Error::debug('contact', $contact);
      exit();
      // add volunteer data
      $insertVolunteer = $this->buildVolunteerInsert($dao);
      if ($insertVolunteer) {
        CRM_Core_DAO::executeQuery($insertVolunteer);
      }
      // add address data
      $insertAddress = $this->buildAddressInsert($dao);
      if ($insertAddress) {
        CRM_Core_DAO::executeQuery($insertAddress);
      }
      // add phone data
      $insertPhones = $this->buildPhonesInsert($dao);
      if ($insertPhones) {
        CRM_Core_DAO::executeQuery($insertPhones);
      }
      // add email
      $insertEmail = $this->buildEmailInsert($dao);
      if ($insertEmail) {
        CRM_Core_DAO::executeQuery($insertEmail);
      }
      // add IM
      $insertIm = $this->buildImInsert($dao);
      if ($insertIm) {
        CRM_Core_DAO::executeQuery($insertIm);
      }
    }
  }

  /**
   * Method to check what numbers (source_field_xx related to target_field_xx) to import
   */
  private function retrievePVCsvSourcesAndValues() {
    // get the index of all fields that are to be imported (pattern = target_field_xx
    // with related field in temp table in source_field_xx)
    $this->_importNumbers = [];
    foreach ($this->_importParams as $importName => $importValue) {
      if (substr($importName, 0, 6) == 'target') {
        if (!empty($importValue)) {
          $importNumber = str_replace('target_field_', '', $importName);
          $this->getVolunteerColumns($importValue, $importNumber);
          $this->_importNumbers[] = $importNumber;
        }
      }
    }
  }

  /**
   * Method to set the relevant contact param
   *
   * @param $importValue
   * @param $importNumber
   */
  private function getVolunteerColumns($importValue, $importNumber) {
    switch ($importValue) {
      case 1:
        $this->_contactColumns[$importNumber] = 'birth_date';
        break;
      case 2:
        $this->_bloodDonorIds[] = $importNumber;
        break;
      case 3:
        $this->_addressColumns[$importNumber] = 'city';
        break;
      case 4:
        $this->_participationColumns[$importNumber] = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_consent_status_id', 'column_name');
        break;
      case 5:
        $this->_addressColumns[$importNumber] = 'state_province_id';
        break;
      case 6:
        $this->_addressColumns[$importNumber] = 'country_id';
        break;
      case 7:
        $this->_emailColumns[$importNumber] = 'email';
        break;
      case 8:
        $this->_volunteerColumns[$importNumber] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_ethnicity_id', 'column_name');
        break;
      case 9:
        $this->_contactColumns[$importNumber] = 'first_name';
        break;
      case 10:
        $this->_contactColumns[$importNumber] = 'gender_id';
        break;
      case 11:
        $this->_contactColumns[$importNumber] = 'last_name';
        break;
      case 12:
        $this->_contactColumns[$importNumber] = 'middle_name';
        break;
      case 13:
        $this->_phoneColumns[$importNumber] = [
          'phone_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getMobilePhoneTypeId(),
        ];
        break;
      case 14:
        $this->_volunteerColumns[$importNumber] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_nhs_id', 'column_name');
        break;
      case 15:
        $this->_phoneColumns[$importNumber] = [
          'phone_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getPhonePhoneTypeId(),
        ];
        break;
      case 16:
        $this->_addressColumns[$importNumber] = 'postal_code';
        break;
      case 17:
        $this->_volunteerColumns[$importNumber] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_recall_group', 'column_name');
        break;
      case 18:
        $this->_volunteerColumns[$importNumber] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_recallable', 'column_name');
        break;
      case 19:
        $this->_sampleIds[] = $importNumber;
        break;
      case 20:
        $this->_imColumns[$importNumber] = 'skype_name';
        break;
      case 21:
        $this->_addressColumns[$importNumber] = 'street_address';
        break;
    }
  }

  /**
   * Method to build a select with all fields from the csv temp table that are to be imported
   *
   * @return string|bool
   */
  private function buildPVSelectQuery() {
    $this->_sourceColumns = [];
    foreach ($this->_importNumbers as $importNumber) {
      $sourceField = 'source_field_' . $importNumber;
      $this->_sourceColumns[$importNumber] = $this->_importParams[$sourceField];
    }
    if (!empty($this->_sourceColumns)) {
      return "SELECT ".  implode(',', $this->_sourceColumns). " FROM " . $this->_tempTable;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to determine if the csv file can be imported
   * - needs a valid project id
   * - needs a temp table name
   * - temp table should contain data
   * - there are target columns
   *
   * @return bool
   */
  private function canImportPVCsvFile() {
    // error if project_id not specified or empty
    if (!isset($this->_importParams['project_id']) || empty($this->_importParams['project_id'])) {
      $this->_importLog->logMessage(E::ts('Element project_id was either absent or empty in the import parameters, file not imported.'), 'error');
      return FALSE;
    }
    // error if project does not exist
    $project = new CRM_Nihrbackbone_NihrProject();
    if (!$project->projectExists($this->_importParams['project_id'])) {
      $this->_importLog->logMessage(E::ts('Project ID specified in import(' . $this->_importParams['project_id'] . ') does not exist, file not imported.', 'error'));
      return FALSE;
    }
    $this->_projectId = $this->_importParams['project_id'];
    // error if temp table name not specified or empty
    if (!isset($this->_importParams['temp_table_name']) || empty($this->_importParams['temp_table_name'])) {
      $this->_importLog->logMessage(E::ts('The name of the temporary table with the data from the csv file was not specified or empty in the import parameters, file not imported for project ID ' . $this->_projectId), 'error');
      return FALSE;
    }
    // error if file does not exist
    if (!CRM_Core_DAO::checkTableExists($this->_importParams['temp_table_name'])) {
      $this->_importLog->logMessage(E::ts('The temporary table with the data from the csv file ( '. $this->_importParams['temp_table_name'] . ') does not exist, file not imported for project ID ' . $this->_projectId, 'error'));
      return FALSE;
    }
    $this->_tempTable = $this->_importParams['temp_table_name'];
    // warning if no target columns specified
    $targets = FALSE;
    foreach ($this->_importParams as $importName => $importValue) {
      if (substr($importName, 0, 6) == 'target' && !empty($importValue)) {
        $targets = TRUE;
      }
    }
    if (!$targets) {
      $this->_importLog->logMessage(E::ts('No target fields selected for temporary table '. $this->_tempTable . ' for volunteer import into project ID ' . $this->_projectId, 'warning'));
      return FALSE;
    }
    // warning if no data in file
    $count = CRM_Core_DAO::singleValueQuery("SELECT COUNT(*) FROM " . $this->_tempTable);
    if ($count == 0) {
      $this->_importLog->logMessage(E::ts('No data in temporary table '. $this->_tempTable . ' for volunteer import into project ID ' . $this->_projectId, 'warning'));
    }
    return TRUE;
  }

}
