<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class NihrVolunteer to deal with volunteer links and data
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 18 Mar 2019
 * @license AGPL-3.0
 * @errorrange 3000-3499
 */
class CRM_Nihrbackbone_NihrVolunteer {

  private $_volunteerContactSubType = [];

  /**
   * CRM_Nihrbackbone_NihrVolunteer constructor.
   */
  public function __construct() {
    try {
      $this->_volunteerContactSubType = civicrm_api3('ContactType', 'getsingle', ['name' => 'nihr_volunteer']);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find a contact_sub_type with name Volunteer in ') . __METHOD__
        . E::ts(', error message from API ContactType getsingle: ') . $ex->getMessage());
    }
  }

  /**
   * Method to check if the contact is a valid volunteer
   *
   * @param $contactId
   * @param string $type
   * @return bool
   */
  public function isValidVolunteer($contactId, string $type = "volunteer") {
    $valid = FALSE;
    if (!empty($contactId)) {
      try {
        $contacts = \Civi\Api4\Contact::get()
          ->addSelect('contact_sub_type')
          ->addWhere('id', '=', (int) $contactId)
          ->execute();
        if ($type == "bioresource") {
          $validTypes = Civi::settings()->get('nbr_bioresource_subtypes');
          foreach ($contacts as $contact) {
            foreach ($contact['contact_sub_type'] as $subType) {
              if (in_array($subType, $validTypes)) {
                $valid = TRUE;
              }
            }
          }
        }
        else {
          foreach ($contacts as $contact) {
            foreach ($contact['contact_sub_type'] as $subType) {
              if ($subType == $this->_volunteerContactSubType['name']) {
                $valid = TRUE;
              }
            }
          }
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return $valid;
  }

  /**
   * Method to get the contact id of the volunteer with the participant id
   *
   * @param $participantId
   * @return false|string
   */
  public function getContactIdWithParticipantId($participantId) {
    if (!empty($participantId)) {
      $query = "SELECT entity_id
        FROM civicrm_value_contact_id_history
        WHERE identifier_type = %1 AND identifier = %2";
      $contactId = CRM_Core_DAO::singleValueQuery($query, [
        1 => [Civi::service('nbrBackbone')->getParticipantIdIdentifierType(), "String"],
        2 => [$participantId, "String"],
      ]);
      if ($contactId) {
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to get the contact id of the volunteer with the study participant id
   *
   * @param $studyParticipantId
   * @return false|string
   */
  public function getContactIdWithStudyParticipantId($studyParticipantId) {
    if (!empty($studyParticipantId)) {
      $query = "SELECT entity_id
        FROM civicrm_value_contact_id_history
        WHERE identifier_type = %1 AND identifier = %2";
      $contactId = CRM_Core_DAO::singleValueQuery($query, [
        1 => [CRM_Nihrnumbergenerator_Config::singleton()->studyParticipantIdIdentifier,  "String"],
        2 => [$studyParticipantId, "String"],
      ]);
      if ($contactId) {
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to get contact id with email (checking for primary first, then single most recent email)
   *
   * @param $email
   * @return false|string
   */
  public function getContactIdWithEmail($email) {
    if (!empty($email)) {
      $query = "SELECT contact_id FROM civicrm_email WHERE email = %1 AND is_primary = %2 ORDER BY id DESC LIMIT 1";
      $contactId = CRM_Core_DAO::singleValueQuery($query, [
        1 => [$email, "String"],
        2 => [1, "Integer"],
        ]);
      if ($contactId) {
        return $contactId;
      }
      $query = "SELECT contact_id FROM civicrm_email WHERE email = %1 ORDER BY id DESC LIMIT 1";
      $contactId = CRM_Core_DAO::singleValueQuery($query, [1 => [$email, "String"]]);
      if ($contactId) {
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to get the contact id of the volunteer with the bioresource id
   *
   * @param $bioresourceId
   * @return false|string
   */
  public function getContactIdWithBioresourceId($bioresourceId) {
    if (!empty($bioresourceId)) {
      $query = "SELECT entity_id
        FROM civicrm_value_contact_id_history
        WHERE identifier_type = %1 AND identifier = %2";
      $contactId = CRM_Core_DAO::singleValueQuery($query, [
        1 => [Civi::service('nbrBackbone')->getBioresourceIdIdentifierType(), "String"],
        2 => [$bioresourceId, "String"],
      ]);
      if ($contactId) {
        return $contactId;
      }
    }
    return FALSE;
  }

  /**
   * Method to find volunteer by identity
   *
   * @param $identifier
   * @param $identifierType
   * @return int|bool
   */
  public function findVolunteerByIdentity($identifier, $identifierType) {
    // first check if API Contactfindbyidentity exists
    try {
      $actions = civicrm_api3('Contact', 'getactions');
      $available = FALSE;
      foreach ($actions[ 'values'] as $action) {
        if ($action == 'findbyidentity') {
          $available = TRUE;
        }
      }
      if (!$available) {
        Civi::log()->error(E::ts('API Contact findbyidentity is not available, make sure the Contact Identity Tracker extension is installed and enabled!'));
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Unexpected issue with API Contact getactions in ') . __METHOD__
        . E::ts(', error from API: ') . $ex->getMessage());
      return FALSE;
    }

    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Unexpected issue with API OptionValue getcount in ') . __METHOD__
        . E::ts(', error from API: ') . $ex->getMessage());
      return FALSE;
    }
    // if all is well, try to find contact
    try {
      /* $result = civicrm_api3('Contact', 'findbyidentity', [
        'identifier' => $identifier,
        'identifier_type' => $identifierType,
      ]); */

      $xID = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'id');

      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        $xID => $identifier,
      ]);


      if (isset($result['id'])) {
        return $result['id'];
      }
      else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Could not find a volunteer with API Contact findbyidentity in ') . __METHOD__
        . E::ts(', error from API: ') . $ex->getMessage());
      return FALSE;
    }

  }

  /**
   * Method to find volunteer by any alias ID
   *
   * @param $identifier
   * @param $identifierType
   * @return int|bool
   */
  public function findVolunteerByAlias($identifier, $alias_type, $logger)
  {

    // for participant ID: $xID = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'id');

    $id = '';

    $sql = "
      SELECT count(*) as cnt, entity_id
      FROM civicrm_value_contact_id_history
      where identifier_type = %1
      and identifier = %2";

    $queryParams = [
      1 => [$alias_type, 'String'],
      2 => [$identifier, 'String'],
    ];

    $data = CRM_Core_DAO::executeQuery($sql, $queryParams);
    if ($data->fetch()) {
      $count = $data->cnt;
      $id = $data->entity_id;
    }

/*
    $sql = "
      SELECT count(*) as cnt, entity_id
      FROM civicrm_value_nihr_volunteer_alias
      where nva_alias_type = %1
      and nva_external_id = %2";

    $queryParams = [
      1 => [$alias_type, 'String'],
      2 => [$identifier, 'String'],
    ];

    $data = CRM_Core_DAO::executeQuery($sql, $queryParams);
    if ($data->fetch()) {
      $count = $data->cnt;
      $id = $data->entity_id;
    } */

    // TODO &&& cnt > 1 -> error, don't store data
    // TODO cnt = 0 -> check further (e.g. name and dob, nhs?)
    // cnt = 1 -> use this ID
    if ($count == 0) {
      $id = '';
    }
    elseif ($count > 1) {
      $logger->logMessage('Multiple records linked to identifier '.$identifier);
    }
    return $id;
  }

  public function findVolunteer($data, $logger)
  {
    $id = '';

    // this function should only be used if mapping on (local) ID failed to ensure that no duplicates are created
    // mapping on first name, surname, gender, dob

    // only use if all four data items are provided
    if (isset($data['first_name']) && $data['first_name'] <> '' &&
      isset($data['last_name']) && $data['last_name'] <> '' &&
      isset($data['gender_id']) && $data['gender_id'] <> '' &&
      isset($data['birth_date']) && $data['birth_date'] <> '') {

      $dob = $data['birth_date']; // todo investigate how to map ('Date' and String are not working without modifying the value)
      $sql = "
        select count(*) as cnt, c.id as id
        from civicrm_contact c
        where c.contact_type = 'Individual'
        and c.contact_sub_type = 'nihr_volunteer'
        and c.first_name = %1
        and c.last_name = %2
        and c.gender_id = if(%3 = 'Female', '1', if(%3 = 'Male', '2', if(%3 = 'Other', '3', 'x')))
        and c.birth_date = '$dob'";

      $queryParams = [
        1 => [$data['first_name'], 'String'],
        2 => [$data['last_name'], 'String'],
        3 => [$data['gender_id'], 'String'],
      ];

      try {
        $xdata = CRM_Core_DAO::executeQuery($sql, $queryParams);
        if ($xdata->fetch()) {
          $count = $xdata->cnt;
          $id = $xdata->id;
        }
      }
      catch (Exception $ex) {
        $logger->logMessage('Select FindVolunteer failed ' . $data['first_name'] . ' ' . $data['first_name']);
      }

      // cnt = 1 -> ID unique for this volunteer
      if ($count == 0) {
        $id = ''; // just in case
      } elseif ($count > 1) {
        // there are already duplicatede records of the volunteer - use one of these but give warning
        $logger->logMessage('Multiple records linked to identifier ' . $data['first_name'] . ' ' . $data['last_name'] . ', used first one (' . $id . ')');
      }
    }
    return $id;
  }

  public function VolunteerStatusActiveOrPending($id, $logger)
  {

      $sql = "SELECT count(*)
              from civicrm_value_nihr_volunteer_status
              where entity_id = %1
              and nvs_volunteer_status in ('volunteer_status_pending', 'volunteer_status_active')";

      $queryParams = [
        1 => [$id, 'Integer'],
      ];

      try {
        $count = (int)CRM_Core_DAO::singleValueQuery($sql, $queryParams);
        if ($count > 0) {
          return TRUE;
        }
      }
      catch (Exception $ex) {
        $logger->logMessage('VolunteerStatusActiveOrPending for ' . $id . ' failed.');
      }
      return FALSE;
  }

  /**
   * Method to calculate BMI
   *
   * @param $weight
   * @param $height
   * @return bool|float
   */
  public function calculateBmi($weight, $height) {
    if (empty($weight) || empty($height)) {
      return FALSE;
      //return 88.8;
    }
    return round($weight / ($height * $height), 1);
  }

  /**
   * Method to calculate the check date for the max invitations each volunteer
   *
   * @throws Exception
   * @return DateTime
   */
  public static function calculateCheckDateMaxInvitations() {
    $noMonths = (int) Civi::settings()->get('nbr_no_months_invitations');
    // subtract period from todays date
    $checkDate = new DateTime();
    $modifier = '-' . $noMonths . ' months';
    $checkDate->modify($modifier);
    return $checkDate;
  }

  /**
   * Method to find out if volunteer is available for blood studies
   *
   * @param $volunteerId
   * @return bool
   */
  public static function availableForBlood($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_willing_to_give_blood', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $willingBlood = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if ($willingBlood || $willingBlood !== "0") {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to find out if volunteer is available for drug studies
   *
   * @param $volunteerId
   * @return bool
   */
  public static function availableForDrugs($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_no_drug_studies', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $excludeFromDrugs = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if (!$excludeFromDrugs) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to find out if volunteer is available for MRI studies
   *
   * @param $volunteerId
   * @return bool
   */
  public static function availableForMri($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_no_mri', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $excludeFromMri = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if (!$excludeFromMri) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to find out if volunteer is available for commercial studies
   *
   * @param $volunteerId
   * @return bool
   */
  public static function availableForCommercial($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_willing_commercial', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $willingCommercial = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if ($willingCommercial) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to find out if volunteer is able to travel
   *
   * @param $volunteerId
   * @return bool
   */
  public static function ableToTravel($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_willing_to_travel', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $travel = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if ($travel || $travel !== "0") {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to determine if contact has a certain gender
   *
   * @param $contactId
   * @param $genderId
   * @return bool
   */
  public static function hasGender($contactId, $genderId) {
    try {
      $contactGenderId = civicrm_api3('Contact', 'getvalue',[
        'id' => $contactId,
        'return' => 'gender_id',
      ]);
      if (empty($contactGenderId) || $contactGenderId == $genderId) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to calculate if contact is in age range
   *
   * @param $contactId
   * @param $fromAge
   * @param $toAge
   * @return bool
   */
  public static function inAgeRange($contactId, $fromAge, $toAge) {
    try {
      $birthDate = civicrm_api3('Contact', 'getvalue',[
        'id' => $contactId,
        'return' => 'birth_date',
      ]);
      if (empty($birthDate)) {
        return TRUE;
      }
      if ($birthDate) {
        $age = CRM_Utils_Date::calculateAge($birthDate);
        if (isset($age['years'])) {
          if (!empty($fromAge) && !empty($toAge)) {
            if ($age['years'] >= $fromAge && $age['years'] <= $toAge) {
              return TRUE;
            }
          }
          if (!empty($fromAge) && empty($toAge)) {
            if ($age['years'] >= $fromAge) {
              return TRUE;
            }
          }
          if (empty($fromAge) && !empty($toAge)) {
            if ($age['years'] <= $toAge) {
              return TRUE;
            }
          }
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to calculate if contact is in bmi range
   *
   * @param int $contactId
   * @param float $fromBmi
   * @param float $toBmi
   * @return bool
   */
  public static function inBmiRange($contactId, $fromBmi, $toBmi) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_bmi', 'column_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $contactBmi = (float) CRM_Core_DAO::singleValueQuery($query, [ 1 => [$contactId, "Integer"]]);
    if (empty($contactBmi)) {
      return TRUE;
    }
    if ($contactBmi) {
      if (!empty($fromBmi) && !empty($toBmi)) {
        if ($contactBmi >= $fromBmi && $contactBmi <= $toBmi) {
          return TRUE;
        }
      }
      if (!empty($fromBmi) && empty($toBmi)) {
        if ($contactBmi >= $fromBmi) {
          return TRUE;
        }
      }
      if (empty($fromBmi) && !empty($toBmi)) {
        if ($contactBmi <= $toBmi) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to determine if contact has ethnicity from param array
   *
   * @param $contactId
   * @param $ethnicityIds
   * @return bool
   */
  public static function hasRequiredEthnicity($contactId, $ethnicityIds) {
    if (!empty($contactId && !empty($ethnicityIds))) {
      if (!is_array($ethnicityIds)) {
        $ethnicityIds = [$ethnicityIds];
      }
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
      $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
      $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
      $contactEthnicityId = CRM_Core_DAO::singleValueQuery($query, [ 1 => [$contactId, "Integer"]]);
      if ($contactEthnicityId) {
        if (in_array($contactEthnicityId, $ethnicityIds)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to determine if contact has required panel
   *
   * @param $contactId
   * @param $panelId
   * @return bool
   */
  public static function hasRequiredPanel($contactId, $panelId) {
    if (!empty($contactId && !empty($panelId))) {
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomGroup('table_name');
      $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerPanelCustomField('nvp_panel', 'column_name');
      $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
      $dao = CRM_Core_DAO::executeQuery($query, [ 1 => [$contactId, "Integer"]]);
      while ($dao->fetch()) {
        if ($dao->$columnName == $panelId) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to check if the volunteer meets all the selection criteria for a project
   *
   * @param $volunteerId
   * @param $projectId
   * @param $criteria
   * @return bool
   */
  public static function meetsProjectSelectionCriteria($volunteerId, $projectId, $criteria = []) {
    if (empty($projectId) || empty($volunteerId)) {
      Civi::log()->error(E::ts('Attempt to check if volunteer meets selection criteria for project without volunteer ID or project ID in ') . __METHOD__);
      return TRUE;
    }
    // if no criteria passed as parameter, take the ones from the project
    if (empty($criteria)) {
      $criteria = CRM_Nihrbackbone_NbrStudy::getSelectionCriteria($projectId);
    }
    // if the project requires blood, the volunteer should not have the exclude from blood studies flag
    if ($criteria['nsc_blood_required'] && !CRM_Nihrbackbone_NihrVolunteer::availableForBlood($volunteerId)) {
      return FALSE;
    }
    // if the project requires an age range, check if the volunteer is in the age range
    if ($criteria['nsc_age_from'] || $criteria['nsc_age_to']) {
      if (!CRM_Nihrbackbone_NihrVolunteer::inAgeRange($volunteerId, (int) $criteria['nsc_age_from'], (int) $criteria['nsc_age_to'])) {
        return FALSE;
      }
    }
    // if the project requires a BMI range, check if the volunteer is in the BMI range
    if ($criteria['nsc_bmi_from'] || $criteria['nsc_bmi_to']) {
      if (!CRM_Nihrbackbone_NihrVolunteer::inBmiRange($volunteerId, (float) $criteria['nsc_bmi_from'], (float) $criteria['nsc_bmi_to'])) {
        return FALSE;
      }
    }
    // if ethnicities are selection criteria, volunteer should have one of the selected
    if ($criteria['nsc_ethnicity_id']) {
      if (!CRM_Nihrbackbone_NihrVolunteer::hasEthnicity($volunteerId, $criteria['nsc_ethnicity_id'])) {
        return FALSE;
      }
    }
    // if gender is used volunteer should have the correct gender
    if ($criteria['nsc_gender_id'] && !CRM_Nihrbackbone_NihrVolunteer::hasGender($volunteerId, $criteria['nsc_gender_id'])) {
      return FALSE;
    }
    // if project requires travel, volunteer shoud not have the unable to travel flag
    if ($criteria['nsc_travel_required'] && !CRM_Nihrbackbone_NihrVolunteer::ableToTravel($volunteerId)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Method to check if the volunteer has been invited on any other study
   * @param int $volunteerId
   * @param int $studyId
   * @return bool
   */
  public static function isInvitedOnOtherStudy($volunteerId, $studyId) {
    if (empty($volunteerId) || empty($studyId)) {
      return FALSE;
    }
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $query = "SELECT COUNT(*)
        FROM ". $participationTable . " AS cvnpd
        JOIN civicrm_case AS cc ON cvnpd.entity_id = cc.id
        JOIN civicrm_case_contact AS ccc ON cc.id = ccc.case_id
        JOIN civicrm_campaign AS camp ON cvnpd.". $studyIdColumn . " = camp.id
        WHERE cvnpd. " . $studyIdColumn . " <> %1 AND cc.is_deleted = %2 AND ccc.contact_id = %3
          AND camp.status_id = %4";
    $queryParams = [
      1 => [(int) $studyId, 'Integer'],
      2 => [0, 'Integer'],
      3 => [(int) $volunteerId, 'Integer'],
      4 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), 'Integer'],
    ];
    $i = 4;
    CRM_Nihrbackbone_Utils::addParticipantStudyStatusClauses(Civi::settings()->get('nbr_invited_study_status'), $i, $query, $queryParams);
    $count = (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to determine if volunteer is temporarily non recallable
   *
   * @param $volunteerId
   * @return bool
   */
  public static function isTemporarilyNonRecallable($volunteerId) {
    if (empty($volunteerId)) {
      return FALSE;
    }
    try {
      $result = civicrm_api3('EntityTag', 'get', [
        'sequential' => 1,
        'return' => ["tag_id"],
        'entity_table' => "civicrm_contact",
        'entity_id' => $volunteerId,
      ]);
      foreach ($result['values'] as $contactTag) {
        if ($contactTag['tag_id'] == Civi::service('nbrBackbone')->getTempNonRecallTagId()) {
          return TRUE;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to determine if volunteer is active
   *
   * @param $volunteerId
   * @return bool
   */
  public static function isActive($volunteerId) {
    if (empty($volunteerId)) {
      return FALSE;
    }
    $customFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerStatusCustomField('nvs_volunteer_status', 'id');
    try {
      $result = (string) civicrm_api3('Contact', 'getvalue', [
        'id' => $volunteerId,
        'return' => $customFieldId,
      ]);
      if ($result == Civi::service('nbrBackbone')->getActiveVolunteerStatus()) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Could not retrieve volunteer status for contactID ') . $volunteerId . E::ts(' in ') . __METHOD__);
      return FALSE;
    }
  }

  /**
   * Method to check if volunteer is deceased
   *
   * @param $volunteerId
   * @return bool
   */
  public static function isDeceased($volunteerId) {
    try {
      $result = civicrm_api3('Contact', 'getvalue', [
        'return' => "is_deceased",
        'id' => (int) $volunteerId,
      ]);
      if ($result == "1") {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to check if the volunteer has at least 1 valid activity of a specific type
   * (where volunteer is target of the activity)
   *
   * @param $volunteerId
   * @param $activityType
   * @param null $activityStatus
   * @return bool
   */
  public static function hasActivity($volunteerId, $activityType, $activityStatus = NULL) {
    // default completed
    if (!$activityStatus) {
      $activityStatus = Civi::service('nbrBackbone')->getCompletedActivityStatusId();
    }
    if (!empty($volunteerId) && !empty($activityType)) {
      $query = "SELECT COUNT(*)
        FROM civicrm_activity_contact AS a
          JOIN civicrm_activity AS b ON a.activity_id = b.id
        WHERE b.is_deleted = %1 AND b.is_test = %1 AND b.is_current_revision = %2 AND b.status_id = %3
          AND b.activity_type_id = %4 AND a.contact_id = %5 AND a.record_type_id = %6";
      $count = CRM_Core_DAO::singleValueQuery($query, [
        1 => [0, "Integer"],
        2 => [1, "Integer"],
        3 => [$activityStatus, "Integer"],
        4 => [(int) $activityType, "Integer"],
        5 => [(int) $volunteerId, "Integer"],
        6 => [Civi::service('nbrBackbone')->getTargetRecordTypeId(), "Integer"],
      ]);
      if ($count > 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Check if volunteer allows email
   *
   * @param $volunteerId
   * @return bool
   */
  public static function allowsEmail($volunteerId) {
    try {
      $result = civicrm_api3('Contact', 'getvalue', [
        'return' => "do_not_email",
        'id' => (int) $volunteerId,
      ]);
      if ($result == "1") {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return TRUE;
  }

  /**
   * Method to set the eligibility other on all cases where volunteer is selected and study is
   * not the current one
   *
   * @param $contactId
   * @param $caseId
   */
  public static function updateEligibilityAfterInvite($contactId, $caseId) {
    // retrieve study id of caseId -> ignore that study
    $currentStudyId = (int) CRM_Nihrbackbone_NbrVolunteerCase::getStudyId($caseId);
    // select all active cases on recruiting studies where volunteer is selected
    $query = "SELECT DISTINCT(pd.entity_id) AS case_id
        FROM civicrm_value_nbr_participation_data AS pd
            JOIN civicrm_case AS cc ON pd.entity_id = cc.id
            JOIN civicrm_case_contact AS ccc ON cc.id = ccc.case_id
        WHERE cc.is_deleted = %1 AND ccc.contact_id = %2 AND cc.case_type_id = %3
          AND pd.nvpd_study_id != %4 AND pd.nvpd_study_participation_status = %5";
    $queryParams = [
      1 => [0, "Integer"],
      2 => [$contactId, "Integer"],
      3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      4 => [$currentStudyId, "Integer"],
      5 => [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"],
    ];
    $case = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($case->fetch()) {
      // set eligibility to "invited on other" for those cases
      CRM_Nihrbackbone_NbrVolunteerCase::setEligibilityStatus([Civi::service('nbrBackbone')->getOtherEligibilityStatusValue()], (int) $case->case_id);
    }
  }

  /**
   * Check if the volunteer has the max number of face to face invites in the period
   *
   * @param $volunteerId
   * @param $type
   * @return bool
   */
  public static function hasMaxFaceToFaceInvitesNow($volunteerId) {
    $max = (int) Civi::settings()->get('nbr_max_facetoface_invitations');
    $count = self::getCountVolunteerStudyInvitesInPeriod($volunteerId, "facetoface");
    if ($count >= $max) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check if the volunteer reaches final if we add one more
   *
   * @param $volunteerId
   * @param $type
   * @return bool
   */
  public static function hasFinalFaceToFaceInvitesNow($volunteerId) {
    $max = (int) Civi::settings()->get('nbr_max_facetoface_invitations');
    $count = self::getCountVolunteerStudyInvitesInPeriod($volunteerId, "facetoface");
    if ($count == $max) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check if the volunteer has the total max number of invites in the period
   *
   * @param $volunteerId
   * @param $type
   * @return bool
   */
  public static function hasMaxTotalInvitesNow($volunteerId) {
    $max = (int) Civi::settings()->get('nbr_max_total_invitations');
    $count = self::getCountVolunteerStudyInvitesInPeriod($volunteerId, "total");
    if ($count >= $max) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check if the volunteer reaches final if we add one more
   *
   * @param $volunteerId
   * @param $type
   * @return bool
   */
  public static function hasFinalTotalInvitesNow($volunteerId) {
    $max = (int) Civi::settings()->get('nbr_max_total_invitations');
    $count = self::getCountVolunteerStudyInvitesInPeriod($volunteerId, "total");
    if ($count == $max) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Method to get the studies the volunteer has been invited to in the last xxxx months
   *
   * @param $contactId
   * @param $type (facetoface or online)
   */
  public static function getCountVolunteerStudyInvitesInPeriod($contactId, $type) {
    if (empty($contactId) || empty($type)) {
      Civi::log()->warning(E::ts("Empty contactId or type in ") . __METHOD__);
      return FALSE;
    }
    $faceToFaceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField("nsd_recall", "column_name");
    $onLineColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField("nsd_online_study", "column_name");
    $studyTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyDataCustomGroup("table_name");
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup("table_name");
    $inviteColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField("nvpd_date_invited", "column_name");
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField("nvpd_study_id", "column_name");
    $checkDate = self::calculateCheckDateMaxInvitations();
    $query = "SELECT COUNT(DISTINCT(c.id)) AS study_count
        FROM " . $participationTable . " AS a
            JOIN civicrm_campaign AS b ON a." . $studyIdColumn . " = b.id
            JOIN civicrm_case AS c ON a.entity_id = c.id
            JOIN civicrm_case_contact AS d ON c.id = d.case_id
            LEFT JOIN " . $studyTable . " AS e ON b.id = e.entity_id
        WHERE a." . $inviteColumn . " >= %1 AND a." . $inviteColumn . " IS NOT NULL
          AND b.status_id IN (%2, %3, %4) AND c.is_deleted = %5 AND c.case_type_id = %6 AND d.contact_id = %7";
    $queryParams = [
      1 => [$checkDate->format("Y-m-d"), "String"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), "Integer"],
      3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getCompletedStudyStatus(), "Integer"],
      4 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedStudyStatus(), "Integer"],
      5 => [0, "Integer"],
      6 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      7 => [(int) $contactId, "Integer"],
    ];
    $index = 7;
    // add participant study status clauses
    CRM_Nihrbackbone_Utils::addParticipantStudyStatusClauses(Civi::settings()->get('nbr_max_invited_study_status'), $index, $query, $queryParams);
    switch ($type) {
      case "facetoface":
        $index++;
        $queryParams[$index] = [1, "Integer"];
        $query .= " AND e." . $faceToFaceColumn . " = %" . $index;
        break;
      case "online":
        $index++;
        $queryParams[$index] = [1, "Integer"];
        $query .= " AND e." . $onLineColumn . " = %" . $index;
        break;
      case "total":
        $index++;
        $queryParams[$index] = [1, "Integer"];
        $query .= " AND (e." . $faceToFaceColumn . " = %" . $index . " OR e." . $onLineColumn . " = %" . $index
          . ")";
        break;
      default:
        Civi::log()->warning(E::ts("Invalid type " . $type . " in ") . __METHOD__);
        return FALSE;
        break;
    }
    return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
  }

  /**
   * Method to set the volunteer status of a volunteer
   *
   * @param $volunteerId
   * @param $sourceStatus
   * @return bool
   */
  public static function setVolunteerStatus($volunteerId, $sourceStatus) {

    $sourceStatus = strtolower($sourceStatus);
    // first check if status exists, use pending if not
    try {
      $count = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => Civi::service('nbrBackbone')->getVolunteerStatusOptionGroupId(),
        'value' => $sourceStatus,
      ]);
      if ($count == 0) {
        $sourceStatus = Civi::service('nbrBackbone')->getPendingVolunteerStatus();
      }
      $query = "UPDATE " . Civi::service('nbrBackbone')->getVolunteerStatusTableName() . " SET "
        . Civi::service('nbrBackbone')->getVolunteerStatusColumnName() . " = %1 WHERE entity_id = %2";
      $queryParams = [
        1 => [$sourceStatus, "String"],
        2 => [(int) $volunteerId, "Integer"],
      ];
      CRM_Core_DAO::executeQuery($query, $queryParams);
      return TRUE;
    }
    catch (CiviCRM_API3_Exception $ex) {
      return FALSE;
    }
  }

  /**
   * Method to set a volunteer to deceased
   * - tick CiviCRM deceased box and set date if not empty
   * - set volunteer status deceased
   *
   * @param $volunteerId
   * @param $deceasedDate
   * @throws Exception
   * @return bool
   */
  public static function processDeceased($volunteerId, $deceasedDate) {
    if (empty($volunteerId)) {
      return FALSE;
    }
    if (!$deceasedDate instanceof DateTime && !empty($deceasedDate)) {
      $deceasedDate = new DateTime($deceasedDate);
    }
    // tick the deceased box in CiviCRM and set deceased date if applicable
    $query = "UPDATE civicrm_contact SET is_deceased = %1, deceased_date = %2 WHERE id = %3";
    $queryParams = [
      1 => [1, "Integer"],
      2 => [$deceasedDate->format("Y-m-d"), "String"],
      3 => [(int) $volunteerId, "Integer"],
    ];
    CRM_Core_DAO::executeQuery($query, $queryParams);
    return TRUE;
  }

  /**
   * Method to determine if volunteer has a valid and correct consent
   *
   * @param $volunteerId
   * @return bool
   */
  public static function hasValidCorrectConsent($volunteerId) {
    $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomGroup('table_name');
    $consentVersionColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_version', 'column_name');
    $consentStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_status', 'column_name');
    if (!empty($volunteerId)) {
      $query = "SELECT c." . $consentVersionColumn ."
        FROM civicrm_activity_contact AS a
        JOIN civicrm_activity AS b ON a.activity_id = b.id AND b.is_current_revision = %1 AND b.is_deleted = %2 AND b.is_test = %2
        JOIN " . $table . " AS c on b.id = c.entity_id
        WHERE a.contact_id = %3 AND c." . $consentStatusColumn . " = %4";
      $queryParams = [
        1 => [1, "Integer"],
        2 => [0, "Integer"],
        3 => [(int) $volunteerId, "Integer"],
        4 => [Civi::service('nbrBackbone')->getCorrectConsentStatusValue(), "String"],
      ];
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      // if volunteer has no consents with status correct, false
      if ($dao->N == 0) {
        return FALSE;
      }
      // for each correct consent status, check if the version is valid. If so, true
      $nbrConsent = new CRM_Nihrbackbone_NbrConsent();
      while ($dao->fetch()) {
        $version = $dao->$consentVersionColumn;
        if ($nbrConsent->isValidConsentVersion($version)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to check if the volunteer is pending
   *
   * @param $volunteerId
   * @return bool
   */
  public static function isPending($volunteerId) {
    if (empty($volunteerId)) {
      return FALSE;
    }
    $customFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerStatusCustomField('nvs_volunteer_status', 'id');
    try {
      $result = (string) civicrm_api3('Contact', 'getvalue', [
        'id' => $volunteerId,
        'return' => $customFieldId,
      ]);
      if ($result == Civi::service('nbrBackbone')->getPendingVolunteerStatus()) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Could not retrieve volunteer status for contactID ') . $volunteerId . E::ts(' in ') . __METHOD__);
      return FALSE;
    }
  }

  /**
   * Method to check if volunteer has status withdrawn
   *
   * @param int $volunteerId
   * @return bool
   */
  public static function isWithdrawn(int $volunteerId) {
    $table = Civi::service('nbrBackbone')->getVolunteerStatusTableName();
    $column = Civi::service('nbrBackbone')->getVolunteerStatusColumnName();
    $withdrawn = Civi::service('nbrBackbone')->getWithdrawnVolunteerStatus();
    $query = "SELECT " . $column . " FROM " . $table . " WHERE entity_id = %1";
    $status = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, "Integer"]]);
    if ($status && $status == $withdrawn) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to check if volunteer has status redundant
   *
   * @param int $volunteerId
   * @return bool
   */
  public static function isRedundant(int $volunteerId) {
    $table = Civi::service('nbrBackbone')->getVolunteerStatusTableName();
    $column = Civi::service('nbrBackbone')->getVolunteerStatusColumnName();
    $redundant = Civi::service('nbrBackbone')->getRedundantVolunteerStatus();
    $query = "SELECT " . $column . " FROM " . $table . " WHERE entity_id = %1";
    $status = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, "Integer"]]);
    if ($status && $status == $redundant) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to check if volunteer has bioresource id
   *
   * @param int $volunteerId
   * @return bool
   */
  public static function hasBioResourceId(int $volunteerId) {
    $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('table_name');
    $bioResourceColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id', 'column_name');
    $query = "SELECT " . $bioResourceColumn . " FROM " . $table . " WHERE entity_id = %1";
    $bioResourceId = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, "Integer"]]);
    if ($bioResourceId) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to check if volunteer has participant id
   *
   * @param int $volunteerId
   * @return bool
   */
  public static function hasParticipantId(int $volunteerId) {
    $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('table_name');
    $participantColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id', 'column_name');
    $query = "SELECT " . $participantColumn . " FROM " . $table . " WHERE entity_id = %1";
    $participantId = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, "Integer"]]);
    if ($participantId) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to determine if volunteer is face to face recall only
   *
   * @param int $volunteerId
   * @return bool
   */
  public static function isFaceToFaceRecallOnly(int $volunteerId) {
    $table = Civi::service('nbrBackbone')->getVolunteerSelectionTableName();
    $column = Civi::service('nbrBackbone')->getNoOnlineStudiesColumnName();
    if ($table && $column) {
      $query = "SELECT " . $column . " FROM " . $table . " WHERE entity_id = %1";
      $faceToFaceOnly = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, "Integer"]]);
      if ($faceToFaceOnly) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
