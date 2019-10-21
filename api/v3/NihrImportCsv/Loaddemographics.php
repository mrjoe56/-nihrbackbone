<?php
use CRM_Nihrbackbone_ExtensionUtil as E;


/**
 * NihrImportCsv.Loaddemographics API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nihr_import_csv_Loaddemographics($params) {
  $returnValues = [];
  // get the csv import and processed folders
  $loadFolder = Civi::settings()->get('nbr_csv_import_folder');
  if ($loadFolder && !empty($loadFolder)) {
    // get all .csv files from folder
    //$csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*demographics.csv");
    $csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*.csv");
    // make sure it's sorted
    sort($csvFiles);

    // only use newest - last - file
    $csvFile = array_pop($csvFiles);

    // process file
    $import = new CRM_Nihrbackbone_NihrImportCsv('demographics', $csvFile, ';', TRUE);
    if ($import->validImportData()) {
      $returnValues = $import->processImport();

      return civicrm_api3_create_success($returnValues, $params, 'NihrImportCsv', 'loaddemographics');
    }
  }
  else {
    throw new API_Exception(E::ts('Folder for csv import (setting nbr_csv_import_folder) not found or empty'),  1001);
  }
}
