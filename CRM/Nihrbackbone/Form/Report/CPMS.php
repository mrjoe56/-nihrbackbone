<?php
/**
 * CPMS.php
 * @title       CPMS (Central processing management system) monthly report template
 * @author      John Boucher <jab1012@bioresource.nihr.ac.uk>
 * @date        06/07/20
 * @extension   nihrbackbone
 **/

class CRM_Nihrbackbone_Form_Report_CPMS extends CRM_Report_Form {

    /** Class constructor. */
    public function __construct() {
        $this->_autoIncludeIndexedFieldsAsOrderBys = 1;
        $this->_columns = array(
            'vw_cpms' =>
                array(
                    'alias' => 'vw_cpms',
                    'fields' =>
                        array(
                          'site_ods_code' => array('alias' => 'vw_cpms', 'title' => ts('Site ODS Code'),'default' => TRUE),
                          'site_name' => array('alias' => 'vw_cpms', 'title' => ts('Site Name'),'default' => TRUE),
                          'consent_date' => array('alias' => 'vw_cpms', 'title' => ts('Volunteer Consent Date'), 'required' => TRUE,),
                          'pack_id' => array('alias' => 'vw_cpms', 'title' => ts('Volunteer Pack ID'), 'required' => TRUE),
                          'panel_name' => array('alias' => 'vw_cpms', 'title' => ts('Panel Name')),
                          'nvp_panel' => array('alias' => 'vw_cpms', 'title' => ts('Panel ID')),
                          'first_name' => array('alias' => 'vw_cpms', 'title' => ts('Forename')),
                          'last_name' => array('alias' => 'vw_cpms', 'title' => ts('Surname')),
                          'birth_date' => array('alias' => 'vw_cpms', 'title' => ts('DOB')),
                          'stage1_visit_status' => array('alias' => 'vw_cpms', 'is_pseudofield' => TRUE, 'no_display' => TRUE),
                          'cpms_accrual_status' => array('alias' => 'vw_cpms', 'is_pseudofield' => TRUE, 'no_display' => TRUE),
                          'sample_received_status' => array('alias' => 'vw_cpms', 'is_pseudofield' => TRUE, 'no_display' => TRUE),
                        ),
                    'filters' => array(
                        'consent_date' => array(
                            'title'        => 'Consent Date',
                            'operatorType' => CRM_Report_Form::OP_DATE,
                            'type'         => CRM_Utils_Type::T_DATE
                        ),
                        'panel_name' => [
                            'alias' => 'vw_cpms',
                            'title' => ts('Panel Name'),
                            'type' => CRM_Utils_Type::T_STRING,
                        ],
                      'stage1_visit_status' => [
                        'title' => ts('Stage 1 Visit actvity where activity status'),
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_PseudoConstant::activityStatus(),
                      ],
                      'sample_received_status' => [
                        'title' => ts('Sample Received actvity where activity status'),
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_PseudoConstant::activityStatus(),
                      ],
                      'cpms_accrual_status' => [
                        'title' => ts('CPMS accrual actvity where activity status'),
                        'type' => CRM_Utils_Type::T_STRING,
                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                        'options' => CRM_Core_PseudoConstant::activityStatus(),
                      ],
                    ),
                ),
        );

        parent::__construct();
    }

    /* Check for empty order_by configurations and remove them.
     * Also set template to hide them. * @param array $formValues */
    public function preProcess() {
        parent::preProcess();
    }

    /* form rule function for custom data. * @params array $fields, array $ignoreFields * @return array */
    public static function formRule($fields, $files, $self) {
        $errors = $grouping = array();
        return $errors;
    }

    /** Generate the SELECT clause and set class variable $_select.  */
    function select() {
        $select = array();
        $this->_columnHeaders = array();

        foreach ($this->_columns as $tableName => $table) {
            if (array_key_exists('fields', $table)) {
                foreach ($table['fields'] as $fieldName => $field) {
                    if (CRM_Utils_Array::value('required', $field)||CRM_Utils_Array::value($fieldName, $this->_params['fields'])) {
                        $select[] = "{$field['dbAlias']} AS {$tableName}_{$fieldName}";
                        $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
                        if (isset($field['title'])) {
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
                        }
                    }
                }
            }
        }
        $this->_select = "select ".implode(', ', $select) . " ";
    }


    function from() {
      $this->_from = "FROM vw_cpms ";
    }


    /* Generate where clause. */
    function where() {

      $clauses = array();
      $filter_clause = ['stage1_visit_status' => '', 'sample_received_status' => '', 'cpms_accrual_status' => ''];      # set filter clause array
      foreach ($this->_columns as $tableName => $table) {

        if (array_key_exists('filters', $table)) {
          #  $operator = '';
          foreach ($table['filters'] as $fieldName => $field) {
            $clause = NULL;
            # date filter clause
            if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
                $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
                $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
                $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);
                if ($relative || $from || $to) {
                    $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
                }
            }
              # activity status filters
              else if ($fieldName == 'stage1_visit_status'||$fieldName == 'sample_received_status'||$fieldName == 'cpms_accrual_status') {

                $status_array = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);                                 # for status activity filters
                $status_op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
                $status_clause = '';                                                                                               #  if we have values for this status filter
                if ($status_array) {                                                                                               #   set the where clause and operator
                  if ($status_op == 'in') {                                                                                        #   operator = 'is one of'
                    $status_clause = ' and a.status_id in (' . implode(',', $status_array) . ')';                             #
                  } else if ($status_op == 'notin') {                                                                              #   operator = 'is not one of'
                    $status_clause = ' and a.status_id not in (' . implode(',', $status_array) . ')';
                  }
                }
                else {
                  $status_array = [1,2,3,4,5,6,7,8];
                  if ($status_op == 'nll') {                                                                                       #  operator = 'is empty'
                    $status_clause = ' and a.status_id not in (' . implode(',', $status_array) . ')';
                  }
                  if ($status_op == 'nnll') {                                                                                      #  operator = 'is not empty'
                    $status_clause = ' and a.status_id in (' . implode(',', $status_array) . ')';
                  }
                }

                $filter_clause[$fieldName] = $status_clause;                                                                      #  update where clause array
              }
            else {
              $op = CRM_Utils_Array::value("{$fieldName}_op", $this->_params);
              if ($fieldName == 'rid') {
                $value = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                if (!empty($value)) {
                  $operator = '';
                  if ($op == 'notin') {
                    $operator = 'NOT';
                  }
                  $regexp = "[[:cntrl:]]*" . implode('[[:>:]]*|[[:<:]]*', $value) . "[[:cntrl:]]*";
                  $clause = "{$field['dbAlias']} {$operator} REGEXP '{$regexp}'";
                }
                $op = NULL;
              }

              if ($op) {
                $clause = $this->whereClause($field,
                  $op,
                  CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
                  CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
                );
              }
            }
            if (!empty($clause)) {
                $clauses[] = $clause;
            }
          }

          $filter_activity_type = ['stage1_visit_status' => '', 'sample_received_status' => '', 'cpms_accrual_status' => ''];      # set activity type values array
          $results = civicrm_api3('OptionValue', 'get', [                                                             # for status activity filters
            'sequential' => 1,                                                                                                     #
            'return' => ["label", "value"],
            'option_group_id' => "activity_type",
            'label' => ['IN' => ["CPMS Accrual", "Sample received", "Visit stage 1"]],
          ]);
          foreach ($results['values'] as $key => $val) {                                                                           #  get activity type values
            if ($val['label'] == 'Visit Stage 1') {$filter_activity_type['stage1_visit_status'] = $val['value'];}
            if ($val['label'] == 'CPMS Accrual') {$filter_activity_type['cpms_accrual_status'] = $val['value'];}
            if ($val['label'] == 'Sample received') {$filter_activity_type['sample_received_status'] = $val['value'];}
          }                                                                                                                        #  and update array

          if ($filter_clause['cpms_accrual_status']!= '') {                                                                        # add filter clauses for status activity filters
            array_push($clauses, ' contact_id in (select ac.contact_id from civicrm_activity_contact ac, civicrm_activity a
                                                      where a.id = ac.activity_id and a.is_deleted = 0 and a.activity_type_id = '.$filter_activity_type['cpms_accrual_status'].$filter_clause['cpms_accrual_status'].')');
          }
          if ($filter_clause['sample_received_status']!= '') {
              array_push($clauses, ' contact_id in (select ac.contact_id from civicrm_activity_contact ac, civicrm_activity a
                                                  where a.id = ac.activity_id and a.is_deleted = 0 and a.activity_type_id = '.$filter_activity_type['sample_received_status'].$filter_clause['sample_received_status'].')');
            }
          if ($filter_clause['stage1_visit_status']!= '') {
            array_push($clauses, ' contact_id in (select cc.contact_id from civicrm_case_contact cc, civicrm_case_activity ca,
                                                   civicrm_activity a where cc.case_id = ca.case_id and ca.activity_id = a.id and a.is_deleted = 0
                                                   and a.activity_type_id = '.$filter_activity_type['stage1_visit_status'].$filter_clause['stage1_visit_status'].')');
          }
        }
      }                                                                                                                            # // for each loop

      if (empty($clauses)) {
        $this->_where = "WHERE ( 1 ) ";
      }
      else {
        $this->_where = "WHERE " .implode(' AND ', $clauses);
      }
      if ($this->_aclWhere) {
        $this->_where .= " AND {$this->_aclWhere} ";
      }
    }                                                                                                                              # // where funct

    function postProcess() {
        $this->beginPostProcess();
        $sql = $this->buildQuery(TRUE);
        $rows = $graphRows = array();
        $this->buildRows($sql, $rows);
        $this->formatDisplay($rows);
        $this->doTemplateAssignment($rows);
        $this->endPostProcess($rows);
    }

    function buildRows($sql, &$rows) {
        $rows = array();
        $dao = CRM_Core_DAO::executeQuery($sql);
        $this->modifyColumnHeaders();
        while ($dao->fetch()) {
            $row = array();
            foreach ($this->_columnHeaders as $key => $value) {
                if (property_exists($dao, $key)) {
                    $row[$key] = $dao->$key;
                }
            }
            $rows[] = $row;
        }
    }
}

