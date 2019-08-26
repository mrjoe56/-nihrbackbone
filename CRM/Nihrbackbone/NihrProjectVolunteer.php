<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class NihrProjectVolunteer to deal with project volunteer links and data
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 7 Mar 2019
 * @license AGPL-3.0
 * @errorrange 3000-3499
 */
class CRM_Nihrbackbone_NihrProjectVolunteer {

  private $_projectId = NULL;

  /**
   * CRM_Nihrbackbone_NihrProjectVolunteer constructor.
   *
   * @param $projectId
   * @throws API_Exception
   */
  public function __construct($projectId) {
    if (empty($projectId)) {
      throw new API_Exception(E::ts('Can not initiate NihrProjectVolunteer class with empty projectId'), 3000);
    }
    if (!is_numeric($projectId)) {
      throw new API_Exception(E::ts('Can not initiate NihrProjectVolunteer class with a non-numeric projectId'), 3001);
    }
    $this->_projectId = trim(stripslashes($projectId));
  }

  /**
   * Method to get all volunteers selected for a project
   *
   * @return array
   */
  public function getVolunteers() {
    $volunteers = [];
    // return empty array if no projectId
    if (!$this->_projectId) {
      return $volunteers;
    }
    // find all cases that belong to the project, each case client is a volunteer
    $queryArray = $this->getVolunteerSelectQuery();
    $dao = CRM_Core_DAO::executeQuery($queryArray['query'], $queryArray['query_params']);
    while ($dao->fetch()) {
      $volunteer = CRM_Nihrbackbone_Utils::moveDaoToArray($dao);
      // set age
      if (isset($volunteer['birth_date']) && !empty($volunteer['birth_date'])) {
        $volunteer['age'] = CRM_Utils_Date::calculateAge($volunteer['birth_date'])['years'];
      }
      // get eligible status (multi value in dao with value separators)
      $volunteer['eligible'] = implode(', ', $this->getEligibleDescriptions($volunteer['eligible_id']));
      $volunteers[] = $volunteer;
    }
    return $volunteers;
  }

  /**
   * Method to get the eligible labels (value separated in custom group)
   *
   * @param $eligibleId
   * @return array
   */
  private function getEligibleDescriptions($eligibleId) {
    $descriptions = [];
    $parts = explode(CRM_Core_DAO::VALUE_SEPARATOR, $eligibleId);
    foreach ($parts as $part) {
      if (!empty($part)) {
        try {
          $descriptions[] = civicrm_api3('OptionValue', 'getvalue', [
            'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getEligibleStatusOptionGroupId(),
            'value' => $part,
            'return' => 'label',
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
        }
      }
    }
    return $descriptions;
  }

  /**
   * Method to set the query and the query parameters for initial volunteer selection
   * @return array
   */
  private function getVolunteerSelectQuery() {
    $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $projectAnonIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $pvStatusIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_participation_status', 'column_name');
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $ethnicityIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_ethnicity_id', 'column_name');
    $volunteerTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerDataCustomGroup('table_name');
    $queryArray = [
      'query' => "SELECT a.entity_id AS case_id, a." . $projectIdColumn. " AS project_id, a." . $projectAnonIdColumn
        . " AS anon_project_id, a." . $pvStatusIdColumn
        . " AS project_status_id, g.label AS volunteer_project_status, a." . $eligibleColumn . " AS eligible_id, 
        h.label AS ethnicity, b.contact_id AS bioresource_id, c.display_name AS volunteer_name, 
        c.birth_date, e.label AS sex, f.city AS location, d." . $ethnicityIdColumn . " AS ethnicity_id
        FROM " .  $participationTable . " AS a
        JOIN civicrm_case_contact AS b ON a.entity_id = b.case_id
        JOIN civicrm_contact AS c ON b.contact_id = c.id
        JOIN civicrm_case AS j ON b.case_id = j.id
        LEFT JOIN ". $volunteerTable . " AS d ON b.contact_id = d.entity_id
        LEFT JOIN civicrm_option_value AS e ON c.gender_id = e.value AND e.option_group_id = %1
        LEFT JOIN civicrm_address AS f ON b.contact_id = f.contact_id AND f.is_primary = %2
        LEFT JOIN civicrm_option_value AS g ON a." . $pvStatusIdColumn . " = g.value AND g.option_group_id = %3
        LEFT JOIN civicrm_option_value AS h ON d." . $ethnicityIdColumn . " = h.value AND h.option_group_id = %4
        WHERE a." . $projectIdColumn . " = %6 AND j.is_deleted = %7",
      'query_params' => [
        1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId(), 'Integer'],
        2 => [1, 'Integer'],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId(), 'Integer'],
        4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId(), 'Integer'],
        5 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getConsentStatusOptionGroupId(), 'Integer'],
        6 => [$this->_projectId, 'Integer'],
        7 => [0, 'Integer'],
      ]];
    return $queryArray;
  }

  /**
   * Method to link a volunteer to a project (create case of type Participation)
   *
   * @param $contactId
   * @throws API_Exception
   * @return array
   */
  public function createProjectVolunteer($contactId) {
    if (empty($this->_projectId)) {
      throw new API_Exception(E::ts('Trying to create a NIHR project volunteer with an empty projectId in ') . __METHOD__, 3002);
    }
    if (empty($contactId)) {
      throw new API_Exception(E::ts('Trying to create a NIHR project volunteer with an empty contactId in ') . __METHOD__, 3003);
    }
    // check if project exists
    $nihrProject = new CRM_Nihrbackbone_NihrProject();
    if ($nihrProject->projectExists($this->_projectId)) {
      // check if contact exists and has contact sub type Volunteer and does not have a case for this project yet
      $nihrVolunteer = new CRM_Nihrbackbone_NihrVolunteer();
      if ($nihrVolunteer->isValidVolunteer($contactId) && !$this->isAlreadyOnProject($contactId)) {
        // create new case linked to project
        try {
          $case = civicrm_api3('Case', 'create', $this->setCaseCreateData($contactId));
          return ['case_id' => $case['id']];
        }
        catch (CiviCRM_API3_Exception $ex) {
          throw new API_Exception(E::ts('Could not create a Participation case for contact ID ') . $contactId
            . E::ts(' and project ID ') . $this->_projectId . E::ts(' in ') . __METHOD__ . E::ts(', error code from API Case create:' ) . $ex->getMessage(), 3004);
        }
      }
    }
  }

  /**
   * Prepare data for case create
   *
   * @param $contactId
   * @return array
   */
  private function setCaseCreateData($contactId) {
    $projectIdCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'id');
    $anonCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_anon_project_id', 'id');
    $pvStatusCustomFieldId = 'custom_'. CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_volunteer_project_status_id', 'id');
    $consentCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_consent_status_id', 'id');
    $caseCreateData =  [
      'contact_id' => $contactId,
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(),
      'subject' => "Selected for project " . $this->_projectId,
      'status_id' => "Open",
      $projectIdCustomFieldId => $this->_projectId,
      $anonCustomFieldId => "3294yt71L",
      $pvStatusCustomFieldId => 1,
      $consentCustomFieldId => 7,
      ];
    return $caseCreateData;
  }

  /**
   * Method to find out if a contact is already on a project
   *
   * @param $contactId
   * @return bool
   */
  public function isAlreadyOnProject($contactId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $query = "SELECT COUNT(*)
      FROM civicrm_case_contact AS a JOIN civicrm_case AS b ON a.case_id = b.id
      LEFT JOIN " . $tableName ." AS c ON a.case_id = c.entity_id
      WHERE a.contact_id = %1 AND " . $projectIdColumn . " = %2 AND b.is_deleted = %3";
    $count = CRM_Core_DAO::singleValueQuery($query, [
      1 => [$contactId, 'Integer'],
      2 => [$this->_projectId, 'Integer'],
      3 => [0, 'Integer'],
    ]);
    if ($count == 0) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Method to add 1 or more statuses to the eligibility of the volunteer on the project (custom field on case)
   *
   * @param array $newStatus
   * @param int $caseId
   * @param bool $replace (if TRUE current statuses will be wiped out and replace with new ones ELSE new ones will be added)
   */
  public static function setEligibilityStatus($newStatus, $caseId, $replace = FALSE) {
    // if new status is not an array, make it one!
    if (!is_array($newStatus)) {
      $newStatus = [$newStatus];
    }
    $eligibleCustomField = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'id');
    if ($eligibleCustomField) {
      try {
        $result = civicrm_api3('Case', 'getsingle', [
          'return' => [$eligibleCustomField, 'contact_id'],
          'id' => $caseId,
        ]);
        // if replace is false, make sure the current status are also saved
        // apart from when it is eligible because that can not be in combination with others
        if (!$replace) {
          foreach ($result[$eligibleCustomField] as $currentStatus) {
            $newStatus[] = $currentStatus;
          }
        }
        // now update the eligibility
        try {
          civicrm_api3('Case', 'create', [
            'id' => $caseId,
            'contact_id' => $result['contact_id'][1],
            $eligibleCustomField => $newStatus,
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts('Not able to update the eligibility for case with ID ') . $caseId . E::ts(' in ')
            . __METHOD__ . E::ts(', error from API Case create: ') . $ex->getMessage());
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->error(E::ts('Could not find case data for case ID ') . $caseId . E::ts(' in ')
          . __METHOD__ . E::ts(', error message from API Case getsingle :') . $ex->getMessage());
      }
    }
    else {
      Civi::log()->error(E::ts('Could not find a custom field for eligible status in ') . __METHOD__);
    }
  }

  /**
   * Method to remove the max reached eligible status for the volunteer
   *
   * @param $volunteerId
   */
  public static function unsetMaxStatus($volunteerId) {
    $maxReachedStatusId = CRM_Nihrbackbone_BackboneConfig::singleton()->getMaxReachedEligibleStatusId();
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $eligibleColumnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    // retrieve current eligible status from each volunteer participation cases
    $query = "SELECT a." . $eligibleColumnName . ", a.entity_id
      FROM " . $tableName . " AS a
      JOIN civicrm_case AS b ON a.entity_id = b.id
      JOIN civicrm_case_contact AS c ON a.entity_id = c.case_id
      WHERE b.is_deleted = %1 AND b.case_type_id = %2 AND c.contact_id = %3 AND b.status_id != %4";
    $queryParams = [
      1 => [0, 'Integer'],
      2 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
      3 => [$volunteerId, 'Integer'],
      4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), 'Integer'],
    ];
    $current = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($current->fetch()) {
      self::removeEligibilityStatus($current->entity_id, $current->$eligibleColumnName, $maxReachedStatusId);
    }
  }

  /**
   * Method to remove an eligible status from the list
   *
   * @param int $caseId
   * @param string $currentEligibleStatus
   * @param string $removeStatusId
   */
  public static function removeEligibilityStatus($caseId, $currentEligibleStatus, $removeStatusId) {
    if (!empty($caseId) && !empty($removeStatusId) && !empty($currentEligibleStatus)) {
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $eligibleColumnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
      $newValues = explode(CRM_Core_DAO::VALUE_SEPARATOR, $currentEligibleStatus);
      foreach ($newValues as $currentValueId => $currentValue) {
        if ($currentValue == $removeStatusId || empty($currentValue)) {
          unset($newValues[$currentValueId]);
        }
      }
      $query = "UPDATE " . $tableName . " SET " . $eligibleColumnName . " = %1 WHERE entity_id = %2";
      $queryParams = [
        1 => [implode(CRM_Core_DAO::VALUE_SEPARATOR, $newValues), 'String'],
        2 => [$caseId, 'Integer'],
      ];
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }
  }

  /**
   * Method to count the number of participation cases for a volunteer in the last xxx months
   *
   * @param $volunteerdId
   * @param $numberOfMonths
   * @return int|string|null
   */
  public static function countVolunteerParticipations($volunteerdId, $numberOfMonths) {
    // start date should be after or on the date xxx months ago
    try {
      $startDate = new DateTime();
      $modifier = '-' . $numberOfMonths . ' months';
      $startDate->modify($modifier);
      $query = "SELECT COUNT(*) FROM civicrm_case_contact AS a
      JOIN civicrm_case AS b ON a.case_id = b.id 
      WHERE b.case_type_id = %1 AND b.is_deleted = %2 AND b.status_id != %3  AND a.contact_id = %4 
      AND b.start_date >= %5";
      $queryParams = [
        1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
        2 => [0, 'Integer'],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), 'Integer'],
        4 => [$volunteerdId, 'Integer'],
        5 => [$startDate->format('Y-m-d'), 'String'],
      ];
      $count = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($count) {
        return $count;
      }
    }
    catch (Exception $ex) {
    }
    return 0;
  }

}
