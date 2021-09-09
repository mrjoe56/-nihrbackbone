{literal}
  <script type="text/javascript">
    hideEligible = false;
    cj(".crm-info-panel td").each(function() {
      myClass = this.className;
      myText = cj(this).text();
      if (myClass === "label" && myText === "Status in Study") {
        nextTxt = cj(this).next().text();
        if (nextTxt !== "Selected") {
          hideEligible = true;
        }
      }
    });
    if (hideEligible) {
      cj(".crm-info-panel td").each(function() {
        myClass = this.className;
        myText = cj(this).text();
        if (myClass === "label" && myText === "Eligibility") {
          cj(this).next().hide();
        }
      });
    }
  </script>
{/literal}
