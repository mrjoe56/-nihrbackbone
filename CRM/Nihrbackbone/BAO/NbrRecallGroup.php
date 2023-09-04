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
          ->setCheckPermissions(FALSE)->execute()->count();
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
          ->setCheckPermissions(FALSE)->execute()->first();
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
          ->setCheckPermissions(FALSE)->execute();
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

  /**
   * Method to add the recall groups to the Case View Form
   *
   * @param int|null $caseId
   * @param CRM_Core_Form $form
   * @return void
   */
  public static function addRecallGroupsToCaseView(?int $caseId, CRM_Core_Form $form): void {
    if ($caseId) {
      $counter = 0;
      $recallGroups = self::getRecallGroupsForCase($caseId);
      $defaults = [];
      if (empty($recallGroups)) {
        $form->add('text', 'recall_group_empty', E::ts("Recall Group"));
      }
      foreach ($recallGroups as $recallGroup) {
        $counter++;
        $elementName = "recall_group_" . $counter;
        $form->add('text', $elementName, E::ts("Recall Group ") . $counter);
        $defaults[$elementName] = $recallGroup;
      }
      if (!empty($defaults)) {
        $form->setDefaults($defaults);
      }
      self::addFormElements($form);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/Form/nbr_recall_groups_case_view.tpl',]);
    }
  }

  /**
   * Add recall groups to custom form
   *
   * @param int $caseId
   * @param CRM_Core_Form $form
   * @return void
   */
  public static function addRecallGroupsToCustomForm(int $caseId, CRM_Core_Form &$form): void {
    if ($caseId) {
      $counter = 0;
      $recallGroups = self::getRecallGroupsForCase($caseId);
      $defaults = [];
      foreach ($recallGroups as $recallGroup) {
        $counter++;
        $elementName = "recall_group_" . $counter;
        $form->add('text', $elementName, E::ts("Recall Group ") . $counter);
        $defaults[$elementName] = $recallGroup;
      }
      if (!empty($defaults)) {
        $form->setDefaults($defaults);
      }
      // now add 5 empty recall groups
      for ($x=0; $x<5; $x++) {
        $counter++;
        $elementName = 'recall_group_' . $counter;
        $form->add('text', $elementName, E::ts("Recall Group ") . $counter);
      }
      self::addFormElements($form);
      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/Form/nbr_recall_groups_custom_form.tpl',]);
    }
  }

  /**
   * Method to add all elements to form
   *
   * @param CRM_Core_Form $form
   * @return void
   */
  private static function addFormElements(CRM_Core_Form &$form) {
    $elementNames = [];
    foreach ($form->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    $form->assign('elementNames', $elementNames);
  }

  /**
   * Method to process postProcess form for case custom data
   *
   * @param CRM_Core_Form $form
   * @return void
   */
  public static function postProcess(CRM_Core_Form &$form) {
    $submitValues = $form->getVar('_submitValues');
    $caseId = $form->getVar('_entityID');
    if ($caseId && $submitValues) {
      foreach ($submitValues as $submitKey => $submitValue) {
        if (strpos($submitKey, 'recall_group_') !== FALSE) {
          if (!empty($submitValue)) {
            if (!self::isExistingRecallGroupOnCase($caseId, $submitValue)) {
              self::addRecallGroupForCase($caseId, $submitValue);
            }
          }
        }
      }
      self::deleteRedundantRecallGroupsForCase($caseId, $submitValues);
    }
  }

  /**
   * Method to delete redundant recall groups for case that are still in the DB
   *
   * @param int $caseId
   * @param array $submitValues
   * @return void
   */
  public static function deleteRedundantRecallGroupsForCase(int $caseId, array $submitValues): void {
    if ($caseId && !empty($submitValues)) {
      $currentRecallGroups = self::getRecallGroupsForCase($caseId);
      $formRecallGroups = [];
      foreach ($submitValues as $submitKey => $submitValue) {
        if (strpos($submitKey, 'recall_group_') !== FALSE) {
          if (!empty($submitValue)) {
            $formRecallGroups[] = $submitValue;
          }
        }
      }
      foreach ($currentRecallGroups as $currentRecallGroup) {
        if (!in_array($currentRecallGroup, $formRecallGroups)) {
          try {
            \Civi\Api4\NbrRecallGroup::delete()
              ->addWhere('case_id', '=', $caseId)
              ->addWhere('recall_group', '=', $currentRecallGroup)
              ->setCheckPermissions(FALSE)->execute();
          }
          catch (API_Exception $ex) {
          }
        }
      }
    }
  }

}
