<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class NbrGuardian to deal with guardian stuff
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 6 July 2021
 * @license AGPL-3.0
 * @errorrange 3000-3499
 */
class CRM_Nihrbackbone_NbrGuardian {

  /**
   * Method to replace email to and name to with guardian
   *
   * @param $contactId
   * @param $mailParams
   */
  public function replaceEmailAddressWithGuardian($contactId, &$mailParams) {
    if (CRM_Nihrbackbone_NihrVolunteer::hasActiveGuardian($contactId)) {
      $guardianData = $this->getGuardianEmailAddressAndName($contactId);
      if ($guardianData) {
        $mailParams['toEmail'] = $guardianData['email'];
        $mailParams['toName'] = $guardianData['name'];
      }
    }
  }

  /**
   * Method to get email address of active guardians of volunteer
   *
   * @param $volunteerId
   * @return bool|array
   */
  public function getGuardianEmailAddressAndName($volunteerId) {
    if (!empty($volunteerId)) {
      $query = "SELECT b.email, c.display_name
        FROM civicrm_relationship AS a
            JOIN civicrm_email AS b ON a.contact_id_b = b.contact_id AND b.is_primary = %1
            JOIN civicrm_contact AS c ON a.contact_id_b = c.id
        WHERE a.relationship_type_id = %2 AND a.is_active = %1 AND a.contact_id_a = %3
          AND (a.end_date IS NULL OR a.end_date > CURDATE())";
      $dao = CRM_Core_DAO::executeQuery($query, [
        1 => [1, "Integer"],
        2 => [Civi::service('nbrBackbone')->getGuardianRelationshipTypeId(), "Integer"],
        3 => [(int) $volunteerId, "Integer"],
      ]);
      if ($dao->fetch()) {
        return [
          'email' =>$dao->email,
          'name' => $dao->display_name,
          ];
      }
    }
    return FALSE;
  }

  /**
   * Alert box that email will be sent to guardian if applicable
   *
   * @param $contactIds
   * @param $contactData
   */
  public function setAlertGuardianInBuildForm($contactIds, $contactData) {
    foreach ($contactIds as $contactId) {
      if (CRM_Nihrbackbone_NihrVolunteer::hasActiveGuardian($contactId)) {
        if (isset($contactData[$contactId]['display_name'])) {
          CRM_Core_Session::setStatus("Volunteer " . $contactData[$contactId]['display_name']
            . " has an active guardian, email will be sent to the guardian even though you see the email of the volunteer in the Recipient(s) list", "Email goes to Guardian", "info");
        }
        else {
          CRM_Core_Session::setStatus("Volunteer has an active guardian, email will be sent to the guardian", "Email goes to Guardian", "info");
        }
      }
    }
  }

}
