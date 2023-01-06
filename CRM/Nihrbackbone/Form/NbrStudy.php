<?php

use CRM_Nihrbackbone_ExtensionUtil as E;
/**
 * Form NbrStudy to view/edit/create studies
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 12 Feb 2020
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_Form_NbrStudy extends CRM_Core_Form {

  private $_studyId = NULL;
  private $_studyData = [];
  private $_customFieldIdsAndColumns = [];
  private $_genderList = [];
  private $_campaignStatusList = [];
  /**
   * Method to add the form elements
   */
  public function buildQuickForm() {
    $this->add('text', 'nsd_study_number', E::ts('Study Number'), [
      'disabled' => 'disabled',
      'placeholder' => 'generated by the system',
    ], FALSE);
    $this->add('hidden', 'study_id');
    // add elements for add or update
    if ($this->_action == CRM_Core_Action::UPDATE || $this->_action == CRM_Core_Action::ADD) {
      $this->addElements();
      $this->addButtons([
        ['type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE],
        ['type' => 'cancel', 'name' => E::ts('Cancel')],
      ]);
    }
    if ($this->_action == CRM_Core_Action::VIEW) {
      $this->addViewElements();
      $this->addButtons([
        ['type' => 'cancel', 'name' => E::ts('Done'), 'isDefault' => TRUE],
      ]);
    }
    // check the study clone information if relevant
    if (class_exists('CRM_Nbrclonestudy_BAO_NbrStudyClone')) {
      $this->assign('clone_of', CRM_Nbrclonestudy_BAO_NbrStudyClone::setCloneOfStudyForm((int) $this->_studyId));
      $this->assign('has_clones', CRM_Nbrclonestudy_BAO_NbrStudyClone::setHasClonesStudyForm((int) $this->_studyId));
    }
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Add elements for add or update action
   */
  private function addElements() {
    $this->add('text', 'title', E::ts('Name'), [], TRUE);
    $this->add('select', 'status_id', E::ts('Status'), $this->_campaignStatusList,TRUE, [
      'class' => 'crm-select2',
      'placeholder' => '- select status -',
      ]);
    $this->add('datepicker', 'start_date', E::ts('Start Date'), [],TRUE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', E::ts('End Date'), [],FALSE, ['time' => FALSE]);
    $this->addEntityRef('nsd_researcher', E::ts('Researcher'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
      'placeholder' => '- select researcher -',
    ], FALSE);
    $this->addEntityRef('nsd_centre_origin', E::ts('Centre of Origin'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_centre']],
      'placeholder' => '- select centre -',
    ], TRUE);
    $this->addEntityRef('nsd_site', E::ts('Site'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_site']],
      'placeholder' => '- select site -',
    ], FALSE);
    $this->addEntityRef('nsd_panel', E::ts('Panel(s)'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_panel']],
      'placeholder' => '- select panel(s) -',
      'multiple' => TRUE,
    ], FALSE);
    $this->addEntityRef('nsd_principal_investigator', E::ts('Principal Investigator'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
      'placeholder' => '- select investigator -',
    ], FALSE);
    $this->add('wysiwyg', 'nsd_scientific_info', E::ts('Scientific Information'), ['rows' => 4, 'cols' => 100], FALSE);
    $this->add('text', 'nsd_lay_title', E::ts('Lay title'), ['size' => 100], FALSE);
    $this->add('wysiwyg', 'nsd_lay_summary', E::ts('Lay summary'), ['rows' => 4, 'cols' => 100], FALSE);
    $this->add('wysiwyg', 'nsd_study_outcome', E::ts('Study outcome'), ['rows' => 4, 'cols' => 100], FALSE);
    $this->add('text', 'nsd_study_long_name', E::ts("Long Name"), ['size' => 100], FALSE);
    $this->add('text', 'nsd_ethics_number', E::ts("Ethics Number"), [], FALSE);
    $this->add('advcheckbox', 'nsd_ethics_approved', E::ts('Ethics approved?'), [], FALSE);
    $this->add('textarea', 'nsd_study_notes', E::ts('Notes'), ['rows' => 4, 'cols' => 100], FALSE);
    $this->add('advcheckbox', 'nsd_prevent_upload_portal', E::ts('Prevent upload to portal?'), [], FALSE);
    $this->add('advcheckbox', 'nsd_recall', E::ts('Recall: Face-to-Face'), [], FALSE);
    $this->add('advcheckbox', 'nsd_online_study', E::ts('Recall: Online'), [], FALSE);
    $this->add('advcheckbox', 'nsd_sample_only', E::ts('Stored Sample'), [], FALSE);
    $this->add('advcheckbox', 'nsd_data_only', E::ts('Data'), [], FALSE);
    $this->add('advcheckbox', 'nsd_commercial', E::ts('Commercial'), [], FALSE);
    $this->addEntityRef('nsc_panel', E::ts('Panel(s)'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_panel']],
      'placeholder' => '- select panel(s) -',
      'multiple' => TRUE,
    ], FALSE);
    $this->add('select', 'nsc_gender_id', E::ts('Gender'), $this->_genderList,FALSE, [
      'class' => 'crm-select2',
      'placeholder' => '- select gender -']);
    $this->add('advcheckbox', 'nsc_blood_required', E::ts('Blood required?'), [], FALSE);
    $this->add('advcheckbox', 'nsc_drug_required', E::ts('Drugs required?'), [], FALSE);
    $this->add('advcheckbox', 'nsc_mri_required', E::ts('MRI required?'), [], FALSE);
    $this->add('advcheckbox', 'nsc_travel_required', E::ts('Travel required?'), [], FALSE);
    $this->addEntityRef('nsc_ethnicity_id', E::ts('Ethnicity'), [
      'entity' => 'option_value',
      'api' => ['params' => ['option_group_id' => 'nihr_ethnicity']],
      'multiple' => TRUE,
      'placeholder' => '- select ethnicity(ies) -'
    ], FALSE);
    $this->add('text', 'nsc_age_from', E::ts("Age from"), [], FALSE);
    $this->add('text', 'nsc_age_to', E::ts("Age to"), [], FALSE);
    $this->add('text', 'nsc_bmi_from', E::ts("BMI from"), [], FALSE);
    $this->add('text', 'nsc_bmi_to', E::ts("BMI to"), [], FALSE);

  }
  /**
   * Add elements for view action
   */
  private function addViewElements() {
    $this->add('text', 'title', E::ts('Name'), ['disabled' => 'disabled'], TRUE);
    $this->add('select', 'status_id', E::ts('Status'), $this->_campaignStatusList,TRUE, [
      'class' => 'crm-select2',
      'placeholder' => '- select status -',
      'disabled' => 'disabled',
    ]);
    $this->add('datepicker', 'start_date', E::ts('Start Date'), ['disabled' => 'disabled'],TRUE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', E::ts('End Date'), ['disabled' => 'disabled'],FALSE, ['time' => FALSE]);
    $this->addEntityRef('nsd_researcher', E::ts('Researcher'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
      'placeholder' => '- select researcher -',
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('textarea', 'nsd_scientific_info', E::ts('Scientific Information'), [
      'rows' => 4,
      'cols' => 100,
      'disabled' => 'disabled',
      ], FALSE);
    $this->add('text', 'nsd_lay_title', E::ts('Lay title'), ['size' => 100,'disabled' => 'disabled'], FALSE);
    $this->add('textarea', 'nsd_lay_summary', E::ts('Lay summary'), [
      'rows' => 4,
      'cols' => 100,
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('textarea', 'nsd_study_outcome', E::ts('Study outcome'), [
      'rows' => 4,
      'cols' => 100,
      'disabled' => 'disabled',
    ], FALSE);
    $this->addEntityRef('nsd_centre_origin', E::ts('Centre of Origin'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_centre']],
      'placeholder' => '- select centre -',
      'disabled' => 'disabled',
    ], FALSE);
    $this->addEntityRef('nsd_site', E::ts('Site'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_site']],
      'placeholder' => '- select site -',
      'disabled' => 'disabled',
    ], FALSE);
    $this->addEntityRef('nsd_panel', E::ts('Panel(s)'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_panel']],
      'placeholder' => '- select panel(s) -',
      'disabled' => 'disabled',
      'multiple' => TRUE,
    ], FALSE);
    $this->add('text', 'nsd_study_long_name', E::ts("Long Name"), [
      'size' => 100,
      'disabled' => 'disabled',
      ], FALSE);
    $this->add('text', 'nsd_ethics_number', E::ts("Ethics Number"), [
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('advcheckbox', 'nsc_ethics_approved', E::ts('Ethics approved?'), [
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('textarea', 'nsd_study_notes', E::ts('Notes'), [
      'rows' => 4,
      'cols' => 100,
      'disabled' => 'disabled',
      ], FALSE);
    $this->add('advcheckbox', 'nsd_prevent_upload_portal', E::ts('Prevent upload to portal?'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsd_commercial', E::ts('Commercial'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsd_recall', E::ts('Recall: Face-tot-Face'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsd_sample_only', E::ts('Stored Sample'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsd_data_only', E::ts('Data'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsd_online_study', E::ts('Recall: Online'), ['disabled' => 'disabled'], FALSE);
    $this->addEntityRef('nsc_panel', E::ts('Panel(s)'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_panel']],
      'placeholder' => '- select panel(s) -',
      'multiple' => TRUE,
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('select', 'nsc_gender_id', E::ts('Gender'), $this->_genderList,FALSE, [
      'class' => 'crm-select2',
      'placeholder' => '- select gender -',
      'disabled' => 'disabled',
      ]);
    $this->add('advcheckbox', 'nsc_blood_required', E::ts('Blood required?'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsc_drug_required', E::ts('Drugs required?'), ['disabled' => 'disabled'], FALSE);    $this->add('advcheckbox', 'nsc_blood_required', E::ts('Blood required?'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsc_mri_required', E::ts('MRI required?'), ['disabled' => 'disabled'], FALSE);    $this->add('advcheckbox', 'nsc_blood_required', E::ts('Blood required?'), ['disabled' => 'disabled'], FALSE);
    $this->add('advcheckbox', 'nsc_travel_required', E::ts('Travel required?'), ['disabled' => 'disabled'], FALSE);
    $this->addEntityRef('nsc_ethnicity_id', E::ts('Ethnicity'), [
      'entity' => 'option_value',
      'api' => ['params' => ['option_group_id' => 'nihr_ethnicity']],
      'multiple' => TRUE,
      'placeholder' => '- select ethnicity(ies) -',
      'disabled' => 'disabled',
    ], FALSE);
    $this->add('text', 'nsc_age_from', E::ts("Age from"), ['disabled' => 'disabled'], FALSE);
    $this->add('text', 'nsc_age_to', E::ts("Age to"), ['disabled' => 'disabled'], FALSE);
    $this->add('text', 'nsc_bmi_from', E::ts("BMI from"), ['disabled' => 'disabled'], FALSE);
    $this->add('text', 'nsc_bmi_to', E::ts("BMI to"), ['disabled' => 'disabled'], FALSE);
  }

  /**
   * Method to set defaults for add, edit and view mode
   *
   * @return array|NULL|void
   */
  public function setDefaultValues() {
    $defaults = ['study_id' => $this->_studyId];
    switch ($this->_action) {
      // status pending when adding
      case CRM_Core_Action::ADD:
        $defaults['status_id'] = CRM_Nihrbackbone_BackboneConfig::singleton()->getPendingStudyStatus();
        break;
      case CRM_Core_Action::UPDATE:
      case CRM_Core_Action::VIEW:
        foreach ($this->_studyData as $key => $value) {
          if ($key == "nsd_prevent_upload_portal" && is_array($value)) {
            foreach ($value as $preventValue) {
              if ($preventValue == "1") {
                $value = "1";
              }
            }
          }
          $defaults[$key] = $value;
        }
        break;
    }
    return $defaults;
  }

  /**
   * Overridden parent method to add validation rules
   *
   */
  public function addRules() {
    $this->addFormRule(['CRM_Nihrbackbone_Form_NbrStudy', 'validateStartEndDate']);
    $this->addFormRule(['CRM_Nihrbackbone_Form_NbrStudy', 'validateFromTo']);
    $this->addFormRule(['CRM_Nihrbackbone_Form_NbrStudy', 'validateStudyType']);
  }

  /**
   * Method to validate if study types are exclusive
   * see https://www.wrike.com/open.htm?id=806555844
   *
   * @param $fields
   * @return array|bool
   */
  public static function validateStudyType($fields) {
    $availableStudyTypes = [
      'nsd_recall',
      'nsd_sample_only',
      'nsd_data_only',
      'nsd_online_study',
    ];
    $atleastOneTypeSelected = false;
    foreach($availableStudyTypes as $studyType) {
      if (isset($fields[$studyType]) && $fields[$studyType]) {
        $atleastOneTypeSelected = true;
        break;
      }
    }
    if (!$atleastOneTypeSelected) {
      foreach($availableStudyTypes as $studyType) {
        $errors[$studyType] = E::ts('Select at least one study type.');
      }
    }

    // if sample only is on make sure face to face, data only and online only are not also on
    if (isset($fields['nsd_sample_only']) && $fields['nsd_sample_only']) {
      if (isset($fields['nsd_recall']) && $fields['nsd_recall']) {
        $errors['nsd_recall'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_online_study']) && $fields['nsd_online_study']) {
        $errors['nsd_online_study'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_data_only']) && $fields['nsd_data_only']) {
        $errors['nsd_data_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
    }
    // if data only on make sure face to face, sample only and online only are not also on
    if (isset($fields['nsd_data_only']) && $fields['nsd_data_only']) {
      if (isset($fields['nsd_recall']) && $fields['nsd_recall']) {
        $errors['nsd_recall'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_online_study']) && $fields['nsd_online_study']) {
        $errors['nsd_online_study'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_sample_only']) && $fields['nsd_sample_only']) {
        $errors['nsd_sample_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
    }
    // if face to face is on make sure sample/data and online only are not also on
    if (isset($fields['nsd_recall']) && $fields['nsd_recall']) {
      if (isset($fields['nsd_sample_only']) && $fields['nsd_sample_only']) {
        $errors['nsd_sample_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_data_only']) && $fields['nsd_data_only']) {
        $errors['nsd_data_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_online_study']) && $fields['nsd_online_study']) {
        $errors['nsd_online_study'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
    }
    // if online only on make sure sample/data and face to face are not also on
    if (isset($fields['nsd_online_study']) && $fields['nsd_online_study']) {
      if (isset($fields['nsd_sample_only']) && $fields['nsd_sample_only']) {
        $errors['nsd_sample_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_data_only']) && $fields['nsd_data_only']) {
        $errors['nsd_data_only'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
      if (isset($fields['nsd_recall']) && $fields['nsd_recall']) {
        $errors['nsd_recall'] = E::ts("Sample/data only, face-to-face and online are exclusive study types, more than one is not allowed.");
      }
    }
    if (!empty($errors)) {
      return $errors;
    }
    return TRUE;
  }

  /**
   * Method to validate if to is not bigger than from for age and bmi
   *
   * @param $fields
   * @return bool|array
   */
  public static function validateFromTo($fields) {
    $errors = [];
    $validates = ['nsc_age', 'nsc_bmi'];
    foreach ($validates as $validate) {
      $toName = $validate . '_to';
      $fromName = $validate . '_from';
      if (isset($fields[$toName]) && !empty($fields[$toName])) {
        if (!empty($fields[$fromName]) && $fields[$toName] < $fields[$fromName]) {
          $errors[$toName] = E::ts('To can not be smaller than from.');
        }
      }
    }
    if (!empty($errors)) {
      return $errors;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Method to validate if end date is not before start date
   *
   * @param $fields
   * @return bool|array
   */
  public static function validateStartEndDate($fields) {
    if (isset($fields['end_date']) && !empty($fields['end_date'])) {
      $endDate = date("Ymd", strtotime($fields['end_date']));
      $startDate = date("Ymd", strtotime($fields['start_date']));
      if ($endDate < $startDate) {
        $errors['end_date'] = E::ts('End Date can not be before Start Date.');
        return $errors;
      }
    }
    return TRUE;
  }

  /**
   * Method to prepare the form
   *
   * @throws
   */
  public function preProcess() {
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url("studies-list", "", TRUE));
    $this->setCustomFieldIdsAndColumns();
    switch ($this->_action) {
      case CRM_Core_Action::ADD:
        $title = E::ts("New Study");
        break;
      case CRM_Core_Action::UPDATE:
        $title = E::ts("Edit Study");
        break;
      case CRM_Core_Action::VIEW:
        $title = E::ts("View Study");
        break;
      default:
        $title = "Study";
        break;
    }
    $this->_campaignStatusList = CRM_Nihrbackbone_Utils::getOptionValueList('campaign_status');
    $this->_genderList = CRM_Nihrbackbone_Utils::getOptionValueList('gender');
    CRM_Utils_System::setTitle($title);
    // get study id from request
    $studyId = CRM_Utils_Request::retrieveValue("id", "Integer");
    if ($studyId) {
      $this->_studyId = $studyId;
      try {
        $this->_studyData = civicrm_api3("Campaign", "getsingle", ["id" => $this->_studyId]);
        $this->fixStudyData();
      }
      catch (CiviCRM_API3_Exception $ex) {
      }
    }
    parent::preProcess(); // TODO: Change the autogenerated stub
  }

  /**
   * Method to set the custom field ids and column names
   */
  private function setCustomFieldIdsAndColumns() {
    $customFields = CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyDataCustomGroup('custom_fields') + CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionCriteriaCustomGroup('custom_fields');
    foreach ($customFields as $key => $values) {
      $this->_customFieldIdsAndColumns[$values['column_name']] = "custom_". $key;
    }
  }

  /**
   * Method to fix the study data - move the custom_ elements to an element with the column name
   */
  private function fixStudyData() {
    foreach ($this->_customFieldIdsAndColumns as $column => $id) {
      $customFieldId = $id . "_id";
      if (isset($this->_studyData[$customFieldId])) {
        $this->_studyData[$column] = $this->_studyData[$customFieldId];
        unset($this->_studyData[$customFieldId]);
        unset($this->_studyData[$id]);
      }
      else {
        if (isset($this->_studyData[$id])) {
          $this->_studyData[$column] = $this->_studyData[$id];
          unset($this->_studyData[$id]);
        }
      }
    }
  }

  /**
   * Method to set the campaign parameters from the submit values of the form
   */
  private function setCampaignParameters() {
    $campaignParameters = [
      'status_id' => $this->_submitValues['status_id'],
      'title' => $this->_submitValues['title'],
      'campaign_type_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getStudyCampaignTypeId(),
      'start_date' => $this->_submitValues['start_date'],
    ];
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $campaignParameters['id'] = $this->_studyId;
    }
    if (isset($this->_submitValues['end_date'])) {
      $campaignParameters['end_date'] = $this->_submitValues['end_date'];
    }
    foreach ($this->_submitValues as $submitKey => $submitValues) {
      if (isset($this->_customFieldIdsAndColumns[$submitKey])) {
        if ($submitKey == 'nsc_ethnicity_id') {
          $ethnicities = explode(",", $submitValues);
          $submitValues = $ethnicities;
        }
        $campaignParameters[$this->_customFieldIdsAndColumns[$submitKey]] = $submitValues;
      }
    }
    return $campaignParameters;
  }

  /**
   * Method to process the submitted form
   *
   * @throws
   */
  public function postProcess() {
    if ($this->_action == CRM_Core_Action::UPDATE) {
      $this->_studyId = $this->_submitValues['study_id'];
    }
    else {
      $this->_studyId = NULL;
    }
    try {
      $createdCampaign = civicrm_api3('Campaign', 'create', $this->setCampaignParameters());
      $this->_studyId = $createdCampaign['id'];
      $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNumberWithId($this->_studyId);
      CRM_Core_Session::setStatus(E::ts("Saved study ") . $studyNumber, E::ts('Saved'), 'success');
    }
    catch (CiviCRM_API3_Exception $ex) {
      throw new Exception(E::ts('Could not create or update study, contact your system administrator. Error message from API request Campaign create: ' . $ex->getMessage()));
    }
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = [];
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
