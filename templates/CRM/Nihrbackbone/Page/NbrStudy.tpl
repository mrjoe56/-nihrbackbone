<div class="crm-content-block crm-block">
  <div class="action-link">
    <a class="button new-option new-nbr-study" href="{$add_url}">
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Study{/ts}</span>
    </a>
  </div>
  <div id="nbr_study_page_wrapper" class="dataTables_wrapper">
    {include file="CRM/common/jsortable.tpl"}
    <table id="nbr_study-table" class="display">
      <thead>
      <tr>
        <th id="sortable">{ts}Study Number{/ts}</th>
        <th id="sortable">{ts}Study Name{/ts}</th>
        <th id="sortable">{ts}Status{/ts}</th>
        <th id="sortable">{ts}Site{/ts}</th>
        <th id="sortable">{ts}Sample only?{/ts}</th>
        <th id="nosort">{ts}Data only?{/ts}</th>
        <th id="nosort">{ts}Multi visit?{/ts}</th>
        <th id="nosort">{ts}Online?{/ts}</th>
        <th id="sortable">{ts}Primary Nurse{/ts}</th>
        <th id="sortable">{ts}Blood?{/ts}</th>
        <th id="sortable">{ts}Travel?{/ts}</th>
        <th id="sortable">{ts}Start Date{/ts}</th>
        <th id="nosort" rowspan="1" colspan="1"></th>
      </tr>
      </thead>
      <tbody>
      {assign var="row_class" value="odd-row"}
      {foreach from=$nbr_studies key=study_id item=study}
        <tr id="nbr_study-{$study_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class} nbr-study-row">
          <td>{$study.study_number}</td>
          <td>{$study.name}</td>
          <td>{$study.status}</td>
          <td>{$study.site}</td>
          <td>{$study.sample_only}</td>
          <td>{$study.data_only}</td>
          <td>{$study.multi_visit}</td>
          <td>{$study.online}</td>
          <td>{$study.primary_nurse}</td>
          <td>{$study.blood_required}</td>
          <td>{$study.travel_required}</td>
          <td>{$study.start_date|truncate:10:''|crmDate}</td>
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
    <a class="button new-option new-nbr-study" href="{$add_url}">
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Study{/ts}</span>
    </a>
  </div>
</div>

