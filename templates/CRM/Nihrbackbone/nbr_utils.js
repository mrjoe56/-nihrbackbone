function nbr_errorMsg(MsgType, Msg) {
  //----- set/clear page status message ----------------------------------------------------------
  var PgStatusMsgNode=CRM.$("#pgStatusMsgNode");           // get page status node
  switch(MsgType) {                               // set msg text
    case "numr":
      MsgTxt="Out of range value for "+Msg;
      break;
    case "CancelMsg":
      MsgTxt="";
      break;
    default:
      MsgTxt=Msg;
  }
  PgStatusMsgNode.html(MsgTxt)   ;                                  // display message
}

function nbr_keypressNumeric(evt, element) {
  //----- allow numeric input only ---------------------------------------------------------------
  var charCode = (evt.which) ? evt.which : evt.keyCode
  if (                                                              // allowed chars:
    ((charCode != 45) || $(element).val().indexOf('-') != -1) &&    //  - minus OK but only one
    (charCode != 46 || $(element).val().indexOf('.') != -1) &&      //  . dot OK but only one
    (charCode < 48 || charCode > 57) &&                             //  numeric char
    (charCode!=8)) {                                                //  Backspace
    return false;
  }
  return true;
}

function nbr_in_range(val, max) {
  //----- check entered value in range 0 - max ---------------------------------------------------
  if (parseFloat(val)<0||parseFloat(val)>max) {return false;} else {return true;}
}

function nbr_set_bmi(ht,wt) {
  //----- calculate bmi value --------------------------------------------------------------------
  if (wt!=0) {
    var bmi_val = (parseFloat(wt) / (parseFloat(ht) * parseFloat(ht))).toFixed(1)
  }
  else {
    var bmi_val = 0;
  }
  return bmi_val
}

function nbr_getWt(kg,st,lb) {
  //----- calculate weight values ----------------------------------------------------------------
  var dict = {};
  if (kg == 0) {
    var totlb = parseInt(st*14) + parseInt(lb);
    dict['kg'] = parseInt(Math.round(totlb * 0.453592));
    dict['st'] = st;
    dict['lb'] = lb;
  }
  else {
    var totlbs = parseInt(kg/0.453592);
    dict['kg'] = kg;
    dict['st'] = parseInt(totlbs/14);
    dict['lb'] = parseInt(totlbs - dict['st']*14)
  }
  return dict
}

function nbr_getHt(hm,hft,hin) {
  //----- calculate height values ----------------------------------------------------------------
  var dict = {};
  if (hm == 0) {
    dict['hm'] = ((parseInt(hft) + hin/12)/3.28084).toFixed(2);
    dict['hft'] = hft;
    dict['hin'] = hin;
  }
  else {
    dict['hm'] = hm;
    dict['hft'] = Math.floor(hm*3.29);
    dict['hin'] = Math.round(((hm*3.29) - dict['hft'])*12);
  }
  return dict;
}
