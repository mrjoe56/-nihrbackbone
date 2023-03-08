CRM.$(function ($) {

  CRM.$('form').bind('submit',function(e){e.preventDefault();});                         // prevent scanned input from submitting form

  setTimeout(function() {

    var $elCID=CRM.$('[data-crm-custom="contact_id_history:id_history_entry"]');         // contact ID element
    $elCID.change(function(e) {                                                          // contact ID change event
      var $cID=$(this).val();
      if ($cID.substring(0, 3)==']C1') {                                                 // if ID is a scanned pack ID -
        var $trueID=$cID.substring(3, 11);                                               // then modify input to the true pack ID
        $(this).val($trueID);
      };
    });

    CRM.$('.ui-button').click(function() {                                               // allow form submit on button click
      CRM.$('form').unbind('submit');
      CRM.$('form').submit();
    });

  }, 1000);

});
