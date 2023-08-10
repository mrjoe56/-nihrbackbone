<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NbrStudyResearcher extends CRM_Nihrbackbone_DAO_NbrStudyResearcher {

  /**
   * Method to get researchers for a study
   *
   * @param int|NULL $studyId
   * @return array
   */
  public static function getStudyResearchers(?int $studyId): array {
    $studyResearchers = [];
    if ($studyId) {
      try {
        $nbrStudyResearchers = \Civi\Api4\NbrStudyResearcher::get()
          ->addSelect('researcher_contact_id')
          ->addWhere('nbr_study_id', '=', $studyId)
          ->setCheckPermissions(FALSE)->execute();
        foreach ($nbrStudyResearchers as $nbrStudyResearcher) {
          if ($nbrStudyResearcher['researcher_contact_id']) {
            $studyResearchers[] = $nbrStudyResearcher['researcher_contact_id'];
          }
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return $studyResearchers;
  }

  /**
   * Method to save the study researcher ids and delete any redundant ones
   *
   * @param int $studyId
   * @param array $researcherIds
   * @param int $formAction
   * @return void
   */
  public static function saveStudyResearchers(int $studyId, array $researcherIds, int $formAction): void {
    if ($studyId) {
      $currentResearcherIds = self::deleteRedundantStudyResearchers($studyId, $researcherIds);
      foreach ($researcherIds as $researcherId) {
        if (!empty($researcherId) && !in_array($researcherId, $currentResearcherIds)) {
            self::createStudyResearcher($studyId, $researcherId);
        }
      }
    }
    else {
      CRM_Core_Session::setStatus("Could not save the Study Researcher(s), contact the system administrator", "Study Researcher not saved", "alert");
      Civi::log()->error("Could not save study researchers in " . __METHOD__ . ", no study id found.");
    }
  }

  /**
   * Method to create study researcher
   *
   * @param int $studyId
   * @param int $researcherId
   * @return void
   */
  public static function createStudyResearcher(int $studyId, int $researcherId): void {
    if ($studyId && $researcherId) {
      try {
        \Civi\Api4\NbrStudyResearcher::create()
          ->addValue('researcher_contact_id', $researcherId)
          ->addValue('nbr_study_id', $studyId)
          ->setCheckPermissions(FALSE)->execute();
      }
      catch (API_Exception $ex) {
        CRM_Core_Session::setStatus("Could not save the Study Researcher, contact the system administrator", "Study Researcher not saved", "alert");
        Civi::log()->error("Could not save study researcher for study ID " . $studyId . " and researcher ID " . $researcherId
          . " in " . __METHOD__ . ", error message from API4 NbrStudyResearcher create: " . $ex->getMessage());
      }
    }
  }

  /**
   * Method to get the current researchers for the study and delete redundant ones
   *
   * @param int $studyId
   * @param array $researcherIds
   * @return array
   */
  public static function deleteRedundantStudyResearchers(int $studyId, array $researcherIds): array {
    $currentResearcherIds = [];
    if ($studyId) {
      try {
        $studyResearchers = \Civi\Api4\NbrStudyResearcher::get()
          ->addSelect('*')
          ->addWhere('nbr_study_id', '=', $studyId)
          ->setCheckPermissions(FALSE)->execute();
        foreach ($studyResearchers as $studyResearcher) {
          if (!in_array($studyResearcher['researcher_contact_id'], $researcherIds)) {
            \Civi\Api4\NbrStudyResearcher::delete()
              ->addWhere('id', '=', $studyResearcher['id'])
              ->setCheckPermissions(FALSE)->execute();
          }
          else {
            $currentResearcherIds[] = $studyResearcher['researcher_contact_id'];
          }
        }
      }
      catch (API_Exception $ex) {
        Civi::log()->error("Could not delete study researcher for study Id " . $studyId . " and researcher contact ID "
          . $studyResearcher['researcher_contact_id'] . ", error from API4 NbrStudyResearcher delete: " . $ex->getMessage());
      }
    }
    return $currentResearcherIds;
  }

}
