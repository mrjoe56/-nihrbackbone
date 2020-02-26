<?php
use CRM_Nihrbackbone_ExtensionUtil as E;
/**
 * Page NbrProject to show all projects (within a study
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 10 Feb 2020
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_Page_NbrProject extends CRM_Core_Page {

  private $_studyId = NULL;

  /**
   * Standard run function created when generating page with Civix
   *
   * @access public
   */
  function run() {
    $this->setPageConfiguration();
    $this->assign('nbr_projects', $this->getProjects());
    parent::run();
  }

  /**
   * Function to get the project(s) in the study if not empty (else all projects)
   *
   * @return array $projects
   * @access protected
   */
  private function getProjects() {
    $projects = [];
    $studyCustomFieldId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('nd_study_id', 'id');
    try {
      $apiParams = [
        'sequential' => 1,
        'campaign_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCampaignTypeId(),
        'is_active' => 1,
      ];
      if ($this->_studyId) {
        $apiParams[$studyCustomFieldId] = $this->_studyId;
      }
      $result = civicrm_api3('Campaign', 'get', $apiParams);
      foreach ($result['values'] as $apiProject) {
        $projects[] = $this->assembleProjectRow($apiProject);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Error getting projects with API Campaign get in ') . __METHOD__
        . E::ts(', error message: '). $ex->getMessage());
    }
    return $projects;
  }

  /**
   * Method to assemble the project row from the api result row
   *
   * @param $apiProject
   * @return array
   */
  private function assembleProjectRow($apiProject) {
    $project = [];
    $site = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_site', 'id');
    $dataOnly = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_data_only', 'id');
    $multiVisit = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_multiple_visits', 'id');
    $sampleOnly = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_sample_only', 'id');
    $online = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_online_project', 'id');
    $nurse = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_primary_nurse', 'id');
    $blood = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_blood_required', 'id');
    $travel = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_travel_required', 'id');
    $elements = [
      'title' => 'name',
      'status_id' => 'status_id',
      'start_date' => 'start_date',
      $site => 'site',
      $dataOnly => 'data_only',
      $multiVisit => 'multi_visit',
      $sampleOnly => 'sample_only',
      $online => 'online',
      $nurse => 'primary_nurse',
      $blood => 'blood_required',
      $travel => 'travel_required',
    ];
    foreach ($elements as $in => $out) {
      if (isset($apiProject[$in])) {
        $project[$out] = $apiProject[$in];
      }
    }
    $yesNos = ['sample_only', 'data_only', 'multi_visit', 'online', 'blood_required', 'travel_required'];
    foreach ($yesNos as $yesNo) {
      if (isset($project[$yesNo]) && $project[$yesNo] == "1") {
        $project[$yesNo] = "Yes";
      }
      else {
        $project[$yesNo] = "No";
      }
    }
    if (isset($project['status_id']) && !empty($project['status_id'])) {
      $project['status'] = CRM_Nihrbackbone_Utils::getOptionValueLabel($project['status_id'], 'campaign_status');
    }
    $project['actions'] = $this->setRowActions($apiProject);
    return $project;
  }

  /**
   * Function to set the row action urls and links for each row
   *
   * @param array $project
   * @return array $actions
   * @access protected
   */
  protected function setRowActions($project) {
    $rowActions = [];
    $csId = CRM_Nihrbackbone_Utils::getVolunteerCsId();
    if ($csId) {
      $volunteersUrl = CRM_Utils_System::url('civicrm/contact/search/custom' , 'reset=1&csid=' . $csId, TRUE);
      $rowActions[] = '<a class="action-item" title="Volunteers" href="' . $volunteersUrl .'">' . E::ts('Volunteers') . '</a>';
    }
    $viewUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrproject', 'reset=1&action=view&id='.
      $project['id']);
    $updateUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrproject', 'reset=1&action=update&id='.
      $project['id']);
    $rowActions[] = '<a class="action-item" title="Update" href="' . $updateUrl .'">' . E::ts('Edit') . '</a>';
    $rowActions[] = '<a class="action-item" title="View" href="' . $viewUrl .'">' . E::ts('View') . '</a>';
    return $rowActions;
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   */
  protected function setPageConfiguration() {
    $title = E::ts("NIHR BioResource projects");
    $studyId = CRM_Utils_Request::retrieveValue('sid', "Integer");
    if ($studyId) {
      $this->_studyId = $studyId;
      $title .= " in study " . CRM_Nihrbackbone_BAO_NihrStudy::getStudyNumber($this->_studyId);
    }
    CRM_Utils_System::setTitle(ts('NIHR BioResource projects'));
    $this->assign('add_url', CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrproject',
      'reset=1&action=add', TRUE));
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/nihrbackbone/page/nbrproject', 'reset=1', TRUE));
  }

}
