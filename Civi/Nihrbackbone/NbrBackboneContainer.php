<?php
/**
 * @author Erik Hommel <erik.hommel@civicoop.org>
 * @date 9 Apr 2020
 * @license AGPL-3.0
 */
namespace Civi\Nihrbackbone;

use Dompdf\Exception;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use CRM_Nihrbackbone_ExtensionUtil as E;

class NbrBackboneContainer implements CompilerPassInterface {

  /**
   * You can modify the container here before it is dumped to PHP code.
   */
  public function process(ContainerBuilder $container) {
    $definition = new Definition('CRM_Nihrbackbone_NbrConfig');
    $definition->setFactory(['CRM_Nihrbackbone_NbrConfig', 'getInstance']);
    $this->setEligibilityStatus($definition);
    $this->setParticipationStatus($definition);
    $this->setTags($definition);
    $this->setVolunteerStatus($definition);
    $container->setDefinition('nbrBackbone', $definition);
  }

  /**
   * Method to set the participation status(es)
   *
   * @param $definition
   */
  private function setParticipationStatus(&$definition) {
    $query = "SELECT cov.value, cov.name
      FROM civicrm_option_value AS cov
          JOIN civicrm_option_group AS cog ON cov.option_group_id = cog.id
      WHERE cog.name = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => ["nbr_study_participation_status", "String"]]);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "study_participation_status_invited":
          $definition->addMethodCall('setInvitedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_selected":
          $definition->addMethodCall('setSelectedParticipationStatusValue', [$dao->value]);
          break;
      }
    }
  }

  /**
   * Method to set the eligibility status
   *
   * @param $definition
   */
  private function setEligibilityStatus(&$definition) {
    $query = "SELECT cov.value, cov.name
        FROM civicrm_option_value AS cov JOIN civicrm_option_group AS cog ON cov.option_group_id = cog.id
        WHERE cog.name = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => ["nihr_eligible_status", "String"]]);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "nihr_eligible":
          $definition->addMethodCall('setEligibleEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_age":
          $definition->addMethodCall('setAgeEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_blood":
          $definition->addMethodCall('setBloodEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_bmi":
          $definition->addMethodCall('setBmiEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_commercial":
          $definition->addMethodCall('setCommercialEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_ethnicity":
          $definition->addMethodCall('setEthnicityEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_gender":
          $definition->addMethodCall('setGenderEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_panel":
          $definition->addMethodCall('setPanelEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_travel":
          $definition->addMethodCall('setTravelEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_invited_other":
          $definition->addMethodCall('setOtherEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_maximum_reached":
          $definition->addMethodCall('setMaxEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_not_active":
          $definition->addMethodCall('setActiveEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_not_recallable":
          $definition->addMethodCall('setRecallableEligibilityStatusValue', [$dao->value]);
          break;
      }
    }
  }

  /**
   * Method to set the Tags
   *
   * @param $definition
   * @throws Exception
   */
  private function setTags(&$definition) {
    $query = "SELECT id FROM civicrm_tag ct WHERE name = %1";
    $tagId = \CRM_Core_DAO::singleValueQuery($query, [1 => ["Temporarily non-recallable", "String"]]);
    if ($tagId) {
      $definition->addMethodCall('setTempNonRecallTagId', [(int) $tagId]);
    }
    else {
      throw new Exception(E::ts('Could not find a tag Temporarily non-recallable in ') . __METHOD__
        . E::ts(", this is required for eligibility rules. Contact your System Administrator"));
    }
  }

  /**
   * Method to set the volunteer status values
   *
   * @param $definition
   */
  private function setVolunteerStatus(&$definition) {
    $query = "SELECT cov.name, cov.value
FROM civicrm_option_group AS cog
JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
WHERE cog.name = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => ["nbr_volunteer_status", "String"]]);
    while ($dao->fetch()) {
      switch($dao->name) {
        case "volunteer_status_active":
          $definition->addMethodCall('setActiveVolunteerStatus', [$dao->value]);
          break;

        case "volunteer_status_deceased":
          $definition->addMethodCall('setDeceasedVolunteerStatus', [$dao->value]);
          break;

        case "volunteer_status_not_recruited":
          $definition->addMethodCall('setNotRecruitedVolunteerStatus', [$dao->value]);
          break;

        case "volunteer_status_pending":
          $definition->addMethodCall('setPendingVolunteerStatus', [$dao->value]);
          break;

        case "volunteer_status_redundant":
          $definition->addMethodCall('setRedundantVolunteerStatus', [$dao->value]);
          break;

        case "volunteer_status_withdrawn":
          $definition->addMethodCall('setWithdrawnVolunteerStatus', [$dao->value]);
          break;
      }
    }
  }

}

