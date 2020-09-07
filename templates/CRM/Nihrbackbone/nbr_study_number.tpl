<table id="nbr_study_number_table" class="crm-info-panel">
  <tbody>
    <tr>
      <td class="label nbr_study_number">{$form.study_number.label}</td>
      <td class="html-adjust nbr_study_number">{$form.study_number.value}</td>
    </tr>
  </tbody>
</table>
{literal}
  <script type="text/javascript">
    cj(".crm-info-panel td").each(function() {
      myText = cj(this).text();
      if (myText === "Study") {
        cj(this).parent().parent().parent().parent().prepend(cj("#nbr_study_number_table"));
        cj(this).parent().parent().parent().hide();
      }
    });
  </script>
{/literal}
