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
    $this->setVolunteerStatus($definition);
    $this->setTags($definition);
    //$this->setEligibleStatus($definition);
    $container->setDefinition('nbrBackbone', $definition);
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

