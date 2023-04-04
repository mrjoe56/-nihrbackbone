<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NbrRecallGroup extends CRM_Nihrbackbone_DAO_NbrRecallGroup {

  /**
   * Method to check if recall group exists on case
   * @param int $caseId
   * @param string $recallGroup
   * @return bool
   */
  public static function isExistingRecallGroupOnCase(int $caseId, string $recallGroup): bool {
    if ($caseId && $recallGroup) {
      try {
        $count = \Civi\Api4\NbrRecallGroup::get()
          ->addSelect('*')
          ->addWhere('case_id', '=', $caseId)
          ->addWhere('recall_group', '=', $recallGroup)
          ->execute()->count();
        if ($count > 0) {
          return TRUE;
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return FALSE;
  }

  /**
   * Method to add a recall group to a participation case
   *
   * @param int $caseId
   * @param string $recallGroup
   * @return int|null
   */
  public static function addRecallGroupForCase(int $caseId, string $recallGroup): ?int {
    $createdId = NULL;
    if ($caseId && $recallGroup) {
      try {
        $created = \Civi\Api4\NbrRecallGroup::create()
          ->addValue('case_id', $caseId)
          ->addValue('recall_group', $recallGroup)
          ->execute()->first();
        if ($created['id']) {
          $createdId = (int) $created['id'];
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return $createdId;
  }

  /**
   * Method to find the recall groups for a case
   *
   * @param int $caseId
   * @return array
   */
  public static function getRecallGroupsForCase(int $caseId): array {
    $recallGroups = [];
    if ($caseId) {
      try {
        $results = \Civi\Api4\NbrRecallGroup::get()
          ->addSelect('*')
          ->addWhere('case_id', '=', $caseId)
          ->execute();
        foreach ($results as $result) {
          if (isset($result['recall_group'])) {
            $recallGroups[] = $result['recall_group'];
          }
        }
      }
      catch (API_Exception $ex) {
      }
    }
    return $recallGroups;
  }
  public static function addRecallGroupsToForm(int $caseId, CRM_Core_Form $form): void {
    if ($caseId) {
      $defaults = [];
      $recallGroups = self::getRecallGroupsForCase($caseId);
      $counter = 0;
      foreach ($recallGroups as $recallGroup) {
        $counter++;
        $elementName = "recall_group_" . $counter;
        $form->add('text', $elementName, E::ts("Recall Group"));
        $defaults[$elementName] = $recallGroup;
      }
      if (!empty($defaults)) {
        $form->setDefaultValues($defaults);
      }
      for($x=0; $x <=5; $x++) {
        $counter++;
        $form->add('text', 'recall_group_' . $counter, E::ts("Recall Group"));
      }
      $form->assign('recall_group_counter', $counter);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/Form/nbr_recall_groups.tpl',]);
    }
  }
}
