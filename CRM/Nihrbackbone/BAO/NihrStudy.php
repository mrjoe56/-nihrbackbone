<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource BAO NihrStudy
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 3 April 2019
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_BAO_NihrStudy extends CRM_Nihrbackbone_DAO_NihrStudy {

  /**
   * Create a new NihrStudy based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Nihrbackbone_DAO_NihrStudy|NULL
   */
  public static function create($params) {
    $className = 'CRM_Nihrbackbone_DAO_NihrStudy';
    $entityName = 'NihrStudy';
    try {
      $opDate = new DateTime;
    }
    catch (Exception $ex) {
      $opDate = NULL;
    }
    if (!isset($params['id']) || empty($params['id'])) {
      $hook = 'create';
      if ($opDate) {
        $params['created_date'] = $opDate->format('YmdHis');
      }
      $params['created_by_id'] = CRM_Core_Session::singleton()->getLoggedInContactID();
    }
    else {
      $hook = 'edit';
      if ($opDate) {
        $params['modified_date'] = $opDate->format('YmdHis');
      }
      $params['modified_by_id'] = CRM_Core_Session::singleton()->getLoggedInContactID();
    }
    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    // process into dummy option group for study id field in project data custom group
    self::processOptionValue($hook, $instance);
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
    return $instance;
  }

  /**
   * Method to reflect the study create or update in the option values for the study id field in
   * project custom group extending campaign of type project
   *
   * @param $action
   * @param $study
   */
  private static function processOptionValue($action, $study) {
    switch ($action) {
      case "create":
        // add option value
        try {
          civicrm_api3('OptionValue', 'create', [
            'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'option_group_id'),
            'name' => $study->title,
            'label' => $study->title,
            'value' => $study->id,
          ]);
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts('Could not create an option value for study ') . $study->title . E::ts(' in ') . __METHOD__);
        }
        break;
      case "edit":
        // update title for option value
        try {
          $optionValue = civicrm_api3('OptionValue', 'getsingle', [
            'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'option_group_id'),
            'value' => $study->id,
            'return' => ["label", "id"],
          ]);
          if ($optionValue['label'] && $optionValue['label'] != $study->title) {
            try {
              civicrm_api3('OptionValue', 'create', [
                'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'option_group_id'),
                'name' => $study->title,
                'label' => $study->title,
                'id' => $optionValue['id'],
              ]);
            }
            catch (CiviCRM_API3_Exception $ex) {
              Civi::log()->error(E::ts('Could not update option value for study ') . $study->title . E::ts(' in ') . __METHOD__);
            }
          }
        }
        catch (CiviCRM_API3_Exception $ex) {
          Civi::log()->error(E::ts('Unexpected error from OptionValue getsingle in ') . __METHOD__ . E::ts(', error message: ') . $ex->getMessage());
        }
    }
  }

}
