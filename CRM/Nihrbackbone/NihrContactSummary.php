<?php
use CRM_Nihrbackbone_ExtensionUtil as E;
/**
 * Class for National BioResource distance to bioresource
 *
 * @author John Boucher
 * @date 26 Sep 2019                  last update 20/12/19
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NihrContactSummary {
  public static function nihrbackbone_civicrm_summary($contactID) {
    $query = "select Concat(coalesce(cp.display_name, ''),', ',coalesce(cc.display_name, ''),', ',coalesce(cs.display_name, '')) as panel_data
            from civicrm_value_nihr_volunteer_panel panel left join civicrm_contact cp on cp.id = panel.nvp_panel
            left join civicrm_contact cc on cc.id = panel.nvp_centre left join civicrm_contact cs on cs.id = panel.nvp_site
            where panel.entity_id  = %1 limit 3";

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [(int) $contactID, 'Integer']]);
    $index = 0;
    while ($dao->fetch()) {
      $panel_data[$index] = $dao->panel_data;
      $index++;
    }
    # get contact subtype
    $sub_types = CRM_Contact_BAO_Contact::getContactSubType($contactID);
    $contact_sub_types = implode('+',$sub_types);

    # get IDs for custom data items
    $participant_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_participant_id')['id'];
    $bioresource_custom_id = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomField('nva_bioresource_id')['id'];
    $participant_status = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerStatusCustomField('nvs_volunteer_status')['id'];
    # get values for data items
    $result = civicrm_api3('Contact', 'getsingle', [
      'return' => [$participant_custom_id, $bioresource_custom_id, $participant_status,],
      'id' => $contactID,
    ]);
    $participant_id = $result[$participant_custom_id];
    $bioresource_id = $result[$bioresource_custom_id];
    $status = $result[$participant_status];
    $row1_data = '';
    $row2_data = '';
    $row3_data = '';
    if (!empty($panel_data[0])) {
      $row1_data = $panel_data[0];
    }
    if (!empty($panel_data[1])) {
      $row2_data = $panel_data[1];
    }
    if (!empty($panel_data[2])) {
      $row3_data = $panel_data[2];
    }
    # create template datastring
    $datastring = '<span id="nbr_data">'.$contactID.'~'.$participant_id.'~'.$bioresource_id.'~'.$row1_data.'~'.$row2_data.'~'.$row3_data.'~'.$status.'~'.$contact_sub_types.'</span>';
    # and pass to custom template
    CRM_Core_Region::instance('page-header')->add(['markup' => $datastring,]);
    CRM_Core_Region::instance('page-header')->add(['template' => 'CRM/Nihrbackbone/nbr_contact_summary.tpl',]);
  }
}
?>
