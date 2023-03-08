<?php
require_once 'nihrbackbone.civix.php';
use CRM_Nihrbackbone_ExtensionUtil as E;
use \Symfony\Component\DependencyInjection\ContainerBuilder;
use \Symfony\Component\DependencyInjection\Definition;

/**
 * Implements hook_civicrm_pre
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_pre/
 */
function nihrbackbone_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == "Case" && $op == "edit") {
    $studyStatusCustomField = "custom_" . CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id');
    // set case id in session if study status custom field is about to change
    if (isset($params[$studyStatusCustomField])) {
      $currentStatus = CRM_Nihrbackbone_NbrVolunteerCase::getCurrentStudyStatus($id);
      if ($currentStatus != $params[$studyStatusCustomField]) {
        $session = CRM_Core_Session::singleton();
        $session->recalcForCaseId = $id;
      }
    }
  }
  if ($objectName == "Mailing" && $op == "delete") {
    // if mailing was not completed, reset volunteers with invitation pending
    CRM_Nihrbackbone_BAO_NbrMailing::resetInvitationPending($id);
  }
}

/**
 * Implements hook_civicrm_postMailing
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postMailing/
 */
function nihrbackbone_civicrm_postMailing($mailingId) {
  // check if mailing exists in NbrMailing and process if so
  if (CRM_Nihrbackbone_BAO_NbrMailing::isNbrMailing($mailingId)) {
    CRM_Nihrbackbone_BAO_NbrMailing::postMailing($mailingId);
  }
}

/**
 * Implements hook_civicrm_links()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_links/
 */
function nihrbackbone_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ($objectName == "Case") {
    if ($op == "case.actions.primary" || $op == "case.selector.actions") {
      foreach ($links as $key => $link) {
        if ($link['name'] == "Assign to Another Client" && $link['ref'] == "reassign" && $link['url'] == "civicrm/contact/view/case/editClient") {
          unset($links[$key]);
        }
      }
    }
  }
}
/**
 * Implements hook_civicrm_container()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_container/
 */
function nihrbackbone_civicrm_container(ContainerBuilder $container) {
  $container->addCompilerPass(new Civi\Nihrbackbone\NbrBackboneContainer());
}

/** Implements hook_civicrm_post 01/12/20 */
function nihrbackbone_civicrm_post($op, $objectName, $objectID, &$objectRef) {
  if ($objectName == "Activity") {
    // if new BulkMail, check if needs to be filed on case
    if ($op == "create" && $objectRef->activity_type_id == Civi::service('nbrBackbone')->getBulkMailActivityTypeId()) {
      if (CRM_Core_Transaction::isActive()) {
        CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, 'CRM_Nihrbackbone_BAO_NbrMailing::fileBulkMailOnCase', [$objectID, $objectRef]);
      }
      else {
        CRM_Nihrbackbone_BAO_NbrMailing::fileBulkMailOnCase($objectID, $objectRef);
      }
    }
    // if new invite activity, set status to invited and invite date to now
    if (isset($objectRef->activity_type_id) && $objectRef->activity_type_id == CRM_Nihrbackbone_BackboneConfig::singleton()->getInviteProjectActivityTypeId()) {
      CRM_Nihrbackbone_NbrInvitation::postInviteHook($op, $objectID, $objectRef);
    }
  }

  # if editing a primary Address activity for a participant or study site - set distance to study site for all linked cases
  if ($op == 'edit' && $objectName == 'Address' && $objectRef->is_primary == 1) {
    CRM_Nihrbackbone_NihrAddress::set_distance_to_studysite_contact($objectRef);
  }
  # if creating (opening) a case -  set distance to study site for the case
  if ($op == 'create' && $objectName == 'Activity' && $objectRef->activity_type_id == Civi::service('nbrBackbone')->getOpenCaseActivityTypeId()) {
    CRM_Nihrbackbone_NihrAddress::set_case_distance($objectRef->case_id);
  }
  # if study edit - set distance to study site for all cases linked to the study
  if ($op == 'edit' && $objectName == 'Campaign') {
    if (CRM_Core_Transaction::isActive()) {
      CRM_Core_Transaction::addCallback(CRM_Core_Transaction::PHASE_POST_COMMIT, 'CRM_Nihrbackbone_NihrAddress::set_distance_to_studysite_study', [$objectRef->id]);
    }
    else {
      CRM_Nihrbackbone_NihrAddress::set_distance_to_studysite_study($objectRef->id);
    }
  }
}
/** implements hook postCommit */
function nihrbackbone_civicrm_postCommit($op, $objectName, $objectId, $objectRef) {
  // check contact identifiers and panel data after contact merge, see https://www.wrike.com/open.htm?id=827910828
  if ($objectName == "Contact" && $op == "merge") {
    CRM_Nihrbackbone_NihrVolunteer::cleanIdentifiers((int) $objectId);
    CRM_Nihrbackbone_NihrVolunteer::cleanPanelData((int) $objectId);
    CRM_Nihrbackbone_NihrAddress::resurrectOverwrittenAddressDuringMerge((int) $objectId);
  }
}

/** Implements hook_civicrm_post_case_merge */
function nihrbackbone_civicrm_post_case_merge($mainContactId, $mainCaseId, $otherContactId, $otherCaseId, $changeClient) {
  if ($changeClient || $otherContactId != $mainContactId) {
    // issue 9003 - merge recruitment cases into one (see https://www.wrike.com/open.htm?id=692748431)
    CRM_Nihrbackbone_NbrRecruitmentCase::mergeRecruitmentCases((int) $mainContactId);
    // issue 7827 - resurrect participation data after case reassigned to new client
    CRM_Nihrbackbone_NbrVolunteerCase::resurrectParticipationData((int) $mainContactId, (int) $otherContactId, (int) $mainCaseId, (int) $otherCaseId);
  }
}

/** Implements hook_civicrm_summary  27/01/20 */
function nihrbackbone_civicrm_summary($contactID, &$content, &$contentPlacement) {
  CRM_Nihrbackbone_NihrContactSummary::nihrbackbone_civicrm_summary($contactID);
}

/** Implements hook_civicrm_tabset  27/01/20 */
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
 * Implements hook_civicrm_custom. JB2
 *
 */
function nihrbackbone_civicrm_custom($op, $groupID, $entityID, &$params) {

  if ($groupID == Civi::service('nbrBackbone')->getContactIdentityCustomGroupId()) {
    CRM_Nihrbackbone_NbrContactIdentity::checkDuplicatContactIdentity($op,$groupID, $entityID, $params);
  }

  // if group = participation data
  if ($groupID == CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationDataCustomGroup('id')) {
    foreach ($params as $paramKey => $paramValues) {
      // if status field
      if (isset($paramValues['custom_field_id']) && $paramValues['custom_field_id'] == CRM_Nihrbackbone_BackboneConfig::singleton()->getParticipationCustomField('nvpd_study_participation_status', 'id')) {
        // check if eligibility should be recalculated
        CRM_Nihrbackbone_NbrVolunteerCase::checkEligibilityRecalculation($entityID, $paramValues);
        // if study status is invited, customInviteHook
        if ($paramValues['value'] == 'study_participation_status_invited') {
          CRM_Nihrbackbone_NbrInvitation::customInviteStatusHook($op, $entityID, $paramValues);
        }
      }
    }
  }

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

/**
 * Implements hook civicrm_buildForm
 */
function nihrbackbone_civicrm_buildForm($formName, &$form) {  # jb2
  // set add new flag to yes and freeze for address/email/phone, see https://www.wrike.com/open.htm?id=692748431
  if ($form instanceof CRM_Contact_Form_Merge) {
    CRM_Nihrbackbone_NihrVolunteer::buildFormMerge($form);
    // add template to hide location blocks
    CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/nbr_merge_location_hide.tpl',]);
  }

  if ($form instanceof CRM_Case_Form_CustomData) {
    CRM_Nihrbackbone_NbrVolunteerCase::buildFormCustomData($form);
  }
  if ($form instanceof CRM_Case_Form_CaseView) {
    CRM_Nihrbackbone_NbrVolunteerCase::buildFormCaseView($form);
  }
  if($form instanceof CRM_Contact_Form_Inline_Phone) {
    CRM_Core_Resources::singleton()->addScriptFile('nihrbackbone', 'templates/CRM/Nihrbackbone/nbr_phone.js');
  }

  if ($formName == 'CRM_Contact_Form_CustomData') {
    // validate custom data form
    CRM_Nihrbackbone_NihrValidation::customFormConfig($formName, $form);
  }

  // call js utils
  CRM_Core_Resources::singleton()->addScriptFile('nihrbackbone', 'templates/CRM/Nihrbackbone/nbr_utils.js');

}

/**
 * Implements hook_civicrm_custom.
 *
 */

function nihrbackbone_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  // validate if contact types can be merged
  if ($form instanceof CRM_Contact_Form_Merge) {
    CRM_Nihrbackbone_NbrContactType::validateForm($fields, $form, $errors);
  }

  // validate Case Form
  if ($form instanceof CRM_Case_Form_Case) {
    CRM_Nihrbackbone_NbrVolunteerCase::validateForm($fields, $form, $errors);
  }

  // if custom form and contact identities
  if ($form instanceof CRM_Contact_Form_CustomData) {
    $groupId = $form->getVar("_groupID");
    if ($groupId == Civi::service('nbrBackbone')->getContactIdentityCustomGroupID()) {
      CRM_Nihrbackbone_NbrContactIdentity::validateForm($fields, $form, $errors);
      CRM_Nihrbackbone_NihrValidation::validateAlias($formName, $fields, $files, $form, $errors);
    }
  }
}

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function nihrbackbone_civicrm_config(&$config) {
  // ugly hack to ensure contact identifiers are not inline editable
  $newCallBack = serialize(['CRM_Nihrbackbone_NbrContactIdentity', 'getMultiRecordFieldList']);
  $query = "UPDATE civicrm_menu SET page_callback = %1 WHERE path = %2";
  CRM_Core_DAO::executeQuery($query, [
    1 => [$newCallBack, "String"],
    2 => ["civicrm/ajax/multirecordfieldlist", "String"],
  ]);
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

