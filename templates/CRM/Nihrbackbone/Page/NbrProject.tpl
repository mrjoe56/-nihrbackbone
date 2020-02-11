<div class="crm-content-block crm-block">
  <div class="action-link">
    <a class="button new-option" href="{$add_url}">
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Project{/ts}</span>
    </a>
  </div>
  <div id="nbr_project_page_wrapper" class="dataTables_wrapper">
    {include file="CRM/common/jsortable.tpl"}
    <table id="nbr_project-table" class="display">
      <thead>
      <tr>
        <th id="sortable">{ts}Project Name{/ts}</th>
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
      {foreach from=$nbr_projects key=project_id item=project}
        <tr id="nbr_project-{$project_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
          <td>{$project.name}</td>
          <td>{$project.status}</td>
          <td>{$project.site}</td>
          <td>{$project.sample_only}</td>
          <td>{$project.data_only}</td>
          <td>{$project.multi_visit}</td>
          <td>{$project.online}</td>
          <td>{$project.primary_nurse}</td>
          <td>{$project.blood_required}</td>
          <td>{$project.travel_required}</td>
          <td>{$project.start_date|truncate:10:''|crmDate}</td>
          <td>
              <span>
                {foreach from=$project.actions item=action_link}
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
      <span><div class="icon add-icon ui-icon-circle-plus"></div>{ts}Add Project{/ts}</span>
    </a>
  </div>
</div>

