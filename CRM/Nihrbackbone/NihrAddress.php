<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource distance to bioresource
 *
 * @author John Boucher
 * @date 26 Sep 2019
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NihrAddress
{

  public static function postProcess($op, $objectName, $objectID, &$objectRef)
  {

    $distance = civicrm_api3("Distance", "calculate", [
      'postcode' => $objectRef->postal_code,
      'NBR_postcode' => 'CB20QQ',
    ]);

    $custom_key = 'custom_'.CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_distance_from_addenbrookes', 'id');

    $result = civicrm_api3('Contact', 'create', [
      'id' => $objectRef->contact_id,
      'contact_type' => "Individual",
      $custom_key => $distance,
    ]);

  }
}

?>

