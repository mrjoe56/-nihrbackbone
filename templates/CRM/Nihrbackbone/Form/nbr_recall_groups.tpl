<div class="crm-section">
  <div class="label">{$form.recall_group_1.label}</div>
  <div class="content">{$form.recall_group_1.html}</div>
  <div class="clear"></div>
</div>
<div class="crm-section">
  <div class="label">{$form.recall_group_2.label}</div>
  <div class="content">{$form.recall_group_2.html}</div>
  <div class="clear"></div>
</div>
{literal}
  <script type="text/javascript">
    cj(document).ready(function() {
      console.log("Ik ben er wel!");
      cj("#nbr_participation_data").children('.crm-accordion-body').children().each(function () {
        console.log(cj(this));
      });
    });
  </script>
{/literal}
