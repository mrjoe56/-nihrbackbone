<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class NbrRecruitmentCase to deal with recruitment case processing
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 8 Feb 2022
 * @license AGPL-3.0
 * @errorrange 3000-3499
 */
class CRM_Nihrbackbone_NbrRecruitmentCase {

  /**
   * Method to create case of type Recruitment
   *
   * @param array $params
   * @throws API_Exception
   * @return array
   */
  public static function createRecruitmentVolunteerCase(array $params) {
    if (empty($params['contact_id'])) {
      throw new API_Exception(E::ts('Trying to create a NIHR recruitment case with an empty or missing param contact_id in ') . __METHOD__, 3003);
    }
    try {
      $case = civicrm_api3('Case', 'create', self::setRecruitmentCaseCreateData($params));
      return ['case_id' => $case['id']];
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception('Could not create a Recruitment case for contact ID ' . $params['contact_id']
        . ' in ' . __METHOD__ . ', error code from API Case create:'  . $ex->getMessage(), 3004);
    }
  }

  /**
   * @param array $params
   * @return array
   */
  private static function setRecruitmentCaseCreateData($params) {
    $subject = "Recruitment";
    if (isset($params['subject'])) {
      $subject = $params['subject'];
    }
    $caseCreateData =  [
      'contact_id' => $params['contact_id'],
      'case_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitmentCaseTypeId(),
      'subject' => $subject,
      'status_id' => "Open",
    ];
    if (isset($params['start_date']) && !empty($params['start_date'])) {
      $caseCreateData['start_date'] = $params['start_date'];
    }
    return $caseCreateData;
  }
  /**
   * Get recruitment case id for contact (there should only be one!)
   *
   * @param $contactId
   * @return bool|string
   */
  public static function getActiveRecruitmentCaseId($contactId) {
    if (empty($contactId)) {
      return FALSE;
    }
    $query = "SELECT ccc.case_id
        FROM civicrm_case AS cc
            JOIN civicrm_case_contact AS ccc ON cc.id = ccc.case_id
        WHERE cc.is_deleted = %1 AND cc.case_type_id = %2 AND ccc.contact_id = %3
        ORDER BY cc.start_date DESC LIMIT 1";
    $caseId = CRM_Core_DAO::singleValueQuery($query, [
      1 => [0, "Integer"],
      2 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitmentCaseTypeId(), "Integer"],
      3 => [(int) $contactId, "Integer"],
    ]);
    if ($caseId) {
      return $caseId;
    }
    return FALSE;
  }

  /**
   * Method to merge recruitment cases after 2 contacts have been merged
   * (see https://www.wrike.com/open.htm?id=692748431)
   *
   * @param int $contactId
   * @return void
   */
  public static function mergeRecruitmentCases(int $contactId) {
    // find all recruitment cases for the contact
    $cases = self::getAllRecruitmentCases($contactId);
    // if there are more.....
    if (count($cases) > 1) {
      $oldestCaseId = (int) $cases[0];
      // merge all activities apart from open case into the oldest one and then delete case
      foreach ($cases as $caseId) {
        if ($caseId != $oldestCaseId) {
          self::mergeCaseActivities((int) $caseId, $oldestCaseId);
          $update = "UPDATE civicrm_case SET is_deleted = TRUE WHERE id = %1";
          CRM_Core_DAO::executeQuery($update, [1 => [(int) $caseId, "Integer"]]);
        }
      }
    }
  }

  /**
   * Method to move all case activities that are not open case from old to new case
   *
   * @param int $oldCaseId
   * @param int $newCaseId
   * @return void
   */
  private static function mergeCaseActivities(int $oldCaseId, int $newCaseId) {
    $caseActivityIds = [];
    $i = 1;
    $updateParams = [1 => [$newCaseId, "Integer"]];
    $query = "SELECT a.id AS case_activity_id
            FROM civicrm_case_activity a
            JOIN civicrm_activity b ON a.activity_id = b.id
            WHERE a.case_id = %1 AND b.activity_type_id != %2";
    $caseActivity = CRM_Core_DAO::executeQuery($query, [
      1 => [(int) $oldCaseId, "Integer"],
      2 => [Civi::service('nbrBackbone')->getOpenCaseActivityTypeId(), "Integer"],
    ]);
    while ($caseActivity->fetch()) {
      $caseActivityIds[] = $caseActivity->case_activity_id;
    }
    if (!empty($caseActivityIds)) {
      $clauses = [];
      foreach ($caseActivityIds as $caseActivityId) {
        $i++;
        $updateParams[$i] = [(int) $caseActivityId, "Integer"];
        $clauses[] = "%" . $i;
      }
      $update = "UPDATE civicrm_case_activity SET case_id = %1 WHERE id IN(" . implode(", ", $clauses) .")";
      CRM_Core_DAO::executeQuery($update, $updateParams);
    }
  }

  /**
   * Method to get all recruitment cases for a contact (used for merge processing where there could be more!)
   *
   * @param int $contactId
   * @return array
   */
  public static function getAllRecruitmentCases(int $contactId) {
    $recruitmentCases = [];
    if (!empty($contactId)) {
      $query = "SELECT a.case_id
        FROM civicrm_case_contact AS a
        JOIN civicrm_case AS b ON a.case_id = b.id
        WHERE a.contact_id = %1 AND b.is_deleted = %2 AND b.case_type_id = %3 ORDER BY b.start_date";
      $case = CRM_Core_DAO::executeQuery($query, [
        1 => [$contactId, "Integer"],
        2 => [0, "Integer"],
        3 => [(int) CRM_Nihrbackbone_BackboneConfig::singleton()->getRecruitmentCaseTypeId(), "Integer"],
      ]);
      while ($case->fetch()) {
        $recruitmentCases[] = $case->case_id;
      }
    }
    return $recruitmentCases;
  }

}
