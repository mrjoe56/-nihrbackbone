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
    // then check if identifierType is valid
    try {
      $count = civicrm_api3('OptionValue', 'getcount', [
        'option_group_id' => "contact_id_history_type",
        'name' => $identifierType,
      ]);
      if ($count == 0) {
        Civi::log()->error(E::ts('Identity type ') . $identifierType . E::ts(' is not a valid contact identity type.'));
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Unexpected issue with API OptionValue getcount in ') . __METHOD__
        . E::ts(', error from API: ') . $ex->getMessage());
      return FALSE;
    }
    // if all is well, try to find contact
    try {
      $result = civicrm_api3('Contact', 'findbyidentity', [
        'identifier' => $identifier,
        'identifier_type' => $identifierType,
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
   * Method to calculate BMI
   *
   * @param $weight
   * @param $height
   * @return bool|float
   */
  public function calculateBmi($weight, $height) {
    if (empty($weight) || empty($height)) {
      return FALSE;
    }
    return $weight / ($height * $height);
  }

  /**
   * Method to check if the volunteer now has the max number of participations in the period
   *
   * @param $contactId
   * @return bool
   * @throws
   */
  public static function hasMaxParticipationsNow($contactId) {
    // get the settings for the max number, the max period and the case status to be counted
    $maxNumber = Civi::settings()->get('nbr_max_participations');
    $noMonths = Civi::settings()->get('nbr_no_months_participation');
    $countCaseStatuses = Civi::settings()->get('nbr_part_case_status');
    $checkDate = new DateTime();
    $modifier = '-' . $noMonths . ' months';
    $checkDate->modify($modifier);
    // retrieve the number of participation cases in the specified period with the status that
    // are to be counted
    try {
      $result = (int)civicrm_api3('Case', 'getcount', [
        'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(),
        'start_date' => ['>' => $checkDate->format('d-m-Y')],
        'status_id' => ['IN' => $countCaseStatuses],
        'contact_id' => $contactId,
        'is_deleted' => 0,
      ]);
      if ($result >= $maxNumber) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
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
      if ($contactGenderId && $contactGenderId == $genderId) {
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
      $birthDate = (string) civicrm_api3('Contact', 'getvalue',[
        'id' => $contactId,
        'return' => 'birth_date',
      ]);
      if ($birthDate) {
        $age = CRM_Utils_Date::calculateAge($birthDate);
        if ($age >= $fromAge && $age <= $toAge) {
          return TRUE;
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
   * @param $contactId
   * @param $fromBmi
   * @param $toBmi
   * @return bool
   */
  public static function inBmiRange($contactId, $fromBmi, $toBmi) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
    $columnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_bmi', 'column_name');
    $query = "SELECT " . $columnName . " FROM " . $tableName . " WHERE entity_id = %1";
    $contactBmi = CRM_Core_DAO::singleValueQuery($query, [ 1 => [$contactId, "Integer"]]);
    if ($contactBmi) {
      if ($contactBmi >= $fromBmi && $contactBmi <= $toBmi) {
        return TRUE;
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
  public static function hasEthnicity($contactId, $ethnicityIds) {
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


}
