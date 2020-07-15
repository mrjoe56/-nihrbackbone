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
  public function addConsent ($contactId, $caseID, $consent_status, $data, $logger)
  {
    // caseID cannot be empty, a consent is always linked to a case
    if ($caseID == '') {
      Civi::log()->error("Case ID is missing in " . __METHOD__);
    }
    else {
      $consentVersion = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_version', 'id');
      $informationLeafletVersion = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_information_leaflet_version', 'id');
      $consentStatus = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_status', 'id');

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
        Civi::log()->debug('Error: ' . $ex->getMessage());
      }


      if ($count == 0) {
        // todo check if information_leaflet_version and consent_version exist on database
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
            'case_id' => (int)$caseID,
            'subject' => "Consent"  // todo add type of consent to subject line
          ]);

          //

          $logger->logMessage('Volunteer consent succesfully loaded/updated');
        } catch (CiviCRM_API3_Exception $ex) {
          $logger->logMessage('Error message when adding volunteer consent ' . $contactId . ' ' . $ex->getMessage(), 'error');
        }
      }
    }
  }
}

?>

