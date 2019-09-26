<div class="crm-content-block crm-block">
  <div id="help">
    {ts}This overview shows you the current volunteer selection on a project{/ts}
  </div>

  <div id="nbr_volunteer_case_wrapper" class="dataTables_wrapper">
    <table id="nbr_volunteer_case-table" class="display">

      <thead>
      <tr>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Name{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}BioResource ID{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}ID in Project{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Sex{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Age{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Ethnicity{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Location{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Status in Project{/ts}</th>
        <th class="sorting-disabled" rowspan="1" colspan="1">{ts}Eligible{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {assign var="row_class" value="odd-row"}
      {foreach from=$volunteers item=volunteer}
        <tr id="nbr_volunteer_case-{$volunteer.case_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
          <td>{$volunteer.volunteer_name}</td>
          <td>{$volunteer.bioresource_id}</td>
          <td>{$volunteer.anon_project_id}</td>
          <td>{$volunteer.sex}</td>
          <td>{$volunteer.age}</td>
          <td>{$volunteer.ethnicity}</td>
          <td>{$volunteer.location}</td>
          <td>{$volunteer.volunteer_project_status}</td>
          <td>{$volunteer.eligible}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>
</div>
