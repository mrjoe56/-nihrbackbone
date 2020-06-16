{literal}
  <script type="text/javascript">
    cj('.action-item').each(function() {
      var myHref = cj(this).attr('href');
      var myTxt = cj(this).text().trim();
      if (myHref === "#mergeCasesDialog" || myTxt === "Assign to Another Client") {
        cj(this).hide();
      }
    });
  </script>
{/literal}
