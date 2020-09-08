<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NbrMailing extends CRM_Nihrbackbone_DAO_NbrMailing {

  /**
   * Create a new NbrMailing based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Nihrbackbone_DAO_NbrMailing|NULL
   *
  public static function create($params) {
    $className = 'CRM_Nihrbackbone_DAO_NbrMailing';
    $entityName = 'NbrMailing';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
