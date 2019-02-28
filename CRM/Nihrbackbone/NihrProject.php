<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource NIHR BioResource Project
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 27 Feb 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NihrProject {

  private $_projectCampaignTypeId = NULL;

  /**
   * CRM_Nihrbackbone_NihrProject constructor.
   */
  public function __construct() {
    $this->_projectCampaignTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCampaignTypeId();
  }

  /**
   * Method to check if campaign is a project
   *
   * @param $campaignId
   * @return bool
   */
  public function isNihrProject($campaignId) {
    try {
      $campaignTypeId = (int) civicrm_api3('Campaign', 'getvalue', [
        'id' => $campaignId,
        'return' => 'campaign_type_id',
      ]);
      if ($campaignTypeId == $this->_projectCampaignTypeId) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->warning(E::ts('Unexpected error retrieving campaign data with Campaign API in ') . __METHOD__
        . ', error message from API Campaign getvalue: ' . $ex->getMessage());
    }
    return FALSE;
  }

  /**
   * Method to process the buildForm hook for CRM_Nihrbackbone_NihrProject
   *
   * @param $form
   */
  public function buildForm(&$form) {
    // introduce select for study, retrieve current value and use as default
    // done because I can not introduce a select for NihrStudy as custom field so introduced
    // hidden (inactive) custom field to store study_id in
    $action = $form->getVar('_action');
    if ($action == CRM_Core_Action::UPDATE || $action == CRM_Core_Action::ADD) {
      $defaultValues = $form->getVar('_defaultValues');
      if (isset($defaultValues['campaign_type_id']) && $defaultValues['campaign_type_id'] == $this->_projectCampaignTypeId) {
        $projectId = $form->getVar('_campaignId');
        $form->add('select', 'npd_ui_project_study_id', E::ts('NIHR BioResource Study'), $this->getStudyFormList(), FALSE);
        // get the study for the project and set default
        $studyId = $this->getProjectStudyId($projectId);
        if ($studyId) {
          $defaults['npd_ui_project_study_id'] = $studyId;
          $form->setDefaults($defaults);
        }
        CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/Form/NihrProjectStudyField.tpl']);
      }
    }
  }

  /**
   * Method to process the postProcess hook for CRM_Nihrbackbone_NihrProject
   * @param $form
   */
  public function postProcess(&$form) {
    // set study id if campaign is project
    // done because I can not introduce a select for NihrStudy as custom field so introduced
    // hidden (inactive) custom field to store study_id in
    $action = $form->getVar('_action');
    if ($action == CRM_Core_Action::UPDATE || $action == CRM_Core_Action::ADD) {
      $submitValues = $form->getVar('_submitValues');
      if (isset($submitValues['campaign_type_id']) && $submitValues['campaign_type_id'] == $this->_projectCampaignTypeId) {
        $projectId = $form->getVar('_campaignId');
        if (isset($submitValues['npd_ui_project_study_id'])) {
          Civi::log()->debug('study is ' . $submitValues['npd_ui_project_study_id']);
          $this->setProjectStudyId($submitValues['npd_ui_project_study_id'], $projectId);
        }
      }
    }
  }

  /**
   * Method to get the active studies for a form list
   *
   * @return array
   */
  private function getStudyFormList() {
    $result = [];
    try {
      $studies = civicrm_api3('NihrStudy', 'get', [
        'options' => ['limit' => 0],
      ]);
      foreach ($studies['values'] as $studyId => $study) {
        $result[$studyId] = $study['title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to get the researchers on a project
   *
   * @param $projectId
   * @return array
   */
  public function getProjectResearchers($projectId) {
    $result = [];
    try {
      $researchers = civicrm_api3('NihrCampaignResearcher', 'get', [
        'sequential' => 1,
        'project_id' => $projectId,
        'options' => ['limit' => 0],
      ]);
      foreach ($researchers['values'] as $researcher) {
        $result[] = $researcher['researcher_id'];
      }
    } catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
  }

  /**
   * Method to determine if the campaign is a NIHR project
   *
   * @param $campaignId
   * @return bool
   */
  public function isProjectCampaign($campaignId) {
    try {
      $campaignTypeId = (int) civicrm_api3('Campaign', 'getvalue', [
        'id' => $campaignId,
        'return' => 'campaign_type_id',
      ]);
      if ($campaignTypeId && $campaignTypeId == $this->_projectCampaignTypeId) {
        return TRUE;
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return FALSE;
  }

  /**
   * Method to get the study id of a project
   * 
   * @param $projectId
   * @return bool|string|null
   */
  public function getProjectStudyId($projectId) {
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectDataCustomGroup('table_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectStudyCustomField('column_name');
    $query = "SELECT " . $studyColumn . " FROM " . $tableName . " WHERE entity_id = %1";
    $studyId = CRM_Core_DAO::singleValueQuery($query, [1 => [$projectId, 'Integer']]);
    if ($studyId) {
      return $studyId;
    }
    return FALSE;
  }

  /**
   * Method to set the study id in a project
   *
   * @param $studyId
   * @param $projectId
   * @return bool
   */
  public function setProjectStudyId($studyId, $projectId) {
    Civi::log()->debug('in set functie study is ' . $studyId . ' en project is ' . $projectId);
    $tableName = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectDataCustomGroup('table_name');
    $studyColumn = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectStudyCustomField('column_name');
    $query = "UPDATE " . $tableName . " SET " . $studyColumn . " = %1 WHERE entity_id = %2";
    Civi::log()->debug('query is ' . $query);
    try {
      CRM_Core_DAO::executeQuery($query, [
        1 => [$studyId, 'Integer'],
        2 => [$projectId, 'Integer'],
      ]);
      return TRUE;
    }
    catch (Exception $ex) {
      Civi::log()->error(E::ts('Error when updating project with ID ') . $projectId . E::ts(' with study becoming ')
        . $studyId . E::ts(', error from CRM_Core_DAO::executeQuery: '). $ex->getMessage() . E::ts(' in ') . __METHOD__);
      return FALSE;
    }
  }

}
