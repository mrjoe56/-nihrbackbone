<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/*
 * Class for National BioResource distance to bioresource
 *
 * @author John Boucher
 * @date 26 Sep 2019                  last update 01/12/20
 * @license AGPL-3.0
 * called from nihrbackbone post hook :
 *  set_distance_to_studysite_contact - called on change of participant or centre postcode
 *  set_distance_to_centre_study   - called on update of a study
 *  set_case_distance              - called when new case added
 *  set_distance_to_cuh            - called on change of participant postcode
 */

class CRM_Nihrbackbone_NihrAddress {

  public static function set_distance_to_studysite_contact(&$objectRef) {
    $contact_id = $objectRef->contact_id;
    $query = "select count(*) from civicrm_value_nbr_study_data where nsd_site = %1";
    $panel_count = CRM_Core_DAO::singleValueQuery($query, [1 => [$contact_id, 'Integer']]);
    if (intval($panel_count >= 1)) {
      // if contact  is a valid study site - set distance to centre value for all cases linked to the study site
      $query = "select pd.entity_id as case_id
                from civicrm_value_nbr_study_data prd, civicrm_value_nbr_participation_data pd, civicrm_case c
                where prd.entity_id = pd.nvpd_study_id and pd.entity_id = c.id
                and c.is_deleted = 0 and prd.nsd_site = %1";
      $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
      while ($cur->fetch()) {
        self::set_case_distance($cur->case_id);
      }
    }
    else {
      $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
      if ($volunteer->isValidVolunteer($contact_id)) {
        // if contact is a valid participant - set distance to study site value for all cases linked to the participant
        $query = "select case_id from civicrm_case c, civicrm_case_contact cc, civicrm_value_nbr_participation_data pd
                   where c.id = cc.case_id and c.is_deleted = 0 and pd.entity_id = c.id and cc.contact_id = %1";
        $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
        while ($cur->fetch()) {self::set_case_distance($cur->case_id);}
        // and set distance to CUH for the participant
        self::set_distance_to_cuh($objectRef);
      }
    }
  }

  public static function set_distance_to_studysite_study($study_id) {
    // if study site for a study is changed - set distance to study site value for all cases linked to the study
      $query = "select pd.entity_id as case_id
                from 	civicrm_value_nbr_participation_data pd, civicrm_value_nbr_study_data sd
                where pd.nvpd_study_id = sd.entity_id
                and sd.entity_id = %1";
      $cur = CRM_Core_DAO::executeQuery($query, [1 => [$study_id, 'Integer']]);
      while ($cur->fetch()) {
        self::set_case_distance($cur->case_id);
      }
  }

  public static function set_case_distance($case_id) {
    // set distance to study site for a case entity
    $query = "select cc.contact_id, adr.postal_code as cont_pc, sd.nsd_site, site_adr.postal_code as site_pc
             from civicrm_case_contact cc, civicrm_contact c, civicrm_address adr, civicrm_address site_adr,
             civicrm_value_nbr_participation_data pd, civicrm_value_nbr_study_data sd
             where cc.contact_id = c.id and c.id = adr.contact_id and cc.case_id = pd.entity_id
             and pd.nvpd_study_id = sd.entity_id and sd.nsd_site = site_adr.contact_id and case_id = %1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$case_id, 'Integer']]);
    if ($dao->fetch()) {
      $distance = civicrm_api3("Distance", "calculate", [
        'postcode_from' => $dao->cont_pc,
        'postcode_to' => $dao->site_pc,
      ]);
      $query1 = "update civicrm_value_nbr_participation_data set nvpd_distance_volunteer_to_study_centre = %1 where entity_id = %2";
      $query1Params = [1 => [$distance, 'Integer'], 2 => [$case_id, 'Integer']];
      CRM_Core_DAO::executeQuery($query1, $query1Params);
    }
  }

  public static function set_distance_to_cuh(&$objectRef) {
    // set distance to Addenbrookes for a contact
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

  /**
   * After a contact merge, the original address(es) of the remaining contact will be overwritten with the
   * address from the removed contact. This method will now reinstate those original addresses
   *
   * @param int $contactId
   * @return void
   */
  public static function resurrectOverwrittenAddressDuringMerge(int $contactId) {
    $addresses = CRM_Core_Session::singleton()->nbr_address_merging_contact;
    foreach ($addresses as $addressId => $address) {
      // if one of the original ones was primary, make sure the new ones are not!
      if ($address['is_primary'] == TRUE) {
        CRM_Core_DAO::executeQuery("UPDATE civicrm_address SET is_primary = FALSE WHERE contact_id = %1", [1 => [$contactId, "Integer"]]);
      }
      try {
        $results = \Civi\Api4\Address::create();
        foreach ($address as $fieldName => $fieldValue) {
          // remove id so the create api does not do an update and remove location type
          if ($fieldName != 'location_type_id' && $fieldName != 'id') {
            $results->addValue($fieldName, $fieldValue);
          }
        }
        // use a location type that does not exist yet on the contact, preferably the one it already had. If no location type is available, create a new one
        $locationTypeId = self::getAvailableLocationTypeId($contactId, (int) $address['location_type_id']);
        if ($locationTypeId) {
          $results->addValue('location_type_id', $locationTypeId);
          $results->execute();
        }
        else {
          // if we have no location type available we add this address to the former communication data if possible
          if (method_exists("CRM_Formercommunicationdata_Utils", "getLocationTypeLabelWithId")) {
            self::storeInFcd($contactId, $address);
          }
        }
      }
      catch (API_Exception $ex) {
      }
    }
  }

  /**
   * Method to add address to fcd
   *
   * @param int $contactId
   * @param array $address
   * @return void
   */
  private static function storeInFcd(int $contactId, array $address) {
    $query = "INSERT INTO civicrm_value_fcd_former_comm_data (entity_id, fcd_communication_type, fcd_location_type, fcd_date_deactivated, fcd_deactivated_by, fcd_details)
        VALUES(%1, %2, %3, %4, %5, %6)";
    $locType = CRM_Formercommunicationdata_Utils::getLocationTypeLabelWithId($address['location_type_id']) . " ( id: " . $address['location_type_id'] . ")";
    $nowDate = CRM_DateTime('now');
    $addressDetails = [];
    foreach ($address as $field => $value) {
      $addressDetails[] = $field . ":&nbsp;".$value;
    }
    $queryParams = [
      1 => [$contactId, "Integer"],
      2 => ["address", "String"],
      3 => [$locType, "String"],
      4 => [$nowDate->format("Y-m-d"), "String"],
      5 => [(int) CRM_Core_Session::getLoggedInContactID(), "Integer"],
      6 => [implode(", ", $addressDetails), "String"],
    ];
    CRM_Core_DAO::executeQuery($query, $queryParams);
  }

  /**
   * Method to get the location type id we can use for the new address
   *
   * @param int $contactId
   * @param int $originalLocTypeId
   * @return int|null
   */
  public static function getAvailableLocationTypeId(int $contactId, int $originalLocTypeId) {
    // first check if the original location type can be used
    if (!empty($originalLocTypeId)) {
      $query = "SELECT COUNT(*) FROM civicrm_address WHERE contact_id = %1 AND location_type_id = %2";
      $count = CRM_Core_DAO::singleValueQuery($query, [
        1 => [$contactId, "Integer"],
        2 => [$originalLocTypeId, "Integer"],
      ]);
      if ($count == 0) {
        return $originalLocTypeId;
      }
    }
    // if we get here the original one can not be used so we need to find another one that is available
    $query = "SELECT id FROM civicrm_location_type clt
        WHERE id NOT IN (SELECT location_type_id FROM civicrm_address WHERE contact_id = %1) LIMIT 1";
    $foundLocTypeId = CRM_Core_DAO::singleValueQuery($query, [1 => [$contactId, "Integer"]]);
    if ($foundLocTypeId) {
      return (int) $foundLocTypeId;
    }
    // if we get here we could not use any so we return NULL
    return NULL;
  }

}


