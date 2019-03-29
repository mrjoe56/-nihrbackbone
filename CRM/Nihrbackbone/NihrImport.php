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
  private $_importIds = [];
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
  private $_query = NULL;
  private $_queryParams = [];

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
      $this->_importLog->logMessage(E::ts('Starting to import csv file with project volunteers for project ID ') . $this->_projectId, 'Info');
      // retrieve columns and values to import from -> into
      $this->retrievePVCsvSourcesAndValues();
      $selectQuery = $this->buildPVSelectQuery();
      if ($selectQuery) {
        $dao = CRM_Core_DAO::executeQuery($selectQuery);
        $lineNo = 0;
        // process all records from temp table with csv file data
        while ($dao->fetch()) {
          $lineNo++;
          $this->_importLog->logMessage(E::ts('Importing line number ') . $lineNo, 'Info');
          $contactId = $this->getOrCreateVolunteer($dao);
          // if volunteer is not already in project, add to project
          $vp = new CRM_Nihrbackbone_NihrProjectVolunteer($this->_projectId);
          $vp->createProjectVolunteer($contactId);
        }
      }
      // remove temp table because import was successful
      CRM_Core_Session::setStatus(E::ts('Successfully imported csv file into project ') . $this->_projectId, E::ts('CSV File Imported'), 'success');
      CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS " . $this->_tempTable);
    }
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
      foreach ($this->_volunteerColumns as $importId => $volunteerColumn) {
        if ($volunteerColumn == $nhsColumn) {
          $nhsIdValue = $this->_sourceColumns[$importId];
          $contactId = $volunteer->findVolunteerByIdentity($dao->$nhsIdValue, 'nihr_nhs_id');
          if ($contactId) {
            $this->_importLog->logMessage(E::ts('Found existing volunteer ') . $contactId .E::ts(' with NHS number ') . $dao->$nhsIdValue, 'Info');
            return $contactId;
          }
        }
      }
    }
    // check if we have sample Ids and if so, use to get contact
    foreach ($this->_sampleIds as $importId) {
      $sampleIdValue = $this->_sourceColumns[$importId];
      $contactId = $volunteer->findVolunteerByIdentity($dao->$sampleIdValue, 'nihr_sample_id');
      if ($contactId) {
        $this->_importLog->logMessage(E::ts('Found existing volunteer ') . $contactId .E::ts(' with sample Id ') . $dao->$sampleIdValue, 'Info');
        return $contactId;
      }
    }
    // check if we have blood donor Ids and if so, use to get contact
    // todo check if this is correct
    foreach ($this->_bloodDonorIds as $importId) {
      $bloodDonorIdValue = $this->_sourceColumns[$importId];
      $contactId = $volunteer->findVolunteerByIdentity($dao->$bloodDonorIdValue, 'nihr_blood_donor_id');
      if ($contactId) {
        $this->_importLog->logMessage(E::ts('Found existing volunteer ') . $contactId .E::ts(' with blood donor Id ') . $dao->$bloodDonorIdValue, 'Info');
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to create volunteer with all relevant data
   *
   * @param CRM_Core_DAO $dao
   * @return int $volunteerId
   */
  private function getOrCreateVolunteer($dao) {
    // check if we already know this contact with identities
    $volunteerId = $this->findVolunteer($dao);
    if (!$volunteerId) {
      // create volunteer
      $contactParams = $this->buildContactParams($dao);
      $contact = CRM_Contact_BAO_Contact::add($contactParams);
      if ($contact->id) {
        $this->_importLog->logMessage(E::ts('Created new volunteer with ID ') . $contact->id, 'Info');
        $volunteerId = $contact->id;
        // add volunteer data
        $processVolunteer = $this->processVolunteerData($dao, $volunteerId);
        if ($processVolunteer) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        // add address data
        $processAddress = $this->processAddressData($dao, $volunteerId);
        if ($processAddress) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        // add phone data
        $processMobile = $this->processPhoneData($dao, $volunteerId, CRM_Nihrbackbone_BackboneConfig::singleton()->getMobilePhoneTypeId());
        if ($processMobile) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        $processPhone = $this->processPhoneData($dao, $volunteerId, CRM_Nihrbackbone_BackboneConfig::singleton()->getPhonePhoneTypeId());
        if ($processPhone) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        // add email
        $processEmail = $this->processEmailData($dao, $volunteerId);
        if ($processEmail) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        // add Skype IM
        $processSkype = $this->processSkypeData($dao, $volunteerId);
        if ($processSkype) {
          CRM_Core_DAO::executeQuery($this->_query, $this->_queryParams);
        }
        // add sampleIds
        $this->processSampleIds($dao, $volunteerId);
        // todo blood donor Ids - is also multiple group? or custom field for volunteer?
      }
      else {
        $this->_importLog->logMessage(E::ts('Could not find or create a contact'), 'Error');
      }
    }
    return $volunteerId;
  }

  /**
   * Method to add sample ids if they do not exist yet
   * @param $dao
   * @param $volunteerId
   */
  private function processSampleIds($dao, $volunteerId) {
    if (!empty($dao) && !empty($volunteerId)) {
      if (!empty($this->_sampleIds)) {
        $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSampleDataCustomGroup('table_name');
        $sampleIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getSampleCustomField('nsd_sample_id', 'column_name');
        foreach ($this->_sampleIds as $key => $fieldName) {
          if (isset($dao->$fieldName) && !empty($dao->$fieldName)) {
            // insert sample id if it does not exist yet
            $query = "SELECT COUNT(*) FROM " . $tableName . " WHERE " . $sampleIdColumn . " = %1 AND entity_id = %2";
            $count = CRM_Core_DAO::singleValueQuery($query, [
              1 => [$dao->$fieldName, 'String'],
              2 => [$volunteerId, 'Integer'],
            ]);
            if ($count == 0) {
              $insert = "INSERT INTO " . $tableName . " (entity_id, " . $sampleIdColumn . ") VALUES(%1, %2)";
              CRM_Core_DAO::executeQuery($insert, [
                1 => [$volunteerId, 'Integer'],
                2 => [$dao->$fieldName, 'String'],
              ]);
            }
          }
        }
      }
    }
  }

  /**
   * Method to build to contact params array
   *
   * @param $dao
   * @return array
   */
  private function buildContactParams($dao) {
    $contactParams = [
      'contact_type' => 'Individual',
      'contact_sub_type' => CRM_Core_DAO::VALUE_SEPARATOR . 'nihr_volunteer' . CRM_Core_DAO::VALUE_SEPARATOR,
      'do_not_email' => 0,
      'do_not_mail' => 0,
      'do_not_phone' => 0,
      'do_not_sms' => 0,
      'do_not_trade' => 0,
      'is_opt_out' => 0,
      'preferred_language' => 'en_GB',
      'communication_style_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultCommmunicationStyleId(),
      'email_greeting_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultIndEmailGreetingId(),
      'postal_greeting_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultIndPostalGreetingId(),
      'addressee_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultIndAddresseeId(),
    ];
    // start with all the numbers that have to be imported
    foreach ($this->_importIds as $importId) {
      // if there is an element in contact columns there is a contact field to import
      if (isset($this->_contactColumns[$importId])) {
        // if we have birth_date, make sure we have a parseable date
        if ($this->_contactColumns[$importId] == 'birth_date') {
          try {
            $daoColumn = $this->_sourceColumns[$importId];
            $birthDate = new DateTime($dao->$daoColumn);
            $contactParams['birth_date'] = $birthDate->format('d-m-Y');
          }
          catch (Exception $ex) {
            $this->_importLog->logMessage(E::ts('Could not parse birth_date ') . $dao->$daoColumn . E::ts(', no birth date imported.'), 'Error');
          }
        }
        else {
          $daoColumn = $this->_sourceColumns[$importId];
          $contactParams[$this->_contactColumns[$importId]] = $dao->$daoColumn;
        }
      }
    }
    return $contactParams;
  }

  /**
   * Method to build the insert (or update) query for the volunteer custom group
   *
   * @param $dao
   * @param $volunteerId
   * @return bool
   */
  private function processVolunteerData($dao, $volunteerId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerDataCustomGroup('table_name');
    $columns = [];
    $values = [];
    $index = 0;
    // check if there is a volunteer column to import and if so, add
    foreach ($this->_importIds as $importId) {
      if (isset($this->_volunteerColumns[$importId])) {
        $index++;
        $columns[$index] = $this->_volunteerColumns[$importId];
        $daoColumn = $this->_sourceColumns[$importId];
        $values[$index] = $dao->$daoColumn;
      }
    }
    if (!empty($columns)) {
      // check if we need to insert or update
      $query = "SELECT COUNT(*) FROM " . $tableName . " WHERE entity_id = %1";
      $count = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
      if ($count == 0) {
        return $this->buildVolunteerInsert($dao, $volunteerId, $tableName, $columns, $values);
      }
      else {
        return $this->buildVolunteerUpdate($dao, $volunteerId, $tableName, $columns, $values);
      }
    }
    return FALSE;
  }

  /**
   * Method to build the volunteer update query
   *
   * @param $dao
   * @param $entityId
   * @param $tableName
   * @param array $columns
   * @param array $values
   * @return bool
   */
  private function buildVolunteerUpdate($dao, $entityId, $tableName, $columns = [], $values = []) {
    if (empty($dao) || empty($entityId) || empty($tableName)) {
      return FALSE;
    }
    if (!empty($columns)) {
      $this->_queryParams = [];
      $clauses = [];
      foreach ($columns as $id => $columnName) {
        $clauses[] = $columnName . " = %" . $id;
        if ($columnName == 'nvd_recallable') {
          $type = 'Integer';
        }
        else {
          $type = 'String';
        }
        $this->_queryParams[$id] = [$values[$id], $type];
      }
      $index = max(array_keys($columns));
      $index++;
      $this->_queryParams[$index] = [$entityId, 'Integer'];
      $this->_query = "UPDATE " . $tableName . " SET " . implode(', ', $clauses) . " WHERE entity_id = %" . $index;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to build the insert query for volunteers if relevant
   *
   * @param $dao
   * @param $entityId
   * @param $tableName
   * @param array $columns
   * @param array $values
   * @return bool
   */
  private function buildVolunteerInsert($dao, $entityId, $tableName, $columns = [], $values = []) {
    if (empty($dao) || empty($entityId) || empty($tableName)) {
      return FALSE;
    }
    if (!empty($columns)) {
      $index = max(array_keys($columns));
      $index++;
      $columns[$index] = 'entity_id';
      $values[$index] = $entityId;
      $this->_queryParams = [];
      $insertValues = [];
      foreach ($columns as $id => $columnName) {
        $insertValues[] = "%" . $id;
        if ($columnName == 'nvd_recallable' || $columnName == 'entity_id') {
          $type = 'Integer';
        }
        else {
          $type = 'String';
        }
        $this->_queryParams[$id] = [$values[$id], $type];
      }
      $this->_query = "INSERT INTO " . $tableName . " (" . implode(', ', $columns) . ") VALUES ( ". implode(',', $insertValues) . ")";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to build the insert query for the address data
   *
   * @param $dao
   * @param $volunteerId
   * @return bool
   */
  private function processAddressData($dao, $volunteerId) {
    if (empty($volunteerId) || empty($dao)) {
      return FALSE;
    }
    $columns = [
      1 => 'contact_id',
      2 => 'location_type_id',
      3 => 'is_primary',
    ];
    $this->_queryParams = [
      1 => [$volunteerId, 'Integer'],
      2 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultLocationTypeId(), 'Integer'],
      3 => [1, 'Integer'],
    ];
    $index = 3;
    // check if there is an address column to import and if so, add
    foreach ($this->_importIds as $importId) {
      if (isset($this->_addressColumns[$importId])) {
        $index++;
        $columns[$index] = $this->_addressColumns[$importId];
        $daoColumn = $this->_sourceColumns[$importId];
        if ($this->_addressColumns[$importId] == 'country_id' || $this->_addressColumns[$importId] == 'state_province_id') {
          $this->_queryParams[$index] = [$dao->$daoColumn, 'Integer'];
        }
        else {
          $this->_queryParams[$index] = [$dao->$daoColumn, 'String'];
        }
      }
    }
    $insertValues = [];
    foreach ($columns as $id => $columnName) {
      $insertValues[] = "%" . $id;
    }
    $this->_query = "INSERT INTO civicrm_address (" . implode(', ', $columns) . ") VALUES ( ". implode(',', $insertValues) . ")";
    return TRUE;
  }

  /**
   * Method to build the insert query for the phone data
   *
   * @param $dao
   * @param $volunteerId
   * @param $phoneTypeId
   * @return bool
   */
  private function processPhoneData($dao, $volunteerId, $phoneTypeId) {
    if (empty($volunteerId) || empty($dao)) {
      return FALSE;
    }
    $processPhone = FALSE;
    // check if there is a phone column to import and if so, add (depends on phone type)
    switch ($phoneTypeId) {
      case CRM_Nihrbackbone_BackboneConfig::singleton()->getMobilePhoneTypeId():
        if (isset($this->_phoneColumns['mobile']) && !empty($this->_phoneColumns['mobile'])) {
          $daoColumn = $this->_phoneColumns['mobile'];
          $processPhone = TRUE;
        }
        break;
      default:
        if (isset($this->_phoneColumns['phone']) && !empty($this->_phoneColumns['phone'])) {
          $daoColumn = $this->_phoneColumns['phone'];
          $processPhone = TRUE;
        }
    }
    if ($processPhone) {
      $insertValues = [];
      $columns = [
        1 => 'contact_id',
        2 => 'location_type_id',
        3 => 'is_primary',
        4 => 'phone_type_id',
        5 => 'phone',
      ];
      $this->_queryParams = [
        1 => [$volunteerId, 'Integer'],
        2 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultLocationTypeId(), 'Integer'],
        3 => [1, 'Integer'],
        4 => [$phoneTypeId, 'Integer'],
        5 => [$dao->$daoColumn, 'String'],
      ];

      foreach ($columns as $id => $columnName) {
        $insertValues[] = "%" . $id;
      }
      $this->_query = "INSERT INTO civicrm_phone (" . implode(', ', $columns) . ") VALUES ( ". implode(',', $insertValues) . ")";
    }
    return $processPhone;
  }

  /**
   * Method to build the insert query for the email data
   *
   * @param $dao
   * @param $volunteerId
   * @return bool
   */
  private function processEmailData($dao, $volunteerId) {
    if (empty($volunteerId) || empty($dao)) {
      return FALSE;
    }
    // check if the email column should be imported
    if (isset($this->_emailColumns['email']) && !empty($this->_emailColumns['email'])) {
      $daoColumn = $this->_sourceColumns['email'];
      $insertValues = [];
      $columns = [
        1 => 'contact_id',
        2 => 'location_type_id',
        3 => 'is_primary',
        4 => 'email',
      ];
      $this->_queryParams = [
        1 => [$volunteerId, 'Integer'],
        2 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultLocationTypeId(), 'Integer'],
        3 => [1, 'Integer'],
        4 => [$dao->$daoColumn, 'String'],
      ];
      foreach ($columns as $id => $columnName) {
        $insertValues[] = "%" . $id;
      }
      $this->_query = "INSERT INTO civicrm_email (" . implode(', ', $columns) . ") VALUES ( ". implode(',', $insertValues) . ")";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to build the insert query for the skype data
   *
   * @param $dao
   * @param $volunteerId
   * @return bool
   */
  private function processSkypeData($dao, $volunteerId) {
    if (empty($volunteerId) || empty($dao)) {
      return FALSE;
    }
    // check if the skype column should be imported
    if (isset($this->_imColumns['skype_name']) && !empty($this->_imColumns['skype_name'])) {
      $daoColumn = $this->_sourceColumns['skype_name'];
      $insertValues = [];
      $columns = [
        1 => 'contact_id',
        2 => 'location_type_id',
        3 => 'is_primary',
        4 => 'provider_id',
        5 => 'name',
      ];
      $this->_queryParams = [
        1 => [$volunteerId, 'Integer'],
        2 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getDefaultLocationTypeId(), 'Integer'],
        3 => [1, 'Integer'],
        4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getSkypeProviderId(), 'Integer'],
        5 => [$dao->$daoColumn, 'String'],
      ];
      foreach ($columns as $id => $columnName) {
        $insertValues[] = "%" . $id;
      }
      $this->_query = "INSERT INTO civicrm_im (" . implode(', ', $columns) . ") VALUES ( ". implode(',', $insertValues) . ")";
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to check what numbers (source_field_xx related to target_field_xx) to import
   */
  private function retrievePVCsvSourcesAndValues() {
    // get the index of all fields that are to be imported (pattern = target_field_xx
    // with related field in temp table in source_field_xx)
    $this->_importIds = [];
    foreach ($this->_importParams as $importName => $importValue) {
      if (substr($importName, 0, 6) == 'target') {
        if (!empty($importValue)) {
          $importId = str_replace('target_', '', $importName);
          $this->getVolunteerColumns($importValue, $importId);
          $this->_importIds[] = $importId;
        }
      }
    }
  }

  /**
   * Method to set the relevant contact param
   *
   * @param $importValue
   * @param $importId
   */
  private function getVolunteerColumns($importValue, $importId) {
    switch ($importValue) {
      case 1:
        $this->_contactColumns[$importId] = 'birth_date';
        break;
      case 2:
        $this->_bloodDonorIds[] = $importId;
        break;
      case 3:
        $this->_addressColumns[$importId] = 'city';
        break;
      case 4:
        $this->_participationColumns[$importId] = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_consent_status_id', 'column_name');
        break;
      case 5:
        $this->_addressColumns[$importId] = 'state_province_id';
        break;
      case 6:
        $this->_addressColumns[$importId] = 'country_id';
        break;
      case 7:
        $this->_emailColumns[$importId] = 'email';
        break;
      case 8:
        $this->_volunteerColumns[$importId] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_ethnicity_id', 'column_name');
        break;
      case 9:
        $this->_contactColumns[$importId] = 'first_name';
        break;
      case 10:
        $this->_contactColumns[$importId] = 'gender_id';
        break;
      case 11:
        $this->_contactColumns[$importId] = 'last_name';
        break;
      case 12:
        $this->_contactColumns[$importId] = 'middle_name';
        break;
      case 13:
        $this->_phoneColumns[$importId] = 'mobile';
        break;
      case 14:
        $this->_volunteerColumns[$importId] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_nhs_id', 'column_name');
        break;
      case 15:
        $this->_phoneColumns[$importId] = 'phone';
        break;
      case 16:
        $this->_addressColumns[$importId] = 'postal_code';
        break;
      case 17:
        $this->_volunteerColumns[$importId] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_recall_group', 'column_name');
        break;
      case 18:
        $this->_volunteerColumns[$importId] = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_recallable', 'column_name');
        break;
      case 19:
        $this->_sampleIds[] = $importId;
        break;
      case 20:
        $this->_imColumns[$importId] = 'skype_name';
        break;
      case 21:
        $this->_addressColumns[$importId] = 'street_address';
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
    foreach ($this->_importIds as $importId) {
      $sourceField = 'source_' . $importId;
      $this->_sourceColumns[$importId] = $this->_importParams[$sourceField];
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
