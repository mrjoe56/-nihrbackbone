<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource distance to bioresource
 *
 * @author John Boucher
 * @date 26 Sep 2019                  last update 20/12/19
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NihrAddress {

  public static function setDistanceToCentre($op, $objectName, $objectID, &$objectRef) {

    $contact_id = $objectRef->contact_id;
    $postcode = $objectRef->postal_code;

    # if contact is a valid panel - set distance to centre value for all cases linked to the panel
    $query = "select count(*) from civicrm_value_nbr_study_data where nsd_site = %1";
    $panel_count = CRM_Core_DAO::singleValueQuery($query, [1 => [$contact_id, 'Integer']]);
    if (intval($panel_count >= 1)) {
      $query = "select pd.entity_id as case_id, cc.contact_id as case_contact_id, addr.postal_code as postcode
                from civicrm_value_nbr_study_data prd, civicrm_value_nbr_participation_data pd, civicrm_case c,
                civicrm_case_contact cc, civicrm_contact contact, civicrm_address addr
                where prd.entity_id = pd.nvpd_study_id and pd.entity_id = c.id and c.id = cc.case_id
                and contact.id = cc.contact_id and contact.id = addr.contact_id and c.is_deleted = 0 and prd.nsd_site = %1";
      $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
      while ($cur->fetch()) {
        self::setCaseDistance($cur->case_id, $cur->postcode, $contact_id,  $postcode);
      }
    }

    # if contact is a valid participant - set distance to centre value for all cases linked to the participant
    else {
      $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
      if ($volunteer->isValidVolunteer($contact_id)) {
        $query = "select case_id from civicrm_case c, civicrm_case_contact cc
                  where c.id = cc.case_id and c.is_deleted = 0 and cc.contact_id = %1";
        $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
        while ($cur->fetch()) {
          $query2 = "select pa.nvpd_study_id, pr.nsd_site, addr.postal_code
                    from civicrm_value_nbr_study_data pr, civicrm_value_nbr_participation_data pa, civicrm_contact c, civicrm_address addr
                    where pr.entity_id = pa.nvpd_study_id and pr.nsd_site = c.id and c.id = addr.contact_id
                    and pa.entity_id = %1";
          $dao = CRM_Core_DAO::executeQuery($query2, [1 => [$cur->case_id, 'Integer']]);
          if ($dao->fetch()) {
            self::setCaseDistance($cur->case_id, $postcode, $dao->nsd_site, $dao->postal_code );
          }
        }
      }
    }

  }


  public static function setCaseDistance($case_id, $contact_postcode, $centre_id,  $centre_postcode) {
    # set distance to centre of a case entity
    $distance = civicrm_api3("Distance", "calculate", [
      'postcode_from' => $contact_postcode,
      'postcode_to' => $centre_postcode,
    ]);
    $query = "update civicrm_value_nbr_participation_data set nvpd_distance_volunteer_to_study_centre = %1 where entity_id = %2";
    $queryParams = [1 => [$distance, 'Integer'], 2 => [$case_id, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $queryParams);

  }


  public static function setContDistance($op, $objectName, $objectID, &$objectRef) {
    # set distance to Addenbrookes for a contact
    $distance = civicrm_api3("Distance", "calculate", [
      'postcode_from' => $objectRef->postal_code,
      'postcode_to' => 'CB20QQ',
    ]);

    $custom_key = 'custom_'.CRM_Nihrbackbone_BackboneConfig::singleton()->getSelectionEligibilityCustomField('nvse_distance_from_addenbrookes', 'id');

    $result = civicrm_api3('Contact', 'create', [
      'id' => $objectRef->contact_id,
      'contact_type' => "Individual",
      $custom_key => $distance,
    ]);

  }

  /**
   * Method to find the state_province_id with a name, either from CiviCRM or from synonym list
   *
   * @param $name
   * @return false|string
   */
  public static function getCountyIdForSynonym($name) {
    if (!empty($name)) {
      $name = trim($name);
      // first check if we can find the county in CiviCRM
      $query = "SELECT id FROM civicrm_state_province WHERE country_id = %1 AND name = %2";
      $countyId = CRM_Core_DAO::singleValueQuery($query, [
        1 => [Civi::service('nbrBackbone')->getUkCountryId(), "Integer"],
        2 => [$name, "String"],
      ]);
      if ($countyId) {
        return $countyId;
      }
      else {
        // if not found in CiviCRM, try with synonym
        $query = "SELECT state_province_id FROM civicrm_nbr_county WHERE synonym = %1";
        $countyId = CRM_Core_DAO::singleValueQuery($query, [1 => [trim($name), "String"]]);
        if ($countyId) {
          return $countyId;
        }
      }
    }
    return FALSE;
  }

}


