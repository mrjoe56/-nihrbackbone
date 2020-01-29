<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrProject.Checkmeets API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nbr_project_Checkmeets($params) {
  CRM_Nihrbackbone_NihrProject::checkMeetsAge();
  return civicrm_api3_create_success([], $params, 'NbrProject', 'Checkmeets');}
