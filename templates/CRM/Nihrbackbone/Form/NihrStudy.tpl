<div class="crm-block crm-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>
  <input type="hidden" name="study_id" id="study_id" value="{$study_id}" />

  <div class="crm-section study_number_section">
    <div class="label">{$form.study_number.label}</div>
    <div class="content">{$form.study_number.html}
      {if !$study_id}<p class="description">{ts}When saving a study number will be automatically generated.{/ts}</p>{/if}
    </div>
    <div class="clear"></div>
  </div>

  <div class="crm-section investigator_id_section">
    <div class="label">{$form.investigator_id.label}</div>
    <div class="content">{$form.investigator_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section short_name_section">
    <div class="label">{$form.short_name.label}</div>
    <div class="content">{$form.short_name.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section long_name_section">
    <div class="label">{$form.long_name.label}</div>
    <div class="content">{$form.long_name.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section description_section">
    <div class="label">{$form.description.label}</div>
    <div class="content">{$form.description.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section ethics_number_section">
    <div class="label">{$form.ethics_number.label}</div>
    <div class="content">{$form.ethics_number.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section ethics_approved_section">
    <div class="label">{$form.ethics_approved_id.label}</div>
    <div class="content">{$form.ethics_approved_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section ethics_approved_date_section">
    <div class="label">{$form.ethics_approved_date.label}</div>
    <div class="content">{$form.ethics_approved_date.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section requirements_section">
    <div class="label">{$form.requirements.label}</div>
    <div class="content">{$form.requirements.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section valid_start_date_section">
    <div class="label">{$form.valid_start_date.label}</div>
    <div class="content">{$form.valid_start_date.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section valid_end_date_section">
    <div class="label">{$form.valid_end_date.label}</div>
    <div class="content">{$form.valid_end_date.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section centre_study_origin_section">
    <div class="label">{$form.centre_study_origin_id.label}</div>
    <div class="content">{$form.centre_study_origin_id.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section notes_section">
    <div class="label">{$form.notes.label}</div>
    <div class="content">{$form.notes.html}</div>
    <div class="clear"></div>
  </div>

  <div class="crm-section status_section">
    <div class="label">{$form.status_id.label}</div>
    <div class="content">{$form.status_id.html}</div>
    <div class="clear"></div>
  </div>

  <br />
  <div class="crm-section created_and_modified_section">
    <div class="content">
      <em>
        {if !empty($created_by)}
          ({$created_by})
        {/if}
        {if !empty($modified_by)}
          ({$modified_by})
        {/if}
      </em>
    </div>
    <div class="clear"></div>
  </div>

  {* FOOTER *}
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
