<div class="crm-block crm-form-block">
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  {foreach from=$elementNames item=elementName}
    {if $elementName eq "nsc_panel"}
      <div class="crm-accordion-wrapper nbr_selection_criteria-block">
        <div class = "crm-accordion-header">Selection criteria</div>
        <div class="crm-accordion-body nbr_selection_criteria-body">
    {/if}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
    </div>
  {/foreach}
        </div></div>
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
