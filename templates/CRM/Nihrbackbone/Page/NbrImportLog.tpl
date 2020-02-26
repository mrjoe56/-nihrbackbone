<div class="crm-content-block crm-block">
  <div class="crm-section">
    <div class="label">{ts}Number of records: {/ts}</div>
    <div class="content">{$record_count}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{ts}Successfully imported: {/ts}</div>
    <div class="content">{$imported_count}</div>
    <div class="clear"></div>
  </div>
  <div class="crm-section">
    <div class="label">{ts}Failed to import: {/ts}</div>
    <div class="content">{$failed_count}</div>
    <div class="clear"></div>
  </div>
  <br />
  <h3>{ts}Detailed import messages:{/ts}</h3>
  <div id="nbr-import-log-wrapper" class="dataTables_wrapper">
      {include file="CRM/common/jsortable.tpl"}
    <table id="nbr-import-log-table" class="display">
      <thead>
      <tr>
        <th id="sortable">{ts}Type{/ts}</th>
        <th id="sortable">{ts}Message{/ts}</th>
      </tr>
      </thead>
      <tbody>
      {assign var="row_class" value="odd-row"}
      {foreach from=$messages key=message_id item=message}
        <tr id="nbr-import-log-{$message_id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
          <td>{$message.message_type}</td>
          <td>{$message.message}</td>
        </tr>
      {/foreach}
      </tbody>
    </table>
  </div>
  <button type="cancel" onclick="location.href='{$done_url}';" name="_qf_NbrImportLog_cancel" data-form-name="NbrImportLog" class="crm-button">
    <i class="crm-i fa-close"></i>
    {ts}Done{/ts}
  </button>
</div>

