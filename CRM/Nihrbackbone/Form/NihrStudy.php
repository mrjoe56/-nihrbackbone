<?php

use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Page NihrStudy to show all studies and add new ones
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 25 Feb 2019
 * @license AGPL-3.0
 * @errorrange 1000-1099
 */
class CRM_Nihrbackbone_Form_NihrStudy extends CRM_Core_Form {
  private $_studyId = NULL;
  private $_currentData = [];

  /**
   * Method to build the form
   */
  public function buildQuickForm() {
    // no action if delete
    if ($this->_action != CRM_Core_Action::DELETE) {
      $this->addElements();
      if ($this->_action != CRM_Core_Action::ADD) {
        $this->assign('study_id', $this->_studyId);
      }
      $this->addButtons([
        ['type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => E::ts('Cancel')],
      ]);
      // export form elements
      parent::buildQuickForm();
    }
  }

  /**
   * Method to add form elements (for update and add)
   */
  private function addElements() {
    $this->addEntityRef('investigator_id', E::ts('Principal Investigator'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
    ], FALSE);
    $this->add('text', 'title', E::ts('Title'), [], TRUE);
    $this->add('textarea', 'description', E::ts('Description'), ['rows' => 4, 'cols' => 50], FALSE);
    $this->add('text', 'ethics_number', E::ts('Ethics Number'), [], FALSE);
    $this->addEntityRef('ethics_approved_id', ts('Ethics Approved'), [
      'entity' => 'option_value',
      'api' => [
        'params' => ['option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getEthicsApprovedOptionGroupId()],
      ],
      'select' => ['minimumInputLength' => 0],
    ]);
    $this->add('textarea', 'requirements', E::ts('Requirements'), ['rows' => 4, 'cols' => 50], FALSE);
    $this->add('datepicker', 'start_date', ts('Start Date'), ['placeholder' => ts('Start Date')],FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', ts('End Date'), ['placeholder' => ts('End Date')],FALSE, ['time' => FALSE]);
    $this->addEntityRef('centre_study_origin_id', E::ts('Centre Study Origin'), [
      'api' => ['params' => ['group' => 'Study Centres']],
    ], FALSE);
    $this->add('textarea', 'notes', E::ts('Notes'), ['rows' => 4, 'cols' => 50], FALSE);
    $this->addEntityRef('status_id', ts('Status'), [
      'entity' => 'option_value',
      'api' => [
        'params' => ['option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyStatusOptionGroupId()],
      ],
      'select' => ['minimumInputLength' => 0],
    ]);
  }

  /**
   * Method to prepare form
   *
   * @throws API_Exception
   */
  public function preProcess() {
    // retrieve study id from request if update or delete
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $studyId = CRM_Utils_Request::retrieveValue('id', 'Integer');
      if (!$studyId) {
        $studyId = CRM_Utils_Request::retrieveValue('study_id', 'Integer');
      }
    }
    $this->_studyId = $studyId;
    // if action update, retrieve current data
    if ($this->_action == CRM_Core_Action::UPDATE) {
      try {
        $this->_currentData = civicrm_api3('NihrStudy', 'getsingle', ['id' => $this->_studyId]);
      }
      catch (CiviCRM_API3_Exception $ex) {
        throw new API_Exception(E::ts('Could not get any study data for ID ') . $this->_studyId . E::ts(' in ') . __METHOD__, 1000);
      }
    }
    $this->setStatusList();
    $this->setEthicsApprovedList();
    CRM_Utils_System::setTitle(E::ts('NIHR BioResource Study'));
  }

  /**
   * Method to build study status list
   */
  private function setStatusList() {
    try {
      $studyStatuses = civicrm_api3('OptionValue', 'get', [
        'options' => ['limit' => 0],
        'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyStatusOptionGroupId(),
        'is_active' => 1,
      ]);
      foreach ($studyStatuses['values'] as $studyStatus) {
        $this->_statusList[$studyStatus['value']] = $studyStatus['label'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to build ethics approved list
   */
  private function setEthicsApprovedList() {
    try {
      $ethicsApproveds = civicrm_api3('OptionValue', 'get', [
        'options' => ['limit' => 0],
        'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getEthicsApprovedOptionGroupId(),
        'is_active' => 1,
      ]);
      foreach ($ethicsApproveds['values'] as $ethicsApproved) {
        $this->_ethicsApprovedList[$ethicsApproved['value']] = $ethicsApproved['label'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
  }

  /**
   * Method to set the default values in view and update mode
   * @return array
   */
  public function setDefaultValues() {
    $defaults = [];
    if ($this->_action == CRM_Core_Action::UPDATE) {
      if (isset($this->_currentData['created_date']) || !empty($this->_currentData['created_date'])) {
        $createdBy = "Created: " . date('d-m-Y H:i:s', strtotime($this->_currentData['created_date']));
        if (isset($this->_currentData['created_by_id']) && !empty($this->_currentData['created_by_id'])) {
          $createdBy .= ', ' . CRM_Nihrbackbone_Utils::getContactName($this->_currentData['created_by_id'], 'sort_name');
        }
        $this->assign('created_by', $createdBy);
      }
      if (isset($this->_currentData['modified_date']) || !empty($this->_currentData['modified_date'])) {
        $modifiedBy = "Modified: " . date('d-m-Y H:i:s', strtotime($this->_currentData['modified_date']));
        if (isset($this->_currentData['modified_by_id']) && !empty($this->_currentData['modified_by_id'])) {
          $modifiedBy .= ' by ' . CRM_Nihrbackbone_Utils::getContactName($this->_currentData['modified_by_id'], 'sort_name');
        }
        $this->assign('modified_by', $modifiedBy);
      }
      foreach ($this->_currentData as $key => $value) {
        $defaults[$key] = $value;
      }
      return $defaults;
    }
  }

  /**
   * Function to add validation condition rules (overrides parent function)
   *
   * @access public
   */
  public function addRules() {
    $this->addFormRule(array('CRM_Nihrbackbone_Form_NihrStudy', 'validateDates'));
    $this->addFormRule(array('CRM_Nihrbackbone_Form_NihrStudy', 'validateTitle'));
  }

  /**
   * Method to validate the fields
   * - start_date can not be later than end_date
   * - end_date can not be earlier than start_date
   *
   * @param $fields
   * @return array|bool
   */
  public static function validateDates($fields) {
    // if start_date > end_date
    if (isset($fields['start_date']) && isset($fields['end_date'])) {
      if (!empty($fields['start_date']) && !empty($fields['end_date'])) {
        try {
          $startDate = new DateTime($fields['start_date']);
        }
        catch (Exception $ex) {
          $errors['start_date'] = E::ts('Invalid start date, format not recognized');
          return $errors;
        }
        try {
          $endDate = new DateTime($fields['end_date']);
        }
        catch (Exception $ex) {
          $errors['end_date'] = E::ts('Invalid end date, format not recognized');
          return $errors;
        }
        if ($startDate > $endDate) {
          $errors['start_date'] = E::ts('Start date can not be later than end date');
          return $errors;
        }
        if ($endDate < $startDate) {
          $errors['end_date'] = E::ts('End date can not be earlier than start date');
          return $errors;
        }
      }
    }
    return TRUE;
  }

  /**
   * Method to validate title (can not already exist)
   *
   * @param $fields
   * @return array|bool
   */
  public static function validateTitle($fields) {
    try {
      // if there is at least 1 study with title
      $count = civicrm_api3('NihrStudy', 'getcount', ['title' => $fields['title']]);
      if ($count > 0) {
        // check if any other id than the one we are dealing with
        $studies = civicrm_api3('NihrStudy', 'get', [
          'return' => "id",
          'title' => $fields['title'],
          'options' => ['limit' => 0],
          'sequential' => 1,
        ]);
        foreach ($studies['values'] as $study) {
          if ($study['id'] != $fields['study_id']) {
            $errors['title'] = E::ts('There is already a study with this title, please change.');
            return $errors;
          }
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return TRUE;
  }

  /**
   * Method to process the form once it is submitted
   *
   * @throws API_Exception when error saving
   */
  public function postProcess() {
    $params = $this->exportValues(NULL, TRUE);
    $ignores = ['qfKey', 'entryURL'];
    foreach ($ignores as $ignore) {
      if (isset($params[$ignore])) {
        unset($params[$ignore]);
      }
    }
    if ($this->_action == CRM_Core_Action::UPDATE && isset($this->_submitValues['study_id'])) {
      $params['id'] = $this->_submitValues['study_id'];
    }
    try {
      civicrm_api3('NihrStudy', 'create', $params);
      if ($this->_action == CRM_Core_Action::ADD) {
        CRM_Core_Session::setStatus(E::ts('Study with title ') . $params['title'] . E::ts(' added to database.'), E::ts('Study saved'), 'success');
      }
      else {
        CRM_Core_Session::setStatus(E::ts('Study with title ') . $params['title'] . E::ts(' saved.'), E::ts('Study saved'), 'success');
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new API_Exception(E::ts('Error saving or updating NIHR study in ') . __METHOD__ . E::ts(', error message from API NihrStudy create: ') . $ex->getMessage(), 1001);
    }
    parent::postProcess();
  }
}
