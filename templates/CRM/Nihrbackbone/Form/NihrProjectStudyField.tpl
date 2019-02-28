<table class="form-layout-compressed nihr_project_study_section">
  <tbody>
    <tr class="custom_field-row nihr_project_study_column">
      <td class="label">{$form.npd_ui_project_study_id.label}</td>
      <td class="html-adjust">{$form.npd_ui_project_study_id.html}</td>
    </tr>
  </tbody>
</table>

{literal}
  <script type="text/javascript">
    cj(document).ready(function() {
      cj('.nihr_project_study_section').insertAfter('.form-layout-compressed');
      cj('.nihr_project_study_section').eq(1).hide();
    });
  </script>
{/literal}
