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

  #public static function set_distance_to_centre($op, $objectName, $objectID, &$objectRef) {
  public static function set_distance_to_centre(&$objectRef) {
    $contact_id = $objectRef->contact_id;
    $postcode = $objectRef->postal_code;

    # if contact  is a valid site - set distance to site value for all cases linked to the site
    $query = "select count(*) from civicrm_value_nbr_study_data where nsd_site = %1";
    $panel_count = CRM_Core_DAO::singleValueQuery($query, [1 => [$contact_id, 'Integer']]);
    if (intval($panel_count >= 1)) {
      #$query = "select pd.entity_id as case_id, cc.contact_id as case_contact_id, addr.postal_code as postcode
      #          from civicrm_value_nbr_study_data prd, civicrm_value_nbr_participation_data pd, civicrm_case c,
      #          civicrm_case_contact cc, civicrm_contact contact, civicrm_address addr
      #          where prd.entity_id = pd.nvpd_study_id and pd.entity_id = c.id and c.id = cc.case_id
      #          and contact.id = cc.contact_id and contact.id = addr.contact_id and c.is_deleted = 0 and prd.nsd_site = %1";
      Civi::log()->debug('For site '.$contact_id.' - ');
      # replace query with case only
      $query = "select pd.entity_id as case_id
                from civicrm_value_nbr_study_data prd, civicrm_value_nbr_participation_data pd, civicrm_case c
                where prd.entity_id = pd.nvpd_study_id and pd.entity_id = c.id
                and c.is_deleted = 0 and prd.nsd_site = %1";
      $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
      while ($cur->fetch()) {
        Civi::log()->debug(' setting distance for case ID '.$cur->case_id);
        #self::setCaseDistance_old($cur->case_id, $cur->postcode, $contact_id,  $postcode);
        self::set_case_distance($cur->case_id);

      }
    }

    # if contact is a valid participant - set distance to site value for all cases linked to the participant
    else {
      $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
      if ($volunteer->isValidVolunteer($contact_id)) {
        $query = "select case_id from civicrm_case c, civicrm_case_contact cc, civicrm_value_nbr_participation_data pd
                   where c.id = cc.case_id and c.is_deleted = 0 and pd.entity_id = c.id and cc.contact_id = %1";
        $cur = CRM_Core_DAO::executeQuery($query, [1 => [$contact_id, 'Integer']]);
        while ($cur->fetch()) {
          #$query2 = "select pa.nvpd_study_id, pr.nsd_site, addr.postal_code
          #          from civicrm_value_nbr_study_data pr, civicrm_value_nbr_participation_data pa, civicrm_contact c, civicrm_address addr
          #          where pr.entity_id = pa.nvpd_study_id and pr.nsd_site = c.id and c.id = addr.contact_id
          #          and pa.entity_id = %1";
          #$dao = CRM_Core_DAO::executeQuery($query2, [1 => [$cur->case_id, 'Integer']]);
          #if ($dao->fetch()) {
          #self::setCaseDistance_old($cur->case_id, $postcode, $dao->nsd_site, $dao->postal_code );
          self::set_case_distance($cur->case_id);
          }
        }
      }
    }



  public static function set_case_distance($case_id) {
    # set distance to centre of a case entity
    $query = "select cc.contact_id, adr.postal_code as cont_pc, stddat.nsd_site, site_adr.postal_code as site_pc
             from civicrm_case_contact cc, civicrm_contact c, civicrm_address adr, civicrm_address site_adr,
             civicrm_value_nbr_participation_data partdat, civicrm_value_nbr_study_data stddat
             where cc.contact_id = c.id and c.id = adr.contact_id and cc.case_id = partdat.entity_id
             and partdat.nvpd_study_id = stddat.entity_id and stddat.nsd_site = site_adr.contact_id and case_id = %1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$case_id, 'Integer']]);
    if ($dao->fetch()) {
      $distance = civicrm_api3("Distance", "calculate", [
        'postcode_from' => $dao->cont_pc,
        'postcode_to' => $dao->site_pc,
      ]);
      Civi::log()->debug('  distance = '.$distance);
      $query1 = "update civicrm_value_nbr_participation_data set nvpd_distance_volunteer_to_study_centre = %1 where entity_id = %2";
      $query1Params = [1 => [$distance, 'Integer'], 2 => [$case_id, 'Integer']];
      CRM_Core_DAO::executeQuery($query1, $query1Params);
    }
  }

  public static function setCaseDistance_old($case_id, $contact_postcode, $centre_id,  $centre_postcode) {
    # set distance to centre of a case entity
    $distance = civicrm_api3("Distance", "calculate", [
      'postcode_from' => $contact_postcode,
      'postcode_to' => $centre_postcode,
    ]);
    $query = "update civicrm_value_nbr_participation_data set nvpd_distance_volunteer_to_study_centre = %1 where entity_id = %2";
    $queryParams = [1 => [$distance, 'Integer'], 2 => [$case_id, 'Integer']];
    CRM_Core_DAO::executeQuery($query, $queryParams);
  }


  public static function set_dist_to_cuh($op, $objectName, $objectID, &$objectRef) {
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

}

?>
