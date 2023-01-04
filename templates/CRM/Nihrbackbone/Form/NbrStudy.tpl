<div class="crm-block crm-form-block">
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  {if !empty($clone_of)}
    <div class="help-block" id="help">
      This study is a clone of {$clone_of}
    </div>
  {/if}
  {if !empty($has_clones)}
    <div class="help-block" id="help">
      This study has clones: {$has_clones}
    </div>
  {/if}
  {foreach from=$elementNames item=elementName}
    {if $elementName eq "nsd_commercial"}
      <div class="crm-accordion-wrapper nbr_selection_criteria-block">
        <div class = "crm-accordion-header">Selection criteria</div>
        <div class="crm-accordion-body nbr_selection_criteria-body">
    {/if}
    {if $elementName eq "nsd_recall"}
      <div class="crm-accordion-wrapper nbr_study type-block">
        <div class = "crm-accordion-header">Study type (must select one)</div>
        <div class="crm-accordion-body nbr_study_type_criteria-body">
    {/if}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}</div>
      <div class="clear"></div>
    </div>
    {if $elementName eq "nsd_data_only" or $elementName eq "nsc_bmi_to"}
        </div></div>
    {/if}
  {/foreach}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
