<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Class for National BioResource CSV Importer (generic)
 *
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 26 Mar 2019
 * @license AGPL-3.0
 */
class CRM_Nihrbackbone_NihrImportCsv
{
  private $_csvFile = NULL;
  private $_importId = NULL;
  private $_separator = NULL;
  private $_firstRowHeaders = NULL;
  private $_csv = NULL;
  private $_studyId = NULL;
  private $_columnHeaders = [];
  private $_imported = NULL;
  private $_failed = NULL;
  private $_read = NULL;
  private $_originalFileName = NULL;

  /**
   * CRM_Nihrbackbone_NihrImportCsv constructor.
   *
   * @param string $csvFileName
   * @param array $additionalParameters
   *
   * @throws Exception when error in logMessage
   */

  public function __construct($csvFileName, $additionalParameters = [])
  {
    if (isset($additionalParameters['separator'])) {
      $this->_separator = $additionalParameters['separator'];
    } else {
      $this->_separator = ';';
    }

    if (isset($additionalParameters['firstRowHeaders'])) {
      $this->_firstRowHeaders = $additionalParameters['firstRowHeaders'];
    } else {
      $this->_firstRowHeaders = 'TRUE';
    }

    $this->_failed = 0;
    $this->_imported = 0;
    $this->_read = 0;
    $this->_importId = uniqid(rand());
    if (isset($additionalParameters['originalFileName'])) {
      $this->_originalFileName = $additionalParameters['originalFileName'];
    } else {
      $this->_originalFileName = $csvFileName;
    }

    if (!empty($csvFileName)) {
      $this->_csvFile = $csvFileName;
    } else {
      $message = E::ts('Empty parameter for name of csv file to be imported.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
    }
  }

  /**
   * Method to check if the import data is valid (depending on type)
   *
   * @param null $studyId
   * @return bool
   * @throws
   */
  public function validImportData($studyId = NULL)
  {
    // study id required
    if ($studyId) {
      $study = new CRM_Nihrbackbone_NbrStudy();
      if (!$study->studyExists($studyId)) {
        $message = E::ts('No study with ID ') . $studyId . E::ts(' found, csv import aborted.');
        CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
        return FALSE;
      }
      else {
        $this->_studyId = $studyId;
      }
    } else {
      $message = E::ts('No studyID parameter passed for participation csv import, is mandatory. Import aborted');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      return FALSE;
    }
    // does the file exist
    if (!file_exists($this->_csvFile)) {
      $message = E::ts('Could not find csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__ . E::ts(', import aborted.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      return FALSE;
    }
    // can i open
    $this->_csv = fopen($this->_csvFile, 'r');
    if (!$this->_csv) {
      $message = E::ts('Could not open csv file ') . $this->_csvFile . E::ts(' in ') . __METHOD__ . E::ts(', import aborted.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
      return FALSE;
    }
    // is there any data?
    $data = fgetcsv($this->_csv, 0, $this->_separator);
    if (!$data || empty($data)) {
      $message = E::ts('No data in csv file ') . $this->_csvFile . E::ts(', no data imported');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
      fclose($this->_csv);
      return FALSE;
    }
    // we only expect 1 field in the csv file
    if (count($data) > 1) {
      $message = E::ts('CSV Import of participation expects only 1 column with participantID, more columns detected. First column will be used as participantID, all other columns will be ignored.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'warning');
    }
    // if we have column headers we can ignore the first line else rewind to start of file
    if (!$this->_firstRowHeaders) {
      rewind($this->_csv);
    } else {
      foreach ($data as $key => $value) {
        $this->_columnHeaders[$key] = $value;
      }
    }
    return TRUE;
  }

  /**
   * Method to process import
   *
   * @param string $recallGroup
   * @return array|void
   * @throws Exception
   */
  public function processImport($recallGroup = NULL) {
    set_time_limit(0);
    $this->import($recallGroup);
    fclose($this->_csv);
  }

  /**
   * Method to process the participation import (participation id)
   *
   * @throws
   */
  private function import($recallGroup = NULL)   {
    $studyNumber = CRM_Nihrbackbone_NbrStudy::getStudyNameWithId($this->_studyId);
    $vol = new CRM_Nihrbackbone_NihrVolunteer();
    $dataOnly = CRM_Nihrbackbone_NbrStudy::isDataOnly((int) $this->_studyId);
    while (!feof($this->_csv)) {
      $data = fgetcsv($this->_csv, 0, $this->_separator);
      if ($data) {
        $data[0] = preg_replace('/\xc2\xa0/', '', $data[0]);
        if (!empty($data[0])) {
          $this->_read++;
          $contactId = $vol->findVolunteerByIdentity($data[0], 'cih_type_participant_id');
          if (!$contactId) {
            $this->_failed++;
            $message = E::ts('Could not find a volunteer with participantID ') . $data[0] . E::ts(', not imported to study.');
            CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
          }
          else {
            if ($this->canImportVolunteer($contactId, $data[0])) {
              $volunteerCaseParams = [
                'study_id' => $this->_studyId,
                'contact_id' => $contactId,
                'case_type' => 'participation',
              ];
              if ($recallGroup) {
                $volunteerCaseParams['recall_group'] = $recallGroup;
              }
              try {
                $case = civicrm_api3('NbrVolunteerCase', 'create', $volunteerCaseParams);
                // https://www.wrike.com/open.htm?id=1011004470 - if study is data only, set status of volunteer to participated
                if ($dataOnly) {
                  CRM_Nihrbackbone_NbrStudy::processDataOnlyImport((int) $case['case_id'], $contactId);
                }
                $this->_imported++;
                $message = E::ts('Volunteer with participantID ') . $data[0] . E::ts(' succesfully added to study ') . $studyNumber;
                CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
              }
              catch (CiviCRM_API3_Exception $ex) {
                $this->_failed++;
                $message = E::ts('Error message when adding volunteer with contactID ') . $contactId
                  . E::ts(' to study ') . $studyNumber . E::ts(' from API NbrVolunteerCase create: ')
                  . $ex->getMessage();
                CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName, 'error');
              }
            }
          }
        }
      }
    }
    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url("civicrm/nihrbackbone/page/nbrimportlog", "reset=1&type=participation&r=" . $this->_read . "&i=" . $this->_imported . "&f=" . $this->_failed . "&iid=" . $this->_importId, TRUE));
  }

  /**
   * Check if volunteer can be imported. Not if:
   * - already on study
   * - withdrawn
   * - redundant
   * - deceased
   *
   * @param $volunteerId
   * @param $participantId
   * @return bool
   */
  private function canImportVolunteer($volunteerId, $participantId) {
    if (CRM_Nihrbackbone_NbrVolunteerCase::isAlreadyOnStudy($volunteerId, $this->_studyId)) {
      $this->_failed++;
      $message = E::ts('Volunteer with participantID ') . $participantId . E::ts(' is already on study ') . $studyNumber . E::ts(', not imported again.');
      CRM_Nihrbackbone_Utils::logMessage($this->_importId, $message, $this->_originalFileName);
      return FALSE;
    }
    return TRUE;
  }
}
