<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for NIHR BioResource Contact Type
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 8 Nov 2022
 * @license AGPL-3.0

 */
class CRM_Nihrbackbone_NbrContactType {

  /**
   * Method to validate if contacts can be merged
   *
   * @param array $fields
   * @param CRM_Contact_Form_Merge $form
   * @param array $errors
   * @return void
   */
  public static function validateForm(array $fields, CRM_Contact_Form_Merge $form, array &$errors) {
    $remainingId = $form->getVar('_cid');
    $otherId = $form->getVar('_oid');
    // now get contact types of both
    $remainingContactTypes = self::getContactTypeIds((int) $remainingId);
    $otherContactTypes = self::getContactTypeIds((int) $otherId);
    $doNotMergeTypes = Civi::settings()->get('nbr_no_merge_contact_types');
    // check if allowed based on contact type(s) of remaining contact
    foreach ($remainingContactTypes as $remainingContactType) {
      if (isset($doNotMergeTypes[$remainingContactType])) {
        foreach ($otherContactTypes as $otherContactType) {
          if (in_array($otherContactType, $doNotMergeTypes[$remainingContactType])) {
            $errors[] = E::ts("Merge not allowed for contact types ");
            CRM_Core_Session::setStatus(E::ts("Merge not allowed for contact types"), E::ts("Can not merge"), "error");
          }
        }
      }
    }
    // if no errors yet, now check based on contact type(s) of contact to be trashed
    if (empty($errors)) {
      foreach ($otherContactTypes as $otherContactType) {
        if (isset($doNotMergeTypes[$otherContactType])) {
          foreach ($remainingContactTypes as $remainingContactType) {
            if (in_array($remainingContactType, $doNotMergeTypes[$otherContactType])) {
              $errors[] = E::ts("Merge not allowed for contact types ");
              CRM_Core_Session::setStatus(E::ts("Merge not allowed for contact types"), E::ts("Can not merge"), "error");
            }
          }
        }
      }
    }
  }

  /**
   * Method to get contact type ids and contact sub types for contact in a single array
   *
   * @param int $contactId
   * @return array
   */
  public static function getContactTypeIds(int $contactId): array {
    $contactTypes = [];
    try {
      $contacts = \Civi\Api4\Contact::get()
        ->addSelect('contact_type', 'contact_sub_type')
        ->addWhere('id', '=', $contactId)
        ->setLimit(1)
        ->execute();
      $contact = $contacts->first();
      $contactTypes[] = self::getContactTypeId($contact['contact_type']);
      foreach ($contact['contact_sub_type'] as $contactSubType) {
        $contactTypes[] = self::getContactTypeId($contactSubType);
      }
    }
    catch (API_Exception $ex) {
    }
    return $contactTypes;
  }

  /**
   * Method to get contact type id for a contact type name
   *
   * @param string $contactTypeName
   * @return int|null
   */
  public static function getContactTypeId(string $contactTypeName): ?int {
    $contactTypeId = NULL;
    try {
      $contactTypes = \Civi\Api4\ContactType::get()
        ->addSelect('id')
        ->addWhere('name', '=', $contactTypeName)
        ->setLimit(1)
        ->execute();
      $contactType = $contactTypes->first();
      $contactTypeId = (int) $contactType['id'];
    }
    catch (API_Exception $ex) {
    }
    return $contactTypeId;
  }

}
