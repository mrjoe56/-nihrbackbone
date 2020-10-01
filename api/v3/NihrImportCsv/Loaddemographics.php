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
  Civi::log()->debug('start tijd is: ' . date('d-m-Y H:i:s'));
  $returnValues = [];
  // get the csv import and processed folders
  $folder = 'nbr_folder_'.$params['dataSource'];
  $loadFolder = Civi::settings()->get($folder);
  if ($loadFolder && !empty($loadFolder)) {
    // get all .csv files from folder
    $csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*.csv");
    // sort
    sort($csvFiles);
    // only use newest - last - file
    $csvFile = array_pop($csvFiles);

    if (!$csvFile) {
      throw new API_Exception(E::ts('Folder for import (' . $loadFolder . ') does not contain csv files'),  1001);
    }

    // process file
    $import = new CRM_Nihrbackbone_NihrImportDemographicsCsv($csvFile, $params);
    if ($import->validImportData()) {
      $returnValues = $import->processImport();
      Civi::log()->debug('eind tijd is: ' . date('d-m-Y H:i:s'));
      return civicrm_api3_create_success($returnValues, $params, 'NihrImportCsv', 'loaddemographics');
    }
  }
  else {
    throw new API_Exception(E::ts('Folder for import (' . $folder . ') not found or empty'),  1001);
  }
}
