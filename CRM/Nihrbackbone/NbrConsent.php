<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for Consent to bioresource
 *
 * @author Carola Kanz
 * @date 18 Dec 2019
 * @license AGPL-3.0
 */

class CRM_Nihrbackbone_NbrConsent
{
  public function addConsent ($contactId, $caseID, $consent_status, $data)
  {
    /*
    // caseID cannot be empty, a consent is always linked to a case
    if ($caseID == '') {
      Civi::log()->debug('Error: ') . " case ID is missing";
    }
    else {
      //todo $consentVersion = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getGeneralObservationCustomField('nvc_consent_version', 'id');
      $consentVersion = 'custom_114';
      $informationLeafletVersion = 'custom_113';
      $consentStatus = 'custom_115';

      $consentDate = date('Y-m-d',strtotime($data['consent_date']));

      $testParams = [
        'sequential' => 1,
        'target_contact_id' => $contactId,
        'is_current_revision' => 1,
        'activity_type_id' => "nihr_consent",
        $consentVersion => $data['consent_version'],
        $informationLeafletVersion => $data['information_leaflet_version'],
        'activity_date_time' => $consentDate,

      ];

      try {
        $count = (int) civicrm_api3('Activity', 'getcount', $testParams);
      }
      catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->debug('Error: ') . $ex->getMessage();
      }


      if ($count == 0) {
        // --- add to case, if given
        try {
          $result = civicrm_api3('Activity', 'create', [
            'source_contact_id' => "user_contact_id",
            'target_id' => $contactId,
            'activity_type_id' => "nihr_consent",
            'status_id' => "Completed",
            $consentVersion => $data['consent_version'],
            $informationLeafletVersion => $data['information_leaflet_version'],
            'activity_date_time' => $data['consent_date'],
            $consentStatus => $consent_status,
            'case_id' => $caseID,
          ]);

          $this->_logger->logMessage('Volunteer consent succesfully loaded/updated');
        } catch (CiviCRM_API3_Exception $ex) {
          $this->_logger->logMessage('Error message when adding volunteer consent ' . $contactID . $ex->getMessage(), 'error');
        }
      }
    } */
    ;
  }
}

?>

