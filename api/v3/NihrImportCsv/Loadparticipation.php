<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NihrImportCsv.Loadparticipation API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC/API+Architecture+Standards
 */
function _civicrm_api3_nihr_import_csv_Loadparticipation_spec(&$spec) {
  $spec['project_id'] = [
    'name' => 'project_id',
    'title' => 'NBR Project ID',
    'description' => 'Unique Project ID (not project code!)',
    'api.required' => 1,
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * NihrImportCsv.Loadparticipation API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws
 */
function civicrm_api3_nihr_import_csv_Loadparticipation($params) {
  $returnValues = [];
  // get the csv import and processed folders
  $loadFolder = Civi::settings()->get('nbr_csv_import_folder');
  $processedFolder = Civi::settings()->get('nbr_csv_processed_folder');
  if ($loadFolder && !empty($loadFolder)) {
    // get all .csv files from folder
    $csvFiles = glob($loadFolder . DIRECTORY_SEPARATOR . "*.csv");
    // make sure it's sorted
    sort($csvFiles);
    foreach ($csvFiles as $csvFile) {
      // process file
      $import = new CRM_Nihrbackbone_NihrImportCsv('participation', $csvFile, $params);
      if ($import->validImportData($params['project_id'])) {
        $returnValues = $import->processImport();
        // move file once succesfully imported
        if ($processedFolder) {
          $processedFile = $processedFolder . DIRECTORY_SEPARATOR . basename($csvFile);
          rename($csvFile, $processedFile);
        }
      }
    }
    return civicrm_api3_create_success($returnValues, $params, 'NihrImportCsv', 'loadparticipation');
  }
  else {
    throw new API_Exception(E::ts('Folder for csv import (setting nbr_csv_import_folder) not found or empty'),  1001);
  }
}
