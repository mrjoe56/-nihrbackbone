
<div id="nbr_hdr">
  <div id="nbr_participant_id" class="nbr_data nbr_ids"></div>
  <div id="nbr_bioresource_id" class="nbr_ids nbr_data"></div>
  <div id="nbr_panel1" class="nbr_data location_data"></div>
  <div id="nbr_panel2" class="nbr_data location_data"></div>
  <div id="nbr_panel3" class="nbr_data location_data"></div>
  <div id="nbr_centre" class="nbr_data location_data"></div>
  <div id="nbr_site" class="nbr_data location_data"></div>
</div>
<div id="nbr_subtype" ></div>

{literal}
  <script type="text/javascript">
    jQuery(document).ready(function($) {
      /*    Display Volunteer IDs under contact name    */
      CRM.$("#crm-main-content-wrapper").prepend(CRM.$('#nbr_hdr'));             // prepend new nbr header to existing civi wrapper
      CRM.$('#nbr_hdr').append(CRM.$(".crm-summary-contactname-block")[0]);      // append existing civi contact name to new nbr header - NOT WORKING ON UBUNTU SO -
      var nbr_data_string = CRM.$("#nbr_data").html();                           // nbr_data - contactID~pid~bid~panel~centre~site
      var nbr_data = nbr_data_string.split("~");                                 // as array

      CRM.$("#nbr_participant_id").html(nbr_data[1]);                            // assign data to display elements
      CRM.$("#nbr_bioresource_id").html(nbr_data[2]);
      CRM.$("#nbr_panel1").html(nbr_data[3]);
      CRM.$("#nbr_panel2").html(nbr_data[4]);
      CRM.$("#nbr_panel3").html(nbr_data[5]);
      switch(nbr_data[6]) {                                                      // set header colour based on status
        case 'volunteer_status_pending':
          $hdr_colour = '#FFD858';                                               // orange
          break;
        case 'volunteer_status_not_recruited':
        case 'volunteer_status_redundant':
        case 'volunteer_status_withdrawn':
          $hdr_colour = '#FA8072';                                               // red
          break;
        case 'volunteer_status_deceased':
          $hdr_colour = 'lightgray';
          break;
        case 'volunteer_status_consent_outdated':                                // blue
          $hdr_colour = '#62cff0';
          break;
        default:
          var $hdr_colour = '#badbae';                                           // green
      }
      CRM.$("#nbr_hdr").css('background-color', $hdr_colour);

      var $subtype = ''                                                          // set contact subtype indicator
      switch(nbr_data[7]) {
        case 'nbr_guardian':
          $subtype = 'G'
          break;
        case 'nihr_researcher':
          $subtype = 'R'
      } 
      CRM.$("#nbr_subtype").html($subtype)

    });
  </script>
{/literal}

{literal}
  <style type="text/css">

    #nbr_subtype {
      position:absolute;
      top:5px;
      right:100px;
      color:darkred;
      font-family: Arial;
      font-size: 60px;
      font-weight: bold;
    }

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
      top:55px;
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
      visibility: hidden;
      top: 75px;
    }
    #nbr_data {
      display:none;
    }
  </style>
{/literal}
