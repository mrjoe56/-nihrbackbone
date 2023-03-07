{literal}
  <script type="text/javascript">
    cj('.action-item').each(function() {
      var hideMerge = {/literal}{if $hideCaseMerge}true{else}false{/if}{literal};
      var myHref = cj(this).attr('href');
      var myTxt = cj(this).text().trim();
      if (hideMerge && myHref === "#mergeCasesDialog") {
        cj(this).hide();
      } else if  (myTxt === "Assign to Another Client") {
        cj(this).hide();
      }
    });
  </script>
{/literal}
