<?php
use CRM_Nihrbackbone_ExtensionUtil as E;

/**
 * NbrVolunteer.Maxinvitations API - will check all cases that have eligibility status
 * 'max invitations in period reached' to see if it can be removed
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_nbr_volunteer_MaxInvitations($params) {
  CRM_Nihrbackbone_NihrVolunteer::checkMaxInvitations();
  return civicrm_api3_create_success([], $params, 'NbrVolunteer', 'MaxInvitations');
}
