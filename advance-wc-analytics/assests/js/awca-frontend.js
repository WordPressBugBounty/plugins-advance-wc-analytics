jQuery(document).ready(function(){
  setTimeout(function() 
  {
    jQuery("form").each(function(){
      if (jQuery(this).hasClass("wpcf7-form")) {
        var form_id = jQuery(this).find("input[name='_wpcf7']").first().val();
        if(form_id){
          gtag("event", "form_view", {
            "form_id": "ContactForm_"+form_id,
          });
        }
      }else if(jQuery(this).closest(".nf-form-cont").attr("id")){
        var form_id = jQuery(this).closest(".nf-form-cont").attr("id");
        if(form_id){
          form_id = form_id.replace ( /[^\d.]/g, '' );
          gtag("event", "form_view", {
            "form_id": "NinjaForm_"+parseInt(form_id),
          });
        }
      }else if(jQuery(this).hasClass("frm-show-form")){
        var form_id = jQuery(this).find("input[name='form_id']").first().val();
        if(form_id){
          gtag("event", "form_view", {
            "form_id": "FormidableForm_"+form_id,
          });
        }
      }else if(jQuery(this).hasClass("wpforms-form")){
        var form_id = jQuery(this).attr("data-formid");
        if(form_id){
          gtag("event", "form_view", {
            "form_id": "WPForm_"+form_id,
          });
        }
      }
    });}
    ,2000);
    /* jQuery("form").submit(function() {
        gtag("event', 'form_submit', {
            'form_id': jQuery(this).attr('id'),
            'form_name': jQuery(this).attr('name'),
          });
        alert(jQuery(this).attr('id'));
        alert(jQuery(this).attr('name'));
    });*/
    // Gravity form impression
    jQuery(document).on('gform_post_render', function(event, form_id, current_page){
        gtag('event', 'form_view', {
          'form_id': 'GravityForm_'+form_id,
        });
    });
    // Gravity form submission 
    jQuery(document).bind("gform_confirmation_loaded", function(event, form_id) {
      gtag('event', 'form_submit', {
        'form_id': 'GravityForm_'+form_id,
      });
    });
    // Contact Form 7 Submission
    document.addEventListener( 'wpcf7mailsent', function( event ) {
      gtag('event', 'form_submit', {
        'form_id': 'ContactForm_'+event.detail.contactFormId,
      });
    }, false );
    // formidable form
    jQuery(document).on( 'frmFormComplete', function( event, form, response ) {
      var formID = jQuery(form).find('input[name="form_id"]').val();
      gtag('event', 'form_submit', {
        'form_id': "FormidableForm_"+formID,
      });
    });
    //wpforms
    jQuery('form.wpforms-form').on('wpformsAjaxSubmitSuccess', function(event){
      //alert(JSON.stringify(event));
      var formId = jQuery(event.target).attr('data-formid');
      gtag('event', 'form_submit', {
        'form_id': "WPForm_"+formId,
      });
    });
    //ninja forms
    jQuery(document).on( 'nfFormSubmitResponse', function( event, response , id) {
      alert(response.id);
      gtag('event', 'form_submit', {
        'form_id': 'NinjaForm_'+response.id,
      });
    });

});