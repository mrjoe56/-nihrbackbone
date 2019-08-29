<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Nihrbackbone_Upgrader extends CRM_Nihrbackbone_Upgrader_Base {

  /**
   * Upgrade 1000 (add option values for studies)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1000() {
    $this->ctx->log->info(E::ts('Applying update 1000'));
    $studies = civicrm_api3('NihrStudy', 'get', ['options' => ['limit' => 0]]);
    foreach ($studies['values'] as $studyId => $study) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => CRM_Nihrbackbone_BackboneConfig::singleton()->getProjectCustomField('npd_study_id', 'option_group_id'),
        'label' => $study['title'],
        'name' => $study['title'],
        'value' => $studyId,
        'is_active' => 1,
      ]);
    }
    return TRUE;
  }

  /**
   * Upgrade 1010 (add column study_number
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1010() {
    $this->ctx->log->info(E::ts('Applying update 1010 (add study number column)'));
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'study_number')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study ADD COLUMN study_number VARCHAR(24) DEFAULT NULL COMMENT 'Specific Study Number in NIHR BioResource' AFTER id");
    }
    return TRUE;
  }

  /**
   * Upgrade 1020 (add option values for studies)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1020() {
    $this->ctx->log->info(E::ts('Applying update 1010 - updating nihr_study table'));
    // rename existing column title to short_name if required
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'title')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study CHANGE COLUMN title short_name VARCHAR(64)");
    }
    // add long_name column after short_name
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'long_name')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study ADD COLUMN long_name VARCHAR(256) AFTER short_name");
    }
    // add ethics_approved_date column after ethics_approved_id
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'ethics_approved_date')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study ADD COLUMN ethics_approved_date DATE after ethics_approved_id");
    }
    // change start_date and end_date columns to valid_start_date and valid_end_date
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'start_date')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study CHANGE COLUMN start_date valid_start_date DATE");
    }
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'end_date')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study CHANGE COLUMN end_date valid_end_date DATE");
    }
    // make sure status_id is varchar
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('civicrm_nihr_study', 'status_id')) {
      CRM_Core_DAO::executeQuery("ALTER TABLE civicrm_nihr_study MODIFY status_id VARCHAR(64)");
    }
    return TRUE;
  }


  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
  public function install() {
    $this->executeSqlFile('sql/myinstall.sql');
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   *
  public function postInstall() {
    $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
      'return' => array("id"),
      'name' => "customFieldCreatedViaManagedHook",
    ));
    civicrm_api3('Setting', 'create', array(
      'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
    ));
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
  public function uninstall() {
   $this->executeSqlFile('sql/myuninstall.sql');
  }

  /**
   * Example: Run a simple query when a module is enabled.
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled.
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }


  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = E::ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
