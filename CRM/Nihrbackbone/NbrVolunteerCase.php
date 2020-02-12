<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class NbrVolunteerCase to deal with project volunteer links and data
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 7 Mar 2019
 * @license AGPL-3.0
 * @errorrange 3000-3499
 */
class CRM_Nihrbackbone_NbrVolunteerCase {

  private $_apiParams = [];

  /**
   * CRM_Nihrbackbone_NbrVolunteerCase constructor.
   *
   * @param $projectId (participation case only)
   * @throws API_Exception
   */
  public function __construct($apiParams) {
    if(!is_array($apiParams)) {
      $apiParams = [$apiParams];
    }
    $this->_apiParams = $apiParams;
  }

  /**
   * Method to get all volunteers selected for a project
   *
   * @return array
   */
  public function getVolunteers() {
    $volunteers = [];
    // return empty array if no projectId
    if (!isset($this->_apiParams['project_id']) || empty($this->_apiParams['project_id'])) {
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
      $volunteer['eligible'] = implode(', ', CRM_Nihrbackbone_NbrVolunteerCase::getEligibleDescriptions($volunteer['eligible_id']));
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
  public static function getEligibleDescriptions($eligibleId) {
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
    $ethnicityIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvgo_ethnicity_id', 'column_name');
    $generalTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('table_name');
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
        LEFT JOIN ". $generalTable . " AS d ON b.contact_id = d.entity_id
        LEFT JOIN civicrm_option_value AS e ON c.gender_id = e.value AND e.option_group_id = %1
        LEFT JOIN civicrm_address AS f ON b.contact_id = f.contact_id AND f.is_primary = %2
        LEFT JOIN civicrm_option_value AS g ON a." . $pvStatusIdColumn . " = g.value AND g.option_group_id = %3
        LEFT JOIN civicrm_option_value AS h ON d." . $ethnicityIdColumn . " = h.value AND h.option_group_id = %4
        WHERE a." . $projectIdColumn . " = %5 AND j.is_deleted = %6",
      'query_params' => [
        1 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getGenderOptionGroupId(), 'Integer'],
        2 => [1, 'Integer'],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectParticipationStatusOptionGroupId(), 'Integer'],
        4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getEthnicityOptionGroupId(), 'Integer'],
        5 => [$this->_apiParams['project_id'], 'Integer'],
        6 => [0, 'Integer'],
      ]];
    return $queryArray;
  }

  /**
   * @param $contactId
   * @throws API_Exception
   */
  public function createVolunteerCase($contactId) {
    // todo use switch
    if ($this->_apiParams['case_type'] == 'recruitment') {
      $this->createRecruitmentVolunteerCase($contactId);
  }
    elseif ($this->_apiParams['case_type'] == 'participation') {
      $this->createParticipationVolunteerCase($contactId);
    }
  }


  /**
   * Method to link a volunteer to a project (create case of type Participation)
   *
   * @param $contactId
   * @throws API_Exception
   * @return array
   */
  public function createParticipationVolunteerCase($contactId) {
    if (empty($this->_apiParams['project_id'])) {
      throw new API_Exception(E::ts('Trying to create a NIHR project volunteer with an empty projectId in ') . __METHOD__, 3002);
    }
    if (empty($contactId)) {
      throw new API_Exception(E::ts('Trying to create a NIHR project volunteer with an empty contactId in ') . __METHOD__, 3003);
    }
    $nihrProject = new CRM_Nihrbackbone_NihrProject();
    if ($nihrProject->projectExists($this->_apiParams['project_id'])) {
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
   * Method to create case of type Recruitment
   *
   * @param $contactId
   * @throws API_Exception
   * @return array
   */
  public function createRecruitmentVolunteerCase($contactId) {
    if (empty($contactId)) {
      throw new API_Exception(E::ts('Trying to create a NIHR project volunteer with an empty contactId in ') . __METHOD__, 3003);
    }
      // check if contact exists and has contact sub type Volunteer and does not have a case for this project yet
      $nihrVolunteer = new CRM_Nihrbackbone_NihrVolunteer();

        try {
          $case = civicrm_api3('Case', 'create', $this->setRecruitmentCaseCreateData($contactId));
          return ['case_id' => $case['id']];
        }
        catch (CiviCRM_API3_Exception $ex) {
          throw new API_Exception('Could not create a Participation case for contact ID ' . $contactId
            . ' in ' . __METHOD__ . ', error code from API Case create:'  . $ex->getMessage(), 3004);
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
    $pvStatusCustomFieldId = 'custom_'. CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_participation_status', 'id');
    $recallGroupCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'id');
    $projectName = CRM_Nihrbackbone_NbrStudy::getStudyNameWithId($this->_apiParams['project_id']);
    if ($projectName) {
      $subject = E::ts("Selected for project ") . $projectName;
    }
    else {
      $subject = E::ts("Selected for project ") . $this->_apiParams['project_id'];
    }
    $caseCreateData =  [
      'contact_id' => $contactId,
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(),
      'subject' => $subject,
      'status_id' => "Open",
      $projectIdCustomFieldId => $this->_apiParams['project_id'],
      $pvStatusCustomFieldId => 'project_participation_status_selected',
      ];
    if ($this->_apiParams['recall_group']) {
      $caseCreateData[$recallGroupCustomFieldId] = $this->_apiParams['recall_group'];
    }
    return $caseCreateData;
  }

  /**
   * @param $contactId
   * @return array
   */
  private function setRecruitmentCaseCreateData($contactId) {
    $caseCreateData =  [
      'contact_id' => $contactId,
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitmentCaseTypeId(),
      'subject' => "Recruited",
      'status_id' => "Open",
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
      2 => [$this->_apiParams['project_id'], 'Integer'],
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
          if (isset($result[$eligibleCustomField])) {
            foreach ($result[$eligibleCustomField] as $currentStatus) {
              if (!in_array($currentStatus, $newStatus)) {
                $newStatus[] = $currentStatus;
              }
            }
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
      self::removeEligibilityStatus((int) $current->entity_id, $current->$eligibleColumnName, $maxReachedStatusId);
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
        $currentValue = (string) $currentValue;
        if ($currentValue == $removeStatusId || empty($currentValue)) {
          unset($newValues[$currentValueId]);
        }
      }
      if (!empty($newValues)) {
        $query = "UPDATE " . $tableName . " SET " . $eligibleColumnName . " = %1 WHERE entity_id = %2";
        $queryParams = [
          1 => [CRM_Core_DAO::VALUE_SEPARATOR . implode(CRM_Core_DAO::VALUE_SEPARATOR, $newValues) . CRM_Core_DAO::VALUE_SEPARATOR, 'String'],
          2 => [$caseId, 'Integer'],
        ];
      }
      else {
        $query = "UPDATE " . $tableName . " SET " . $eligibleColumnName . " = NULL WHERE entity_id = %1";
        $queryParams = [
          1 => [$caseId, 'Integer'],
        ];
      }
      CRM_Core_DAO::executeQuery($query, $queryParams);
    }
  }


  /**
   * Method to get all participation case ids for volunteer
   *
   * @param $volunteerId
   * @return array
   */
  public static function getVolunteerParticipations($volunteerId) {
    $result = [];
    if (!empty($volunteerId)) {
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      // get all active participations for contact
      $query = "SELECT a.case_id, c.* FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        lEFT JOIN " . $tableName . " AS c ON a.case_id = c.entity_id
        WHERE contact_id = %1 AND b.is_deleted = %2 AND b.case_type_id = %3 AND b.status_id != %4";
      $queryParams = [
        1 => [$volunteerId, "Integer"],
        2 => [0, "Integer"],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), "Integer"],
      ];
      $projects = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($projects->fetch()) {
        $result[] = CRM_Nihrbackbone_Utils::moveDaoToArray($projects);
      }
    }
    return $result;
  }

  /**
   * Method to get all active participations on a project
   *
   * @param $projectId
   * @return array
   */
  public static function getProjectParticipations($projectId) {
    $result = [];
    if (!empty($projectId)) {
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $projectColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
      // get all active participations for project
      $query = "SELECT a.case_id, a.contact_id, c.* FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        lEFT JOIN " . $tableName . " AS c ON a.case_id = c.entity_id
        WHERE c." . $projectColumn . " = %1 AND b.is_deleted = %2 AND b.case_type_id = %3 AND b.status_id != %4";
      $queryParams = [
        1 => [$projectId, "Integer"],
        2 => [0, "Integer"],
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), "Integer"],
      ];
      $projects = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($projects->fetch()) {
        $result[] = CRM_Nihrbackbone_Utils::moveDaoToArray($projects);
      }
    }
    return $result;
  }

  /**
   * Method to add the invite activity to the participation case
   *
   * @param $caseId
   * @param $contactId
   * @param $projectId
   * @return bool
   */
  public static function addInviteActivity($caseId, $contactId, $projectId) {
    if (empty($caseId) || empty($contactId)) {
      Civi::log()->warning(E::ts('Trying to add an invite activity with empty case id or contact id in ') . __METHOD__);
      return FALSE;
    }
    try {
      $projectTitle = (string) civicrm_api3('Campaign', 'getvalue', [
        'return' => 'title',
        'id' => $projectId,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      $projectTitle = $projectId;
    }
    $activityTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getInviteProjectActivityTypeId();
    $activityParams = [
      'source_contact_id' => 'user_contact_id',
      'target_id' => $contactId,
      'case_id' => $caseId,
      'activity_type_id' => $activityTypeId,
      'subject' => E::ts("Invited to project ") . $projectTitle,
      'status_id' => 'Completed',
    ];
    try {
      civicrm_api3('Activity', 'create', $activityParams);
      return TRUE;
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::Ts('Could not create invite activity for case ID ') . $caseId . E::ts(' and contact ID ')
        . $contactId . E::ts(', error from API Activity create: ') . $ex->getMessage());
      return FALSE;
    }
  }

  /**
   * Method to check if the volunteer has been invited in the relevant study
   *
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function hasBeenInvited($contactId, $studyId) {
    if (empty($contactId) || empty($studyId)) {
      return FALSE;
    }
    $query = "SELECT COUNT(*)
      FROM civicrm_value_nbr_study_data AS a
      LEFT JOIN civicrm_value_nbr_participation_data AS b ON a.entity_id = b.nvpd_study_id
      LEFT JOIN civicrm_case_activity AS c ON b.entity_id = c.case_id
      LEFT JOIN civicrm_case_contact AS d ON c.case_id = d.case_id
      LEFT JOIN civicrm_case AS e ON c.case_id = e.id
      LEFT JOIN civicrm_activity AS f ON c.activity_id = f.id
      WHERE a.nsd_study_number = %1 AND d.contact_id = %2 AND e.is_deleted = %3
        AND f.is_current_revision = %4 AND f.is_deleted = %3 AND f.activity_type_id = %5";
    $count = (int) CRM_Core_DAO::singleValueQuery($query, [
      1 => [(int) $studyId, "Integer"],
      2 => [(int) $contactId, "Integer"],
      3 => [0, "Integer"],
      4 => [1, "Integer"],
      5 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getInviteProjectActivityTypeId(), "Integer"],
    ]);
    if ($count > 0) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get all active participation cases that have a certain eligibility status
   *
   * @return array
   */
  public static function getEligibilityCases($statusId) {
    $result = [];
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $query = "SELECT a.entity_id AS case_id, c.contact_id, a." . $eligibleColumn . " AS eligible, a." . $projectIdColumn . " AS project_id
        FROM " . $tableName . " AS a
        JOIN civicrm_case AS b ON a.entity_id = b.id
        JOIN civicrm_case_contact AS c ON b.id = c.case_id
        WHERE b.is_deleted = %1 AND b.case_type_id = %2 AND a." . $eligibleColumn . " LIKE %3";
    $statusId = CRM_Core_DAO::VALUE_SEPARATOR . $statusId . CRM_Core_DAO::VALUE_SEPARATOR;
    $queryParams = [
      1 => [0, "Integer"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      3 => ["%" . $statusId . "%", "String"],
    ];
    $index = 3;
    $clauses = [];
    $valids = Civi::settings()->get('nbr_inv_case_status');
    if (!empty($valids)) {
      $statuses = explode(",", $valids);
      foreach ($statuses as $status) {
        $index++;
        $clauses[] = "%" . $index;
        $queryParams[$index] = [$status, "String"];
      }
      $query .= " AND b.status_id IN (" . implode(",", $clauses) . ")";
    }
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $case = [
        'case_id' => $dao->case_id,
        'contact_id' => $dao->contact_id,
      ];
      $case[$eligibleColumn] = $dao->eligible;
      $case[$projectIdColumn] = $dao->project_id;
      $result[] = $case;
    }
    return $result;
  }

  /**
   * Method to get all active participation cases
   */
  public static function getAllActiveParticipations() {
    $result = [];
    $eligibleColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $projectIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_project_id', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $valids = Civi::settings()->get('nbr_inv_case_status');
    // first step: retrieve all active participation cases
    $query = "SELECT a.id AS case_id, c.contact_id, b." . $projectIdColumn . ", b."
      . $eligibleColumn . " FROM civicrm_case AS a
      LEFT JOIN " . $tableName . " AS b ON a.id = b.entity_id
      LEFT JOIN civicrm_case_contact AS c ON a.id = c.case_id
      WHERE a.is_deleted = %1 AND a.case_type_id = %2";
    $queryParams = [
      1 => [0, "Integer"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
    ];
    $index = 2;
    if (!empty($valids)) {
      $statuses = explode(",", $valids);
      foreach ($statuses as $status) {
        $index++;
        $clauses[] = "%" . $index;
        $queryParams[$index] = [$status, "String"];
      }
      $query .= " AND a.status_id IN (" . implode(",", $clauses) . ")";
    }
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $result[] = CRM_Nihrbackbone_Utils::moveDaoToArray($dao);
    }
    return $result;
  }

}
