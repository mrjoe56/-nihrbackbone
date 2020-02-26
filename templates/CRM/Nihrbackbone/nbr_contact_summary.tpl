
<div id="nbr_hdr">
  <div id="nbr_participant_id" class="nbr_data nbr_ids"></div>
  <div id="nbr_bioresource_id" class="nbr_ids nbr_data"></div>
  <div id="nbr_panel1" class="nbr_data location_data"></div>
  <div id="nbr_panel2" class="nbr_data location_data"></div>
  <div id="nbr_panel3" class="nbr_data location_data"></div>
  <div id="nbr_centre" class="nbr_data location_data"></div>
  <div id="nbr_site" class="nbr_data location_data"></div>
</div>

{literal}
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      /*    Display Volunteer IDs under contact name    */
      CRM.$("#crm-main-content-wrapper").prepend(CRM.$('#nbr_hdr'));             // prepend new nbr header to existing civi wrapper
      CRM.$('#nbr_hdr').append(CRM.$(".crm-summary-contactname-block")[0]);      // append existing civi contact name to new nbr header - NOT WORKING ON UBUNTU SO -
      //CRM.$('#nbr_hdr').append(CRM.$(".crm-summary-display_name")[0]);           // SO USE THIS?
      var nbr_data_string = CRM.$("#nbr_data").html();                           // nbr_data - contactID~pid~bid~panel~centre~site
      var nbr_data = nbr_data_string.split("~");                                 // as array
<<<<<<< HEAD
      //console.log('status = ' + nbr_data[6]);
=======
      //console.log('nbr_data = ' + nbr_data);
>>>>>>> 853f6075c68106e856ab2ae4a9151ccb3b226ade
      CRM.$("#nbr_participant_id").html(nbr_data[1]);                            // assign data to display elements
      CRM.$("#nbr_bioresource_id").html(nbr_data[2]);
      CRM.$("#nbr_panel1").html(nbr_data[3]);
      CRM.$("#nbr_panel2").html(nbr_data[4]);
      CRM.$("#nbr_panel3").html(nbr_data[5]);
      switch(nbr_data[6]) {                                                      // set header colour based on status
<<<<<<< HEAD
        case 'volunteer_status_active':
          var $hdr_colour = '#badbae';                                           //  active - light green
          break;
        case 'volunteer_status_withdrawn':
        case 'volunteer_status_not_recruited':
        case 'volunteer_status_redundant':
          var $hdr_colour = '#FA8072';                                           //  redundant/withdrawn/not recruited
          break;                                                                 //  salmon pink
        case 'volunteer_status_deceased':
          var $hdr_colour = 'lightgray';
          break;
        default:
          var $hdr_colour = '#FFD858';                                           // pending - orange
=======
        case 'volunteer_status_pending':
          $hdr_colour = '#FFD858';  //
          break;
        case 'volunteer_status_withdrawn':
          $hdr_colour = '#FA8072'; //salmon
          break;
        case 'volunteer_status_deceased':
          $hdr_colour = 'lightgray';
          break;
        default:
          var $hdr_colour = '#badbae'; //
>>>>>>> 853f6075c68106e856ab2ae4a9151ccb3b226ade
      }
      CRM.$("#nbr_hdr").css('background-color', $hdr_colour);
    });
  </script>
{/literal}

{literal}
  <style type="text/css">
    #nbr_hdr {
      display:block;
      width:100%;
      height:70px;
      color:#28291d;
      margin-bottom: 20px;
    }
    .nbr_data {
      left:40%;
      position:absolute;
      display: block;
      height:auto;
      font:normal 81.3%/1.538em "Lucida Grande", "Lucida Sans Unicode", sans-serif;
      font-size: 15px;
    }
    .crm-summary-contactname-block {
      width:70%;
      white-space: nowrap;
      overflow: hidden;
    }
    .nbr_ids {
      position:absolute;
<<<<<<< HEAD
      top:55px;
=======
      top:70px;
>>>>>>> 853f6075c68106e856ab2ae4a9151ccb3b226ade
      width:9%;
    }
    #nbr_bioresource_id {
      left:3%;
    }
    #nbr_participant_id {
      left:15%;
    }
    .location_data {
      width:60%;
      font:normal 81.3%/1.538em "Lucida Grande", "Lucida Sans Unicode", sans-serif;
      font-size: 15px;
    }
    #nbr_centre {
      left:60%;
    }
    #nbr_site {
      left:80%;
    }
    #nbr_panel1 {
      top: 35px;
    }
    #nbr_panel2 {
      top: 55px;
    }
    #nbr_panel3 {
<<<<<<< HEAD
      visibility: hidden;
=======
>>>>>>> 853f6075c68106e856ab2ae4a9151ccb3b226ade
      top: 75px;
    }
    #nbr_data {
      display:none;
    }
  </style>
{/literal}
