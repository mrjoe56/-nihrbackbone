<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Nihrbackbone_Form_Report_SummaryNbr extends CRM_Report_Form {

  public $_summary = NULL;

  protected $_emailField = FALSE;

  protected $_phoneField = FALSE;

  protected $_customGroupExtends = array(
    'Contact',
    'Individual',
    'Household',
    'Organization',
  );

  public $_drilldownReport = array('contact/detail' => 'Link to Detail Report');

  /**
   * This report has not been optimised for group filtering.
   *
   * The functionality for group filtering has been improved but not
   * all reports have been adjusted to take care of it. This report has not
   * and will run an inefficient query until fixed.
   *
   * CRM-19170
   *
   * @var bool
   */
  protected $groupFilterNotOptimised = TRUE;

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
    $this->_columns = array(
        'civicrm_contact' => array(
          'dao' => 'CRM_Contact_DAO_Contact',
          'fields' => array_merge(
          #$this->getBasicContactFields(),
            $this->getNbrContactFields(),
            array(
              'modified_date' => array(
                'title' => ts(' Modified Date'),
                'default' => FALSE,
              ),
            )
          ),
          'filters' => $this->getBasicContactFilters(),
          'grouping' => 'contact-fields',
          'order_bys' => array(
            'sort_name' => array(
              'title' => ts('Last Name, First Name'),
              'default' => '1',
              'default_weight' => '0',
              'default_order' => 'ASC',
            ),
            'first_name' => array(
              'name' => 'first_name',
              'title' => ts('First Name'),
            ),
            'gender_id' => array(
              'name' => 'gender_id',
              'title' => ts('Gender'),
            ),
            'birth_date' => array(
              'name' => 'birth_date',
              'title' => ts('Birth Date'),
            ),
            'contact_type' => array(
              'title' => ts('Contact Type'),
            ),
            'contact_sub_type' => array(
              'title' => ts('Contact Subtype'),
            ),
          ),
        ),
        'civicrm_email' => array(
          'dao' => 'CRM_Core_DAO_Email',
          'fields' => array(
            'email' => array(
              'title' => ts(' Email'),
              'no_repeat' => TRUE,
            ),
          ),
          'grouping' => 'contact-fields',
          'order_bys' => array(
            'email' => array(
              'title' => ts('Email'),
            ),
          ),
        ),
        'civicrm_phone' => array(
          'dao' => 'CRM_Core_DAO_Phone',
          'fields' => array(
            'phone' => array(
              'title' => ts(' Phone'),
            ),
            'phone_ext' => array(
              'title' => ts(' Phone Extension'),
            ),
          ),
          'grouping' => 'contact-fields',
        ),
      ) + $this->getNbrAddressColumns(array('group_bys' => FALSE));

    $this->_groupFilter = TRUE;
    $this->_tagFilter = TRUE;
    parent::__construct();
  }

  public function preProcess() {
    parent::preProcess();
  }

  /**
   * @param $fields
   * @param $files
   * @param $self
   *
   * @return array
   */
  public static function formRule($fields, $files, $self) {
    $errors = $grouping = array();
    return $errors;
  }

  public function from() {
    $this->_from = "
        FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom} ";
    $this->joinAddressFromContact();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();
    $this->joinCountryFromAddress();
  }

  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = array();
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  /**
   * Alter display of rows.
   *
   * Iterate through the rows retrieved via SQL and make changes for display purposes,
   * such as rendering contacts as links.
   *
   * @param array $rows
   *   Rows generated by SQL, with an array for each row.
   */
  public function alterDisplay(&$rows) {
    $entryFound = FALSE;

    foreach ($rows as $rowNum => $row) {
      // make count columns point to detail report
      // convert sort name to links
      if (array_key_exists('civicrm_contact_sort_name', $row) &&
        array_key_exists('civicrm_contact_id', $row)
      ) {
        $url = CRM_Report_Utils_Report::getNextUrl('contact/detail',
          'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
          $this->_absoluteUrl, $this->_id, $this->_drilldownReport
        );
        $rows[$rowNum]['civicrm_contact_sort_name_link'] = $url;
        $rows[$rowNum]['civicrm_contact_sort_name_hover'] = ts('View Contact Detail Report for this contact');
        $entryFound = TRUE;
      }

      // Handle ID to label conversion for contact fields
      $entryFound = $this->alterDisplayContactFields($row, $rows, $rowNum, 'contact/summary', 'View Contact Summary') ? TRUE : $entryFound;

      // skip looking further in rows, if first row itself doesn't
      // have the column we need
      if (!$entryFound) {
        break;
      }
    }
  }



  public function getNbrContactFields() {
    return [
      'sort_name' => [
        'title' => ts('Contact Name'),
        'required' => TRUE,
        'default' => TRUE,
      ],
      'id' => [
        'no_display' => TRUE,
        'required' => TRUE,
      ],
      'prefix_id' => [
        'title' => ts(' Contact Prefix'),
      ],
      'first_name' => [
        'title' => ts(' First Name'),
      ],
      'nick_name' => [
        'title' => ts(' Nick Name'),
      ],
      'middle_name' => [
        'title' => ts(' Middle Name'),
      ],
      'last_name' => [
        'title' => ts(' Last Name'),
      ],
      'suffix_id' => [
        'title' => ts(' Contact Suffix'),
      ],
      'postal_greeting_display' => ['title' => ts(' Postal Greeting')],
      'email_greeting_display' => ['title' => ts(' Email Greeting')],
      'addressee_display' => ['title' => ts(' Addressee')],
      'contact_type' => [
        'title' => ts(' Contact Type'),
      ],
      'contact_sub_type' => [
        'title' => ts(' Contact Subtype'),
      ],
      'sic_code' => [
        'title' => ts(' ODS Code'),
      ],
      'gender_id' => [
        'title' => ts(' Gender'),
      ],
      'birth_date' => [
        'title' => ts(' Birth Date'),
      ],
      'age' => [
        'title' => ts(' Age'),
        'dbAlias' => 'TIMESTAMPDIFF(YEAR, contact_civireport.birth_date, CURDATE())',
      ],
      'job_title' => [
        'title' => ts(' Contact Job title'),
      ],
      'organization_name' => [
        'title' => ts(' Organization Name'),
      ],
      'external_identifier' => [
        'title' => ts(' Contact identifier from external system'),
      ],
      'do_not_email' => [
        'title' => ts(' Do not email'),
      ],
      'do_not_phone' => [
        'title' => ts(' Do not phone'),
      ],
      'do_not_mail' => [
        'title' => ts(' Do not mail'),
      ],
      'do_not_sms' => [
        'title' => ts(' Do not SMS'),
      ],
      'is_opt_out' => [
        'title' => ts(' No bulk emails (User opt out)'),
      ],
      'is_deceased' => [
        'title' => ts(' Deceased'),
      ],
      'preferred_language' => [
        'title' => ts(' Preferred language'),
      ],
      'employer_id' => [
        'title' => ts(' Current Employer'),
      ],
    ];
  }

  protected function getNbrAddressColumns($options = []) {
    $defaultOptions = [
      'prefix' => '',
      'prefix_label' => '',
      'fields' => TRUE,
      'group_bys' => FALSE,
      'order_bys' => TRUE,
      'filters' => TRUE,
      'join_filters' => FALSE,
      'fields_defaults' => [],
      'filters_defaults' => [],
      'group_bys_defaults' => [],
      'order_bys_defaults' => [],
    ];

    $options = array_merge($defaultOptions, $options);
    $defaults = $this->getDefaultsFromOptions($options);
    $tableAlias = $options['prefix'] . 'address';

    $spec = [
      $options['prefix'] . 'name' => [
        'title' => $options['prefix_label'] . ts(' Address Name'),
        'name' => 'name',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_number' => [
        'name' => 'street_number',
        'title' => $options['prefix_label'] . ts(' Street Number'),
        'type' => 1,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'odd_street_number' => [
        'title' => ts(' Odd / Even Street Number'),
        'name' => 'odd_street_number',
        'type' => CRM_Utils_Type::T_INT,
        'no_display' => TRUE,
        'required' => TRUE,
        'dbAlias' => "({$tableAlias}_civireport.street_number % 2)",
        'is_fields' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'street_name' => [
        'name' => 'street_name',
        'title' => $options['prefix_label'] . ts(' Street Name'),
        'type' => 1,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'operator' => 'like',
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'street_address' => [
        'title' => $options['prefix_label'] . ts(' Street Address'),
        'name' => 'street_address',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_1' => [
        'title' => $options['prefix_label'] . ts(' Supplementary Address Field 1'),
        'name' => 'supplemental_address_1',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_2' => [
        'title' => $options['prefix_label'] . ts(' Supplementary Address Field 2'),
        'name' => 'supplemental_address_2',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'supplemental_address_3' => [
        'title' => $options['prefix_label'] . ts(' Supplementary Address Field 3'),
        'name' => 'supplemental_address_3',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_number' => [
        'name' => 'street_number',
        'title' => $options['prefix_label'] . ts(' Street Number'),
        'type' => 1,
        'is_order_bys' => TRUE,
        'is_filters' => TRUE,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'street_unit' => [
        'name' => 'street_unit',
        'title' => $options['prefix_label'] . ts(' Street Unit'),
        'type' => 1,
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'city' => [
        'title' => $options['prefix_label'] . ts(' City'),
        'name' => 'city',
        'operator' => 'like',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'postal_code' => [
        'title' => $options['prefix_label'] . ts(' Postal Code'),
        'name' => 'postal_code',
        'type' => 1,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'postal_code_suffix' => [
        'title' => $options['prefix_label'] . ts(' Postal Code Suffix'),
        'name' => 'postal_code_suffix',
        'type' => 1,
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
        'is_order_bys' => TRUE,
      ],
      $options['prefix'] . 'county_id' => [
        'title' => $options['prefix_label'] . ts(' County'),
        'alter_display' => 'alterCountyID',
        'name' => 'county_id',
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::county(),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'state_province_id' => [
        'title' => $options['prefix_label'] . ts(' State/Province'),
        'alter_display' => 'alterStateProvinceID',
        'name' => 'state_province_id',
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::stateProvince(),
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
      ],
      $options['prefix'] . 'country_id' => [
        'title' => $options['prefix_label'] . ts(' Country'),
        'alter_display' => 'alterCountryID',
        'name' => 'country_id',
        'is_fields' => TRUE,
        'is_filters' => TRUE,
        'is_group_bys' => TRUE,
        'type' => CRM_Utils_Type::T_INT,
        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
        'options' => CRM_Core_PseudoConstant::country(),
      ],
      $options['prefix'] . 'location_type_id' => [
        'name' => 'location_type_id',
        'title' => $options['prefix_label'] . ts(' Location Type'),
        'type' => CRM_Utils_Type::T_INT,
        'is_fields' => TRUE,
        'alter_display' => 'alterLocationTypeID',
      ],
      $options['prefix'] . 'id' => [
        'title' => $options['prefix_label'] . ts(' ID'),
        'name' => 'id',
        'is_fields' => TRUE,
      ],
      $options['prefix'] . 'is_primary' => [
        'name' => 'is_primary',
        'title' => $options['prefix_label'] . ts(' Primary Address?'),
        'type' => CRM_Utils_Type::T_BOOLEAN,
        'is_fields' => TRUE,
      ],
    ];
    return $this->buildColumns($spec, $options['prefix'] . 'civicrm_address', 'CRM_Core_DAO_Address', $tableAlias, $defaults, $options);
  }



}
