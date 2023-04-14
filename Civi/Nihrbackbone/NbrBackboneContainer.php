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
    $this->setActivityContactRecordTypeIds($definition);
    $this->setActivityStatus($definition);
    $this->setActivityTypes($definition);
    $this->setConsentStatus($definition);
    $this->setCustomGroups($definition);
    $this->setEligibilityStatus($definition);
    $this->setEncounterMedium($definition);
    $this->setGroups($definition);
    $this->setLocationTypes($definition);
    $this->setMailingDefaultIds($definition);
    $this->setOptionGroups($definition);
    $this->setParticipationStatus($definition);
    $this->setPriority($definition);
    $this->setTags($definition);
    $this->setVolunteerStatus($definition);
    $definition->addMethodCall('setVisitStage2Substring', ["nihr_visit_stage2"]);
    $definition->addMethodCall('setOtherBleedDifficultiesValue', ["bd_other"]);
    $definition->addMethodCall('setOtherSampleSiteValue', ["visit_bleed_site_other"]);
    $definition->addMethodCall('setDefaultNbrMailingType', ["invite"]);
    $definition->addMethodCall('setUkCountryId', [1226]);
    $definition->addMethodCall('setParticipantIdIdentifierType', ["cih_type_participant_id"]);
    $definition->addMethodCall('setBioresourceIdIdentifierType', ["cih_type_bioresource_id"]);
    $definition->addMethodCall('setParticipationCaseTypeName', ['nihr_participation']);
    $definition->addMethodCall('setRecruitmentCaseTypeName', ['nihr_recruitment']);
    $definition->setPublic(TRUE);
    $container->setDefinition('nbrBackbone', $definition);
  }

  /**
   * Method to set the group ids
   *
   * @param $definition
   */
  public function setGroups(&$definition) {
    $query = "SELECT id FROM civicrm_group WHERE name = %1";
    $groupId = \CRM_Core_DAO::singleValueQuery($query, [1 => ['nbr_bioresourcers', "String"]]);
    if ($groupId) {
      $definition->addMethodCall('setBioResourcersGroupId', [(int) $groupId]);
    }
  }

  /**
   * Method to set the location types
   *
   * @param $definition
   */
  private function setLocationTypes(&$definition) {
    $dao = \CRM_Core_DAO::executeQuery("SELECT id, name FROM civicrm_location_type");
    while ($dao->fetch()) {
      switch($dao->name) {
        case "Home":
          $definition->addMethodCall('setHomeLocationTypeId', [(int) $dao->id]);
          break;
        case "Other":
          $definition->addMethodCall('setOtherLocationTypeId', [(int) $dao->id]);
          break;
        case "Work":
          $definition->addMethodCall('setWorkLocationTypeId', [(int) $dao->id]);
          break;
      }
    }
  }

  /**
   * Method to set the activity contact record type ids
   *
   * @param $definition
   */
  private function setActivityContactRecordTypeIds(&$definition) {
    $query = "SELECT cov.value, cov.name
        FROM civicrm_option_group AS cog JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
        WHERE cog.name = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => ["activity_contacts", "String"]]);
    while ($dao->fetch()) {
      switch($dao->name) {
        case "Activity Assignees":
          $definition->addMethodCall('setAssigneeRecordTypeId', [(int) $dao->value]);
          break;
        case "Activity Source":
          $definition->addMethodCall('setSourceRecordTypeId', [(int) $dao->value]);
          break;
        case "Activity Targets":
          $definition->addMethodCall('setTargetRecordTypeId', [(int) $dao->value]);
          break;
      }
    }
  }
  /**
   * Method to set the default mailing component ids
   *
   * @param $definition
   */
  private function setMailingDefaultIds(&$definition) {
    $query = "SELECT id, component_type FROM civicrm_mailing_component WHERE is_default = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => [1, "Integer"]]);
    while ($dao->fetch()) {
      switch ($dao->component_type) {
        case "Footer":
          $definition->addMethodCall('setMailingFooterId', [(int) $dao->id]);
          break;
        case "Header":
          $definition->addMethodCall('setMailingHeaderId', [(int) $dao->id]);
          break;
        case "OptOut":
          $definition->addMethodCall('setOptOutId', [(int) $dao->id]);
          break;
        case "Reply":
          $definition->addMethodCall('setAutoResponderId', [(int) $dao->id]);
          break;
        case "Resubscribe":
          $definition->addMethodCall('setResubscribeId', [(int) $dao->id]);
          break;
        case "Subscribe":
          $definition->addMethodCall('setSubscribeId', [(int) $dao->id]);
          break;
        case "Unsubscribe":
          $definition->addMethodCall('setUnsubscribeId', [(int) $dao->id]);
          break;
        case "Welcome":
          $definition->addMethodCall('setWelcomeId', [(int) $dao->id]);
          break;
      }
    }
  }
  /**
   * Method to set the encounter medium ids
   *
   * @param $definition
   */
  private function setEncounterMedium(&$definition) {
    $query = "SELECT cov.value, cov.name
        FROM civicrm_option_group AS cog
            JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
        WHERE cog.name = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => ["encounter_medium", "String"]]);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "email":
          $definition->addMethodCall('setEmailMediumId', [(int) $dao->value]);
          break;
        case "in_person":
          $definition->addMethodCall('setInPersonMediumId', [(int) $dao->value]);
          break;
        case "letter_mail":
          $definition->addMethodCall('setLetterMediumId', [(int) $dao->value]);
          break;
        case "phone":
          $definition->addMethodCall('setPhoneMediumId', [(int) $dao->value]);
          break;
        case "SMS":
          $definition->addMethodCall('setSmsMediumId', [(int) $dao->value]);
          break;
      }
    }
    $dao->free();
  }

  /**
   * Method to set the (activity) priority
   *
   * @param $definition
   */
  private function setPriority(&$definition) {
    $query = "SELECT cov.value
        FROM civicrm_option_group AS cog
            JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
        WHERE cog.name = %1 AND cov.name = %2";
    $priorityId = \CRM_Core_DAO::singleValueQuery($query, [
      1 => ["priority", "String"],
      2 => ["Normal", "String"],
    ]);
    if ($priorityId) {
      $definition->addMethodCall('setNormalPriorityId', [(int) $priorityId]);
    }
  }

  /**
   * Method to set the activity status ids
   *
   * @param $definition
   */
  private function setActivityStatus(&$definition) {
    $query = "SELECT cov.name, cov.value
      FROM civicrm_option_group AS cog
          JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
      WHERE cog.name = %1 AND cov.name IN (%2, %3, %4, %5)";
    $dao = \CRM_Core_DAO::executeQuery($query, [
      1 => ["activity_status", "String"],
      2 => ["Arrange", "String"],
      3 => ["Completed", "String"],
      4 => ["Scheduled", "String"],
      5 => ["Return to sender", "String"],
    ]);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "Arrange":
          $definition->addMethodCall('setArrangeActivityStatusId', [(int) $dao->value]);
          break;

        case "Completed":
          $definition->addMethodCall('setCompletedActivityStatusId', [(int) $dao->value]);
          break;

        case "Return to sender":
          $definition->addMethodCall('setReturnToSenderActivityStatusId', [(int) $dao->value]);
          break;

        case "Scheduled":
          $definition->addMethodCall('setScheduledActivityStatusId', [(int) $dao->value]);
          break;
      }
    }
    $dao->free();
  }

  /**
   * Method om custom fields te halen
   *
   * @param int $customGroupId
   * @param string $customGroupName
   * @param $definition
   * @return array
   */
  private function setCustomFields($customGroupId, $customGroupName, &$definition) {
    $result = [];
    $query = "SELECT id, name, column_name FROM civicrm_custom_field WHERE custom_group_id = %1";
    $dao = \CRM_Core_DAO::executeQuery($query, [1 => [(int) $customGroupId, "Integer"]]);
    while ($dao->fetch()) {
      switch ($customGroupName) {
        case "contact_id_history":
          switch($dao->name) {
            case "id_history_entry_type":
              $definition->addMethodCall('setIdentifierTypeCustomFieldId', [(int) $dao->id]);
              $definition->addMethodCall('setIdentifierTypeColumnName', [$dao->column_name]);
              break;

            case "id_history_entry":
              $definition->addMethodCall('setIdentifierColumnName', [$dao->column_name]);
              break;
          }
          break;

        case "nbr_participation_data":
          if ($dao->name == "nvpd_study_participation_status") {
            $definition->addMethodCall('setStudyParticipationStatusColumnName', [$dao->column_name]);
          }
          if ($dao->name == "nvpd_study_id") {
            $definition->addMethodCall('setParticipationStudyIdColumnName', [$dao->column_name]);
          }
          break;

        case "nbr_site_alias":
          if ($dao->name == "nsa_alias") {
            $definition->addMethodCall('setSiteAliasColumnName', [$dao->column_name]);
          }
          if ($dao->name == "nsa_alias_type") {
            $definition->addMethodCall('setSiteAliasTypeColumnName', [$dao->column_name]);
          }
          break;

        case "nihr_volunteer_consent":
          if ($dao->name == "nvc_consent_version") {
            $definition->addMethodCall('setConsentVersionColumnName', [$dao->column_name]);
          }
          if ($dao->name == "nvc_information_leaflet_version") {
            $definition->addMethodCall('setLeafletVersionColumnName', [$dao->column_name]);
          }
          break;

        case "nbr_assent_data":
          if ($dao->name == "nbr_assent_version") {
            $definition->addMethodCall('setAssentVersionColumnName', [$dao->column_name]);
          }
          if ($dao->name == "nbr_assent_pis_version") {
            $definition->addMethodCall('setAssentPisVersionColumnName', [$dao->column_name]);
          }
          if ($dao->name == "nbr_assent_status") {
            $definition->addMethodCall('setAssentStatusColumnName', [$dao->column_name]);
          }
          break;

        case "nihr_volunteer_disease":
          $this->setDiseaseCustomFields($dao, $definition);
          break;

        case "nihr_volunteer_medication":
          $this->setMedicationCustomFields($dao, $definition);
          break;

        case "nihr_volunteer_panel":
          $this->setPanelCustomFields($dao, $definition);
          break;

        case "nihr_volunteer_selection_eligibility":
          $this->setVolunteerSelectionCustomFields($dao, $definition);
          break;

        case "nihr_visit_data":
          $this->setVisitCustomFields($dao, $definition);
          break;

        case "nihr_visit_data_stage2":
          if ($dao->name == "nvi_visit_participation_study_payment") {
            $definition->addMethodCall('setStudyPaymentCustomFieldId', [(int) $dao->id]);
          }
          break;

        case "nihr_volunteer_consent_stage2":
          $this->setConsentStage2CustomFields($dao, $definition);
          break;

        case "nihr_volunteer_not_recruited":
          if ($dao->name == "avnr_not_recruited_reason") {
            $definition->addMethodCall('setNotRecruitedReasonCustomFieldId', [(int) $dao->id]);
          }
          break;

        case "nihr_volunteer_redundant":
          $this->setRedundantCustomFields($dao, $definition);
          break;

        case "nihr_volunteer_status":
          if ($dao->name == "nvs_volunteer_status") {
            $definition->addMethodCall('setVolunteerStatusColumnName', [$dao->column_name]);
          }
          break;

        case "nihr_volunteer_withdrawn":
          $this->setWithdrawnCustomFields($dao, $definition);
          break;
      }
    }
    $dao->free();
    return $result;
  }

  /**
   * Method to set the custom field properties for consent stage2 data
   *
   * @param $dao
   * @param $definition
   */
  private function setConsentStage2CustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "nvc2_consent_version":
        $definition->addMethodCall('setConsentVersionStage2CustomFieldId', [(int) $dao->id]);
        break;

      case "nvc2_questionnaire_version":
        $definition->addMethodCall('setQuestionnaireVersionStage2CustomFieldId', [(int) $dao->id]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for redundant activity data
   *
   * @param $dao
   * @param $definition
   */
  private function setRedundantCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "avr_redundant_reason":
        $definition->addMethodCall('setRedundantReasonCustomFieldId', [(int) $dao->id]);
        break;

      case "avr_request_to_destroy_data":
        $definition->addMethodCall('setRedundantDestroyDataCustomFieldId', [(int) $dao->id]);
        $definition->addMethodCall('setRedundantDestroyDataColumnName', [$dao->column_name]);
        break;

      case "avr_request_to_destroy_samples":
        $definition->addMethodCall('setRedundantDestroySamplesCustomFieldId', [(int) $dao->id]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for withdrawn activity data
   *
   * @param $dao
   * @param $definition
   */
  private function setWithdrawnCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "avw_withdrawn_reason":
        $definition->addMethodCall('setWithdrawnReasonCustomFieldId', [(int) $dao->id]);
        break;

      case "avw_request_to_destroy_data":
        $definition->addMethodCall('setWithdrawnDestroyDataCustomFieldId', [(int) $dao->id]);
        $definition->addMethodCall('setWithdrawnDestroyDataColumnName', [$dao->column_name]);
        break;

      case "avw_request_to_destroy_samples":
        $definition->addMethodCall('setWithdrawnDestroySamplesCustomFieldId', [(int) $dao->id]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for disease data
   *
   * @param $dao
   * @param $definition
   */
  private function setDiseaseCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "nvdi_diagnosis_age":
        $definition->addMethodCall('setDiagnosisAgeColumnName', [$dao->column_name]);
        break;

      case "nvdi_diagnosis_year":
        $definition->addMethodCall('setDiagnosisYearColumnName', [$dao->column_name]);
        break;

      case "nvdi_disease":
        $definition->addMethodCall('setDiseaseColumnName', [$dao->column_name]);
        break;

      case "nvdi_disease_notes":
        $definition->addMethodCall('setDiseaseNotesColumnName', [$dao->column_name]);
        break;

      case "nvdi_family_member":
        $definition->addMethodCall('setFamilyMemberColumnName', [$dao->column_name]);
        break;

      case "nvdi_taking_medication":
        $definition->addMethodCall('setTakingMedicationColumnName', [$dao->column_name]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for volunteer medication data
   *
   * @param $dao
   * @param $definition
   */
  private function setMedicationCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "nvm_medication_drug_family":
        $definition->addMethodCall('setDrugFamilyColumnName', [$dao->column_name]);
        break;

      case "nvm_medication_date":
        $definition->addMethodCall('setMedicationDateColumnName', [$dao->column_name]);
        break;

      case "nvm_medication_name":
        $definition->addMethodCall('setMedicationNameColumnName', [$dao->column_name]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for panel data
   *
   * @param $dao
   * @param $definition
   */
  private function setPanelCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "nvp_centre":
        $definition->addMethodCall('setVolunteerCentreColumnName', [$dao->column_name]);
        $definition->addMethodCall('setVolunteerCentreCustomFieldId', [(int) $dao->id]);
        break;

      case "nvp_panel":
        $definition->addMethodCall('setVolunteerPanelColumnName', [$dao->column_name]);
        $definition->addMethodCall('setVolunteerPanelCustomFieldId', [(int) $dao->id]);
        break;

      case "nvp_site":
        $definition->addMethodCall('setVolunteerSiteColumnName', [$dao->column_name]);
        $definition->addMethodCall('setVolunteerSiteCustomFieldId', [(int) $dao->id]);
        break;

      case "nvp_source":
        $definition->addMethodCall('setVolunteerSourceColumnName', [$dao->column_name]);
        $definition->addMethodCall('setVolunteerSourceCustomFieldId', [(int) $dao->id]);
        break;
    }
  }

  /**
   * Method to set the custom field properties for selection eligibility of volunteer data
   *
   * @param $dao
   * @param $definition
   */
  private function setVolunteerSelectionCustomFields($dao, &$definition) {
    if ($dao->name == "nvse_no_online_studies") {
      $definition->addMethodCall('setNoOnlineStudiesColumnName', [$dao->column_name]);
      $definition->addMethodCall('setNoOnlineStudiesCustomFieldId', [(int) $dao->id]);
    }
  }

  /**
   * Method to set the custom field properties for visit data
   *
   * @param $dao
   * @param $definition
   */
  private function setVisitCustomFields($dao, &$definition) {
    switch ($dao->name) {
      case "nvi1_bleed_attempts":
        $definition->addMethodCall('setAttemptsCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_bd_incident_form_completed":
        $definition->addMethodCall('setIncidentFormCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_bleed_difficulties":
        $definition->addMethodCall('setBleedDifficultiesCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_bleed_site":
        $definition->addMethodCall('setSampleSiteCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_claim_received":
        $definition->addMethodCall('setClaimReceivedDateCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_claim_submitted":
        $definition->addMethodCall('setClaimSubmittedDateCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_collected_by":
        $definition->addMethodCall('setCollectedByCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_mileage":
        $definition->addMethodCall('setMileageCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_other_expenses":
        $definition->addMethodCall('setOtherExpensesCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_parking_fee":
        $definition->addMethodCall('setParkingFeeCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_to_lab_date":
        $definition->addMethodCall('setToLabDateCustomFieldId', [(int) $dao->id]);
        break;
      case "nvi1_visit_expenses_notes":
        $definition->addMethodCall('setExpensesNotesCustomFieldId', [(int) $dao->id]);
        break;
    }
  }


  /**
   * Method to set the custom group ids
   *
   * @param $definition
   */
  private function setCustomGroups(&$definition) {
    $query = "SELECT id, name, table_name FROM civicrm_custom_group WHERE name IN(%1, %2, %3, %4, %5, %6, %7, %8, %9, %10, %11, %12, %13, %14, %15)";
    $queryParams = [
      1 => ["contact_id_history", "String"],
      2 => ["nbr_participation_data", "String"],
      3 => ["nbr_site_alias", "String"],
      4 => ["nihr_volunteer_consent", "String"],
      5 => ["nihr_volunteer_disease", "String"],
      6 => ["nihr_volunteer_panel", "String"],
      7 => ["nihr_visit_data", "String"],
      8 => ["nihr_visit_data_stage2", "String"],
      9 => ["nihr_volunteer_consent_stage2", "String"],
      10 => ["nihr_volunteer_not_recruited", "String"],
      11 => ["nihr_volunteer_redundant", "String"],
      12 => ["nihr_volunteer_withdrawn", "String"],
      13 => ["nihr_volunteer_status", "String"],
      14 => ["nihr_volunteer_medication", "String"],
      15 => ["nihr_volunteer_selection_eligibility", "String"],
      16 => ["nbr_assent_data", "String"]
    ];
    $dao = \CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "contact_id_history":
          $definition->addMethodCall('setContactIdentityCustomGroupId', [(int) $dao->id]);
          $definition->addMethodCall('setContactIdentityTableName', [$dao->table_name]);
          break;

        case "nbr_participation_data":
          $definition->addMethodCall('setParticipationDataTableName', [$dao->table_name]);
          break;

        case "nbr_site_alias":
          $definition->addMethodCall('setSiteAliasTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_consent":
          $definition->addMethodCall('setConsentTableName', [$dao->table_name]);
          break;

        case "nbr_assent_data":
          $definition->addMethodCall('setAssentTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_disease":
          $definition->addMethodCall('setDiseaseTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_medication":
          $definition->addMethodCall('setMedicationTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_panel":
          $definition->addMethodCall('setVolunteerPanelTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_selection_eligibility":
          $definition->addMethodCall('setVolunteerSelectionTableName', [$dao->table_name]);
          break;

        case "nihr_visit_data":
          $definition->addMethodCall('setVisitTableName', [$dao->table_name]);
          break;

        case "nihr_visit_data_stage2":
          $definition->addMethodCall('setVisitStage2TableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_consent_stage2":
          $definition->addMethodCall('setConsentStage2TableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_redundant":
          $definition->addMethodCall('setRedundantTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_status":
          $definition->addMethodCall('setVolunteerStatusTableName', [$dao->table_name]);
          break;

        case "nihr_volunteer_withdrawn":
          $definition->addMethodCall('setWithdrawnTableName', [$dao->table_name]);
          break;

      }
      $this->setCustomFields($dao->id, $dao->name, $definition);
    }
    $dao->free();
  }

  /**
   * Method to set option group ids
   *
   * @param $definition
   */
  private function setOptionGroups(&$definition) {
    $query = "SELECT id, name FROM civicrm_option_group WHERE name IN (%1, %2, %3, %4, %5, %6, %7, %8, %9,
                 %10, %11, %12, %13, %14, %15, %16, %17, %18 ,%19,%20)";
    $queryParams = [
      1 => ["activity_type", "String"],
      2 => ["campaign_status", "String"],
      3 => ["nbr_bleed_difficulties", "String"],
      4 => ["nbr_visit_bleed_site", "String"],
      5 => ["nbr_visit_participation_consent_version", "String"],
      6 => ["nbr_visit_participation_questionnaire_version", "String"],
      7 => ["nbr_visit_participation_study_payment", "String"],
      8 => ["nbr_not_recruited_reason", "String"],
      9 => ["nbr_redundant_reason", "String"],
      10 => ["nbr_withdrawn_reason", "String"],
      11 => ["nbr_volunteer_status", "String"],
      12 => ["nbr_consent_version", "String"],
      13 => ["nbr_information_leaflet_version", "String"],
      14 => ["nbr_medication", "String"],
      15 => ["nbr_drug_family", "String"],
      16 => ["nihr_ethnicity", "String"],
      17 => ["nbr_disease", "String"],
      18 => ["nbr_family_member", "String"],
      19 => ["nbr_assent_version", "String"],
      20 => ["nbr_assent_pis_version", "String"]
    ];
    $dao = \CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      switch($dao->name) {
        case "activity_type":
          $definition->addMethodCall('setActivityTypeOptionGroupId', [(int) $dao->id]);
          break;

        case "campaign_status":
          $definition->addMethodCall('setCampaignStatusOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_bleed_difficulties":
          $definition->addMethodCall('setBleedDifficultiesOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_consent_version":
          $definition->addMethodCall('setConsentVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_assent_version":
          $definition->addMethodCall('setAssentVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_assent_pis_version":
          $definition->addMethodCall('setAssentPisVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_disease":
          $definition->addMethodCall('setDiseaseOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_drug_family":
          $definition->addMethodCall('setDrugFamilyOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_family_member":
          $definition->addMethodCall('setFamilyMemberOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_information_leaflet_version":
          $definition->addMethodCall('setLeafletVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_medication":
          $definition->addMethodCall('setMedicationOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_not_recruited_reason":
          $definition->addMethodCall('setNotRecruitedReasonOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_redundant_reason":
          $definition->addMethodCall('setRedundantReasonOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_visit_bleed_site":
          $definition->addMethodCall('setSampleSiteOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_visit_participation_consent_version":
          $definition->addMethodCall('setParticipationConsentVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_visit_participation_questionnaire_version":
          $definition->addMethodCall('setQuestionnaireVersionOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_visit_participation_study_payment":
          $definition->addMethodCall('setStudyPaymentOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_volunteer_status":
          $definition->addMethodCall('setVolunteerStatusOptionGroupId', [(int) $dao->id]);
          break;

        case "nbr_withdrawn_reason":
          $definition->addMethodCall('setWithdrawnReasonOptionGroupId', [(int) $dao->id]);
          break;

        case "nihr_ethnicity":
          $definition->addMethodCall('setEthnicityOptionGroupId', [(int) $dao->id]);
          break;
      }
    }
    $dao->free();
  }

  /**
   * Method to set activity types
   *
   * @param $definition
   */
  private function setActivityTypes(&$definition) {
    $query = "SELECT cov.value, cov.name FROM civicrm_option_group AS cog
        JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
        WHERE cog.name = %1 AND cov.name IN (%2, %3, %4, %5, %6, %7, %8, %9, %10, %11, %12, %13, %14, %15, %16, %17, %18)";
    $dao = \CRM_Core_DAO::executeQuery($query, [
      1 => ["activity_type", "String"],
      2 => ["nihr_consent", "String"],
      3 => ["Meeting", "String"],
      4 => ["Email", "String"],
      5 => ["nbr_incoming_communication", "String"],
      6 => ["Phone Call", "String"],
      7 => ["Print PDF Letter", "String"],
      8 => ["SMS", "String"],
      9 => ["nbr_follow_up", "String"],
      10 => ["nbr_sample_received", "String"],
      11 => ["nihr_visit_stage1", "String"],
      12 => ["nihr_visit_stage2", "String"],
      13 => ["nihr_consent_stage2", "String"],
      14 => ["nihr_volunteer_not_recruited", "String"],
      15 => ["nihr_volunteer_redundant", "String"],
      16 => ["nihr_volunteer_withdrawn", "String"],
      17 => ["Open Case", "String"],
      18 => ["Bulk Email", "String"],
      19 => ["nbr_assent", "String"]
    ]);
    while ($dao->fetch()) {
      switch ($dao->name) {
        case "Bulk Email":
          $definition->addMethodCall('setBulkMailActivityTypeId', [(int) $dao->value]);
          break;

        case "Email":
          $definition->addMethodCall('setEmailActivityTypeId', [(int) $dao->value]);
          break;

        case "Meeting":
          $definition->addMethodCall('setMeetingActivityTypeId', [(int) $dao->value]);
          break;

        case "nbr_follow_up":
          $definition->addMethodCall('setFollowUpActivityTypeId', [(int) $dao->value]);
          break;

        case "nbr_incoming_communication":
          $definition->addMethodCall('setIncomingCommunicationActivityTypeId', [(int) $dao->value]);
          break;

        case "nbr_sample_received":
          $definition->addMethodCall('setSampleReceivedActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_consent":
          $definition->addMethodCall('setConsentActivityTypeId', [(int) $dao->value]);
          break;

        case "nbr_assent":
          $definition->addMethodCall('setAssentActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_consent_stage2":
          $definition->addMethodCall('setConsentStage2ActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_visit_stage1":
          $definition->addMethodCall('setVisitStage1ActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_visit_stage2":
          $definition->addMethodCall('setVisitStage2ActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_volunteer_not_recruited":
          $definition->addMethodCall('setNotRecruitedActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_volunteer_redundant":
          $definition->addMethodCall('setRedundantActivityTypeId', [(int) $dao->value]);
          break;

        case "nihr_volunteer_withdrawn":
          $definition->addMethodCall('setWithdrawnActivityTypeId', [(int) $dao->value]);
          break;

        case "Open Case":
          $definition->addMethodCall('setOpenCaseActivityTypeId', [(int) $dao->value]);
          break;

        case "Phone Call":
          $definition->addMethodCall('setPhoneActivityTypeId', [(int) $dao->value]);
          break;

        case "Print PDF Letter":
          $definition->addMethodCall('setLetterActivityTypeId', [(int) $dao->value]);
          break;

        case "SMS":
          $definition->addMethodCall('setSmsActivityTypeId', [(int) $dao->value]);
          break;
      }
    }
    $dao->free();
  }

  /**
   * Method to set consent status
   *
   * @param $definition
   */
  private function setConsentStatus(&$definition) {
    $query = "SELECT cov.value FROM civicrm_option_group AS cog
        JOIN civicrm_option_value AS cov ON cog.id = cov.option_group_id
        WHERE cog.name = %1 AND cov.name = %2";
    $status = \CRM_Core_DAO::singleValueQuery($query, [
      1 => ["nbr_consent_status", "String"],
      2 => ["consent_form_status_correct", "String"],
    ]);
    if ($status) {
      $definition->addMethodCall('setCorrectConsentStatusValue', [$status]);
    }
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
        case "study_participation_status_accepted":
          $definition->addMethodCall('setAcceptedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_declined":
          $definition->addMethodCall('setDeclinedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_excluded":
          $definition->addMethodCall('setExcludedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_invitation_pending":
          $definition->addMethodCall('setInvitationPendingParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_invited":
          $definition->addMethodCall('setInvitedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_no_response":
          $definition->addMethodCall('setNoResponseParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_not_participated":
          $definition->addMethodCall('setNotParticipatedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_participated":
          $definition->addMethodCall('setParticipatedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_reneged":
          $definition->addMethodCall('setRenegedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_return_to_sender":
          $definition->addMethodCall('setReturnToSenderParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_selected":
          $definition->addMethodCall('setSelectedParticipationStatusValue', [$dao->value]);
          break;

        case "study_participation_status_withdrawn":
          $definition->addMethodCall('setWithdrawnParticipationStatusValue', [$dao->value]);
          break;
      }
    }
    $dao->free();
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
        case "nihr_excluded_drugs":
          $definition->addMethodCall('setDrugsEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_ethnicity":
          $definition->addMethodCall('setEthnicityEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_gender":
          $definition->addMethodCall('setGenderEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_mri":
          $definition->addMethodCall('setMriEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_excluded_online":
          $definition->addMethodCall('setExclOnlineEligibilityStatusValue', [$dao->value]);
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
        case "nihr_max_invites":
          $definition->addMethodCall('setMaxEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_not_active":
          $definition->addMethodCall('setActiveEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_not_recallable":
          $definition->addMethodCall('setRecallableEligibilityStatusValue', [$dao->value]);
          break;
        case "nihr_online_only":
          $definition->addMethodCall('setOnlineOnlyEligibilityStatusValue', [$dao->value]);
          break;
      }
    }
    $dao->free();
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

        case "volunteer_status_consent_outdated":
          $definition->addMethodCall('setOutdatedVolunteerStatus', [$dao->value]);
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
    $dao->free();
  }

}

