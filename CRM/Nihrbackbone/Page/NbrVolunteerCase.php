<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Page NbrVolunteerCase to show the project/volunteer data
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 28 Feb 2019
 * @license AGPL-3.0
 * @errorrange 2000-2100
 */
class CRM_Nihrbackbone_Page_NbrVolunteerCase extends CRM_Core_Page {

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
      $volunteers = civicrm_api3('NbrVolunteerCase', 'get', [
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
      }
    }
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   * @throws CiviCRM_API3_Exception
   */
  private function setPageConfiguration() {
    CRM_Utils_System::setTitle(ts('NIHR BioResource Project Volunteers'));
    try {
      $this->_projectId = CRM_Utils_Request::retrieve('pid', 'Integer');
    }
    catch (Exception $ex) {
      throw new CiviCRM_API3_Exception(E::ts('Could not retrieve pid from the request URL in ') . __METHOD__
        . E::ts(', error message: ') . $ex->getMessage(), 2000);
    }
    if (!$this->_projectId) {
      throw new CiviCRM_API3_Exception(E::ts('Could not find a project id in the request URL in ') . __METHOD__, 2001);
    }
  }

}
