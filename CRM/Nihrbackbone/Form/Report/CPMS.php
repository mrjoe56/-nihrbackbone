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
                            'pack_id' => array('alias' => 'vw_cpms', 'title' => ts('Volunteer Pack ID'), 'required' => TRUE),
                            'first_name' => array('alias' => 'vw_cpms', 'title' => ts('Forename')),
                            'last_name' => array('alias' => 'vw_cpms', 'title' => ts('Surname')),
                            'birth_date' => array('alias' => 'vw_cpms', 'title' => ts('DOB')),
                            'consent_date' => array('alias' => 'vw_cpms', 'title' => ts('Volunteer Consent Date'), 'required' => TRUE,),
                            'site_name' => array('alias' => 'vw_cpms', 'title' => ts('Site Name'),'default' => TRUE),
                            'site_ods_code' => array('alias' => 'vw_cpms', 'title' => ts('Site ODS Code'),'default' => TRUE),
                            'nvp_panel' => array('alias' => 'vw_cpms', 'title' => ts('Panel ID')),
                            'panel_name' => array('alias' => 'vw_cpms', 'title' => ts('Panel Name')),
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
                            'default' => 'IBD'
                        ],
                        'visit' => [
                            'title' => ts('Completed Stage 1 Visit'),
                            'type' => CRM_Utils_Type::T_BOOLEAN,
                            'default' => 1
                        ],

                    ),
                ),
        );

        #$this->_groupFilter = TRUE;
        #$this->_tagFilter = TRUE;
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
        foreach ($this->_columns as $tableName => $table) {

            if (array_key_exists('filters', $table)) {
                $operator = '';
                foreach ($table['filters'] as $fieldName => $field) {
                    $clause = NULL;
                    if (CRM_Utils_Array::value('type', $field) & CRM_Utils_Type::T_DATE) {
                        $relative = CRM_Utils_Array::value("{$fieldName}_relative", $this->_params);
                        $from = CRM_Utils_Array::value("{$fieldName}_from", $this->_params);
                        $to = CRM_Utils_Array::value("{$fieldName}_to", $this->_params);

                        if ($relative || $from || $to) {
                            $clause = $this->dateClause($field['name'], $relative, $from, $to, $field['type']);
                        }
                    }
                    else if ($fieldName == 'visit') {
                        $visit = CRM_Utils_Array::value("{$fieldName}_value", $this->_params);
                        if ($visit == '') {
                            $operator = '';
                        } else {
                            $operator = ($visit == 1 ? 'in' : 'not in');
                        }
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


                $result = civicrm_api3('OptionValue', 'get', [
                    'sequential' => 1,
                    'return' => ["value"],
                    'option_group_id' => "activity_type",
                    'label' => "Visit Stage 1",
                ]);
                $stage1_visit_act_type = $result['values'][0]['value'];
                Civi::log()->debug('visit = '.$visit.'  $operator = '.$operator);
                if ($operator != '') {
                    array_push($clauses, ' contact_id '.$operator.' (select cc.contact_id from civicrm_case_contact cc, civicrm_case_activity ca, 
                                                       civicrm_activity a where cc.case_id = ca.case_id and ca.activity_id = a.id 
                                                       and a.activity_type_id = '.$stage1_visit_act_type.' and a.status_id = 1)');
                }
            }
        }
        if (empty($clauses)) {
            $this->_where = "WHERE ( 1 ) ";
            Civi::log()->debug('no clauses');
        }
        else {
            $this->_where = "WHERE " .implode(' AND ', $clauses);
            Civi::log()->debug('$this->_where :'.$this->_where);
        }
        if ($this->_aclWhere) {
            $this->_where .= " AND {$this->_aclWhere} ";
        }
    }

    function postProcess() {
        $this->beginPostProcess();
        $sql = $this->buildQuery(TRUE);
        Civi::log()->debug('$sql : '.$sql);
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

