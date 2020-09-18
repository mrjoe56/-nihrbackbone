<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource Invitation activity
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 27 Mar 2020
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrInvitation {
  /**
   * Method to process post hook for an invite activity:
   * - if new set study status to invited
   *
   * @param string $op
   * @param int $activityId
   * @param CRM_Core_DAO $activityObject
   */
  public static function postInviteHook($op, $activityId, $activityObject) {
    if ($op == "create" && isset($activityObject->case_id)) {
      try {
        $targetId = civicrm_api3('ActivityContact', 'getvalue', [
          'return' => "contact_id",
          'activity_id' => (int) $activityId,
          'record_type_id' => "Activity Targets",
        ]);
        // set eligibility to "invited on other" for all other cases where volunteer is selected
        CRM_Nihrbackbone_NihrVolunteer::updateEligibilityAfterInvite((int) $targetId, (int) $activityObject->case_id);
        $qfDefault = CRM_Utils_Request::retrieveValue('_qf_default', "String");
        if ($qfDefault != "CustomData:upload") {
          CRM_Nihrbackbone_NbrVolunteerCase::updateStudyStatus((int) $activityObject->case_id, (int) $targetId, 'study_participation_status_invited');
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->warning(E::ts("Could not find a target contact id for activity ") . $activityId
          . E::ts(" in ") . __METHOD__ . E::ts(", study status NOT set to invited!"));
      }
    }
  }

  /**
   * Method to process the custom hook for study status invite change
   *
   * @param $op
   * @param $caseId
   * @param $customData
   * @throws CRM_Core_Exception
   */
  public static function customInviteStatusHook($op, $caseId, $customData) {
    CRM_Nihrbackbone_NbrVolunteerCase::updateStudyInviteDate($caseId);
    // if done in the UI by changing the study status directly, add invite activity
    $qfDefault = CRM_Utils_Request::retrieveValue('_qf_default', "String");
    if ($qfDefault == "CustomData:upload") {
      $customFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_id', 'id');
      try {
        $result = civicrm_api3('Case', 'getsingle', [
          'return' => [$customFieldId, "contact_id"],
          'id' => (int) $caseId,
        ]);
        if (isset($result['contact_id'][1]) && isset($result[$customFieldId])) {
          self::addInviteActivity($caseId, $result['contact_id'][1], $result[$customFieldId], 'Changed Study Status manually');
        }
        else {
          Civi::log()->warning(E::ts("Could not find case client ID and study ID in ") . __METHOD);
        }
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->warning(E::ts("Could not find case client ID and study ID in ") . __METHOD__
          . E::ts(", error from API Case getsingle: ") . $ex->getMessage());
      }
    }
  }

  /**
   * Method to add the invitation activity if required
   *
   * @param int $caseId
   * @param int $contactId
   * @param int $studyId
   * @param string $context
   */
  public static function addInviteActivity($caseId, $contactId, $studyId = NULL, $context = "") {
    $activityData = ['status_id' => 'Completed'];
    if ($studyId) {
      $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($studyId);
      if ($studyNumber) {
        $activityData['subject'] = "Invited to study " . $studyNumber . " with " . $context . ".";
      }
      else {
        $activityData['subject'] = "Invited to study " . $studyId . " with " . $context . ".";
      }
    }
    else {
      $activityData['subject'] = "Invited to unknown study with " . $context . "?";
    }
    CRM_Nihrbackbone_NbrVolunteerCase::addCaseActivity($caseId, $contactId, CRM_Nihrbackbone_BackboneConfig::singleton()->getInviteProjectActivityTypeId(), $activityData);;
  }


}
