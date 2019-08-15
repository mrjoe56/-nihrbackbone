<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Page NihrStudy to show all studies and add new ones
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 25 Feb 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_Page_NihrStudy extends CRM_Core_Page {

  /**
   * Standard run function created when generating page with Civix
   *
   * @access public
   */
  function run() {
    $this->setPageConfiguration();
    $this->assign('studies', $this->getStudies());
    parent::run();
  }

  /**
   * Function to get the study data
   *
   * @return array $rules
   * @access protected
   */
  private function getStudies() {
    $studies = [];
    try {
      $studies = civicrm_api3('NihrStudy', 'get', [])['values'];
      foreach ($studies as $studyId => $study) {
        // set study row
        $studies[$studyId] = $this->enhanceStudyRow($study);
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      Civi::log()->error(E::ts('Error getting studies with API NihrStudy get in ') . __METHOD__
        . E::ts(', error message: '). $ex->getMessage());
    }
    return $studies;
  }

  /**
   * Method to enhance the study row
   *
   * @param $study
   * @return mixed
   */
  private function enhanceStudyRow($study) {
    // get relevant contact names
    $contactNames = ['investigator_id', 'centre_study_origin_id', 'created_by_id', 'modified_by_id'];
    foreach ($contactNames as $contactName) {
      $rowElement = str_replace('_id', '', $contactName);
      if (isset($study[$contactName]) && !empty($study[$contactName])) {
        $result = CRM_Nihrbackbone_Utils::getContactName($study[$contactName], 'display_name');
        if ($result) {
          $study[$rowElement] = $result;
        }
      }
    }
    // get relevant option value labels
    $optionValues = [
      'ethics_approved_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getEthicsApprovedOptionGroupId(),
      'status_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyStatusOptionGroupId(),
      ];
    foreach ($optionValues as $optionValue => $optionGroup) {
      $rowElement = str_replace('_id', '', $optionValue);
      if (isset($study[$optionValue]) && !empty($study[$optionValue])) {
        $study[$rowElement] = CRM_Nihrbackbone_Utils::getOptionValueLabel($study[$optionValue], $optionGroup);
      }
    }
    $study['actions'] = $this->setRowActions($study);
    $this->createClickableColumns($study);
    return $study;
  }

  /**
   * Method to some columns in the study rows clickable
   *
   * @param $study
   */
  private function createClickableColumns(&$study) {
    $clickableContacts = ['investigator_id', 'centre_study_origin_id', 'created_by_id', 'modified_by_id'];
    foreach ($clickableContacts as $clickableContact) {
      if (isset($study[$clickableContact]) && !empty($study[$clickableContact])) {
        $element = str_replace('_id', '', $clickableContact);
        $contactUrl = CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $study[$clickableContact]);
        $study[$element] = '<a href="' . $contactUrl . '">' . $study[$element] . '</a>';

      }
    }
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
    $updateUrl = CRM_Utils_System::url('civicrm/nihrbackbone/form/nihrstudy', 'reset=1&action=update&id='.
      $study['id']);
    $projectUrl = CRM_Utils_System::url('http://localhost/nihrdev/index.php?q=civicrm/campaign', 'reset=1', TRUE);
    $rowActions[] = '<a class="action-item" title="Update" href="' . $updateUrl .'">' . E::ts('Edit') . '</a>';
    $rowActions[] = '<a class="action-item" title="Projects" href="' . $projectUrl . '">' . E::ts('Project(s)') . '</a>';
    return $rowActions;
  }

  /**
   * Function to set the page configuration
   *
   * @access protected
   */
  protected function setPageConfiguration() {
    CRM_Utils_System::setTitle(ts('NIHR BioResource studies'));
    $this->assign('add_url', CRM_Utils_System::url('civicrm/nihrbackbone/form/nihrstudy',
      'reset=1&action=add', TRUE));
    $session = CRM_Core_Session::singleton();
    $session->pushUserContext(CRM_Utils_System::url('civicrm/nihrbackbone/page/nihrstudy', 'reset=1', TRUE));
    $this->assign('helpTxt', E::ts('The existing NIHR BioResource studies are listed below. You can manage or add a study.'));
  }

}
