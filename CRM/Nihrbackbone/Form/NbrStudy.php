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
  /**
   * Method to add the form elements
   */
  public function buildQuickForm() {
    // add elements for add or update
    if ($this->_action == CRM_Core_Action::UPDATE || $this->_action == CRM_Core_Action::ADD) {
      $this->addElements();
    }
    $this->addButtons([
      ['type' => 'next', 'name' => E::ts('Save'), 'isDefault' => TRUE],
      ['type' => 'cancel', 'name' => E::ts('Cancel')],
    ]);
    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  /**
   * Add elements for add or update action
   */
  private function addElements() {
    $this->add('text', 'nsd_study_number', E::ts('Study Number'), [
      'disabled' => 'disabled',
      'placeholder' => 'will be generated automatically',
    ], FALSE);
    $this->add('text', 'title', E::ts('Name'), [], TRUE);
    $this->add('select', 'status_id', E::ts('Status'), $this->getOptionGroupList('campaign_status'),
      TRUE, ['class' => 'crm-select2']);
    $this->add('datepicker', 'start_date', E::ts('Start Date'), ['placeholder' => ts('Start Date')],TRUE, ['time' => FALSE]);
    $this->add('datepicker', 'end_date', E::ts('End Date'), ['placeholder' => ts('End Date')],FALSE, ['time' => FALSE]);
    $this->addEntityRef('nsd_researcher', E::ts('Researcher'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
    ], FALSE);
    $this->addEntityRef('nsd_centre_origin', E::ts('Centre of Origin'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_centre']],
    ], FALSE);
    $this->addEntityRef('nsd_site', E::ts('Site'), [
      'api' => ['params' => ['contact_sub_type' => 'nbr_site']],
    ], FALSE);
    $this->add('text', 'nsd_study_long_name', E::ts("Long Name"), ['size' => 120], FALSE);
    $this->add('textarea', 'nsd_study_notes', E::ts('Notes'), ['rows' => 4, 'cols' => 50], FALSE);
    $this->add('advcheckbox', 'nsd_sample_only', E::ts('Sample only?'), [], FALSE);
    $this->add('advcheckbox', 'nsd_data_only', E::ts('Data only?'), [], FALSE);
    $this->add('advcheckbox', 'nsd_online_study', E::ts('Online study?'), [], FALSE);
    $this->add('advcheckbox', 'nsd_multiple_visits', E::ts('Multiple visits?'), [], FALSE);
    $this->addEntityRef('nsd_primary_nurse', E::ts('Primary nurse'), [
      'api' => ['params' => ['group' => 'nbr_bioresourcers']],
    ], FALSE);
    $this->add('select', 'nsc_gender_id', E::ts('Gender'), $this->getOptionGroupList('gender'),
      TRUE, [
        'class' => 'crm-select2',
        'placeholder' => 'select a gender'
        ]);
    $this->add('advcheckbox', 'nsc_blood_required', E::ts('Blood required?'), [], FALSE);
    $this->add('advcheckbox', 'nsc_travel_required', E::ts('Travel required?'), [], FALSE);
    $this->addEntityRef('nsc_ethnicity_id', E::ts('Ethnicity'), [
      'entity' => 'option_value',
      'api' => ['params' => ['option_group_id' => 'nihr_ethnicity']],
    ], FALSE);
    $this->add('text', 'age_from', E::ts("Age from"), [], FALSE);
    $this->add('text', 'age_to', E::ts("Age to"), [], FALSE);
    $this->add('text', 'bmi_from', E::ts("BMI from"), [], FALSE);
    $this->add('text', 'bmi_to', E::ts("BMI to"), [], FALSE);
  }

  /**
   * Method to process the submitted form
   */
  public function postProcess() {
    parent::postProcess();
  }

  /**
   * Method to get the campaign status list
   *
   * @return array
   */
  private function getOptionGroupList($name) {
    $result = [];
    try {
      $optionValues = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => $name,
        'is_active' => 1,
        'return' => ['label'],
        'options' => ['limit' => 0],
      ]);
      foreach ($optionValues['values'] as $optionValue) {
        $result[$optionValue['id']] = $optionValue['label'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    return $result;
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
    $elementNames = array();
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
