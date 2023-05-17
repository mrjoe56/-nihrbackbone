<?php
namespace Civi\Api4;

/**
 * NbrStudyResearcher entity.
 *
 * Provided by the NIHR BioResource CiviCRM Backbone extension.
 *
 * @package Civi\Api4
 */
class NbrStudyResearcher extends Generic\DAOEntity {
  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['access CiviCRM']
    ];
  }
}
