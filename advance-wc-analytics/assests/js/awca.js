jQuery('.AWCA-access-revoke').click(function() {
        var data;
        data = {
            action: 'web_awca_revoke_access',
            security: awca_js_object.revoke_access_nonce
        },
        jQuery.post(awca_js_object.ajax_url, data, function() {
            return window.location.reload()
        })
});
jQuery('.AWCA-un-link').click(function() {
        var data;
        data = {
            action: 'web_awca_un_link',
            security: awca_js_object.un_link_nonce
        },
        jQuery.post(awca_js_object.ajax_url, data, function() {
            return window.location.reload()
        })
});
jQuery('.tabs a').click(function() {
        var data;
        var tab_id = jQuery(this).attr("href");
        if( !(tab_id.indexOf('pro') > -1)){
          jQuery(tab_id).html("<div class='progress on'><div class='indeterminate'></div></div>");
          data = {
              action: 'web_awca_tab_update',
              security: awca_js_object.tab_update_nonce,
              tab: tab_id,
          },
          jQuery.post(awca_js_object.ajax_url, data, function(data2,status) {
            if(status == 'success'){ 
              jQuery(tab_id).html( data2);
            }
          })
        }
});
jQuery(document).ready(function(){
    jQuery('.tooltipped').tooltip(); //initiating tooltip
    jQuery('#modal1').modal();  //initiating model for revoke access
    jQuery('#modal2').modal();  //initiating model for revoke access
    jQuery('.tabs').tabs();     //initiating tabs for auto and manual connect
    jQuery('.collapsible').collapsible(); //initiating collapsible divs
    jQuery('.main-content').on('load', '.tooltipped', function() {
      jQuery(this).tooltip();
    });
    jQuery('.main-content').on('click', '.awca-box-title.right i', function() {
      jQuery(this).closest('.awca-box').find('.awca-box-description').first().toggle();
    });
    var view_id = jQuery('#report_view_id').val();
    jQuery('#report_view_id').on('change', function() {
      var reg_view_id = new RegExp(view_id,"g");
      jQuery("#view_reports").html(jQuery("#view_reports").html().replace(reg_view_id,this.value));
      view_id = jQuery('#report_view_id').val();
    });
    M.updateTextFields();
    jQuery('select').formSelect();
    //authentication process strats
    jQuery('.AWCA-authenticate').click(function(o){
            jQuery('.awca-options .progress').show();
            return o.preventDefault(), 500, 300, a = window.open(awca_js_object.auth_url, "AWCA-Authentication", "menubar=0")
    });
    var data
    var tab_id = awca_js_object.current_tab_id;
    jQuery('.tabs').tabs('select',tab_id);
    jQuery('.tabs a.active').trigger('click');
    check_start();
    from_to_dates();
    jQuery('#from').datepicker();
    jQuery( "#to" ).datepicker({
      selectMonths: true, // Creates a dropdown to control month
      closeOnSelect: true,
      closeOnClear: true,
      format: 'yyyy-mm-dd',
      maxDate: new Date(),
       onSelect: function (selected) {
            var dt = new Date(selected);
            dt.setDate(dt.getDate() - 1);
            var elem = document.getElementById('from');
            var instance = M.Datepicker.getInstance(elem);
            instance.options.maxDate = dt
      }
    });
    jQuery('#from').datepicker({
        selectMonths: true, // Creates a dropdown to control month
        closeOnSelect: true,
        closeOnClear: true,
        format: 'yyyy-mm-dd',
        maxDate: new Date(),
         onSelect: function (selected) {
            var dt2 = new Date(selected);
            dt2.setDate(dt2.getDate() + 1);
            var elem2 = document.getElementById('to');
            var instance2 = M.Datepicker.getInstance(elem2);
            instance2.options.minDate = dt2
        }
    });
});
jQuery('#report_frame').on('change', function(){
		from_to_dates();
});
function from_to_dates(){
  var frame_val = jQuery('#report_frame').val();
  if(frame_val == 'Custom Range'){
    jQuery('.from').show();
    jQuery('.to').show();
  }else{
    jQuery('.from').hide();
    jQuery('.to').hide();
  }
}
jQuery(document).on('change', function(){
		check_start();
});
//auth call back function after successful authentication
function auth_callback() {
    window.location.reload();
    return a.close();
}
function awca_stop_video(){
  jQuery('.modal-content iframe').contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
}
//correcting showing authfields
function check_start(){
  if(jQuery('.check_manual').is(':checked')){
    jQuery('.auto-connect').hide();
    jQuery('.manual-connect').show();
  }else{
    jQuery('.auto-connect').show();
    jQuery('.manual-connect').hide();
  }
  if(jQuery('.check_gdpr').is(':checked')){
    jQuery('.awca-gdpr').show();
  }else{
    jQuery('.awca-gdpr').hide();
  }
  var api_need_tracking = jQuery('.tracking-id').val();
  var api_need_property = jQuery('.property-id').val();
  if(api_need_tracking){
    if ((api_need_tracking.indexOf('G') > -1)){
      jQuery('.api-require').show();
    }else{
      jQuery('.api-require').hide();
    }
  }
  if(api_need_property){
    if ((api_need_property.indexOf('G') > -1)){
      jQuery('.api-require').show();
    }else{
      jQuery('.api-require').hide();
    }
  }
}
 /*// 2. This code loads the IFrame Player API code asynchronously.
 var tag = document.createElement('script');

 tag.src = "https://www.youtube.com/iframe_api";
 var firstScriptTag = document.getElementsByTagName('script')[0];
 firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

 // 3. This function creates an <iframe> (and YouTube player)
 //    after the API code downloads.
 var player;
 function onYouTubeIframeAPIReady() {
   player = new YT.Player('player', {
     height: '390',
     width: '640',
     videoId: 'l218_mkOxCc',
     playerVars: {
       'playsinline': 1
     },
     events: {
       'onReady': onPlayerReady,
       'onStateChange': onPlayerStateChange
     }
   });
 }

 // 4. The API will call this function when the video player is ready.
 function onPlayerReady(event) {
   event.target.playVideo();
 }

 // 5. The API calls this function when the player's state changes.
 //    The function indicates that when playing a video (state=1),
 //    the player should play for six seconds and then stop.
 var done = false;
 function onPlayerStateChange(event) {
   if (event.data == YT.PlayerState.PLAYING && !done) {
     setTimeout(stopVideo, 6000);
     done = true;
   }
 }
 function stopVideo() {
   player.stopVideo();
 }
 */