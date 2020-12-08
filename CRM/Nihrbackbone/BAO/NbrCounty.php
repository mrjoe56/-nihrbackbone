<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NbrCounty extends CRM_Nihrbackbone_DAO_NbrCounty {

  /**
   * Method to migrate counties from address for volunteers using standard migration files
   */
  public static function migrate() {
    $messages = [];
    $volunteer = new CRM_Nihrbackbone_NihrVolunteer();
    $fileName = Civi::settings()->get("nbr_csv_import_folder"). "volunteer_counties.csv";
    $csv = fopen($fileName, 'r');
    // get column headers
    $columnHeaders = [];
    $source = fgetcsv($csv, 0, "~");
    foreach ($source as $key => $value) {
      $columnHeaders[$key] = $value;
    }
    while (!feof($csv)) {
      $source = fgetcsv($csv, 0, "~");
      $row = [];
      foreach ($source as $key => $value) {
        $row[$columnHeaders[$key]] = $value;
      }
      if (!empty($row['county']) && !empty($row['address1']) && !empty($row['town']) && !empty($row['postcode'])) {
        $mappedCountyId = CRM_Nihrbackbone_NihrAddress::getCountyIdForSynonym(trim($row['county']));
        if ($mappedCountyId) {
          $contactId = $volunteer->getContactIdWithParticipantId($row['participant_id']);
          if ($contactId) {
            $query = "SELECT id FROM civicrm_address
                WHERE contact_id = %1 AND postal_code = %2 AND city = %3 AND street_address LIKE %4";
            $addressId = CRM_Core_DAO::singleValueQuery($query, [
              1 => [(int) $contactId, "Integer"],
              2 => [trim($row['postcode']), "String"],
              3 => [trim($row['town']), "String"],
              4 => ["%" . trim($row['address1']) . "%", "String"],
            ]);
            if ($addressId) {
              $update = "UPDATE civicrm_address SET country_id = %1, state_province_id = %2 WHERE id = %3";
              CRM_Core_DAO::executeQuery($update, [
                1 => [Civi::service('nbrBackbone')->getUkCountryId(), "Integer"],
                2 => [(int) $mappedCountyId, "Integer"],
                3 => [(int) $addressId, "Integer"],
              ]);
            }
          }
        }
        else {
          $messages[] = "Could not map county " . $row['county'] . " for address " . $row['address1'] . ", town "
            . $row['town'] . " and postcode " . $row['postcode']. " of participant id " . $row['participant_id'];
        }
      }
    }
    fclose($csv);
    return $messages;
  }

}
