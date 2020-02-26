<?php
require_once 'nihrbackbone.civix.php';
use CRM_Nihrbackbone_ExtensionUtil as E;

/** Implements hook_civicrm_post JB 18/12/19 */
function nihrbackbone_civicrm_post($op, $objectName, $objectID, &$objectRef) {

  # if editing a primary Address activity for a participant or site - update distance to centre values for linked cases
  if ($op == 'edit' && $objectName == 'Address' && $objectRef->is_primary == 1) {
    CRM_Nihrbackbone_NihrAddress::setDistanceToCentre($op,$objectName, $objectID,$objectRef);
  }
  # if creating (opening) a case (activity type 13) :
  #  get PID and postcode, site ID and postcode, and set distance to centre for this case
  if ($op == 'create' && $objectName == 'Activity' && $objectRef->activity_type_id == 13) {
    $query = "select cc.contact_id, adr.postal_code as cont_pc, projdat.npd_site, site_adr.postal_code as site_pc
             from civicrm_case_contact cc, civicrm_contact c, civicrm_address adr, civicrm_address site_adr,
             civicrm_value_nihr_participation_data partdat, civicrm_value_nihr_project_data projdat
             where cc.contact_id = c.id and c.id = adr.contact_id and cc.case_id = partdat.entity_id
             and partdat.nvpd_project_id = projdat.entity_id and projdat.npd_site = site_adr.contact_id and case_id = %1";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$objectRef->case_id, 'Integer']]);
    if ($dao->fetch()) {
      CRM_Nihrbackbone_NihrAddress::setCaseDistance($objectRef->case_id, $dao->cont_pc, $dao->npd_site,  $dao->site_pc);
    }
  }
  # if editing a primary Address activity for a participant - update distance to Addenbrookes value
  if ($objectName == 'Address' && $objectRef->is_primary) {
    if ($op == 'edit' || $op == 'create') {

      try {
        $contactType = (string)civicrm_api3('Contact', 'getvalue', [
          'id' => $objectRef->contact_id,
          'return' => 'contact_type',
        ]);
        if ($contactType == "Individual") {
          CRM_Nihrbackbone_NihrAddress::setContDistance($op, $objectName, $objectID, $objectRef);
        }
      } catch (CiviCRM_API3_Exception $ex) {
      }
    }
  }

}

/** Implements hook_civicrm_summary JB 27/01/20 */
function nihrbackbone_civicrm_summary($contactID, &$content, &$contentPlacement) {
  CRM_Nihrbackbone_NihrContactSummary::nihrbackbone_civicrm_summary($contactID);
  #Civi::log()->debug('civicrm_summary hook - $contactID : ' . $contactID);
}

/** Implements hook_civicrm_tabset JB 27/01/20 */
function nihrbackbone_civicrm_tabset($tabsetName, &$tabs, $context) {
  if ($tabsetName == 'civicrm/contact/view') {
    $customGroupId = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerIdsCustomGroup('id');
    foreach ($tabs as $key => $value) {
      if ($tabs[$key]['id'] == $customGroupId) {
        unset ($tabs[$key]);
      }
    }
  }
}
/**
 * Implements hook_civicrm_custom.
 *
 */
function nihrbackbone_civicrm_custom($op, $groupID, $entityID, &$params) {
  /** if this custom post is to add or edit General observations, and parameters are present, update the bmi from ht and wt */
  if ($op == 'create' || $op == 'edit') {

    if ($groupID == CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerGeneralObservationsCustomGroup('id')&&count($params)>1) {
      $weight = NULL;                                                          // initialise ht, wt
      $height = NULL;
      foreach ($params as $key => $param) {                                    // retrieve ht, wt from paramas
        if ($param['column_name'] == 'nvgo_weight_kg') {
          $weight = $param['value'];
        }
        if ($param['column_name'] == 'nvgo_height_m') {
          $height = $param['value'];
        }
      }
      if ($weight && $height) {                                                // if we have ht/wt values ..
        $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
        $bmi = $volunteer->calculateBmi($weight, $height);                     //   calculate bmi
        writeBmi($entityID, $bmi);                                             //   and save
      }
      else {                                                                   // else
        writeBmi($entityID, 0);                                            //   save bmi as 0
      }
    }
  }
}

function writeBmi($entityID, $bmi) {

  try {
    civicrm_api3('Contact', 'create', [
      'id' => $entityID,
      'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()
        ->getGeneralObservationCustomField('nvgo_bmi', 'id') => $bmi,
    ]);
  }
  catch (CiviCRM_API3_Exception $ex) {
    Civi::log()->error("This is an error when the BMI is updated");
  }
}

function nihrbackbone_civicrm_buildForm($formName, &$form) {
  if ($formName == 'CRM_Contact_Form_CustomData') {
    CRM_Core_Resources::singleton()->addScriptFile('nihrbackbone', 'resources/nbrcustom.js', 10, 'page-body');
  }
}

/**
 * Implements hook_civicrm_custom.
 *
 */

function nihrbackbone_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  //Civi::log()->debug('backbone.validateForm') ;
  //foreach ($fields as $value)
    //Civi::log()->debug(strval($value)) ;

  if ($form instanceof CRM_Nihrbackbone_Form_ImportCsvMap) {
    CRM_Nihrbackbone_Form_ImportCsvMap::validateForm($fields, $form, $errors);
  }
}

/**
 * Implements hook_civicrm_links().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_links/
 */
function nihrbackbone_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($op == 'campaign.dashboard.row' && $objectName == "Campaign") {
    $project = new CRM_Nihrbackbone_NihrProject();
    if ($project->isNihrProject($objectId)) {
      // only if the campaign is a project
      $links[] = [
        'name' => ts('Import'),
        'url' => 'civicrm/nihrbackbone/form/importcsvselect',
        'title' => 'Import',
        'class' => 'no-popup',
        'qs' => 'reset=1&pid=%%id%%',
        ];
      try {
        $csId = (int) civicrm_api3('OptionValue', 'getvalue', [
          'return' => "value",
          'option_group_id' => "custom_search",
          'name' => "CRM_Nbrprojectvolunteerlist_Form_Search_VolunteerList",
        ]);
      } catch (CiviCRM_API3_Exception $ex) {
        $csId = NULL;
      }
      if ($csId) {
        $links[] = [
          'name' => ts('Volunteers'),
          'url' => 'civicrm/contact/search/custom',
          'title' => 'Volunteers',
          'class' => 'no-popup',
          'qs' => 'reset=1&csid=' . $csId,
      ];
      }
    }
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu/
 */
function nihrbackbone_civicrm_navigationMenu(&$menu) {
  _nihrbackbone_civix_insert_navigation_menu($menu, 'Campaigns', array(
    'label' => E::ts('NIHR BioResource Studies'),
    'name' => 'nihrstudies',
    'url' => 'civicrm/nihrbackbone/page/nihrstudy',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
  ));

  _nihrbackbone_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function nihrbackbone_civicrm_config(&$config) {
  _nihrbackbone_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function nihrbackbone_civicrm_xmlMenu(&$files) {
  _nihrbackbone_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function nihrbackbone_civicrm_install() {
  _nihrbackbone_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function nihrbackbone_civicrm_postInstall() {
  _nihrbackbone_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function nihrbackbone_civicrm_uninstall() {
  _nihrbackbone_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function nihrbackbone_civicrm_enable() {
  _nihrbackbone_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function nihrbackbone_civicrm_disable() {
  _nihrbackbone_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function nihrbackbone_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _nihrbackbone_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function nihrbackbone_civicrm_managed(&$entities) {
  _nihrbackbone_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function nihrbackbone_civicrm_caseTypes(&$caseTypes) {
  _nihrbackbone_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function nihrbackbone_civicrm_angularModules(&$angularModules) {
  _nihrbackbone_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function nihrbackbone_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _nihrbackbone_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function nihrbackbone_civicrm_entityTypes(&$entityTypes) {
  _nihrbackbone_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function nihrbackbone_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function nihrbackbone_civicrm_navigationMenu(&$menu) {
  _nihrbackbone_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _nihrbackbone_civix_navigationMenu($menu);
} // */

