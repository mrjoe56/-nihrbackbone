<div class="crm-block crm-form-block">
  <div class="help-block" id="help">
    {ts}On the left side you see all the fields in your csv file, on the right hand you can select where they should be imported in to. The data will be imported into a new individual (Volunteer).{/ts}
    <br />
    {ts}A log of each import action will be kept in the civicrm/ConfigAndLog folder.{/ts}
    <br />
    {ts}When a Volunteer already exists with the same NHS number or the same first_name/last_name/birth_date, the import contact will be skipped but logged.{/ts}
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout-compressed">
    <thead>
      <th>{ts}Source Colum from CSV file{/ts}</th>
      <th></th>
      <th>{ts}Column to Import to{/ts}</th>
    </thead>
    <tbody>
      {foreach from=$elementNames item=elementName}
      {assign var="field_title" value=$elementName|truncate:6:""}
        {if $field_title eq "source"}
          <tr id="nihr_import_map_{$elementName}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
        {/if}
        <td class="content">{$form.$elementName.html}</td>
        {if $field_title eq "source"}
          <td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
        {/if}
        {if $field_title eq "target"}
          </tr>
        {/if}
      {/foreach}
    </tbody>
  </table>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>

