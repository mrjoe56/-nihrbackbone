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
  public function addConsent($contactId, $caseID, $consent_status, $subject, $data, $logger)
  {
    // caseID cannot be empty, a consent is always linked to a case
    if ($caseID == '') {
      Civi::log()->error("Case ID is missing in " . __METHOD__);
    } else {
      $consentVersion = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_version', 'id');
      $informationLeafletVersion = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_information_leaflet_version', 'id');
      $consentStatus = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consent_status', 'id');
      $consentedBy = 'custom_' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_consented_by', 'id');
      $geneticFeedback = 'custom_ ' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_genetic_feedback', 'id');
      $inviteType = 'custom_ ' . CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerConsentCustomField('nvc_invite_type', 'id');
      $consentDate = date('Y-m-d', strtotime($data['consent_date']));

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
        $count = (int)civicrm_api3('Activity', 'getcount', $testParams);
      } catch (CiviCRM_API3_Exception $ex) {
        Civi::log()->debug('Error: ' . $ex->getMessage());
      }


      if ($count == 0) {
        // consent not yet on Civi - add
        // &&& todo check if information_leaflet_version and consent_version exist on database

        // *** check if 'consented by' has got a 'BioResourcer' record; if not, add name to details
        $details = '';
        $consented_by = '';

        $names = explode(' ', $data['consented_by']);
        if ($names[0] <> '' && isset($names[1])) {
          $result = civicrm_api3('Contact', 'get', [
            'sequential' => 1,
            'first_name' => $names[0],
            'last_name' => $names[1],
            'group' => "nbr_bioresourcers",
          ]);

          if ($result['count'] == 0) {
            $details = 'consented by ' . $data['consented_by'];
          } else {
            $consented_by = $result['id'];
          }
        }

        // **** --- add consent to case
        $consentDate = new DateTime($data['consent_date']);
        try {
          $result2 = civicrm_api3('Activity', 'create', [
            'source_contact_id' => "user_contact_id",
            'target_id' => $contactId,
            'activity_type_id' => "nihr_consent",
            'status_id' => "Completed",
            $consentVersion => $data['consent_version'],
            $informationLeafletVersion => $data['information_leaflet_version'],
            'activity_date_time' => $consentDate->format('Y-m-d'),
            $consentStatus => $consent_status,
            'case_id' => (int)$caseID,
            $consentedBy => $consented_by,
            'details' => $details,
            $geneticFeedback => $data['genetic_feedback'],
            $inviteType => $data['invite_type'],
            'subject' => $subject,
          ]);

          //


        } catch (CiviCRM_API3_Exception $ex) {
          $logger->logMessage('Error message when adding volunteer consent ' . $contactId . ' ' . $ex->getMessage(), 'error');
        }
      }
    }
  }
}

?>

