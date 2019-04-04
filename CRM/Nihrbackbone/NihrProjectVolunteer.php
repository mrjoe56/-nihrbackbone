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
    $projectCodeColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_code', 'column_name');
    $projectAnonIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_anon_project_id', 'column_name');
    $pvStatusIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_volunteer_project_status_id', 'column_name');
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectConsentColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_consent_status_id', 'column_name');
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $ethnicityIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerCustomField('nvd_ethnicity_id', 'column_name');
    $volunteerTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerDataCustomGroup('table_name');
    $queryArray = [
      'query' => "SELECT a.entity_id AS case_id, a." . $projectIdColumn. " AS project_id, a." . $projectCodeColumn
        . " AS project_code, a." . $projectAnonIdColumn . " AS anon_project_id, a." . $pvStatusIdColumn
        . " AS project_status_id, g.label AS volunteer_project_status, a." . $eligibleColumn . " AS eligible_id, 
        h.label AS ethnicity, i.label AS project_consent_status, b.contact_id AS bioresource_id, c.display_name AS volunteer_name, 
        c.birth_date, e.label AS sex, f.city AS location, d." . $ethnicityIdColumn . " AS ethnicity_id, 
        a." . $projectConsentColumn . " AS project_consent_status_id 
        FROM " .  $participationTable . " AS a
        JOIN civicrm_case_contact AS b ON a.entity_id = b.case_id
        JOIN civicrm_contact AS c ON b.contact_id = c.id
        JOIN civicrm_case AS j ON b.case_id = j.id
        LEFT JOIN ". $volunteerTable . " AS d ON b.contact_id = d.entity_id
        LEFT JOIN civicrm_option_value AS e ON c.gender_id = e.value AND e.option_group_id = %1
        LEFT JOIN civicrm_address AS f ON b.contact_id = f.contact_id AND f.is_primary = %2
        LEFT JOIN civicrm_option_value AS g ON a." . $pvStatusIdColumn . " = g.value AND g.option_group_id = %3
        LEFT JOIN civicrm_option_value AS h ON d." . $ethnicityIdColumn . " = h.value AND h.option_group_id = %4
        LEFT JOIN civicrm_option_value AS i ON a." . $projectConsentColumn . " = i.value AND i.option_group_id = %5
        WHERE a." . $projectIdColumn . " = %6 AND j.is_deleted = %7",
      'query_params' => [
        1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId(), 'Integer'],
        2 => [1, 'Integer'],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerProjectStatusOptionGroupId(), 'Integer'],
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
    // check if project exists and retrieve project code
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
    $nihrProject = new CRM_Nihrbackbone_NihrProject();
    $projectIdCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'id');
    $projectCodeCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_code', 'id');
    $projectCode = $nihrProject->getProjectAttributeWithId($this->_projectId, 'npd_project_code');
    $anonCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_anon_project_id', 'id');
    $pvStatusCustomFieldId = 'custom_'. CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_volunteer_project_status_id', 'id');
    $consentCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_consent_status_id', 'id');
    $caseCreateData =  [
      'contact_id' => $contactId,
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(),
      'subject' => "Selected for project " . $projectCode,
      'status_id' => "Open",
      $projectIdCustomFieldId => $this->_projectId,
      $projectCodeCustomFieldId => $projectCode,
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

}
