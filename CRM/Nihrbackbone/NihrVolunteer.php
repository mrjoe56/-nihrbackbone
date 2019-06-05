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

}
