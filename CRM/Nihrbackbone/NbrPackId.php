<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource Pack ID processing
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 7 Feb 2023
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrPackId {

  /**
   * Method to validate pack ID (used in various forms)
   *
   * @param string $packId
   * @return array
   */
  public static function isValidPackId(string $packId): array {
    $messages = [];
    $first = $packId[0];                                                                                        #  get first character
    $last = substr($packId, -1);                                                                                #  last character
    $nString = substr($packId, 1, 6);                                                                          #  numeric part
    $mod = $nString % 23;                                                                                           #  and modulus
    $mod2chk = ['0' => 'Z','1' => 'A','2' => 'B','3' => 'C','4' => 'D','5' => 'E','6' => 'F',
      '7' => 'G','8' => 'H','9' => 'J','10' => 'K','11' => 'L','12' => 'M','13' => 'N','14' => 'P',
      '15' => 'Q','16' => 'R','17' => 'S','18' => 'T','19' => 'V','20' => 'W','21' => 'X','22' => 'Y'];
    $chkChr = $mod2chk[$mod];                                                                                       # VALIDATION:
    if (! preg_match( '/[A-Z]/', $first)) {                                                                          #  first char is alpha
      $messages[] = "Error in Pack ID - first character must be 'A' to 'Z'";
    }
    elseif (strlen($packId) != 8) {                                                                             #  length of input is 8
      $messages[] = "Error in Pack ID - must be 8 characters long";
    }
    elseif (! preg_match( '/[0-9]{6}/', $nString)) {                                                                # chars 2-7 are numeric
      $messages[] = "Error in Pack ID - 2nd to 7th characters must be numeric";
    }
    elseif ($chkChr != $last) {                                                                                     # check digit is correct
      $messages[] = "Error in Pack ID - Pack ID is invalid";
    }
    return $messages;
  }

}
