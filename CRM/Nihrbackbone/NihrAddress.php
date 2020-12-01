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

}

?>
