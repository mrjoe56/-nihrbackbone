<div class="crm-block crm-form-block">
  <div class="help-block" id="help">
    {ts}Select the .csv file to import from. You can specify if the first row of the .csv file contains the headers of the fields, and which separator to use for the data (comma or semi-colon){/ts}
  </div>
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  <input type="hidden" name="study_id" id="study_id" value="{$study_id}" />

  <div class="crm-section csv_file_section">
    <div class="label">{$form.csv_file.label}</div>
    <div class="content">{$form.csv_file.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section first_row_headers_section">
    <div class="label">{$form.first_row_headers.label}</div>
    <div class="content">{$form.first_row_headers.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section separator_id_section">
    <div class="label">{$form.separator_id.label}</div>
    <div class="content">{$form.separator_id.html}</div>
    <div class="clear"></div>
  </div>

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>