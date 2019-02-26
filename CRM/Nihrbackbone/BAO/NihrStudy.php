<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

class CRM_Nihrbackbone_BAO_NihrStudy extends CRM_Nihrbackbone_DAO_NihrStudy {

  /**
   * Create a new NihrStudy based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Nihrbackbone_DAO_NihrStudy|NULL
   */
  public static function create($params) {
    $className = 'CRM_Nihrbackbone_DAO_NihrStudy';
    $entityName = 'NihrStudy';
    try {
      $opDate = new DateTime;
    }
    catch (Exception $ex) {
      $opDate = NULL;
    }
    if (!isset($params['id']) || empty($params['id'])) {
      $hook = 'create';
      if ($opDate) {
        $params['created_date'] = $opDate->format('YmdHis');
      }
      $params['created_by_id'] = CRM_Core_Session::singleton()->getLoggedInContactID();
    }
    else {
      $hook = 'edit';
      if ($opDate) {
        $params['modified_date'] = $opDate->format('YmdHis');
      }
      $params['modified_by_id'] = CRM_Core_Session::singleton()->getLoggedInContactID();
    }
    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
    return $instance;
  }

}
