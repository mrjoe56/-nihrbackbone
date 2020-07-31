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
      if (!empty($contactSubTypes)) {
        foreach ($contactSubTypes as $contactSubTypeId => $contactSubTypeName) {
          if ($contactSubTypeName == $this->_volunteerContactSubType['name']) {
            return TRUE;
          }
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
    if ($count == 0) {
      $id = '';
    }
    elseif ($count > 1) {
      // &&& $this->_logger->logMessage('Multiple records linked to identifier '.$identifier);
      Civi::log()->warning(E::ts('Multiple records linked to identifier '.$identifier));
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
    $partStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField("nvpd_study_participation_status", "column_name");
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField("nvpd_study_id", "column_name");
    $checkDate = self::calculateCheckDateMaxInvitations();
    $query = "SELECT COUNT(DISTINCT(c.id)) AS study_count
        FROM " . $participationTable . " AS a
            JOIN civicrm_campaign AS b ON a." . $studyIdColumn . " = b.id
            JOIN civicrm_case AS c ON a.entity_id = c.id
            JOIN civicrm_case_contact AS d ON c.id = d.case_id
            LEFT JOIN " . $studyTable . " AS e ON b.id = e.entity_id
        WHERE a." . $inviteColumn . " >= %1 AND a." . $inviteColumn . " IS NOT NULL
          AND b.status_id IN (%2, %3, %4) AND c.is_deleted = %5 AND c.case_type_id = %6 AND d.contact_id = %7
          AND a." . $partStatusColumn. " IN(%8, %9, %10, %11, %12, %13, %14, %15, %16)  AND ";
    $queryParams = [
      1 => [$checkDate->format("Y-m-d"), "String"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), "Integer"],
      3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getCompletedStudyStatus(), "Integer"],
      4 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedStudyStatus(), "Integer"],
      5 => [0, "Integer"],
      6 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      7 => [(int) $contactId, "Integer"],
      8 => [Civi::service('nbrBackbone')->getAcceptedParticipationStatusValue(), "String"],
      9 => [Civi::service('nbrBackbone')->getExcludedParticipationStatusValue(), "String"],
      10 => [Civi::service('nbrBackbone')->getInvitationPendingParticipationStatusValue(), "String"],
      11 => [Civi::service('nbrBackbone')->getInvitedParticipationStatusValue(), "String"],
      12 => [Civi::service('nbrBackbone')->getParticipatedParticipationStatusValue(), "String"],
      13 => [Civi::service('nbrBackbone')->getRenegedParticipationStatusValue(), "String"],
      14 => [Civi::service('nbrBackbone')->getWithdrawnParticipationStatusValue(), "String"],
      15 => [Civi::service('nbrBackbone')->getNoResponseParticipationStatusValue(), "String"],
      16 => [Civi::service('nbrBackbone')->getNotParticipatedParticipationStatusValue(), "String"],
      17 => [1, "Integer"],
    ];
    switch ($type) {
      case "facetoface":
        $query .= $faceToFaceColumn . " = %17";
        break;
      case "online":
        $query .= $onLineColumn . " = %13";
        break;
      case "total":
        $query .= "(" . $faceToFaceColumn . " = %13 OR " . $onLineColumn . " = %13)";
        break;
      default:
        Civi::log()->warning(E::ts("Invalid type " . $type . " in ") . __METHOD__);
        return FALSE;
        break;
    }
    return (int) CRM_Core_DAO::singleValueQuery($query, $queryParams);
  }

}
