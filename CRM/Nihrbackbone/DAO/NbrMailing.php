<?php

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from nihrbackbone/xml/schema/CRM/Nihrbackbone/NbrMailing.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 * (GenCodeChecksum:44f95de3421d99140a40d87142ef450d)
 */

/**
 * Database access object for the NbrMailing entity.
 */
class CRM_Nihrbackbone_DAO_NbrMailing extends CRM_Core_DAO {

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = 'civicrm_nbr_mailing';

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  public static $_log = TRUE;

  /**
   * Unique NbrMailing ID
   *
   * @var int
   */
  public $id;

  /**
   * FK to Mailing
   *
   * @var int
   */
  public $mailing_id;

  /**
   * FK to Group
   *
   * @var int
   */
  public $group_id;

  /**
   * FK to Study (Campaign)
   *
   * @var int
   */
  public $study_id;

  /**
   * Type of Mailing (invite initially)
   *
   * @var string
   */
  public $nbr_mailing_type;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = 'civicrm_nbr_mailing';
    parent::__construct();
  }

  /**
   * Returns foreign keys and entity references.
   *
   * @return array
   *   [CRM_Core_Reference_Interface]
   */
  public static function getReferenceColumns() {
    if (!isset(Civi::$statics[__CLASS__]['links'])) {
      Civi::$statics[__CLASS__]['links'] = static::createReferenceColumns(__CLASS__);
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'mailing_id', 'civicrm_mailing', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'group_id', 'civicrm_group', 'id');
      Civi::$statics[__CLASS__]['links'][] = new CRM_Core_Reference_Basic(self::getTableName(), 'study_id', 'civicrm_campaign', 'id');
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'links_callback', Civi::$statics[__CLASS__]['links']);
    }
    return Civi::$statics[__CLASS__]['links'];
  }

  /**
   * Returns all the column names of this table
   *
   * @return array
   */
  public static function &fields() {
    if (!isset(Civi::$statics[__CLASS__]['fields'])) {
      Civi::$statics[__CLASS__]['fields'] = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => CRM_Nihrbackbone_ExtensionUtil::ts('Unique NbrMailing ID'),
          'required' => TRUE,
          'where' => 'civicrm_nbr_mailing.id',
          'table_name' => 'civicrm_nbr_mailing',
          'entity' => 'NbrMailing',
          'bao' => 'CRM_Nihrbackbone_DAO_NbrMailing',
          'localizable' => 0,
        ],
        'mailing_id' => [
          'name' => 'mailing_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => CRM_Nihrbackbone_ExtensionUtil::ts('FK to Mailing'),
          'where' => 'civicrm_nbr_mailing.mailing_id',
          'table_name' => 'civicrm_nbr_mailing',
          'entity' => 'NbrMailing',
          'bao' => 'CRM_Nihrbackbone_DAO_NbrMailing',
          'localizable' => 0,
          'FKClassName' => 'CRM_Mailing_DAO_Mailing',
        ],
        'group_id' => [
          'name' => 'group_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => CRM_Nihrbackbone_ExtensionUtil::ts('FK to Group'),
          'where' => 'civicrm_nbr_mailing.group_id',
          'table_name' => 'civicrm_nbr_mailing',
          'entity' => 'NbrMailing',
          'bao' => 'CRM_Nihrbackbone_DAO_NbrMailing',
          'localizable' => 0,
          'FKClassName' => 'CRM_Contact_DAO_Group',
        ],
        'study_id' => [
          'name' => 'study_id',
          'type' => CRM_Utils_Type::T_INT,
          'description' => CRM_Nihrbackbone_ExtensionUtil::ts('FK to Study (Campaign)'),
          'where' => 'civicrm_nbr_mailing.study_id',
          'table_name' => 'civicrm_nbr_mailing',
          'entity' => 'NbrMailing',
          'bao' => 'CRM_Nihrbackbone_DAO_NbrMailing',
          'localizable' => 0,
          'FKClassName' => 'CRM_Campaign_DAO_Campaign',
        ],
        'nbr_mailing_type' => [
          'name' => 'nbr_mailing_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => CRM_Nihrbackbone_ExtensionUtil::ts('Nbr Mailing Type'),
          'description' => CRM_Nihrbackbone_ExtensionUtil::ts('Type of Mailing (invite initially)'),
          'maxlength' => 32,
          'size' => CRM_Utils_Type::MEDIUM,
          'where' => 'civicrm_nbr_mailing.nbr_mailing_type',
          'table_name' => 'civicrm_nbr_mailing',
          'entity' => 'NbrMailing',
          'bao' => 'CRM_Nihrbackbone_DAO_NbrMailing',
          'localizable' => 0,
        ],
      ];
      CRM_Core_DAO_AllCoreTables::invoke(__CLASS__, 'fields_callback', Civi::$statics[__CLASS__]['fields']);
    }
    return Civi::$statics[__CLASS__]['fields'];
  }

  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns the names of this table
   *
   * @return string
   */
  public static function getTableName() {
    return self::$_tableName;
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of fields that can be imported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &import($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getImports(__CLASS__, 'nbr_mailing', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of fields that can be exported
   *
   * @param bool $prefix
   *
   * @return array
   */
  public static function &export($prefix = FALSE) {
    $r = CRM_Core_DAO_AllCoreTables::getExports(__CLASS__, 'nbr_mailing', $prefix, []);
    return $r;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = [];
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}
