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
  // get the csv import and processed folders
  $folder = 'nbr_folder_'.$params['dataSource'];
  $loadFolder = Civi::settings()->get($folder);
  if ($loadFolder && !empty($loadFolder)) {
    // get all .csv files from folder
    //$csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*demographics.csv");
    $csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*.csv");
    // make sure it's sorted
    sort($csvFiles);

    // only use newest - last - file
    $csvFile = array_pop($csvFiles);

    // todo firstRowHeaders always needs to be true as used for the mapping
    // process file
    // todo $import = new CRM_Nihrbackbone_NihrImportCsv('demographics', $csvFile, $params['separator'], TRUE);
    $import = new CRM_Nihrbackbone_NihrImportCsv('demographics', $csvFile);
    if ($import->validImportData()) {
      $import->processImport();

      return civicrm_api3_create_success([], $params, 'NihrImportCsv', 'loaddemographics');
    }
  }
  else {
    throw new API_Exception(E::ts('Folder for csv import (setting nbr_csv_import_folder) not found or empty'),  1001);
  }
}
