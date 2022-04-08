<?php

use CRM_Nihrbackbone_ExtensionUtil as E;
/**
 * Page NbrStudy to show all studies
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 10 Feb 2020
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_Page_NbrStudy extends CRM_Core_Page {

  /**
   * Standard run function created when generating page with Civix
   *
   * @access public
   */
  function run() {
    $this->setPageConfiguration();
    $this->assign('nbr_studies', $this->getStudies());
    parent::run();
  }

  /**
   * Function to get the studies
   *
   * @return array $studies
   * @access protected
   */
  private function getStudies() {
    $studies = [];
    try {
      $apiParams = [
        'sequential' => 1,
        'campaign_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId(),
        'is_active' => 1,
        'options' => ['limit' => 0],
      ];
      $result = civicrm_api3('Campaign', 'get', $apiParams);
      foreach ($result['values'] as $apiStudy) {
        $studies[] = $this->assembleStudyRow($apiStudy);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Error getting studies with API Campaign get in ') . __METHOD__
        . E::ts(', error message: '). $ex->getMessage());
    }
    return $studies;
  }

  /**
   * Method to assemble the study row from the api result row
   *
   * @param $apiStudy
   * @return array
   */
  private function assembleStudyRow($apiStudy) {
    $study = [];
    $studyNumber = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_study_number', 'id');
    $site = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_site', 'id');
    $dataOnly = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_data_only', 'id');
    //$multiVisit = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_multiple_visits', 'id');
    $recall = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_recall', 'id');
    $sampleOnly = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_sample_only', 'id');
    $online = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_online_project', 'id');
    $blood = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_blood_required', 'id');
    $travel = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomField('nsc_travel_required', 'id');
    $pi = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_principal_investigator', 'id');
    $researcher = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCustomField('nsd_researcher', 'id');
    $elements = [
      'title' => 'name',
      'status_id' => 'status_id',
      'start_date' => 'start_date',
      $studyNumber => 'study_number',
      $site => 'site',
      $dataOnly => 'data_only',
      //$multiVisit => 'multi_visit',
      $recall => 'recall',
      $sampleOnly => 'sample_only',
      $online => 'online',
      $blood => 'blood_required',
      $travel => 'travel_required',
    ];
    foreach ($elements as $in => $out) {
      if (isset($apiStudy[$in])) {
        $study[$out] = $apiStudy[$in];
      }
    }
    $yesNos = ['recall', 'sample_only', 'data_only', 'online', 'blood_required', 'travel_required'];
    foreach ($yesNos as $yesNo) {
      if (isset($study[$yesNo]) && $study[$yesNo] == "1") {
        $study[$yesNo] = "Yes";
      }
      else {
        $study[$yesNo] = "No";
      }
    }
    if (isset($study['status_id']) && !empty($study['status_id'])) {
      $study['status'] = CRM_Nihrbackbone_Utils::getOptionValueLabel($study['status_id'], 'campaign_status');
    }
    // add pi/researcher names in one field
    $study['pi_researcher'] = "";
    if (isset($apiStudy[$pi])) {
      $study['pi_researcher'] = $apiStudy[$pi] . "/";
    }
    if (isset($apiStudy[$researcher])) {
      if (empty($study['pi_researcher'])) {
        $study['pi_researcher'] = "/" . $apiStudy[$researcher];
      } else {
        $study['pi_researcher'] .= $apiStudy[$researcher];
      }
    }
    $study['actions'] = $this->setRowActions($apiStudy);
    return $study;
  }

  /**
   * Function to set the row action urls and links for each row
   *
   * @param array $study
   * @return array $actions
   * @access protected
   */
  protected function setRowActions($study) {
    $rowActions = [];
    $csId = CRM_Nihrbackbone_Utils::getVolunteerCsId();
    $viewUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrstudy', 'reset=1&action=view&id='.
      $study['id']);
    $updateUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrstudy', 'reset=1&action=update&id='.
      $study['id']);
    $importUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/importcsvselect', 'reset=1&sid=' . $study['id'], TRUE);
    $rowActions[] = '<a class="action-item" title="Update" href="' . $updateUrl .'">' . E::ts('Edit') . '</a>';
    $rowActions[] = '<a class="action-item" title="View" href="' . $viewUrl .'">' . E::ts('View') . '</a>';
    $rowActions[] = '<a class="action-item" title="Import" href="' . $importUrl .'">' . E::ts('Import') . '</a>';
    if ($csId) {
      $volunteersUrl = CRM_Utils_System::url('civicrm/contact/search/custom' , 'reset=1&force=1&csid=' . $csId . '&sid=' . $study['id'], TRUE);
      $rowActions[] = '<a class="action-item" title="Volunteers" href="' . $volunteersUrl .'">' . E::ts('Volunteers') . '</a>';
    }
    return $rowActions;
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   */
  protected function setPageConfiguration() {
    CRM_Utils_System::setTitle(ts('NIHR BioResource studies'));
    $this->assign('add_url', CRM_Utils_System::url('civicrm/nihrbackbone/form/nbrstudy',
      'reset=1&action=add', TRUE));
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/nihrbackbone/page/nbrstudy', 'reset=1', TRUE));
  }

}
