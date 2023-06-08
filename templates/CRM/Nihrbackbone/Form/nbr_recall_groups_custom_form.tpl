<table id="nbr_recall_groups_customform_table" class="crm-info-panel">
  <tbody>
  {foreach from=$elementNames item=elementName}
    {if $elementName|strstr:"recall_group_"}
      <tr>
        <td class="label nbr_recall_group_label">{$form.$elementName.label}</td>
        <td class="html-adjust nbr_recall_group">{$form.$elementName.html}</td>
      </tr>
    {/if}
  {/foreach}
  </tbody>
</table>
{literal}
  <script type="text/javascript">
    CRM.$(document).ready(function($) {
      $(".crm-info-panel td").each(function() {
        $('.custom-group-nbr_participation_data').children().append($("#nbr_recall_groups_customform_table"));
      });
    });
  </script>
{/literal}
