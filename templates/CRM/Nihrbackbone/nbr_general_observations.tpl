{* NBR general observations formatting/validation *}
<div id="nbr_wt">
  <div id="nbr_wt_stones">
    <div>Weight (stone):</div>
    <div class="abc">{$form.nvgo_val_wt_stones.html}</div>
  </div>
  <div id="nbr_wt_lbs">
    <div>(lb):</div>
    <div>{$form.nvgo_val_wt_lbs.html}</div>
  </div>
</div>

<div id="nbr_ht">
  <div id="nbr_ht_ft">
    <div>Height (ft):</div>
    <div>{$form.nvgo_val_ht_ft.html}</div>
  </div>
  <div id="nbr_ht_in">
    <div>(in):</div>
    <div>{$form.nvgo_val_ht_in.html}</div>
  </div>
</div>

<div id="pgStatusMsgNode"></div>

{literal}

  <script type="text/javascript">

    CRM.$(document).ready(function($) {

      setTimeout(function() {                                                  // timout to load civi core stuff

        //----- get ht, wt, bmi object handles ----------------------------------------------------
        var obj_ht_m=CRM.$("[data-crm-custom='nihr_volunteer_general_observations:nvgo_height_m']");
        var obj_wt_kg=CRM.$("[data-crm-custom='nihr_volunteer_general_observations:nvgo_weight_kg']");
        var obj_bmi=CRM.$("[data-crm-custom='nihr_volunteer_general_observations:nvgo_bmi']");
        var obj_ht_ft=CRM.$("#nvgo_val_ht_ft");
        var obj_ht_in=CRM.$("#nvgo_val_ht_in");
        var obj_wt_stones=CRM.$("#nvgo_val_wt_stones");
        var obj_wt_lbs=CRM.$("#nvgo_val_wt_lbs");
        obj_bmi.attr('readonly', true);                                       // set BMI read only

        //------ keypress event handler - prevent non-numeric input -------------------------------
        //------ focus event handler - cancel error message ---------------------------------------
        $([obj_ht_m, obj_wt_kg, obj_bmi, obj_ht_ft, obj_ht_in, obj_wt_stones, obj_wt_lbs]).each( function(){
          $(this).focus(function (event) {nbr_errorMsg('CancelMsg');});
          $(this).keypress(function (event) {return nbr_keypressNumeric(event, this);});
        });

        //----- onchange event handlers  ----------------------------------------------------------
        obj_wt_kg.change(function() {                                          // onChange of wt(kg)
          if (!(nbr_in_range($(this).val(), 635))) {                           //  check value in range
            $(this).val(0);
            nbr_errorMsg('numr','Weight');
          }
          newWt = nbr_getWt(obj_wt_kg.val(),0,0)
          obj_wt_stones.val(newWt['st']);                                      //  reset wt(stones)
          obj_wt_lbs.val(newWt['lb']);                                         //   wt(lbs)
          obj_bmi.val(nbr_set_bmi(obj_ht_m.val(),newWt['kg']));                //   and BMI
        });
        obj_wt_stones.add(obj_wt_lbs).change(function () {                     // onChange of wt stones/lbs
          ubound = ($(this).attr('id') == 'nvgo_val_wt_lbs')?13:100;           //  check value in range
          if (!(nbr_in_range($(this).val(), ubound))) {
            $(this).val(0);
            nbr_errorMsg('numr','Weight');
          }
          var newWt = nbr_getWt(0,obj_wt_stones.val(),obj_wt_lbs.val());
          obj_wt_kg.val(newWt['kg']);                                          // reset wt(kg)
          obj_bmi.val(nbr_set_bmi(obj_ht_m.val(),newWt['kg']));                //   and BMI
        });
        obj_ht_ft.add(obj_ht_in).change(function () {                          // onChange of ht ft/in
          if (!(nbr_in_range($(this).val(), 12))) {                            //  check value in range
            $(this).val(0);
            nbr_errorMsg('numr','Height');
          }
          var newHt = nbr_getHt(0,obj_ht_ft.val(),obj_ht_in.val());
          obj_ht_m.val(newHt['hm']);                                           //  reset ht(m)
          obj_bmi.val(nbr_set_bmi(newHt['hm'],obj_wt_kg.val()));               //   and BMI
        });
        obj_ht_m.change(function () {                                          // onChange of ht(m)
          if (!(nbr_in_range($(this).val(), 3.63))) {$(this).val(0);}          //  check value in range
          var newHt = nbr_getHt($(this).val(),0,0);
          obj_ht_ft.val(newHt['hft']);                                         //  reset ht(ft)
          obj_ht_in.val(newHt['hin']);                                         //   ht(in)
          obj_bmi.val(nbr_set_bmi(obj_ht_m.val(),obj_wt_kg.val()));            //   and BMI
        });
        //----- /onchange event handlers  ---------------------------------------------------------

      }, 2000);

    })

  </script>

{/literal}

{literal}

<style type="text/css">

  #nvgo_val_wt_stones, #nvgo_val_wt_lbs, #nvgo_val_ht_ft, #nvgo_val_ht_in {
    height: 20px;
    width:30px;
  }
  #pgStatusMsgNode {
    position: absolute;
    top:100px;
    left:400px;
    color: red;
    fontWeight, normal;
    display, block;
  }
  #nbr_ht {
    width:500px;
    height: 70px;
  }
  #nbr_ht_ft {
    position: relative;
    width:70px;
    top:-5px;
  }
  #nbr_ht_in {
    position: relative;
    width:70px;
    top:-49px;
    left:100px;
  }
  #nbr_wt{
    width:500px;
    height: 70px;
  }
  #nbr_wt_stones {
    position: relative;
    width:100px;
    top:10px;
  }
  #nbr_wt_lbs {
    position: relative;
    width:60px;
    top:-34px;
    left:100px;
  }
</style>

{/literal}
