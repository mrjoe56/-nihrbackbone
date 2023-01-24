//----- validate phone number in CRM_Contact_Form_Inline_Phone form -------------------------------
CRM.$(function ($) {

  CRM.$("#crm-phone-content").prepend("<span id='crm-phone-content-msg' class='phone1'>Add / Edit Phone number and press Enter</span>");
  CRM.$("#crm-phone-content-msg").css({'position':'absolute','top':'20px','left':'273px', 'font-weight': 'bold'});

  var $valid_uk_phone_pattern = /^(?:(?:\(?(?:0(?:0|11)\)?[\s-]?\(?|\+)44\)?[\s-]?(?:\(?0\)?[\s-]?)?)|(?:\(?0))(?:(?:\d{5}\)?[\s-]?\d{4,5})|(?:\d{4}\)?[\s-]?(?:\d{5}|\d{3}[\s-]?\d{3}))|(?:\d{3}\)?[\s-]?\d{3}[\s-]?\d{3,4})|(?:\d{2}\)?[\s-]?\d{4}[\s-]?\d{4}))(?:[\s-]?(?:x|ext\.?|\#)\d{3,4})?$/;
  var $nbr_confirm_msg = "Invalid UK phone number\nEnter 'Yes' to accept input and override.";

  $(".crm-form-submit").attr('disabled','disabled');                                               // disable form submit
  CRM.$(document).keyup(function(e) {                                                              // remove user prompt if escaping back to main form
    if (e.which==27) {
      CRM.$("#crm-phone-content-msg").css("display", "none");
    }
  });

  CRM.$(".crm-delete-inline, .crm-hover-button").click(function() {                                // delete button click event handler :
    CRM.$(".crm-form-submit").removeAttr('disabled');                                              //  allow form submission
  });

  $(".crm_phone, .twelve, .crm-form-text, .valid").keypress(function (e){                          // phone number elements keypress event handler :
    var charCode = (e.which) ? e.which : e.keyCode;                                                // allow numeric input only
    if (charCode != 13 && (charCode > 31 && (charCode < 48 || charCode > 57))) {
      return false;
    }
  });

  $(".crm_phone, .twelve, .crm-form-text, .valid").change(function() {                             // phone number elements onchange event handler :
    var $nbr_phone = $(this).val();
    var $nbr_lencheck = true;
    $nbr_phone = $nbr_phone.replace(" ", "");                                                      // remove spaces
    $nbr_phone = $nbr_phone.replace("+", "00");                                                    // convert + to 00
    $nbr_phone = $nbr_phone.replace("0044", "0");                                                  // convert 0044 to 0
    $nbr_phone = $nbr_phone.replace(/\D/g,'');                                                     // remove non-numeric

    if ($nbr_phone.slice(0, 2) != "00") {                                                          // validate number (UK only)

      if ($nbr_phone.slice(0, 2) == "07" && $nbr_phone.length != 11) {$nbr_lencheck = false}       //   for mobile check length 11
      else if ($nbr_phone.length != 10 && $nbr_phone.length != 11) {                               //   for landline check length 10 or 11
        $nbr_lencheck = false;
      }
      $(this).val($nbr_phone);

      if ($(this).val().match($valid_uk_phone_pattern) && $nbr_lencheck) {                         //   if valid number
        $(".crm-form-submit").removeAttr('disabled');                                              //     allow page submit
      }
      else {                                                                                       //   else
        if (['Yes', 'yes'].includes(prompt($nbr_confirm_msg, ''))) {                       //     prompt for override
          $(".crm-form-submit").removeAttr('disabled');                                            //     if yes submit form
        }
        else {                                                                                     //     else throw civicrm error
          CRM.alert('Invalid phone number.<br><br>Please enter a valid phone number <br>and press enter.<br>', 'Input Error');
        }
      }
    }
    else {                                                                                         // else
      $(".crm-form-submit").removeAttr('disabled');                                                //   non-uk number - submit form
    }
  });

});
