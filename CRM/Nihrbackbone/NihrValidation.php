<?php
class CRM_Nihrbackbone_NihrValidation {

  public static function validateAlias($formName, &$fields, &$files, &$form, &$errors) {

    # get contact id history custom field IDs
    $query = "select id from civicrm_custom_field where name = 'id_history_entry_type'";  # custom_7
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$alias_type_id = 'custom_'.$dao->id;};

    $query = "select id from civicrm_custom_field where name = 'id_history_entry'"; # custom_8
    $dao  = CRM_Core_DAO::executeQuery($query);
    if ($dao->fetch()) {$alias_value_id = 'custom_'.$dao->id;};

    foreach($fields as $key => $value) {
      if (strpos($key,$alias_type_id) !== false) {                   # contact id history custom field found
        $alias_type = $value;                                        #  get custom field value
        $errorField = $key;                                          #  and field name - for error logging
      }
      if (strpos($key,$alias_value_id) !== false) {
        $alias_value= $value;
      }
    }

    if ($alias_type == 'cih_type_packid') {                          # pack ID validation
      $msg = 'Error in K Pack ID - ';
      $first = $alias_value[0];                                      #  get first character
      $last = substr($alias_value, -1);                        #  last character
      $n_string = substr($alias_value, 1, 6);           #  numeric part
      $mod = $n_string % 23;                                         #  and modulus
      $chk_chr = chr($mod+64);                                  #  calculate check digit
      if ($chk_chr == '@') {$chk_chr = 'Z';}
      if ($first != 'K') {                                           # VALIDATION:
        $errors[$errorField] = $msg."first character must be 'K'";   #  first char is K
      }
      elseif (strlen($alias_value) != 8) {                           #  length of input is 8
        $errors[$errorField] = $msg."must be 8 characters long";
      }
      elseif (! preg_match( '/[0-9]{6}/', $n_string)) {       #  chars 2-7 are numeric
        $errors[$errorField] = $msg."2nd to 7th characters must be numeric";
      }
      elseif ($chk_chr != $last) {                                   # check digit is correct
        $errors[$errorField] = $msg."Check character incorrect (".$chk_chr.")";
      }
    }

    if ($alias_type == 'cih_type_nhs_number') {                      # NHS number validation

      $msg = 'Error in NHS Number - ';
      $cd = intval(substr($alias_value, 9, 1));                      #  get check digit
      $sum =                                                         #  and weighted sum of first 9 digits
        intval(substr($alias_value, 0, 1)) * 10 +
        intval(substr($alias_value, 1, 1)) * 9 +
        intval(substr($alias_value, 2, 1)) * 8 +
        intval(substr($alias_value, 3, 1)) * 7 +
        intval(substr($alias_value, 4, 1)) * 6 +
        intval(substr($alias_value, 5, 1)) * 5 +
        intval(substr($alias_value, 6, 1)) * 4 +
        intval(substr($alias_value, 7, 1)) * 3 +
        intval(substr($alias_value, 8, 1)) * 2;
      $calc_cd = 11 - ($sum % 11);                                   # calc check digit :
      # 11 - modulus 11 of weighted sum
      if ($calc_cd == 11) {$calc_cd = 0;}                            # map 11 > 0

      if (strlen($alias_value) != 10) {                              # VALIDATION:
        $errors[$errorField] = $msg."must be 10 characters long";    #  length of input is 10
      }
      elseif (! preg_match( '/[0-9]{10}/', $alias_value)) {  #  all chars are numeric
        $errors[$errorField] = $msg."all characters must be numeric";
      }
      elseif ($calc_cd == 10) {                                      #  invalid check digit
        $errors[$errorField] = $msg."Check digit cannot be calculated";
      }
      elseif ($cd !== $calc_cd) {                                    # check digit is correct
        $errors[$errorField] = $msg."Check digit incorrect (".strval($calc_cd).")";
      }

    }

  } # /validateAlias
} # /class
?>
