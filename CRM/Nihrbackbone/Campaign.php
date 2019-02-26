<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource specific Campaign processing
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Feb 2019
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_Campaign {

  private $_projectCampaignTypeId = NULL;

  /**
   * CRM_Nihrbackbone_Campaign constructor.
   */
  public function __construct() {
    $this->_projectCampaignTypeId = CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCampaignTypeId();
  }

  /**
   * Method to process the buildForm hook for CRM_Nihrbackbone_Campaign(
   * @param $form
   */
  public function buildForm(&$form) {
    $projectId = $form->getVar('_campaignId');
    // add entity ref to form
    $form->addEntityRef('researcher_id', E::ts('Researcher'), [
      'api' => ['params' => ['contact_sub_type' => 'nihr_researcher']],
      'multiple' => TRUE,
    ], FALSE);
    // if update, set default values for researcher
    if ($form->_action == CRM_Core_Action::UPDATE) {
      $defaults['researcher_id'] = $this->getProjectResearchers($projectId);
      if (!empty($defaults)) {
        $form->setDefaultValues($defaults);
      }
    }
    // add template with jQuery to position researcher
    CRM_Core_Region::instance('page-body')->add(['template' => 'CRM/Nihrbackbone/Campaign/addResearcher.tpl']);
  }
  private function getProjectResearchers($projectId) {

  }

}
