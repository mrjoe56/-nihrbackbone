<?php
use CRM_Nihrbackbone_ExtensionUtil as E;
/**
 * Class for National BioResource contact identity processing
 * (linked to the identity tracker extension)
 *
 * @author Erik Hommel (CiviCooP) <erik.hommel@civicoop.org>
 * @date 16 Jun 2020
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NbrContactIdentity {
  /**
   * Method to process validateForm hook for contact identity
   * - do not allow edit or add when contact identifier is protected
   *
   * @param $fields
   * @param $form
   * @param $errors
   */
  public static function validateForm(&$fields, &$form, &$errors) {
    $protected = Civi::settings()->get('nbr_protected_identifier_types');

  }

  /**
   * Ugly hack to disable the crm-editable (inline editing) class on contact identifiers
   * - copy of CRM_Custom_Page_AJAX
   * - menu item is updated in Upgrader of this extension so CiviCRM uses this function rather
   *   than the core one
   *
   */
  public static function getMultiRecordFieldList() {
    $params = CRM_Core_Page_AJAX::defaultSortAndPagerParams(0, 10);
    $params['cid'] = CRM_Utils_Type::escape($_GET['cid'], 'Integer');
    $params['cgid'] = CRM_Utils_Type::escape($_GET['cgid'], 'Integer');

    $contactType = CRM_Contact_BAO_Contact::getContactType($params['cid']);

    $obj = new CRM_Profile_Page_MultipleRecordFieldsListing();
    $obj->_pageViewType = 'customDataView';
    $obj->_contactId = $params['cid'];
    $obj->_customGroupId = $params['cgid'];
    $obj->_contactType = $contactType;
    $obj->_DTparams['offset'] = ($params['page'] - 1) * $params['rp'];
    $obj->_DTparams['rowCount'] = $params['rp'];
    if (!empty($params['sortBy'])) {
      $obj->_DTparams['sort'] = $params['sortBy'];
    }

    list($fields, $attributes) = $obj->browse();

    // format params and add class attributes
    $fieldList = [];
    foreach ($fields as $id => $value) {
      $field = [];
      foreach ($value as $fieldId => &$fieldName) {
        if (!empty($attributes[$fieldId][$id]['class'])) {
          $fieldName = ['data' => $fieldName, 'cellClass' => $attributes[$fieldId][$id]['class']];
        }
        if (is_numeric($fieldId)) {
          $fName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField', $fieldId, 'column_name');
          CRM_Utils_Array::crmReplaceKey($value, $fieldId, $fName);
        }
      }
      $field = $value;
      array_push($fieldList, $field);
    }
    $totalRecords = !empty($obj->_total) ? $obj->_total : 0;

    $multiRecordFields = [];
    $multiRecordFields['data'] = $fieldList;
    $multiRecordFields['recordsTotal'] = $totalRecords;
    $multiRecordFields['recordsFiltered'] = $totalRecords;

    // make sure inline editing is disallowed for contact identities
    if ($params['cgid'] == Civi::service('nbrBackbone')->getContactIdentityCustomGroupId()) {
      foreach ($multiRecordFields['data'] as $mrKey => $multiRecordField) {
        foreach ($multiRecordField as $mrFieldKey => $mrFieldValues) {
          if ($mrFieldKey == 'identifier_type') {
            if (CRM_Nihrbackbone_NbrContactIdentity::checkIdentifierTypeProtected($mrFieldValues)) {
              $multiRecordFields['data'][$mrKey]['action'] = "";
            }
          }
          if (isset($mrFieldValues['cellClass'])) {
            $classReplace = str_replace("crm-editable", "", $mrFieldValues['cellClass']);
            $multiRecordFields['data'][$mrKey][$mrFieldKey]['cellClass'] = $classReplace;
          }
        }
      }
    }
    if (!empty($_GET['is_unit_test'])) {
      return $multiRecordFields;
    }

    CRM_Utils_JSON::output($multiRecordFields);
  }

  /**
   * Method to check if the identifier type is a protected one (so actions not allowed)
   *
   * @param $field
   * @return bool
   */
  private static function checkIdentifierTypeProtected($field) {
    if (isset($field['data'])) {
      $protected = explode(",", Civi::settings()->get("nbr_protected_identifier_types"));
      try {
        $optionValue = civicrm_api3("OptionValue", "getvalue", [
          'option_group_id' => 'contact_id_history_type',
          'label' => $field['data'],
          'return' => 'value',
        ]);
        if (in_array($optionValue, $protected)) {
          return TRUE;
        }
      } catch (CiviCRM_API3_Exception $ex) {
      }
    }
    return FALSE;
  }
}
