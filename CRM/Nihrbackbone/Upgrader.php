<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Nihrbackbone_Upgrader extends CRM_Nihrbackbone_Upgrader_Base {

  /**
   * Upgrade 1030 (add log table - see https://issues.civicoop.org/issues/4950)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1030() {
    $this->ctx->log->info(E::ts('Applying update 1030 - add import log table'));
    if (!CRM_Core_DAO::checkTableExists('civicrm_nbr_import_log')) {
      $query = "CREATE TABLE `civicrm_nbr_import_log` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Unique NbrImportLog ID',
     `import_id` varchar(32)    COMMENT 'Unique ID of the import job',
     `filename` varchar(128)    COMMENT 'Name of the import file that is being logged',
     `message_type` varchar(128)    COMMENT 'Type of message (info, warning, error)',
     `message` text    COMMENT 'Message',
     `logged_date` date    COMMENT 'The date the message was logged', PRIMARY KEY (`id`));";
      CRM_Core_DAO::executeQuery($query);
    }
    return TRUE;
  }

  /**
   * Upgrade 1040 (remove study table)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1040() {
    $this->ctx->log->info(E::ts('Applying update 1040 - delete table civicrm_nihr_study and rename campaign_type'));
    if (CRM_Core_DAO::checkTableExists('civicrm_nihr_study')) {
      CRM_Core_DAO::executeQuery("DROP TABLE civicrm_nihr_study");
    }
    return TRUE;
  }

  /**
   * Upgrade 1050 (add scientific notes to study data)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1050() {
    $this->ctx->log->info(E::ts('Applying update 1050 - add scientific info to study data'));
    // only if custom group for study data is present
    if (CRM_Core_DAO::checkTableExists("civicrm_value_nbr_study_data")) {
      // add custom field scientific info
      if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists("civicrm_value_nbr_study_data", "nsd_scientific_info")) {
        civicrm_api3("CustomField", "create", [
          'custom_group_id' => 'nbr_study_data',
          'name' => 'nsd_scientific_info',
          'column_name' => 'nsd_scientific_info',
          'label' => 'Scientific Information',
          'data_type' => 'Memo',
          'html_type' => 'TextArea',
          'is_active' => 1,
          'is_searchable' => 0,
          'weight' => '500',
        ]);
      }
    }
    return TRUE;
  }

    /** Upgrade 1060 (add views for CPMS report - see https://issues.civicoop.org/issues/4925)*/
    public function upgrade_1060() {
        $this->ctx->log->info(E::ts('Applying update 1060 - add view vw_valid_consent'));
        if (!CRM_Core_DAO::checkTableExists('vw_valid_consent')) {
            $query = "CREATE view vw_valid_consent as
            select cc.contact_id, activity_date_time as consent_date
            from civicrm_case_contact cc
            join civicrm_contact con on cc.contact_id = con.id
            join civicrm_case c on c.id = cc.case_id
            join civicrm_case_type ct on ct.id = c.case_type_id
            join civicrm_case_activity ca on c.id = ca.case_id
            join civicrm_value_nihr_volunteer_consent vc on ca.activity_id = vc.entity_id
            join civicrm_activity act on ca.activity_id = act.id
            where c.is_deleted = 0
            and con.is_deleted = 0
            and vc.nvc_consent_status = 'consent_form_status_correct'
            and ct.name = 'nihr_recruitment'";
            CRM_Core_DAO::executeQuery($query);
        }
        if (!CRM_Core_DAO::checkTableExists('vw_stage1_consent_site')) {
            $query = "CREATE view vw_stage1_consent_site as select c.id as contact_id, c.first_name, c.last_name, c.birth_date,
            ch_pack.identifier as pack_id, vp.nvp_site, sc.display_name as site_name, sc.sic_code as site_ods_code,
            vp.nvp_panel, pc.display_name as panel_name
            from
            (civicrm_contact c
            left join civicrm_value_contact_id_history ch_pack on c.id = ch_pack.entity_id
            left join civicrm_value_nihr_volunteer_panel vp on vp.entity_id = c.id
            join civicrm_contact as sc on sc.id = vp.nvp_site
            join civicrm_contact as pc on pc.id = vp.nvp_panel)
            where ch_pack.identifier_type = 'cih_type_packid'";
            CRM_Core_DAO::executeQuery($query);
        }
        if (!CRM_Core_DAO::checkTableExists('vw_cpms')) {
            $query = "CREATE view vw_cpms  as select cs.*, vc.consent_date from vw_valid_consent vc, vw_stage1_consent_site cs
                    where vc.contact_id = cs.contact_id";
            CRM_Core_DAO::executeQuery($query);
        }
        return TRUE;
    }

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

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
