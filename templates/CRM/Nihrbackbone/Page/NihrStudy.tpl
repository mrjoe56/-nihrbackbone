<div class="crm-content-block crm-block">
  <div id="help">{$helpTxt}</div>
  <div class="action-link">
    <a class="button new-option" href="{$add_url}">
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Study{/ts}</span>
    </a>
  </div>
  <div id="nihr_study_page_wrapper" class="dataTables_wrapper">
    {include file="CRM/common/jsortable.tpl"}
    <table id="nihr_study-table" class="display">
      <thead>
        <tr>
          <th id="sortable">{ts}Study ID{/ts}</th>
          <th id="sortable">{ts}Title{/ts}</th>
          <th id="sortable">{ts}Principal Investigator{/ts}</th>
          <th id="nosort">{ts}Description{/ts}</th>
          <th id="nosort">{ts}Ethics Number{/ts}</th>
          <th id="nosort">{ts}Ethics Approved{/ts}</th>
          <th id="sortable">{ts}Status{/ts}</th>
          <th id="sortable">{ts}Start Date{/ts}</th>
          <th id="sortable">{ts}End Date{/ts}</th>
          <th id="sortable">{ts}Centry Study Origin{/ts}</th>
          <th id="sortable">{ts}Created Date{/ts}</th>
          <th id="sortable">{ts}Created By{/ts}</th>
          <th id="sortable">{ts}Modified Date{/ts}</th>
          <th id="sortable">{ts}Modified By{/ts}</th>
          <th id="nosort" rowspan="1" colspan="1"></th>
        </tr>
      </thead>
      <tbody>
      {assign var="row_class" value="odd-row"}
      {foreach from=$studies key=study_id item=study}
        <tr id="nihr_study-{$study_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
          <td>{$study.id}</td>
          <td>{$study.title}</td>
          <td>{$study.investigator}</td>
          <td>{$study.description}</td>
          <td>{$study.ethics_number}
          <td>{$study.ethics_approved}
          <td>{$study.status}
          <td>{$study.start_date|crmDate}
          <td>{$study.end_date|crmDate}
          <td>{$study.centre_study_origin}</td>
          <td>{$study.created_date|crmDate}</td>
          <td>{$study.created_by}</td>
          <td>{$study.modified_date|crmDate}</td>
          <td>{$study.modified_by}</td>
          <td>
              <span>
                {foreach from=$study.actions item=action_link}
                  {$action_link}
                {/foreach}
              </span>
          </td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>
  <div class="action-link">
    <a class="button new-option" href="{$add_url}">
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Study{/ts}</span>
    </a>
  </div>
</div>

