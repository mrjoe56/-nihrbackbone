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


  public static function checkDuplicatContactIdentity($op,$groupID, $entityID, $params)  {
    Civi::log()->debug('CID call, $entityID ' . $entityID);
    $contact_duplicates = [];                                                                                                      # array of contacts with a duplicate
    # contact identity (CI)
    foreach ($params as $key => $valuesarray) {
      if ($valuesarray['column_name'] == 'identifier_type') {                                                                        # for CI being edited
        $this_ci_type = $valuesarray['value'];                                                                                     #   get type
        if ($valuesarray['column_name'] == 'identifier') {
          $this_ci_value = $valuesarray['value'];                                                                                    #   and value
        }
      }

      # check if old value was duplicated here - hmm


      foreach (['cih_type_packid', 'cih_type_nhs_number'] as $ci_type) {                                                              # for each appropriate CI type
        $query = "select identifier from civicrm_value_contact_id_history
                where identifier_type = '" . $ci_type . "' and entity_id = " . $entityID;
        $dao = CRM_Core_DAO::executeQuery($query);                                                                                   #  and for each this.value of the type
        while ($dao->fetch()) {
          #Civi::log()->debug('$ci_type : ' . $ci_type . ' value : ' . $dao->identifier);
          if ($ci_type == 'cih_type_packid') {
            $tag_id = 'Duplicate Pack ID';
          }
          if ($ci_type == 'cih_type_nhs_number') {
            $tag_id = 'Duplicate NHS number';
          }
          $query1 = "select entity_id as duplicate_contact_id from civicrm_value_contact_id_history
                   where identifier_type = '" . $ci_type . "' and identifier = '" . $dao->identifier . "'
                   and entity_id != " . $entityID;
          #Civi::log()->debug('$query1 ' . $query1);
          $dao1 = CRM_Core_DAO::executeQuery($query1);                                                                               #   if there are other contacts with the same
          while ($dao1->fetch()) {                                                                                                   #   CI type and value
            #Civi::log()->debug('$ci_type : ' . $ci_type . ' value duplicated for contact : ' . $dao1->duplicate_contact_id);
            $params = ['entity_table' => 'civicrm_contact', 'entity_id' => $dao1->duplicate_contact_id, 'tag_id' => $tag_id,];
            $getcount = civicrm_api3('EntityTag', 'getcount', $params);                                                #     then check if the other contact
            if ($getcount == 0) {                                                                                                   #     already has a tag for the CI type
              #Civi::log()->debug('adding');
              $result = civicrm_api3('EntityTag', 'create', $params);                                                  #      and if not - add the tag
            }
          }
        }
      }


      #Civi::log()->debug('$contact_duplicates : '.implode(', ', $contact_duplicates));

    }
  }

  /**
   * Method to process validateForm hook for contact identity
   * - do not allow edit or add when contact identifier is protected
   *
   * @param $fields
   * @param $form
   * @param $errors
   */
  public static function validateForm(&$fields, &$form, &$errors) {
    $protected = explode(",", Civi::settings()->get('nbr_protected_identifier_types'));
    $typeCustomField = "custom_" . Civi::service('nbrBackbone')->getIdentifierTypeCustomFieldId();
    foreach ($fields as $key => $value) {
      if (strpos($key, $typeCustomField) !== FALSE) {
        if (in_array($value, $protected)) {
          $errors[$key] = E::ts('You can not manually add an identifier of this type.');
        }
      }
    }
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
