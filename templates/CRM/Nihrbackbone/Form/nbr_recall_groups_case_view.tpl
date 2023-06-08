<table id="nbr_recall_groups_caseview_table" class="crm-info-panel">
  <tbody>
  {foreach from=$elementNames item=elementName}
    {if $elementName|strstr:"recall_group_"}
      <tr>
        <td class="label nbr_recall_group_label">{$form.$elementName.label}</td>
        <td class="html-adjust nbr_recall_group">{$form.$elementName.value}</td>
      </tr>
    {/if}
  {/foreach}
  </tbody>
</table>
{literal}
  <script type="text/javascript">
    CRM.$(document).ready(function($) {
      $(".crm-info-panel td").each(function () {
        let myText = $(this).text();
        if (myText === "Study") {
          $(this).parent().parent().parent().parent().prepend($("#nbr_recall_groups_caseview_table"));
          $(this).parent().parent().parent().hide();
        }
      });
    });
  </script>
{/literal}
