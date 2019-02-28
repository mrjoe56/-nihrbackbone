<?php
use CRM_Nihrprototype_ExtensionUtil as E;

/**
 * Page NihrProject to show the project/volunteer data
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 28 Feb 2019
 * @license AGPL-3.0
 * @errorrange 2000-2100
 */
class CRM_Nihrbackbone_Page_NihrProjectVolunteer extends CRM_Core_Page {

  private $_projectId = NULL;

  /**
   * Standard run function created when generating page with Civix
   *
   * @access public
   */
  function run() {
    $this->setPageConfiguration();
    $volunteers = [];
    try {
      $volunteers = civicrm_api3('NihrProjectVolunteer', 'get', [
        'options' => ['limit' => 0],
        'project_id' => $this->_projectId,
      ])['values'];
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    $this->createClickableColumns($volunteers);
    $this->assign('volunteers', $volunteers);
    parent::run();
  }

  /**
   * Method to some columns in the volunteer rows clickable
   *
   * @param $volunteers
   */
  private function createClickableColumns(&$volunteers) {
    foreach ($volunteers as $volunteerId => $volunteer) {
      // click on name to see contact
      if (isset($volunteer['volunteer_name']) && isset($volunteer['bioresource_id'])) {
        $volunteerUrl = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $volunteer['bioresource_id']);
        $volunteers[$volunteerId]['volunteer_name'] = '<a href="' . $volunteerUrl . '">' . $volunteer['volunteer_name'] . '</a>';
        $volunteers[$volunteerId]['bioresource_id'] = '<a href="' . $volunteerUrl . '">' . $volunteer['bioresource_id'] . '</a>';
        $volunteers[$volunteerId]['sample_id'] = '<a href="' . $volunteerUrl . '">' . $volunteer['sample_id'] . '</a>';
      }
    }
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   * @throws API_Exception
   */
  private function setPageConfiguration() {
    CRM_Utils_System::setTitle(ts('NIHR BioResource Project Volunteers'));
    try {
      $this->_projectId = CRM_Utils_Request::retrieve('pid', 'Integer');
    }
    catch (Exception $ex) {
      throw new API_Exception(E::ts('Could not retrieve pid from the request URL in ') . __METHOD__
        . E::ts(', error message: ') . $ex->getMessage(), 2000);
    }
    if (!$this->_projectId) {
      throw new API_Exception(E::ts('Could not find a project id in the request URL in ') . __METHOD__, 2001);
    }
    $this->assign('import_file_url', CRM_Utils_System::url('civicrm/nihrprototype/form/npfileimport',
      'reset=1&action=import', TRUE));
    $this->assign('import_group_url', CRM_Utils_System::url('civicrm/contact/search',
      'reset=1', TRUE));
    // get project code
    try {
      $projectCode = civicrm_api3('NihrProject', 'getvalue', [
        'id' => $this->_projectId,
        'return' => 'project_code',
      ]);
    }
    catch (CiviCRM_API3_Exception $ex) {
      $projectCode = '';
    }
    $projectUrl = CRM_Utils_System::url('civicrm/nihrprototype/form/nihrproject', 'reset=1&action=update&id=' . $this->_projectId, TRUE);
    $this->assign('project_code', '<a href="' . $projectUrl . '">' . $projectCode . '</a>');
  }

}
