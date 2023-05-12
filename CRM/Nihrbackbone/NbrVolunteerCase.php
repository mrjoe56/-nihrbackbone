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
    $pvStatusIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
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
        3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyParticipationStatusOptionGroupId(), 'Integer'],
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
      $result = CRM_Nihrbackbone_NbrRecruitmentCase::createRecruitmentVolunteerCase($contactId);
      return $result;
  }
    elseif ($this->_apiParams['case_type'] == 'participation') {
      return $this->createParticipationVolunteerCase($contactId);
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
    if (empty($this->_apiParams['study_id'])) {
      throw new API_Exception(E::ts('Trying to add a volunteer to a study with an empty studyId in ') . __METHOD__, 3002);
    }
    if (empty($contactId)) {
      throw new API_Exception(E::ts('Trying to add a volunteer to a study with an empty contactId in ') . __METHOD__, 3003);
    }
    $nbrStudy = new CRM_Nihrbackbone_NbrStudy();
    if ($nbrStudy->studyExists($this->_apiParams['study_id'])) {
      // check if contact exists and has contact sub type Volunteer and does not have a case for this study yet
      $nihrVolunteer = new CRM_Nihrbackbone_NihrVolunteer();
      if ($nihrVolunteer->isValidVolunteer($contactId, 'participant') && !CRM_Nihrbackbone_NbrVolunteerCase::isAlreadyOnStudy($contactId, $this->_apiParams['study_id'])) {
        // create new case linked to study
        try {
          $case = civicrm_api3('Case', 'create', $this->setCaseCreateData($contactId));
          return ['case_id' => $case['id']];
        }
        catch (CiviCRM_API3_Exception $ex) {
          throw new API_Exception(E::ts('Could not create a Participation case for contact ID ') . $contactId
            . E::ts(' and study ') . CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_apiParams['study_id']) . E::ts(' in ') . __METHOD__ . E::ts(', error code from API Case create:' ) . $ex->getMessage(), 3004);
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
    $studyIdCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'id');
    $pvStatusCustomFieldId = 'custom_'. CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id');
    $recallGroupCustomFieldId = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'id');
    $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_apiParams['study_id']);
    if ($studyNumber) {
      $subject = E::ts("Study ") . $studyNumber;
    }
    else {
      $subject = E::ts("Study ") . $this->_apiParams['study_id'];
    }
    $caseCreateData =  [
      'contact_id' => $contactId,
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(),
      'subject' => $subject,
      'status_id' => "Open",
      $studyIdCustomFieldId => $this->_apiParams['study_id'],
      $pvStatusCustomFieldId => 'study_participation_status_selected',
      ];
    if (isset($this->_apiParams['recall_group'])) {
      $caseCreateData[$recallGroupCustomFieldId] = $this->_apiParams['recall_group'];
    }
    if (isset($this->_apiParams['start_date'])) {
      $caseCreateData['start_date'] = $this->_apiParams['start_date'];
    }
    return $caseCreateData;
  }

  /**
   * Method to find out if a contact is already on a study
   *
   * @param $contactId
   * @return bool
   */
  public static function isAlreadyOnStudy($contactId, $studyId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $query = "SELECT COUNT(*)
      FROM civicrm_case_contact AS a JOIN civicrm_case AS b ON a.case_id = b.id
      LEFT JOIN " . $tableName ." AS c ON a.case_id = c.entity_id
      WHERE a.contact_id = %1 AND " . $studyIdColumn . " = %2 AND b.is_deleted = %3";
    $count = CRM_Core_DAO::singleValueQuery($query, [
      1 => [$contactId, 'Integer'],
      2 => [$studyId, 'Integer'],
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
        // note: if adding a new status, eligible can always be removed
        if (!$replace) {
          if (isset($result[$eligibleCustomField])) {
            foreach ($result[$eligibleCustomField] as $currentStatus) {
              if (!in_array($currentStatus, $newStatus) && $currentStatus != Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue()) {
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
   * Method to remove an eligible status from the list
   *
   * @param int $caseId
   * @param string $removeStatusId
   */
  public static function removeEligibilityStatus($caseId, $removeStatusId) {
    if (!empty($caseId) && !empty($removeStatusId)) {
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $newValues = CRM_Nihrbackbone_NbrVolunteerCase::getCurrentEligibleStatus($caseId);
      $eligibleColumnName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
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
   * Method to get the current eligibility for a case
   *
   * @param $caseId
   * @return array
   */
  public static function getCurrentEligibleStatus($caseId) {
    $result = [];
    $eligibleCustomFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'id');
    try {
      $result = civicrm_api3('Case', 'getvalue', [
        'return' => $eligibleCustomFieldId,
        'id' => $caseId,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to get the current study status for a case
   *
   * @param $caseId
   * @return array
   */
  public static function getCurrentStudyStatus($caseId) {
    $result = [];
    $statusCustomFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id');
    try {
      $result = civicrm_api3('Case', 'getvalue', [
        'return' => $statusCustomFieldId,
        'id' => $caseId,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }


  /**
   * Method to get all participation case ids where volunteer = selected
   *
   * @param $volunteerId
   * @return array
   */
  public static function getVolunteerSelections($volunteerId) {
    $result = [];
    if (!empty($volunteerId)) {
      $calculateStatuses = Civi::settings()->get('nbr_eligible_calc_study_status');
      if (!empty($calculateStatuses)) {
        $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
        // get all active participations for contact where he/she is selected
        $query = "SELECT a.case_id, c.* FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        LEFT JOIN " . $tableName . " AS c ON a.case_id = c.entity_id
        WHERE contact_id = %1 AND b.is_deleted = %2 AND b.case_type_id = %3 AND b.status_id != %4";
        $queryParams = [
          1 => [$volunteerId, "Integer"],
          2 => [0, "Integer"],
          3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
          4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), "Integer"],
          5 => [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"],
        ];
        $index = 4;
        CRM_Nihrbackbone_Utils::addParticipantStudyStatusClauses($calculateStatuses, $index, $query, $queryParams);
        $case = CRM_Core_DAO::executeQuery($query, $queryParams);
        while ($case->fetch()) {
          $result[] = CRM_Nihrbackbone_Utils::moveDaoToArray($case);
        }
      }
    }
    return $result;
  }

  /**
   * Method to get all active participations on a project
   *
   * @param $studyId
   * @return array
   */
  public static function getStudySelections($studyId) {
    $result = [];
    if (!empty($studyId)) {
      // get participation statuses for eligibility calculation from settings
      $calculateStatuses = Civi::settings()->get('nbr_eligible_calc_study_status');
      if (!empty($calculateStatuses)) {
        $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
        $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
        // get all active participations for project where participation status is selected
        $query = "SELECT a.case_id, a.contact_id, c.* FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        LEFT JOIN " . $tableName . " AS c ON a.case_id = c.entity_id
        WHERE c." . $studyIdColumn . " = %1 AND b.is_deleted = %2 AND b.case_type_id = %3 AND b.status_id != %4";
        $queryParams = [
          1 => [$studyId, "Integer"],
          2 => [0, "Integer"],
          3 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
          4 => [CRM_Nihrbackbone_BackboneConfig::singleton()->getClosedCaseStatusId(), "Integer"],
        ];
        $index = 4;
        CRM_Nihrbackbone_Utils::addParticipantStudyStatusClauses($calculateStatuses, $index, $query, $queryParams);
        $case = CRM_Core_DAO::executeQuery($query, $queryParams);
        while ($case->fetch()) {
          $result[] = CRM_Nihrbackbone_Utils::moveDaoToArray($case);
        }
      }
    }
    return $result;
  }

  /**
   * Method to add a activity to the participation case
   *
   * @param $caseId
   * @param $contactId
   * @param $activityTypeId
   * @param $activityParams
   * @return bool
   */
  public static function addCaseActivity($caseId, $contactId, $activityTypeId, $activityParams) {
    if (empty($caseId) || empty($contactId) || empty($activityTypeId)) {
      Civi::log()->warning(E::ts('Trying to add a case activity with empty case id, contact id or activity type id in ') . __METHOD__);
      return FALSE;
    }
    // check and complete activity params
    $activityParams['activity_type_id'] = (int) $activityTypeId;
    $activityParams['target_id'] = (int) $contactId;
    $activityParams['case_id'] = (int) $caseId;
    if (!isset($activityParams['source_contact_id']) || empty($activityParams['source_contact_id'])) {
      $activityParams['source_contact_id'] = 'user_contact_id';
    }
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
   * Method to update the study invited date on a case
   *
   * @param $caseId
   * @param null $inviteDate
   * @throws Exception
   */
  public static function updateStudyInviteDate($caseId, $inviteDate = NULL) {
    if (!$inviteDate) {
      $inviteDate = new DateTime('now');
    }
    if (!$inviteDate instanceof DateTime) {
      $inviteDate = new DateTime($inviteDate);
    }
    $inviteCustomFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'id');
    try {
      $result = civicrm_api3('Case', 'create', [
        'id' => (int) $caseId,
        $inviteCustomFieldId => $inviteDate->format('d-m-Y'),
        ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning("Could not update study invite date on case ID " . $caseId . ", error message from API Case create: " . $ex->getMessage());
    }
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
    //$valids = Civi::settings()->get('nbr_inv_case_status');
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
    //$valids = Civi::settings()->get('nbr_inv_case_status');
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

  /**
   * Method to get the latest export date
   *
   * @param $caseId
   * @return bool|false|string
   */
  public static function getLatestExportDate($caseId) {
    if (empty($caseId)) {
      return FALSE;
    }
    $query = "SELECT ca.activity_date_time
        FROM civicrm_case_activity AS cca
            JOIN civicrm_case AS cc ON cca.case_id = cc.id
            JOIN civicrm_activity AS ca ON cca.activity_id = ca.id
        WHERE cc.is_deleted = %1 AND ca.is_current_revision = %2 AND ca.is_test = %1 AND ca.is_deleted = %1
          AND cca.case_id = %3 AND cc.case_type_id = %4 AND ca.activity_type_id = %5
        ORDER BY ca.activity_date_time DESC LIMIT 1";
    $queryParams = [
      1 => [0, "Integer"],
      2 => [1, "Integer"],
      3 => [(int) $caseId, "Integer"],
      4 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      5 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getExportExternalActivityTypeId(), "Integer"],
    ];
    $exportDate = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($exportDate) {
      return date('d-m-Y', strtotime($exportDate));
    }
    return FALSE;
  }

  /**
   * Method to update the study status for a case/contact
   *
   * @param $caseId
   * @param $contactId
   * @param $status
   * @throws API_Exception
   */
  public static function updateStudyStatus($caseId, $contactId, $status) {
    $statusCustomField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id');
    try {
      civicrm_api3('Case', 'create', [
        'contact_id' => $contactId,
        'id' => $caseId,
        $statusCustomField => $status,
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(E::ts('Could not update status in study for contact ID ') . $contactId
        . E::ts(' and case ID ') . $caseId . E::ts(' in ') . __METHOD__ . E::ts(', error from API Case create: '). $ex->getMessage());
    }
  }

  /**
   * Method to validate the volunteer case forms
   *
   * @param $fields
   * @param $form
   * @param $errors
   */
  public static function validateForm($fields, $form, &$errors) {
    if ($form instanceof CRM_Case_Form_Case) {
      $action = $form->getVar('_action');
      $submitValues = $form->getVar('_submitValues');
      if ($action == CRM_Core_Action::ADD) {
        if (isset($submitValues['case_type_id']) && $submitValues['case_type_id'] ==
          CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId()) {
          $errors['case_type_id'] = E::ts("You can not add a participation case manually, import a file with participant ID's instead");
        }
      }
    }
  }

  /**
   * Method to determine if volunteer on case is eligible
   *
   * @param $caseId
   * @return bool
   */
  public static function isEligible($caseId) {
    if (empty($caseId)) {
      return FALSE;
    }
    $customFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'id');
    try {
      $result = civicrm_api3('Case', 'getvalue', [
        'id' => $caseId,
        'return' => $customFieldId,
      ]);
      $eligible = Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue();
      // check if there is only 1 eligibility status, if there are more then volunteer
      // is not eligible
      if (is_array($result)) {
        if (count($result) == 1) {
          foreach ($result as $key => $value) {
            $status = $value;
          }
        }
        else {
          return FALSE;
        }
      }
      else {
        $status = $result;
      }
      if ($status == $eligible) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Could not retrieve eligible status for caseId ') . $caseId . E::ts(' in ') . __METHOD__);
      return FALSE;
    }
  }

  /**
   * Process build form hook for CRM_Case_Form_CaseView
   *
   * @param $form
   */
  public static function buildFormCaseView(CRM_Core_Form &$form) {
    // add template to show or not show eligibility depending on status
    CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_show_eligible.tpl',]);
    // add template to remove merge case and reassign case links from form
    CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_case_links.tpl',]);
    $caseType = $form->getVar('_caseType');
    $caseId = $form->getVar('_caseID');
    $contactId = $form->getVar('_contactID');
    // add template to set all custom fields to readonly if study status has no action status
    $studyId = CRM_Nihrbackbone_NbrVolunteerCase::getStudyId((int) $caseId);
    if ($studyId && CRM_Nihrbackbone_NbrStudy::hasNoActionStatus((int) $studyId)) {
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_hide_custom_edit_button.tpl',]);
      CRM_Core_Session::setStatus("Edit of the participation data is not allowed because study status does not allow volunteer actions.");
    }
    $studyNumber = CRM_Nihrbackbone_NbrVolunteerCase::getStudyNumberWithCaseId($caseId);
    if ($studyNumber) {
      $form->addElement('text', 'study_number', "Study Number", ['readonly' => 'readonly']);
      $form->setDefaults(['study_number' => $studyNumber]);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_study_number.tpl',]);
    }
    // if volunteer on case is not eligible, do not allow the invite activity
    if (!CRM_Nihrbackbone_NbrVolunteerCase::isEligible($caseId)) {
      $activityElement = $form->getElement('add_activity_type_id');
      $activityTypeOptions = &$activityElement->_options;
      foreach ($activityTypeOptions as $optionId => $option) {
        if (isset($option['text']) && $option["text"] == "Invited") {
          unset($activityTypeOptions[$optionId]);
        }
      }
    }
    /** @var \CRM_Nihrbackbone_NbrConfig $config */
    $config = \Civi::service('nbrBackbone');
    $form->assign('hideCaseMerge', true);
    $form->assign('mergeCases', false);
    if ($config->getRecruitmentCaseTypeName() == $caseType) {
      $form->assign('hideCaseMerge', false);

      $otherCases = [];
      $result = civicrm_api3('Case', 'get', [
        'check_permissions' => TRUE,
        'contact_id' => $contactId,
        'is_deleted' => 0,
        'option.limit' => 0,
        'id' => ['!=' => $caseId],
        'case_type_id' => $caseType,
        'return' => ['id', 'start_date', 'case_type_id.title'],
      ]);
      foreach ($result['values'] as $id => $case) {
        $otherCases[$id] = "#$id: {$case['case_type_id.title']} " . ts('(opened %1)', [1 => $case['start_date']]);
      }
      //$form->setVar('_mergeCases', (bool) $otherCases);
      $form->assign('mergeCases', (bool) $otherCases);
      if ($otherCases) {
        $otherCases = ['' => E::ts(' - Select Case - ')] + $otherCases;
        /** @var HTML_QuickForm_select $mergeCaseIdDropdown */
        $mergeCaseIdDropdown = $form->getElement('merge_case_id');
        $mergeCaseIdDropdown->unfreeze();
        $mergeCaseIdDropdown->_options = NULL;
        $mergeCaseIdDropdown->load($otherCases);
      }
    }
  }

  /**
   * Process build form hook for CRM_Case_Form_CustomData
   *
   * @param $form
   */
  public static function buildFormCustomData(&$form) {
    $groupId = $form->getVar("_groupID");
    // if it is participation data
    if ($groupId == CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('id')) {
      // if volunteer is not eligible, remove the invited study statuses from options
      $caseId = (int) $form->getVar("_entityID");
      $elementPartName = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id');
      $index = $form->getVar("_elementIndex");
      foreach ($index as $elementName => $elementId) {
        if (strpos($elementName, $elementPartName) !== FALSE) {
          $element = $form->getElement($elementName);
          $options = &$element->_options;
          if (!self::shouldIncludeInvitedStatusOption($caseId)) {
            foreach($options as $optionId => $option) {
              if (isset($option['attr']['value']) && CRM_Nihrbackbone_NbrInvitation::isInviteOption($option['attr']['value'])) {
                unset($options[$optionId]);
              }
            }
          }
          if (!CRM_Nihrbackbone_NbrVolunteerCase::isEligible($caseId)) {
            foreach ($options as $optionId => $option) {
              if (isset($option['attr']['value']) && CRM_Nihrbackbone_NbrInvitation::isInviteOption($option['attr']['value'])) {
                unset($options[$optionId]);
              }
            }
          }
        }
      }
    }
  }

  /**
   * Method to calculate eligibility of volunteer on study
   *
   * @param $studyId
   * @param $volunteerId
   * @return array
   */
  public static function calculateEligibility($studyId, $volunteerId) {
    $eligibilities = [];
    // is volunteer inactive?
    if (!CRM_Nihrbackbone_NihrVolunteer::isActive($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getActiveEligibilityStatusValue();
    }
    // is volunteer temporarily non-recallable?
    if (CRM_Nihrbackbone_NihrVolunteer::isTemporarilyNonRecallable($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getRecallableEligibilityStatusValue();
    }
    // is volunteer invited on other studies?
    if (CRM_Nihrbackbone_NihrVolunteer::isInvitedOnOtherStudy($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getOtherEligibilityStatusValue();
    }
    // does volunteer have max total invitations in period?
    if (CRM_Nihrbackbone_NbrStudy::isOnline($studyId) || CRM_Nihrbackbone_NbrStudy::isFaceToFace($studyId)) {
      if (CRM_Nihrbackbone_NihrVolunteer::hasMaxTotalInvitesNow($volunteerId)) {
        $eligibilities[] = Civi::service('nbrBackbone')->getMaxEligibilityStatusValue();
      }
    }
    // is study face-to-face and has volunteer max face to face?
    if (CRM_Nihrbackbone_NbrStudy::isFaceToFace($studyId) && CRM_Nihrbackbone_NihrVolunteer::hasMaxFaceToFaceInvitesNow($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getMaxEligibilityStatusValue();
    }
    // is study online and volunteer excluded online studies?
    if (CRM_Nihrbackbone_NbrStudy::isOnline($studyId) && CRM_Nihrbackbone_NihrVolunteer::isExclOnline((int) $volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getExclOnlineEligibilityStatusValue();
    }
    // does study require age range and is volunteer outside?
    if (!CRM_Nihrbackbone_NbrVolunteerCase::isInAgeRange($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getAgeEligibilityStatusValue();
    }
    // does study require bmi range and is volunteer outside?
    if (!CRM_Nihrbackbone_NbrVolunteerCase::isInBmiRange($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getBmiEligibilityStatusValue();
    }
    // is commercial study and does volunteer not allow commercial?
    if (CRM_Nihrbackbone_NbrStudy::isCommercial($studyId) && !CRM_Nihrbackbone_NihrVolunteer::availableForCommercial($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getCommercialEligibilityStatusValue();
    }
    // does study require blood and does volunteer not give blood?
    if (CRM_Nihrbackbone_NbrStudy::requiresBlood($studyId) && !CRM_Nihrbackbone_NihrVolunteer::availableForBlood($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getBloodEligibilityStatusValue();
    }
    // does study require drugs and does volunteer not do drugs?
    if (CRM_Nihrbackbone_NbrStudy::requiresDrugs($studyId) && !CRM_Nihrbackbone_NihrVolunteer::availableForDrugs($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getDrugsEligibilityStatusValue();
    }
    // does study require MRI and does volunteer not do MRI?
    if (CRM_Nihrbackbone_NbrStudy::requiresMri($studyId) && !CRM_Nihrbackbone_NihrVolunteer::availableForMri($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getMriEligibilityStatusValue();
    }
    // does volunteer have required ethnicity?
    if (!CRM_Nihrbackbone_NbrVolunteerCase::hasEthnicity($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getEthnicityEligibilityStatusValue();
    }
    // does volunteer have required panel?
    if (!CRM_Nihrbackbone_NbrVolunteerCase::hasPanel($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getPanelEligibilityStatusValue();
    }
    // does volunteer have required gender?
    if (!CRM_Nihrbackbone_NbrVolunteerCase::hasGender($volunteerId, $studyId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getGenderEligibilityStatusValue();
    }
    // does study require travel and does volunteer not travel?
    if (CRM_Nihrbackbone_NbrStudy::requiresTravel($studyId) && !CRM_Nihrbackbone_NihrVolunteer::ableToTravel($volunteerId)) {
      $eligibilities[] = Civi::service('nbrBackbone')->getTravelEligibilityStatusValue();
    }
    // if no eligibility found yet, volunteer is eligible
    if (empty($eligibilities)) {
      $eligibilities = [Civi::service('nbrBackbone')->getEligibleEligibilityStatusValue()];
    }
    return $eligibilities;
  }

  /**
   * Method to determine if volunteer is in study age range
   *
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function isInAgeRange($contactId, $studyId) {
    $ageRange = CRM_Nihrbackbone_NbrStudy::requiresAgeRange($studyId);
    if (!empty($ageRange)) {
      return CRM_Nihrbackbone_NihrVolunteer::inAgeRange($contactId, $ageRange['age_from'], $ageRange['age_to']);
    }
    return TRUE;
  }

  /**
   * Method to determine if volunteer is in study bmi range
   *
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function isInBmiRange($contactId, $studyId) {
    $bmiRange = CRM_Nihrbackbone_NbrStudy::requiresBmiRange($studyId);
    if (!empty($bmiRange)) {
      return CRM_Nihrbackbone_NihrVolunteer::inBmiRange($contactId, (float) $bmiRange['bmi_from'], (float) $bmiRange['bmi_to']);
    }
    return TRUE;
  }

  /**
   * Method to determine if volunteer has required ethnicities
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function hasEthnicity($contactId, $studyId) {
    $ethnicities = CRM_Nihrbackbone_NbrStudy::requiresEthnicities($studyId);
    if (!empty($ethnicities)) {
      return CRM_Nihrbackbone_NihrVolunteer::hasRequiredEthnicity($contactId, $ethnicities);
    }
    return TRUE;
  }

  /**
   * Method to determine if volunteer has required gender
   *
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function hasGender($contactId, $studyId) {
    $genderId = CRM_Nihrbackbone_NbrStudy::requiresGender($studyId);
    if ($genderId) {
      return CRM_Nihrbackbone_NihrVolunteer::hasGender($contactId, $genderId);
    }
    return TRUE;
  }

  /**
   * Method to determine if volunteer has required panel
   *
   * @param $contactId
   * @param $studyId
   * @return bool
   */
  public static function hasPanel($contactId, $studyId) {
    $panelIds = CRM_Nihrbackbone_NbrStudy::requiresPanel($studyId);
    if ($panelIds) {
      // turn comma separated list into array
      $panelParts = explode(",", $panelIds);
      foreach ($panelParts as $panelPart) {
        if (!CRM_Nihrbackbone_NihrVolunteer::hasRequiredPanel($contactId, $panelPart)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  /**
   * Method to get study id with case id
   *
   * @param $caseId
   * @return int|string|null
   */
  public static function getStudyId($caseId) {
    $studyId = NULL;
    if (!empty($caseId)) {
      $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
      $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
      $query = "SELECT " . $studyIdColumn . " FROM " . $tableName . " WHERE entity_id = %1";
      $studyId = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $caseId, "Integer"]]);
      if ($studyId) {
        return (int) $studyId;
      }
    }
    return $studyId;
  }

  /**
   * Returns whether a should include the invited option or not.
   *
   * @param $caseId
   *
   * @return bool
   */
  public static function shouldIncludeInvitedStatusOption($caseId) {
    $includeInvitedOption = FALSE;
    $studyId = self::getStudyId($caseId);
    if (!empty($studyId)) {
      try {
        $studyStatus = civicrm_api3('Campaign', 'getvalue', [
          'return' => 'status_id',
          'id' => $studyId,
        ]);
        $invitateStatuses = explode(",", Civi::settings()->get('nbr_invite_campaign_status'));
        if (in_array($studyStatus, $invitateStatuses) && (CRM_Nihrbackbone_NbrStudy::isFaceToFace($studyId) || CRM_Nihrbackbone_NbrStudy::isOnline($studyId))) {
          $includeInvitedOption = TRUE;
        }
      } catch (\Exception $e) {
        // Do nothing
      }
    }
    return $includeInvitedOption;
  }

  /**
   * Method to get the latest visit from the case
   *
   * @param $caseId
   * @return bool|string
   * @throws Exception
   */
  public static function getNearestVisit($caseId) {
    if (empty($caseId)) {
      return FALSE;
    }
    // get nearest in the future if there is one
    $query = "SELECT ca.activity_date_time
        FROM civicrm_case_activity AS cca
            JOIN civicrm_case AS cc ON cca.case_id = cc.id
            JOIN civicrm_activity AS ca ON cca.activity_id = ca.id
            JOIN civicrm_option_value AS ov ON ca.activity_type_id = ov.value AND ov.option_group_id = %1
        WHERE cc.is_deleted = %2 AND ca.is_deleted = %2 AND ca.is_test = %2 AND ca.is_current_revision = %3
          AND cca.case_id = %4 AND ov.name LIKE %5 AND ca.activity_date_time > NOW() ORDER BY ca.activity_date_time LIMIT 1";
    $queryParams = [
      1 => [Civi::service('nbrBackbone')->getActivityTypeOptionGroupId(), "Integer"],
      2 => [0, "Integer"],
      3 => [1, "Integer"],
      4 => [(int) $caseId, "Integer"],
      5 => [Civi::service('nbrBackbone')->getVisitStage2Substring() . "%", "String"],
    ];
    $nearestVisit = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($nearestVisit) {
      if (!$nearestVisit instanceof DateTime) {
        $nearestVisit = new DateTime($nearestVisit);
      }
      return $nearestVisit->format('d-m-Y H:i');
    }
    else {
      // get latest in the past if no in the future
      $query = "SELECT ca.activity_date_time
        FROM civicrm_case_activity AS cca
            JOIN civicrm_case AS cc ON cca.case_id = cc.id
            JOIN civicrm_activity AS ca ON cca.activity_id = ca.id
            JOIN civicrm_option_value AS ov ON ca.activity_type_id = ov.value AND ov.option_group_id = %1
        WHERE cc.is_deleted = %2 AND ca.is_deleted = %2 AND ca.is_test = %2 AND ca.is_current_revision = %3
          AND cca.case_id = %4 AND ov.name LIKE %5 ORDER BY ca.activity_date_time DESC LIMIT 1";
      $latestVisit = CRM_Core_DAO::singleValueQuery($query, $queryParams);
      if ($latestVisit) {
        if (!$latestVisit instanceof DateTime) {
          $latestVisit = new DateTime($latestVisit);
        }
        return $latestVisit->format('d-m-Y H:i');
      }
    }
    return "";
  }

  /**
   * Method to get the study number with case ID
   *
   * @param $caseId
   * @return false|string
   */
  public static function getStudyNumberWithCaseId($caseId) {
    $participationTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $studyTable = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyDataCustomGroup('table_name');
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $studyNumberColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_study_number', 'column_name');
    $query = "SELECT cvnsd." . $studyNumberColumn .
      " FROM " . $participationTable . " AS cvnpd
      JOIN ". $studyTable . " AS cvnsd ON cvnpd. " . $studyIdColumn . " = cvnsd.entity_id
      WHERE cvnpd.entity_id = %1  ";
    $studyNumber = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $caseId, "Integer"]]);
    if ($studyNumber) {
      return $studyNumber;
    }
    return FALSE;
  }

  /**
   * Method to get the case id (if found) of a participation case for a contact and study
   *
   * @param $studyId
   * @param $contactId
   * @return false|int
   */
  public static function getActiveParticipationCaseId($studyId, $contactId) {
    if (empty($contactId) || empty($studyId)) {
      return FALSE;
    }
    $query = "SELECT a.case_id
        FROM civicrm_case_contact AS a JOIN civicrm_case AS b ON a.case_id = b.id
            LEFT JOIN civicrm_value_nbr_participation_data AS c ON a.case_id = c.entity_id
        WHERE b.case_type_id = %1 AND b.is_deleted = %2 AND a.contact_id = %3 AND c.nvpd_study_id = %4";
    $queryParams = [
      1 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
      2 => [0, "Integer"],
      3 => [(int) $contactId, "Integer"],
      4 => [(int) $studyId, "Integer"],
    ];
    $caseId = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($caseId) {
      return (int) $caseId;
    }
    return FALSE;
  }



  /**
   * Method to create query for daily calculate of eligibility
   * @param $query
   * @param $queryParams
   * @param string $mode
   */
  public static function getQueryForCalculationMode(&$query, &$queryParams, $mode = "default") {
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $statusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'column_name');
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('table_name');
    $query = "SELECT DISTINCT(a.contact_id), c.entity_id AS case_id, d.id AS study_id
    FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        JOIN " . $tableName . " AS c on a.case_id = c.entity_id
        JOIN civicrm_campaign AS d ON c." . $studyIdColumn . " = d.id
    WHERE d.campaign_type_id = %1 AND d.status_id = %2 AND b.is_deleted = %3";
    $queryParams = [
      1 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId(), "Integer"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitingStudyStatus(), "Integer"],
      3 => [0, "Integer"],
    ];
    if ($mode == "full") {
      $query .= " AND c." . $statusColumn . " IN(%4, %5, %6, %7)";
      $queryParams[4] = [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"];
      $queryParams[5] = [Civi::service('nbrBackbone')->getInvitationPendingParticipationStatusValue(), "String"];
      $queryParams[6] = [Civi::service('nbrBackbone')->getInvitedParticipationStatusValue(), "String"];
      $queryParams[7] = [Civi::service('nbrBackbone')->getAcceptedParticipationStatusValue(), "String"];
    }
    else {
      $index = 3;
      $calculateStatuses = Civi::settings()->get('nbr_eligible_calc_study_status');
      CRM_Nihrbackbone_Utils::addParticipantStudyStatusClauses($calculateStatuses, $index, $query, $queryParams);
    }
  }

  /**
   * Method to get volunteer id of a case
   *
   * @param $caseId
   * @return false|int
   */
  public static function getCaseVolunteerId($caseId) {
    try {
      $result = civicrm_api3('Case', 'getvalue', [
        'return' => "contact_id",
        'id' => $caseId,
      ]);
      if ($result[1]) {
        return $result[1];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to check if the eligibility should be recalculated for the volunteer
   * (if study participation status has changed)
   *
   * @param $caseId
   * @param $values
   */
  public static function checkEligibilityRecalculation($caseId, $values) {
    $session = CRM_Core_Session::singleton();
    if (isset($session->recalcForCaseId) && !empty($session->recalcForCaseId)) {
      // recalculate eligibility on all studies for volunteer
      $volunteerId = self::getCaseVolunteerId($caseId);
      $cases = self::getVolunteerSelections($volunteerId);
      foreach ($cases as $case) {
        $eligibilities = self::calculateEligibility($case['nvpd_study_id'], $volunteerId);
        CRM_Nihrbackbone_NbrVolunteerCase::setEligibilityStatus($eligibilities, (int) $case['case_id'], TRUE);
      }
      unset($session->recalcForCaseId);
    }
  }

  /**
   * Method to find the deleted participation cases of a contact for the merge process
   *
   * @param int $contactId
   * @return array
   */
  public static function getParticipationCasesMergeData(int $contactId) {
    $cases = [];
    $participationTable = Civi::service('nbrBackbone')->getParticipationDataTableName();
    $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
    $studyParticipantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
    $eligibleStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
    $recallGroupColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
    $dateInvitedColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
    $query = "SELECT DISTINCT(a.case_id), c." . $studyIdColumn . " AS study_id, c.". $studyParticipantIdColumn . " AS study_participant_id, c."
      . $eligibleStatusColumn . " AS eligible_status, c." . $recallGroupColumn ." AS recall_group, c." . $dateInvitedColumn . " AS date_invited,
       b.start_date, b.subject, b.created_date
        FROM civicrm_case_contact a
            JOIN civicrm_case b ON a.case_id = b.id
            LEFT JOIN " . $participationTable . " c ON a.case_id = c.entity_id
        WHERE contact_id = %1 AND b.case_type_id = %2";
    $dao = CRM_Core_DAO::executeQuery($query, [
      1 => [$contactId, 'Integer'],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), 'Integer'],
    ]);
    while ($dao->fetch()) {
      $cases[$dao->case_id] = CRM_Nihrbackbone_Utils::moveDaoToArray($dao);
    }
    return $cases;
  }

  /**
   * Method to resurrect participation data after case merge
   * @link https://www.wrike.com/open.htm?id=692748431 or https://issues.civicoop.org/issues/7827
   *
   * @param int $newContactId
   * @param int $oldContactId
   * @param int $newCaseId
   * @param int $oldCaseId
   */
  public static function resurrectParticipationData(int $newContactId, int $oldContactId, int $newCaseId, int $oldCaseId) {
    // get all participation cases from old contact
    $oldCases = self::getParticipationCasesMergeData($oldContactId);
    // for all old cases, check if there is one and only case for the same study on the new volunteer and if so, send
    // participation data there
    foreach ($oldCases as $oldCaseId => $caseData) {
      // find new case (this is complicated as the study_id in the new case is empty but the combination of contact_id, subject, start_date
      // and creation date should identify uniquely IF the study participant id is empty in the new case
      $participationTable = Civi::service('nbrBackbone')->getParticipationDataTableName();
      $studyParticipantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
      $query = "SELECT a.case_id
        FROM civicrm_case_contact a
            JOIN civicrm_case b ON a.case_id = b.id
            LEFT JOIN " . $participationTable . " c ON a.case_id = c.entity_id
        WHERE a.contact_id = %1 AND b.case_type_id = %2 AND b.is_deleted = FALSE AND b.subject = %3 AND c." . $studyParticipantIdColumn . " IS NULL
        ORDER BY a.case_id DESC LIMIT 1";
      $newCase = CRM_Core_DAO::executeQuery($query, [
        1 => [$newContactId, "Integer"],
        2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"],
        3 => [$caseData['subject'], "String"],
      ]);
      if ($newCase->fetch()) {
        // fix participation data if data found
        self::fixParticipationData((int) $oldCaseId, (int) $newCase->case_id, $caseData);
      }
    }
  }

  /**
   * Method to fix study id, study participation id and eligibility after reassign case
   *
   * @param int $oldCaseId
   * @param int $newCaseId
   * @param array $caseData
   * @return bool
   */
  public static function fixParticipationData(int $oldCaseId, int $newCaseId, array $caseData) {
    if (!empty($oldCaseId) && !empty($newCaseId)) {
      $table = Civi::service('nbrBackbone')->getParticipationDataTableName();
      // first check if there already is a record in participation data for case and if so, update with data from old case. If not, update old
      // record with new case id
      $checkQuery = "SELECT COUNT(*) FROM civicrm_value_nbr_participation_data WHERE entity_id = %1 AND nvpd_study_participant_id IS NULL";
      $count = CRM_Core_DAO::singleValueQuery($checkQuery, [1 => [$newCaseId, "Integer"]]);
      if ($count == 0) {
        $fixQuery = "UPDATE " . $table . " SET entity_id = %1 WHERE entity_id = %2";
        $fixParams = [
          1 => [$newCaseId, "Integer"],
          2 => [$oldCaseId, "Integer"],
        ];
        CRM_Core_DAO::executeQuery($fixQuery, $fixParams);
        return TRUE;
      }
      else {
        $studyIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'column_name');
        $studyParticipantIdColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participant_id', 'column_name');
        $eligibleStatusColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_eligible_status_id', 'column_name');
        $recallGroupColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_recall_group', 'column_name');
        $dateInvitedColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_date_invited', 'column_name');
        $fixParams = [];
        $fixElements = [];
        $i = 0;
        if (isset($caseData['study_id']) && !empty($caseData['study_id'])) {
          $i++;
          $fixElements[] = $studyIdColumn . " = %" . $i;
          $fixParams[$i] = [(int) $caseData['study_id'], "Integer"];
        }
        if (isset($caseData['study_participant_id']) && !empty($caseData['study_participant_id'])) {
          $i++;
          $fixElements[] = $studyParticipantIdColumn . " = %" . $i;
          $fixParams[$i] = [$caseData['study_participant_id'], "String"];
        }
        if (isset($caseData['eligible_status']) && !empty($caseData['eligible_status'])) {
          $i++;
          $fixElements[] = $eligibleStatusColumn . " = %" . $i;
          $fixParams[$i] = [$caseData['eligible_status'], "String"];
        }
        if (isset($caseData['recall_group']) && !empty($caseData['recall_group'])) {
          $i++;
          $fixElements[] = $recallGroupColumn . " = %" . $i;
          $fixParams[$i] = [$caseData['recall_group'], "String"];
        }
        if (isset($caseData['date_invited']) && !empty($caseData['date_invited'])) {
          $i++;
          $fixElements[] = $dateInvitedColumn . " = %" . $i;
          $fixParams[$i] = [$caseData['date_invited'], "String"];
        }
        if (!empty($fixElements)) {
          $i++;
          $fixQuery = "UPDATE " . $table . " SET " . implode(', ', $fixElements) . " WHERE entity_id = %" . $i;
          $fixParams[$i] = [$newCaseId, "Integer"];
          CRM_Core_DAO::executeQuery($fixQuery, $fixParams);
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to get participation data
   *
   * @param $caseId
   * @return array|false
   */
  public static function getParticipationData($caseId) {
    if (!empty($caseId)) {
      $table = Civi::service('nbrBackbone')->getParticipationDataTableName();
      if ($table) {
        $query = "SELECT * FROM " . $table . " WHERE entity_id = %1";
        $dao = CRM_Core_DAO::executeQuery($query, [1 => [(int) $caseId, "Integer"]]);
        if ($dao->fetch()) {
          return CRM_Nihrbackbone_Utils::moveDaoToArray($dao);
        }
      }
    }
    return FALSE;
  }

  /**
   * Method to check if case is a participation case
   *
   * @param $caseId
   * @return bool
   */
  public static function isParticipationCase($caseId) {
    $query = "SELECT case_type_id FROM civicrm_case WHERE id = %1";
    $caseTypeId = CRM_Core_DAO::singleValueQuery($query, [1 => [(int) $caseId, "Integer"]]);
    if ($caseTypeId) {
      if ((int) $caseTypeId == (int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId()) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Method to generate study participant id for relevant volunteers on data only study
   *
   * @param string $studyNumber
   * @return array
   */
  public static function generateIdForDataOnlyParticipants(string $studyNumber) {
    $result = [];
    if (!empty($studyNumber)) {
      $query = "SELECT b.entity_id AS participation_id, d.contact_id AS volunteer_id
        FROM civicrm_value_nbr_study_data AS a
            JOIN civicrm_value_nbr_participation_data AS b ON a.entity_id = b.nvpd_study_id
            JOIN civicrm_case AS c ON b.entity_id = c.id
            JOIN civicrm_case_contact AS d ON c.id = d.case_id
        WHERE a.nsd_study_number = %1 AND a.nsd_data_only = TRUE AND (b.nvpd_study_participant_id IS NULL OR b.nvpd_study_participant_id = %2)
            AND b.nvpd_study_participation_status = %3 AND c.is_deleted = %4 AND c.case_type_id = %5";
      $queryParams = [
        1 => [$studyNumber, "String"],
        2 => ["", "String"],
        3 => [Civi::service('nbrBackbone')->getSelectedParticipationStatusValue(), "String"],
        4 => [0, "Integer"],
        5 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCaseTypeId(), "Integer"]
      ];
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      while ($dao->fetch()) {
        if (CRM_Nihrbackbone_NihrVolunteer::hasValidCorrectConsent((int) $dao->volunteer_id) && !CRM_Nihrbackbone_NihrVolunteer::wantsDataDestroyed((int) $dao->volunteer_id)) {
          CRM_Nihrnumbergenerator_StudyParticipantNumberGenerator::createNewNumberForCase((int) $dao->participation_id);
          $result[] = "Generated Study Participant ID for participation case ID: " . $dao->participation_id . " belonging to volunteer ID: " . $dao->volunteer_id . " on study " . $studyNumber;
        }
      }
    }
    return $result;
  }

  /**
   * Method is copied from CRM_Activity_Page_AJAX::getCaseActivity
   * (see https://www.wrike.com/open.htm?id=692748479)
   **
   * @return void
   * @throws CRM_Core_Exception
   */
  public static function getAjaxParticipationActivities() {
    // Should those params be passed through the validateParams method?
    $caseID = CRM_Utils_Type::validate($_GET['caseID'], 'Integer');
    $contactID = CRM_Utils_Type::validate($_GET['cid'], 'Integer');
    $userID = CRM_Utils_Type::validate($_GET['userID'], 'Integer');
    $context = CRM_Utils_Type::validate(CRM_Utils_Array::value('context', $_GET), 'String');

    $optionalParameters = [
      'source_contact_id' => 'Integer',
      'status_id' => 'Integer',
      'activity_deleted' => 'Boolean',
      'activity_type_id' => 'Integer',
      // "Date" validation fails because it expects only numbers with no hyphens
      'activity_date_low' => 'Alphanumeric',
      'activity_date_high' => 'Alphanumeric',
    ];

    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams();
    $params += CRM_Core_Page_AJAX::validateParams([], $optionalParameters);

    // get the activities related to given case
    $activities = CRM_Nihrbackbone_NbrVolunteerCase::getParticipationCaseActivities($caseID, $params, $contactID, $context, $userID);

    CRM_Utils_JSON::output($activities);
  }

  /**
   * Method is copied from CRM_Case_BAO_Case::getCaseActivity
   * (see https://www.wrike.com/open.htm?id=692748479)
   *
   * add contactID to where
   *
   * @param int $caseID
   *   Case id.
   * @param array $params
   *   Posted params.
   * @param int $contactID
   *   Contact id.
   *
   * @param null $context
   * @param int $userID
   * @param null $type (deprecated)
   *
   * @return array
   *   Array of case activities
   * @throws
   */
  public static function getParticipationCaseActivities($caseID, &$params, $contactID, $context = NULL, $userID = NULL, $type = NULL) {
    $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
    $assigneeID = CRM_Utils_Array::key('Activity Assignees', $activityContacts);
    $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);
    $targetID = CRM_Utils_Array::key('Activity Targets', $activityContacts);

    // CRM-5081 - formatting the dates to omit seconds.
    // Note the 00 in the date format string is needed otherwise later on it thinks scheduled ones are overdue.
    $select = "
           SELECT SQL_CALC_FOUND_ROWS COUNT(ca.id) AS ismultiple,
                  ca.id AS id,
                  ca.activity_type_id AS type,
                  ca.activity_type_id AS activity_type_id,
                  tcc.sort_name AS target_contact_name,
                  tcc.id AS target_contact_id,
                  scc.sort_name AS source_contact_name,
                  scc.id AS source_contact_id,
                  acc.sort_name AS assignee_contact_name,
                  acc.id AS assignee_contact_id,
                  DATE_FORMAT(
                    IF(ca.activity_date_time < NOW() AND ca.status_id=ov.value,
                      ca.activity_date_time,
                      DATE_ADD(NOW(), INTERVAL 1 YEAR)
                    ), '%Y%m%d%H%i00') AS overdue_date,
                  DATE_FORMAT(ca.activity_date_time, '%Y%m%d%H%i00') AS display_date,
                  ca.status_id AS status,
                  ca.subject AS subject,
                  ca.is_deleted AS deleted,
                  ca.priority_id AS priority,
                  ca.weight AS weight,
                  GROUP_CONCAT(ef.file_id) AS attachment_ids ";

    $from = "
             FROM civicrm_case_activity cca
       INNER JOIN civicrm_activity ca
               ON ca.id = cca.activity_id
       INNER JOIN civicrm_activity_contact cas
               ON cas.activity_id = ca.id
              AND cas.record_type_id = {$sourceID}
       INNER JOIN civicrm_contact scc
               ON scc.id = cas.contact_id
        LEFT JOIN civicrm_activity_contact caa
               ON caa.activity_id = ca.id
              AND caa.record_type_id = {$assigneeID}
        LEFT JOIN civicrm_contact acc
               ON acc.id = caa.contact_id
        LEFT JOIN civicrm_activity_contact cat
               ON cat.activity_id = ca.id
              AND cat.record_type_id = {$targetID}
        LEFT JOIN civicrm_contact tcc
               ON tcc.id = cat.contact_id
       INNER JOIN civicrm_option_group cog
               ON cog.name = 'activity_type'
       INNER JOIN civicrm_option_value cov
               ON cov.option_group_id = cog.id
              AND cov.value = ca.activity_type_id
              AND cov.is_active = 1
        LEFT JOIN civicrm_entity_file ef
               ON ef.entity_table = 'civicrm_activity'
              AND ef.entity_id = ca.id
  LEFT OUTER JOIN civicrm_option_group og
               ON og.name = 'activity_status'
  LEFT OUTER JOIN civicrm_option_value ov
               ON ov.option_group_id=og.id
              AND ov.name = 'Scheduled'";

    $where = '
            WHERE cca.case_id= %1
              AND ca.is_current_revision = 1';

    // Erik Hommel: added NBR specific change to add contact ID (see https://www.wrike.com/open.htm?id=692748479) */
    if (!empty($contactID)) {
      $where .= " AND tcc.id = " . CRM_Utils_Type::escape($contactID, 'Integer');
    }
    // Erik Hommel end

    if (!empty($params['source_contact_id'])) {
      $where .= "
              AND cas.contact_id = " . CRM_Utils_Type::escape($params['source_contact_id'], 'Integer');
    }

    if (!empty($params['status_id'])) {
      $where .= "
              AND ca.status_id = " . CRM_Utils_Type::escape($params['status_id'], 'Integer');
    }

    if (!empty($params['activity_deleted'])) {
      $where .= "
              AND ca.is_deleted = 1";
    }
    else {
      $where .= "
              AND ca.is_deleted = 0";
    }

    if (!empty($params['activity_type_id'])) {
      $where .= "
              AND ca.activity_type_id = " . CRM_Utils_Type::escape($params['activity_type_id'], 'Integer');
    }

    if (!empty($params['activity_date_low'])) {
      $fromActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_low']), 'Date');
    }
    if (!empty($fromActivityDate)) {
      $where .= "
              AND ca.activity_date_time >= '{$fromActivityDate}'";
    }

    if (!empty($params['activity_date_high'])) {
      $toActivityDate = CRM_Utils_Type::escape(CRM_Utils_Date::processDate($params['activity_date_high']), 'Date');
      $toActivityDate = $toActivityDate ? $toActivityDate + 235959 : NULL;
    }
    if (!empty($toActivityDate)) {
      $where .= "
              AND ca.activity_date_time <= '{$toActivityDate}'";
    }

    $groupBy = "
         GROUP BY ca.id, tcc.id, scc.id, acc.id, ov.value";

    $sortBy = $params['sortBy'] ?? NULL;
    if (!$sortBy) {
      // CRM-5081 - added id to act like creation date
      $orderBy = "
         ORDER BY overdue_date ASC, display_date DESC, weight DESC";
    }
    else {
      $sortBy = CRM_Utils_Type::escape($sortBy, 'String');
      $orderBy = " ORDER BY $sortBy ";
    }

    $page = $params['page'] ?? NULL;
    $rp = $params['rp'] ?? NULL;

    if (!$page) {
      $page = 1;
    }
    if (!$rp) {
      $rp = 10;
    }
    $start = (($page - 1) * $rp);
    $limit = " LIMIT $start, $rp";

    $query = $select . $from . $where . $groupBy . $orderBy . $limit;
    $queryParams = [1 => [$caseID, 'Integer']];

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $caseCount = CRM_Core_DAO::singleValueQuery('SELECT FOUND_ROWS()');

    $activityTypes = CRM_Case_PseudoConstant::caseActivityType(FALSE, TRUE);

    $compStatusValues = array_keys(
      CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::COMPLETED) +
      CRM_Activity_BAO_Activity::getStatusesByType(CRM_Activity_BAO_Activity::CANCELLED)
    );

    if (!$userID) {
      $userID = CRM_Core_Session::getLoggedInContactID();
    }

    $caseActivities = [];

    while ($dao->fetch()) {
      $caseActivityId = $dao->id;

      //Do we have permission to access given case activity record.
      if (!CRM_Case_BAO_Case::checkPermission($caseActivityId, 'view', $dao->activity_type_id, $userID)) {
        continue;
      }

      $caseActivities[$caseActivityId]['DT_RowId'] = $caseActivityId;
      //Add classes to the row, via DataTables syntax
      $caseActivities[$caseActivityId]['DT_RowClass'] = "crm-entity status-id-$dao->status";

      if (CRM_Utils_Array::crmInArray($dao->status, $compStatusValues)) {
        $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-completed";
      }
      else {
        if (CRM_Utils_Date::overdue($dao->display_date)) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-overdue";
        }
        else {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " status-scheduled";
        }
      }

      if (!empty($dao->priority)) {
        if ($dao->priority == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Urgent')) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " priority-urgent ";
        }
        elseif ($dao->priority == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'priority_id', 'Low')) {
          $caseActivities[$caseActivityId]['DT_RowClass'] .= " priority-low ";
        }
      }

      //Add data to the row for inline editing, via DataTable syntax
      $caseActivities[$caseActivityId]['DT_RowAttr'] = [];
      $caseActivities[$caseActivityId]['DT_RowAttr']['data-entity'] = 'activity';
      $caseActivities[$caseActivityId]['DT_RowAttr']['data-id'] = $caseActivityId;

      //Activity Date and Time
      $caseActivities[$caseActivityId]['activity_date_time'] = CRM_Utils_Date::customFormat($dao->display_date);

      //Activity Subject
      $caseActivities[$caseActivityId]['subject'] = $dao->subject;

      //Activity Type
      $caseActivities[$caseActivityId]['type'] = (!empty($activityTypes[$dao->type]['icon']) ? '<span class="crm-i ' . $activityTypes[$dao->type]['icon'] . '"></span> ' : '')
        . $activityTypes[$dao->type]['label'];

      // Activity Target (With Contact) (There can be more than one)
      $targetContact = self::formatContactLink($dao->target_contact_id, $dao->target_contact_name);
      if (empty($caseActivities[$caseActivityId]['target_contact_name'])) {
        $caseActivities[$caseActivityId]['target_contact_name'] = $targetContact;
      }
      else {
        if (strpos($caseActivities[$caseActivityId]['target_contact_name'], $targetContact) === FALSE) {
          $caseActivities[$caseActivityId]['target_contact_name'] .= '; ' . $targetContact;
        }
      }

      // Activity Source Contact (Reporter) (There can only be one)
      $sourceContact = self::formatContactLink($dao->source_contact_id, $dao->source_contact_name);
      $caseActivities[$caseActivityId]['source_contact_name'] = $sourceContact;

      // Activity Assignee (There can be more than one)
      $assigneeContact = self::formatContactLink($dao->assignee_contact_id, $dao->assignee_contact_name);
      if (empty($caseActivities[$caseActivityId]['assignee_contact_name'])) {
        $caseActivities[$caseActivityId]['assignee_contact_name'] = $assigneeContact;
      }
      else {
        if (strpos($caseActivities[$caseActivityId]['assignee_contact_name'], $assigneeContact) === FALSE) {
          $caseActivities[$caseActivityId]['assignee_contact_name'] .= '; ' . $assigneeContact;
        }
      }

      // Activity Status Label for Case activities list
      $deleted = '';
      if ($dao->deleted) {
        $deleted = '<br /> ' . ts('(deleted)');
      }
      $caseActivities[$caseActivityId]['status_id'] = CRM_Core_PseudoConstant::getLabel('CRM_Activity_BAO_Activity', 'activity_status_id', $dao->status) . $deleted;
      // if there are file attachments we will return how many
      if (!empty($dao->attachment_ids)) {
        $attachmentIDs = array_unique(explode(',', $dao->attachment_ids));
        $caseActivity['no_attachments'] = count($attachmentIDs);
      }

      $caseActivities[$caseActivityId]['links']
        = CRM_Case_Selector_Search::addCaseActivityLinks($caseID, $contactID, $userID, $context, $dao);
    }

    $caseActivitiesDT = [];
    $caseActivitiesDT['data'] = array_values($caseActivities);
    $caseActivitiesDT['recordsTotal'] = $caseCount;
    $caseActivitiesDT['recordsFiltered'] = $caseCount;

    return $caseActivitiesDT;
  }

  /**
   * Helper function to generate a formatted contact link/name for display in the Case activities tab
   *
   * @param $contactId
   * @param $contactName
   *
   * @return string
   */
  private static function formatContactLink($contactId, $contactName) {
    if (empty($contactId)) {
      return NULL;
    }

    $hasViewContact = CRM_Contact_BAO_Contact_Permission::allow($contactId);

    if ($hasViewContact) {
      $contactViewUrl = CRM_Utils_System::url("civicrm/contact/view", "reset=1&cid={$contactId}");
      return "<a href=\"{$contactViewUrl}\">" . $contactName . "</a>";
    }
    else {
      return $contactName;
    }
  }


}
