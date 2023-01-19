//----- validate phone number in CRM_Contact_Form_Inline_Phone form -------------------------------
CRM.$(function ($) {

  var valid_uk_phone_pattern = /^(?:(?:\(?(?:0(?:0|11)\)?[\s-]?\(?|\+)44\)?[\s-]?(?:\(?0\)?[\s-]?)?)|(?:\(?0))(?:(?:\d{5}\)?[\s-]?\d{4,5})|(?:\d{4}\)?[\s-]?(?:\d{5}|\d{3}[\s-]?\d{3}))|(?:\d{3}\)?[\s-]?\d{3}[\s-]?\d{3,4})|(?:\d{2}\)?[\s-]?\d{4}[\s-]?\d{4}))(?:[\s-]?(?:x|ext\.?|\#)\d{3,4})?$/;

  $(".crm-form-submit").attr('disabled','disabled');

  $(".crm_phone, .twelve, .crm-form-text, .valid").change(function() {
    if ($(this).val().match(valid_uk_phone_pattern)) {
      $(".crm-form-submit").removeAttr('disabled');
    }
    else {
      CRM.alert('Invalid phone number.<br><br>Please enter a valid phone number <br>and press enter.<br>','Input Error');
    }
  });

});
