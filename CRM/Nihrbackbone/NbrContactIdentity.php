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

#jb new
  /**
   * method to check for duplicate contact identities -
   * if a contact ID (CID) is added or modified :
   *  a. check if the new CID is duplicated on another civi contact, and if so, warn the user, and add the relevent tags to the contacts
   *  b. as the old CID may have been a duplicate, check all civi contacts with the relevent tag set, and delete the tag if CID is longer a duplicate
   * if a CID is deleted, then for all civi contacts with ANY tag set (this::CID details are not known for deletes):
   *  check if the CID value is still a duplicate and if not - delete the tag
   */
  public static function checkDuplicatContactIdentity($op, $groupID, $entityID, $params)  {

    $thisCiviID = $entityID;
    $tagNames = ['cih_type_packid'=>'Duplicate Pack ID','cih_type_nhs_number'=>'Duplicate NHS number'];                # Applicable tag IDs and names

    if ($op=='delete') {
      # no information in parameters for a delete so need to run checkDuplicates for each tag type
      foreach ($tagNames as $tagType => $tagName) {
        CRM_Nihrbackbone_NbrContactIdentity::checkDuplicates($tagName, $tagType);
      }
    }
    else {
      $duplicate_contact = 'none';
      $query = "select id from civicrm_custom_field where name = 'id_history_entry_type'";                             # get this CI (contact identity) custom field IDs
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        $alias_type_id = $dao->id;
      };
      $query = "select id from civicrm_custom_field where name = 'id_history_entry'"; # custom_13
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        $alias_value_id = $dao->id;
      };

      foreach ($params as $key => $valuesarray) {                                                                      # get this CI type and value
        if ($valuesarray['custom_field_id'] == $alias_type_id) {
          $this_ci_type = $valuesarray['value'];
        }
        if ($valuesarray['custom_field_id'] == $alias_value_id) {
          $this_ci_value = $valuesarray['value'];
        }
      }

      if (array_key_exists($this_ci_type, $tagNames)) {                                                                # if duplicate checking is applicable to this CI type
        $tagName = $tagNames[$this_ci_type];                                                                           #   get the tag name for this CI type
        $sqlParams = [1 => [$this_ci_type, 'String'], 2 => [$this_ci_value, 'String'], 3 => [intval($thisCiviID), 'Integer'],];
        $query1 = "select entity_id as duplicate_contact_id, c.sort_name as duplicate_contact_name from civicrm_value_contact_id_history ch, civicrm_contact c
                   where c.id = ch.entity_id and identifier_type = %1 and identifier = %2 and entity_id != %3";
        $dao1 = CRM_Core_DAO::executeQuery($query1, $sqlParams);                                                       #   if there are other contacts with the same
        while ($dao1->fetch()) {                                                                                       #   CI type and value
          $duplicate_contact = $dao1->duplicate_contact_id;
           self::setContactTag($dao1->duplicate_contact_id, $tagName, 'set');                                          #   set the appropriate tag for the other contacts ..
        }
        if ($duplicate_contact != 'none') {
          self::setContactTag($thisCiviID, $tagName, 'set');                                                           #   .. and for this contact
          $msg = ts('This Contact Identity is already in use for Contact ID ' . $duplicate_contact . ' (' . $dao1->duplicate_contact_name . ')');
          CRM_Core_Session::setStatus($msg, ts('Notice'), 'alert');                                                    #    and post alert message
        }
        self::checkDuplicates($tagName, $this_ci_type);
      }
    }
  }

  /**
   * method to check
   * IF:
   * A contact ID is modified or deleted, and it's original value was a duplicate - tags will be set against one or more contacts -
   * THEN:
   * For all contacts with the relevant tag set, check if the CI value is still a duplicate and if not - remove the tag(s)
   */
  public static function checkDuplicates($tagName, $this_ci_type) {
    $sqlParams = [1 => [$tagName, 'String'],];
    $query = "select et.entity_id as civi_id from civicrm_tag t, civicrm_entity_tag et where t.id = et.tag_id and t.name = %1";
    $dao = CRM_Core_DAO::executeQuery($query, $sqlParams);
    while ($dao->fetch()) {                                                                                            # for each contact with a tag set for this CI type
      $duplicates = 'N';
      $other_civi_id = $dao->civi_id;
      $sqlParams = [1 => [$this_ci_type, 'String'], 2 => [intval($other_civi_id), 'Integer'],];
      $query1 = "select identifier from civicrm_value_contact_id_history where identifier_type = %1 and entity_id = %2";
      $dao1 = CRM_Core_DAO::executeQuery($query1, $sqlParams);                                                         #  get CI's for the contact and type
      while ($dao1->fetch()) {
        $sqlParams = [1 => [$this_ci_type, 'String'], 2 => [$dao1->identifier, 'String'], 3 => [$other_civi_id, 'String'],];    #  and check if CID is unique to the contact
        $query2 = "select count(*) from civicrm_value_contact_id_history where identifier_type = %1 and identifier = %2 and entity_id != %3";
        $duplicate_count = CRM_Core_DAO::singleValueQuery($query2, $sqlParams);
        if ($duplicate_count > 0) {                                                                                    #  if duplicate found elswhere
          $duplicates = 'Y';
        }
      }
      if ($duplicates == 'N') {
        self::setContactTag($dao->civi_id, $tagName, 'unset');
      }
    }
  }

  /**
   * method to add or remove a tag to/from contact
   */
  public static function setContactTag($contact_id, $tagName, $action) {
    $params = ['entity_table' => 'civicrm_contact', 'entity_id' => $contact_id, 'tag_id' => $tagName, 'check_permissions' => 0, ];
    $getcount = civicrm_api3('EntityTag', 'getcount', $params);                                                        # check if tag exists for contact
    if ($action=='set'&&$getcount==0) {                                                                                # if tag does not exist and action is 'set'
      $result = civicrm_api3('EntityTag', 'create', $params);                                                          #   then add tag
    }
    if ($action=='unset'&&$getcount>0) {                                                                               # if tag exists and action is 'unset'
      $params = ['name' => $tagName, 'return' => $entityTagId, 'sequential' => 1, ];
      $getTag = civicrm_api3('Tag', 'get', $params);
      $entityTagId = $getTag['values'][0]['id'];
      $params = ['entity_id' => $contact_id, 'tag_id' => $entityTagId, 'check_permissions' => 0, ];
      $result = civicrm_api3('EntityTag', 'delete', $params);                                                          #   then delete tag
    }
  }


  # /jb  new

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
