

<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource distance to bioresource
 *
 * @author John Boucher
 * @date 26 Sep 2019
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NihrAddress {

  public static function processPost($op, $objectName, $objectID, &$objectRef) {

    Civi::log()->debug('CRM_Nihrbackbone_NihrAddress call : $op:' . $op . '  $objectName : ' . $objectName);
    if ($objectName == 'Address') {
      //$properties = CRM_Nihrbackbone_Utils::moveDaoToArray($objectRef);
      //Civi::log()->debug('primary is ' . $objectRef->is_primary);

      if ($objectRef->is_primary) {
        Civi::log()->debug('POSTCODE is ' . $objectRef->postal_code);
        
        //civicrm_api3('Distance', 'calculate', [
          // call api with current postcode
        //]);

        Civi::log()->debug('primary address - call api here');
      }

    }

  }

}



?>

