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
   * @return bool
   */
  public function isValidVolunteer($contactId) {
    try {
      $contactSubTypes = civicrm_api3('Contact', 'getvalue', [
        'id' => $contactId,
        'return' => 'contact_sub_type',
      ]);
      foreach ($contactSubTypes as $contactSubTypeId => $contactSubTypeName) {
        if ($contactSubTypeName == $this->_volunteerContactSubType['name']) {
          return TRUE;
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
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
  public function findVolunteerByAlias($identifier, $alias_type)
  {

    try {
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
    }
    catch (CiviCRM_API3_Exception $ex) {
      // todo
    }

    // TODO &&& cnt > 1 -> error, don't store data
    // TODO cnt = 0 -> check further (e.g. name and dob, nhs?)
    // cnt = 1 -> use this ID
    if ($count <> 1) {
      $id = '';
    }
    return $id;
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
   * @param int $volunteerId
   * @param int $studyId
   * @return bool
   * @throws
   */
  public static function hasMaxStudyInvitationsNow($volunteerId, $studyId) {
    if (!empty($volunteerId)) {
      $maxNumber = (int) Civi::settings()->get('nbr_max_invitations');
      $checkDate = self::calculateCheckDateMaxInvitations();
      $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
      $query = "SELECT COUNT(*)
        FROM civicrm_value_nbr_participation_data AS cvnpd
        JOIN civicrm_case AS cc ON cvnpd.entity_id = cc.id
        JOIN civicrm_case_contact AS ccc ON cc.id = ccc.case_id
        JOIN civicrm_campaign AS camp ON cvnpd.nvpd_study_id = camp.id
        WHERE cvnpd. nvpd_study_id <> %1 AND cc.is_deleted = %2 AND ccc.contact_id = %3
        AND camp.start_date >= %4";
      $queryParams = [
        1 => [(int) $studyId, "Integer"],
        2 => [0, "Integer"],
        3 => [(int) $volunteerId, "Integer"],
        4 => [$checkDate->format('Y-m-d'), "String"],
      ];
      $studyCount = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($studyCount >= $maxNumber) {
        return TRUE;
      }
    }
    return FALSE;
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
   * Method to count the distinct number of studies where volunteer has been invited after a certain date
   *
   * @param $contactId
   * @param $checkDate
   * @return bool|int
   * @throws Exception
   */
  private static function countDistinctStudiesWithInvitations($contactId, $checkDate) {
    if (empty($contactId) || empty($checkDate)) {
      return FALSE;
    }
    if (!$checkDate instanceof DateTime) {
      $checkDate = new DateTime($checkDate);
    }
    $studyQuery = "SELECT COUNT(DISTINCT(std.nsd_study_number))
        FROM civicrm_value_nbr_study_data AS std
        LEFT JOIN civicrm_value_nbr_participation_data AS nvpd ON std.entity_id = nvpd.nvpd_study_id
        LEFT JOIN civicrm_case AS cc ON nvpd.entity_id = cc.id
        LEFT JOIN civicrm_case_contact AS cont ON cc.id = cont.case_id
        LEFT JOIN civicrm_case_activity AS cac ON cc.id = cac.case_id
        LEFT JOIN civicrm_activity AS act ON cac.activity_id = act.id AND act.is_current_revision = %1
        WHERE cc.is_deleted = %2 AND cc.case_type_id = %3 AND act.activity_type_id = %4 AND cont.contact_id = %5
        AND act.is_deleted = %2 AND act.activity_date_time > %6";
    $studyCount =  (int) CRM_Core_DAO::singleValueQuery($studyQuery, [
      1 => [1, 'Integer'],
      2 => [0, 'Integer'],
      3 => [(int)CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
      4 => [(int)CRM_Nihrbackbone_BackboneConfig::singleton()->getInviteProjectActivityTypeId(), 'Integer'],
      5 => [(int)$contactId, 'Integer'],
      6 => [$checkDate->format('Y-m-d'), 'String'],
    ]);
    return $studyCount;
  }

  /**
   * Method to return all the active studies the contact has not been invited to yet
   *
   * @param $contactId
   * @return array
   * @throws Exception
   */
  public static function getCurrentSelectedStudies($contactId) {
    $studies = [];
    if (!empty($contactId)) {
      $studyQuery = "SELECT DISTINCT(c.nvpd_study_id) AS study_id
        FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        LEFT JOIN civicrm_value_nbr_participation_data AS c ON a.case_id = c.entity_id
        LEFT JOIN civicrm_campaign AS d ON d.id = c.nvpd_study_id
        WHERE a.contact_id = %1 AND b.is_deleted = %2 AND b.case_type_id = %3
          AND d.status_id in (%4, %5)";
      $dao = CRM_Core_DAO::executeQuery($studyQuery, [
        1 => [(int) $contactId, 'Integer'],
        2 => [0, 'Integer'],
        3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
        4 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), 'Integer'],
        5 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getPendingStudyStatus(), 'Integer'],
      ]);
      while ($dao->fetch()) {
        if (!CRM_Nihrbackbone_NbrVolunteerCase::hasBeenInvited($contactId, $dao->study_id)) {
          $studies[] = $dao->study_id;
        }
      }
    }
    return $studies;
  }

  /**
   * Method to find out if volunteer is available for blood studies
   *
   * @param $volunteerId
   * @return bool
   */
  public static function availableForBlood($volunteerId) {
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_no_blood_studies', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $excludeFromBlood = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if (!$excludeFromBlood) {
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
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_no_commercial_studies', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $excludeFromCommercial = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if (!$excludeFromCommercial) {
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
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_unable_to_travel', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $unable = CRM_Core_DAO::singleValueQuery($query, [1 => [$volunteerId, 'Integer']]);
    if (!$unable) {
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
      $contactPanelId = CRM_Core_DAO::singleValueQuery($query, [ 1 => [$contactId, "Integer"]]);
      if ($contactPanelId && $contactPanelId == $panelId) {
        return TRUE;
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
    $invited = [];
    $invitedStatuses = explode(",", Civi::settings()->get('nbr_invited_study_status'));
    foreach ($invitedStatuses as $invitedStatus) {
      $i++;
      $invited[] = "%" . $i;
      $queryParams[$i] = [$invitedStatus, 'String'];
    }
    if (!empty($invited)) {
      $query .= " AND cvnpd." . $statusColumn . " IN (" . implode(", ", $invited) . ")";
    }
    $count = (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Method to determine if this invitation will be the one that brings the volunteer to the max
   *
   * @param $contactId
   * @return bool
   * @throws
   */
  public static function isFinalInvitationInPeriod($contactId) {
    // get settings
    $maxInvitations = (int) Civi::settings()->get('nbr_max_invitations');
    // subtract 1 from max as it will not count the current invitation in the database yet
    $maxInvitations--;
    $checkDate = self::calculateCheckDateMaxInvitations();
    $studyCount = (int) self::countDistinctStudiesWithInvitations($contactId, $checkDate);
    if ($studyCount == $maxInvitations) {
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

}
