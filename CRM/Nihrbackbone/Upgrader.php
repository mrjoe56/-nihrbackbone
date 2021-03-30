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

    /**
     * Upgrade 1060 (add views for CPMS report - see https://issues.civicoop.org/issues/4925)
     */
    public function upgrade_1060() {
        $this->ctx->log->info(E::ts('Applying update 1060 - add view vw_valid_consent'));
        if (!CRM_Core_DAO::checkTableExists('vw_valid_consent')) {
            $query = "CREATE view vw_valid_consent as
            select cc.contact_id, date(activity_date_time) as consent_date
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
            $query = "CREATE view vw_stage1_consent_site as
            select c.id as contact_id, c.first_name, c.last_name, c.birth_date, ch_pack.identifier as pack_id,
            vp.nvp_site, sc.display_name as site_name, sc.sic_code as site_ods_code,
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

  /**
   * Upgrade 1070 (create table civicrm_nbr_mailing if not exists)
   *
   * @return TRUE on success
   * @throws Exception
   */
  public function upgrade_1070() {
    $this->ctx->log->info(E::ts('Applying update 1070 - create table civicrm_nbr_mailing if not exist'));
    $this->executeSqlFile('sql/createNbrMailing.sql');
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

  public function upgrade_1080() {
    $this->ctx->log->info(E::ts('Applying update 1080 - add lay summary to study data'));
    // only if custom group for study data is present
    if (CRM_Core_DAO::checkTableExists("civicrm_value_nbr_study_data")) {
      // add custom field scientific info
      if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists("civicrm_value_nbr_study_data", "nsd_lay_summary")) {
        civicrm_api3("CustomField", "create", [
          'custom_group_id' => 'nbr_study_data',
          'name' => 'nsd_lay_summary',
          'column_name' => 'nsd_lay_summary',
          'label' => 'Lay summary',
          'data_type' => 'Memo',
          'html_type' => 'RichTextEditor',
          'is_active' => 1,
          'is_searchable' => 0,
          'weight' => '500',
        ]);
      }
    }
    return TRUE;
  }

  /**
   * Upgrade 1090 - remove constraint on civicrn_nbr_mailing to civicrm_group from onDelete cascadate to
   * onDelete set null
   *
   * @return bool
   * @throws CiviCRM_API3_Exception
   */
  public function upgrade_1090() {
    $this->ctx->log->info(E::ts('Applying update 1090 - change constraint onDelete civicrm_nbr_mailing'));
    // save data in nbrmailing before drop table
    $data = CRM_Core_DAO::executeQuery("SELECT * FROM civicrm_nbr_mailing");
    CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS civicrm_nbr_mailing");
    $this->executeSqlFile('sql/recreateNbrMailing.sql');
    // insert data if required
    while ($data->fetch()) {
      if ($data->group_id) {
        $insert = "INSERT INTO civicrm_nbr_mailing (mailing_id, group_id, study_id, nbr_mailing_type)
        VALUES(%1, %2, %3, %4)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [$data->mailing_id, "Integer"],
          2 => [$data->group_id, "Integer"],
          3 => [$data->study_id, "Integer"],
          4 => [$data->nbr_mailing_type, "String"],
        ]);
      } else {
        $insert = "INSERT INTO civicrm_nbr_mailing (mailing_id, study_id, nbr_mailing_type)
        VALUES(%1, %2, %3)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [$data->mailing_id, "Integer"],
          2 => [$data->study_id, "Integer"],
          3 => [$data->nbr_mailing_type, "String"],
        ]);
      }
    }
    return TRUE;
  }

  /**
   * Upgrade 1100 - add somerset to state/province, create and populate civicrm_nbr_county if required
   *
   * @return bool
   * @throws CiviCRM_API3_Exception
   */
  public function upgrade_1100() {
    $this->ctx->log->info(E::ts('Applying update 1100 - add somerset to state/province, create and populate civicrm_nbr_county if required'));
    // add county Somerset
    $this->addSomerset();
    // add civicrm_nbr_county if required
    if (!CRM_Core_DAO::checkTableExists('civicrm_nbr_county')) {
      $this->executeSqlFile('sql/createNbrCounty.sql');
    }
    $this->populateNbrCounty();
    return TRUE;
  }

  /**
   * Upgrade 1110 - change names, labels and values for bood/commercial/travel to willing
   *
   * @return bool
   * @throws CiviCRM_API3_Exception
   */
  public function upgrade_1110() {
    $this->ctx->log->info(E::ts('Applying update 1110 - change names, labels and values for bood/commercial/travel to willing'));
    set_time_limit(0);
    $columns = [
      [
        'old_name' => "nvse_unable_to_travel",
        'old_label' => "Unable to travel",
        'new_name' => "nvse_willing_to_travel",
        'new_label' => "Travel",
      ],
      [
        'old_name' => "nvse_no_blood_studies",
        'old_label' => "Exc. from blood studies",
        'new_name' => "nvse_willing_to_give_blood",
        'new_label' => "Blood samples",
      ],
      [
        'old_name' => "nvse_no_commercial_studies",
        'old_label' => "Exc. commercial",
        'new_name' => "nvse_willing_commercial",
        'new_label' => "Commercial studies",
      ],
    ];
    $this->swapUnableToWillingColumns($columns);
    $this->swapUnableToWillingValues($columns);
    return TRUE;
  }

  /**
   * Swap values for unable to willing columns
   *
   * @param $columns
   */
  private function swapUnableToWillingValues($columns) {
    $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
    $dao = CRM_Core_DAO::executeQuery("SELECT id FROM " . $table);
    while ($dao->fetch()) {
      foreach ($columns as $column) {
        $columnName = $column['new_name'];
        if ($dao->$columnName == "1") {
          CRM_Core_DAO::executeQuery("UPDATE " . $table . " SET " . $columnName . " = %1 WHERE id = %2", [
            1 => [0, "Integer"],
            2 => [(int) $dao->id, "Integer"],
          ]);
        }
        else {
          CRM_Core_DAO::executeQuery("UPDATE " . $table . " SET " . $columnName . " = %1 WHERE id = %2", [
            1 => [1, "Integer"],
            2 => [(int) $dao->id, "Integer"],
          ]);
        }
      }
    }
  }

  /**
   * Method to swap unable to travel / no blood / no commercial custom field names and labels
   *
   * @param $columns
   */
  private function swapUnableToWillingColumns($columns) {
    $customGroupId = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('id');
    foreach ($columns as $column) {
      // upgrade old name to new name and label if needed (both custom field and actual column)
      $countOldNameQuery = "SELECT COUNT(*) FROM civicrm_custom_field WHERE custom_group_id = %1 AND name = %2";
      $countOldName = CRM_Core_DAO::singleValueQuery($countOldNameQuery, [
        1 => [(int) $customGroupId, "Integer"],
        2 => [$column['old_name'], "String"],
      ]);
      if ($countOldName > 0) {
        $this->updateCustomFieldOldName($customGroupId, $column);
      }
      else {
        // upgrade to new label if needed
        $countOldLabelQuery = "SELECT COUNT(*) FROM civicrm_custom_field WHERE custom_group_id = %1 AND name = %2 AND label = %3";
        $countOldLabel = CRM_Core_DAO::singleValueQuery($countOldLabelQuery, [
          1 => [(int) $customGroupId, "Integer"],
          2 => [$column['new_name'], "String"],
          3 => [$column['old_label'], "String"],
        ]);
        if ($countOldLabel > 0) {
          $this->updateCustomFieldOldLabel($customGroupId, $column);
        }
      }
    }
  }

  /**
   * Method to update custom field label to new value
   *
   * @param $customGroupId
   * @param $column
   */
  private function updateCustomFieldOldLabel($customGroupId, $column) {
    $updateCustomField = "UPDATE civicrm_custom_field SET label = %1 WHERE custom_group_id = %2
        AND name = %3";
    $updateParams = [
      1 => [$column['new_label'], "String"],
      2 => [(int) $customGroupId, "Integer"],
      3 => [$column['new_name'], "Integer"],
    ];
    CRM_Core_DAO::executeQuery($updateCustomField, $updateParams);
  }

  /**
   * Method to update custom field name and label to new values
   *
   * @param $customGroupId
   * @param $column
   */
  private function updateCustomFieldOldName($customGroupId, $column) {
    $updateCustomField = "UPDATE civicrm_custom_field SET name = %1, label = %2, column_name = %1
        WHERE custom_group_id = %3 AND name = %4";
    $updateParams = [
      1 => [$column['new_name'], "String"],
      2 => [$column['new_label'], "String"],
      3 => [(int) $customGroupId, "Integer"],
      4 => [$column['old_name'], "String"],
    ];
    try {
      CRM_Core_DAO::executeQuery($updateCustomField, $updateParams);
      $table = CRM_Nihrbackbone_BackboneConfig::singleton()->getVolunteerSelectionEligibilityCustomGroup('table_name');
      $alterQuery = "ALTER TABLE " . $table . " CHANGE COLUMN " . $column['old_name'] . " " . $column['new_name'] . " TINYINT";
      CRM_Core_DAO::executeQuery($alterQuery);
    }
    catch (Exception $ex) {
    }
  }

  /**
   * Method to populate civicrm_nbr_county with existing synonyms
   */
  private function populateNbrCounty() {
    // check if starfish mapping table exists and if so, add if required
    $this->addStarfishMapping();
    // add synonyms from csv file in resources
    $container = CRM_Extension_System::singleton()->getFullContainer();
    $csvFile = $container->getPath('nihrbackbone') . '/resources/starfish_county_synonyms.csv';
    $csv = fopen($csvFile, 'r');
    while (!feof($csv)) {
      $data = fgetcsv($csv, 0, "~");
      if ($data) {
        // retrieve state_province_id of starfish record
        $query = "SELECT state_province_id FROM civicrm_nbr_county WHERE synonym = %1";
        $stateProvinceId = CRM_Core_DAO::singleValueQuery($query, [1 => [$data[0], "String"]]);
        if ($stateProvinceId) {
          $query = "SELECT COUNT(*) FROM civicrm_nbr_county WHERE synonym = %1 AND state_province_id = %2";
          $count = CRM_Core_DAO::singleValueQuery($query, [
            1 => [$data[1], "String"],
            2 => [(int) $stateProvinceId, "Integer"],
          ]);
          if ($count == 0) {
            $insert = "INSERT INTO civicrm_nbr_county (state_province_id, synonym) VALUES(%1, %2)";
            CRM_Core_DAO::executeQuery($insert, [
              1 => [(int) $stateProvinceId, "Integer"],
              2 => [$data[1], "String"],
            ]);
          }
        }
      }
    }
    fclose($csv);
  }

  /**
   * Method to add the starfish county mapping
   */
  private function addStarfishMapping() {
    if (!CRM_Core_DAO::checkTableExists('starfish_civi_county_mapping')) {
      $this->executeSqlFile('sql/starfish_civi_county_mapping.sql');
    }
    $starfishCounty = CRM_Core_DAO::executeQuery("SELECT * FROM starfish_civi_county_mapping WHERE starfish_county IS NOT NULL");
    while ($starfishCounty->fetch()) {
      $query = "SELECT COUNT(*) FROM civicrm_nbr_county WHERE synonym = %1 AND state_province_id = %2";
      $count = CRM_Core_DAO::singleValueQuery($query, [
        1 => [$starfishCounty->starfish_county, "String"],
        2 => [(int)$starfishCounty->id, "Integer"],
      ]);
      if ($count == 0) {
        $insert = "INSERT INTO civicrm_nbr_county (state_province_id, synonym) VALUES(%1, %2)";
        CRM_Core_DAO::executeQuery($insert, [
          1 => [(int) $starfishCounty->id, "Integer"],
          2 => [$starfishCounty->starfish_county, "String"],
        ]);
      }
    }
  }

  /**
   * Method to add Somerset as county if not exists
   */
  private function addSomerset() {
    $ukId = Civi::service('nbrBackbone')->getUkCountryId();
    $somAbb = "SOM";
    $query = "SELECT COUNT(*) FROM civicrm_state_province WHERE country_id = %1 AND abbreviation = %2";
    $count = CRM_Core_DAO::singleValueQuery($query, [
      1 => [$ukId, "Integer"],
      2 => [$somAbb, "String"]
    ]);
    if ($count == 0) {
      $insert = "INSERT INTO civicrm_state_province (name, abbreviation, country_id) VALUES (%1, %2, %3)";
      CRM_Core_DAO::executeQuery($insert, [
        1 => ["Somerset", "String"],
        2 => [$somAbb, "String"],
        3 => [$ukId, "Integer"],
      ]);
    }
    $query = "SELECT id FROM civicrm_state_province WHERE abbreviation = %1";
    $somersetId = CRM_Core_DAO::singleValueQuery($query, [1 => [$somAbb, "String"]]);
    if ($somersetId && CRM_Core_DAO::checkTableExists('starfish_civi_county_mapping')) {
      $update = "UPDATE starfish_civi_county_mapping SET id = %1 WHERE abbreviation = %2";
      CRM_Core_DAO::executeQuery($update, [
        1 => [(int) $somersetId, "Integer"],
        2 => [$somAbb, "String"],
      ]);
    }
  }
}
