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

  /**
   * Method to check if consent version is a valid one
   *
   * @param $consentVersion
   * @return bool
   */
  public function isValidConsentVersion($consentVersion) {
    $validVersions = Civi::settings()->get('nbr_considered_consent_versions');
    if (in_array($consentVersion, $validVersions)) {
      return TRUE;
    }
    return FALSE;
  }

}

