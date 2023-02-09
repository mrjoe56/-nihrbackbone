<?php
class CRM_Nihrbackbone_NihrValidation {

  public static function validateAlias($formName, &$fields, &$files, &$form, &$errors) {

    $contact_id = CRM_Utils_Request::retrieve('entityID', 'String');

    # get contact id history custom field IDs
    $query = "select id from civicrm_custom_field where name = 'id_history_entry_type'";  #
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$alias_type_id = 'custom_'.$dao->id;};

    $query = "select id from civicrm_custom_field where name = 'id_history_entry'"; #
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$alias_value_id = 'custom_'.$dao->id;};

    foreach($fields as $key => $value) {
      if (strpos($key,$alias_type_id) !== false) {                                                                     # contact id hist custom field found
        $alias_type = $value;                                                                                          #  get custom field value
        $errorField = $key;                                                                                            #  and field name - for error logging
      }
      if (strpos($key,$alias_value_id) !== false) {
        $alias_value= $value;
      }
    }

    $sqlParams = [1 => [$alias_type, 'String'], 2 => [intval($contact_id), 'Integer'], 3 => [$alias_value, 'String'], ];
    $query = "select count(*) as dup from civicrm_value_contact_id_history where identifier_type = %1 and entity_id = %2 and identifier = %3";
    $duplicateCount = CRM_Core_DAO::singleValueQuery($query, $sqlParams);                                                                  #
    if ($duplicateCount>0) {$errors[$errorField] = $msg."This identity already exists or has not been updated - please cancel or update";}

    if ($alias_type == 'cih_type_packid') {
      # pack ID validation
      $packIdErrors = CRM_Nihrbackbone_NbrPackId::isValidPackId($alias_value);
      foreach ($packIdErrors as $packIdError) {
        $errors[$errorField] = $packIdError;
      }
    }

    if ($alias_type == 'cih_type_nhs_number') {                                                                        # NHS number validation

      $msg = 'Error in NHS Number - ';
      $cd = intval(substr($alias_value, 9, 1));                                                                        #   get check digit
      $sum =                                                                                                           #   + weighted sum of first 9 digits
        intval(substr($alias_value, 0, 1)) * 10 +
        intval(substr($alias_value, 1, 1)) * 9 +
        intval(substr($alias_value, 2, 1)) * 8 +
        intval(substr($alias_value, 3, 1)) * 7 +
        intval(substr($alias_value, 4, 1)) * 6 +
        intval(substr($alias_value, 5, 1)) * 5 +
        intval(substr($alias_value, 6, 1)) * 4 +
        intval(substr($alias_value, 7, 1)) * 3 +
        intval(substr($alias_value, 8, 1)) * 2;
      $calc_cd = 11 - ($sum % 11);                                                                                     # calc check digit :
      # 11 - modulus 11 of weighted sum
      if ($calc_cd == 11) {$calc_cd = 0;}                                                                              # map 11 > 0

      if (strlen($alias_value) != 10) {                                                                                # VALIDATION:
        $errors[$errorField] = $msg."must be 10 characters long";                                                      #  length of input is 10
      }
      elseif (! preg_match( '/[0-9]{10}/', $alias_value)) {                                                            #  all chars are numeric
        $errors[$errorField] = $msg."all characters must be numeric";
      }
      elseif ($calc_cd == 10) {                                                                                        #  invalid check digit
        $errors[$errorField] = $msg."Check digit cannot be calculated";
      }
      elseif ($cd !== $calc_cd) {                                                                                      # check digit is correct
        $errors[$errorField] = $msg."Check digit incorrect (".strval($calc_cd).")";
      }

    }

  } # /validateAlias

  public static function customFormConfig($formName, &$form) {

    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive');

    # get general observations custom group ID
    $query = "select id from civicrm_custom_group where name = 'nihr_volunteer_general_observations'";                 #
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$gen_obvs_group_id = $dao->id;};
    # get general observations custom group ID
    $query = "select id from civicrm_custom_group where name = 'contact_id_history'";                                  #
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$contact_hist_group_id = $dao->id;};

    # For gen observations form - allow input of ht/wt as feet/inches/lbs
    if ($formName == 'CRM_Contact_Form_CustomData' && $form->getVar('_groupID') == $gen_obvs_group_id) {

      # get height/weight and calculate imperial values
      $query = "select nvgo_weight_kg, nvgo_height_m from civicrm_value_nihr_volunteer_general_observations where entity_id = " . $contactId;
      $dao = CRM_Core_DAO::executeQuery($query);
      if ($dao->fetch()) {
        $wt_kg = intval($dao->nvgo_weight_kg);
        $wt_totlbs = intval($wt_kg / 0.453592);
        $wt_stone = intval($wt_totlbs / 14);
        $wt_lbs = ($wt_totlbs / 14 - $wt_stone) * 14;
        $ht_m = floatval($dao->nvgo_height_m);
        $ht_fd = $ht_m * 3.28084;
        $ht_ft = intval($ht_fd);
        $ht_in = round(12 * ($ht_fd - $ht_ft));
      };

      // Add the field elements to the form
      $form->add('text', 'nvgo_val_wt', ts(''));
      $defaults['nvgo_val_wt'] = $wt_totlbs;
      $form->add('text', 'nvgo_val_wt_stones', ts(''));
      $defaults['nvgo_val_wt_stones'] = $wt_stone;
      $form->add('text', 'nvgo_val_wt_lbs', ts(''));
      $defaults['nvgo_val_wt_lbs'] = $wt_lbs;
      $form->add('text', 'nvgo_val_ht_ft', ts(''));
      $defaults['nvgo_val_ht_ft'] = $ht_ft;
      $form->add('text', 'nvgo_val_ht_in', ts(''));
      $defaults['nvgo_val_ht_in'] = $ht_in;
      $form->setDefaults($defaults);

      CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_general_observations.tpl',]);   # add template to form
    }
  }
}

?>
