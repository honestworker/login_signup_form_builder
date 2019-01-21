jQuery(function(){



    var tt;

    function updateimgkeyDown(){
        if(tt){
            clearTimeout(tt);
            tt = setTimeout( updateImgs, 600 );
        }else{
            tt = setTimeout( updateImgs, 600 );
        }
    }

    var dui;
    function updateImgs(){
        dui.trigger('change');
    }


    var dig_prst_name = jQuery(".dig_prst_name");
    var isPresetUpdate = false;
    jQuery(".dig_preset").change(function () {
       if(jQuery(this).is(':checked')) {

           isCustom = false;
           isPresetUpdate = true;
           var id = jQuery(this).attr('id');
           var preset = jQuery("#dig_" + id).val();
           var jsonobj = jQuery.parseJSON(preset);


           var preset_name = jQuery.trim(jQuery(this).closest('label').find('.dig_preset_name').text());

           dig_prst_name.val(preset_name);


           jQuery.each(jsonobj, function (i, va) {
               var c = jQuery("input[name="+i+"]");

               if(c.is(':radio')){
                   jQuery("#"+i+va).prop("checked", true).trigger('change');
               }else {
                   c.val(va).trigger('change');
                   if (c.hasClass("bg_color")) c.iris('color', va);
               }
           });

           setTimeout(function(){
               isPresetUpdate = false;
           },10);

       }
    });


    var isCustom = false;
    var custom_preset_radio = jQuery("#dig_preset_custom");
    var custom_preset = custom_preset_radio.attr('data-lab');
    jQuery(".dig_ad_left_side .customizetab").find('input,textarea,select').on('change',function(){
        if(!isPresetUpdate && !isCustom){
            isCustom = true;
            jQuery('input[name="dig_preset"]').prop('checked', false);
            custom_preset_radio.prop('checked',true);
            custom_preset_radio.trigger('change');
            dig_prst_name.val(custom_preset);

        }
    })

    function isUrlValid(url) {
        return /^(https?|s?ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(url);
    }

    var dig_url_image = jQuery(".dig_url_img");
    dig_url_image.focusin(function(){
        dui = jQuery(this);
    });
    dig_url_image.on("keydown",function(){
        updateimgkeyDown();
    });
    dig_url_image.on("change",function(){
       var d = jQuery(this).parent().find("div");
       var v = jQuery(this).val();
        if(isUrlValid(v)) {
            d.find("img").attr("src", v);
            d.parent().find("button").text(dig.removeimage);
        }else{
            d.find("img").removeAttr("src");
            d.parent().find("button").text(dig.selectimage);
        }
    });
    var file_frame;
    var wp_media_post_id = wp.media.model.settings.post.id;
    var set_to_post_id = dig.logo;
    var uploadimagebutton = jQuery('#upload_image_button');
    uploadimagebutton.click(function() {

        var v = jQuery(this).parent().find(".dig_url_img").val();
        if(v.length>0){
            if(isUrlValid(v)) {
                jQuery('#image-preview').attr('src', "").trigger('change');
                jQuery('#image_attachment_id').val("").trigger('change');
                uploadimagebutton.text(dig.selectimage).trigger('change');

                return;
            }
        }
        if ( file_frame ) {
            file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
            file_frame.open();
            return;
        } else {
            wp.media.model.settings.post.id = set_to_post_id;
        }
        file_frame = wp.media.frames.file_frame = wp.media({
            title: dig.selectalogo,
            button: {text: dig.usethislogo},
            multiple: false
        });
        file_frame.on( 'select', function() {
            attachment = file_frame.state().get('selection').first().toJSON();

            wp.media.model.settings.post.id = wp_media_post_id;
            jQuery( '#image-preview' ).attr( 'src', attachment.url );
            jQuery( '#image_attachment_id' ).val( attachment.url ).trigger('change');
            uploadimagebutton.text(dig.removeimage).trigger('change');

        });
        file_frame.open();
    });



    var bg_file_frame;
    var bg_wp_media_post_id = wp.media.model.settings.post.id;
    var set_to_post_id = dig.logo;
    var bg_uploadimagebutton = jQuery('#bg_upload_image_button');
    bg_uploadimagebutton.click(function() {

        var v = jQuery(this).parent().find(".dig_url_img").val();

        if(v.length>0){
            if(isUrlValid(v)) {
                jQuery('#bg_image-preview').attr('src', "").trigger('change');
                jQuery('#bg_image_attachment_id').val("").trigger('change');
                bg_uploadimagebutton.text(dig.selectimage).trigger('change');

                return;
            }
        }
        if ( bg_file_frame ) {
            bg_file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
            bg_file_frame.open();
            return;
        } else {
            wp.media.model.settings.post.id = set_to_post_id;
        }
        bg_file_frame = wp.media.frames.bg_file_frame = wp.media({
            title: dig.selectalogo,
            button: {text: dig.usethislogo},
            multiple: false
        });
        bg_file_frame.on( 'select', function() {
            attachment = bg_file_frame.state().get('selection').first().toJSON();
            wp.media.model.settings.post.id = bg_wp_media_post_id;
            jQuery( '#bg_image-preview' ).attr( 'src', attachment.url ).trigger('change');
            jQuery( '#bg_image_attachment_id' ).val( attachment.url ).trigger('change');
            bg_uploadimagebutton.text(dig.removeimage).trigger('change');

        });
        bg_file_frame.open();
    });





    var bg_file_frame_modal;
    var bg_wp_media_post_id_modal = wp.media.model.settings.post.id;
    var set_to_post_id = dig.logo;
    var bg_uploadimagebutton_modal = jQuery('#bg_upload_image_button_modal');
    bg_uploadimagebutton_modal.click(function() {
        var v = jQuery(this).parent().find(".dig_url_img").val();
        if(v.length>0){
            if(isUrlValid(v)) {
                jQuery('#bg_image-preview_modal').attr('src', "").trigger('change');
                jQuery('#bg_image_attachment_id_modal').val("").trigger('change');
                bg_uploadimagebutton_modal.text(dig.selectimage).trigger('change');

                return;
            }
        }
        if ( bg_file_frame_modal ) {
            bg_file_frame_modal.uploader.uploader.param( 'post_id', set_to_post_id );
            bg_file_frame_modal.open();
            return;
        } else {
            wp.media.model.settings.post.id = set_to_post_id;
        }
        bg_file_frame_modal = wp.media.frames.bg_file_frame_modal = wp.media({
            title: dig.selectalogo,
            button: {text: dig.usethislogo},
            multiple: false
        });
        bg_file_frame_modal.on( 'select', function() {
            attachment = bg_file_frame_modal.state().get('selection').first().toJSON();
            wp.media.model.settings.post.id = bg_wp_media_post_id_modal;
            jQuery( '#bg_image-preview_modal' ).attr( 'src', attachment.url );
            jQuery( '#bg_image_attachment_id_modal' ).val( attachment.url ).trigger('change');
            bg_uploadimagebutton_modal.text(dig.removeimage).trigger('change');

        });
        bg_file_frame_modal.open();
    });



    var bg_file_frame_left;
    var bg_wp_media_post_id = wp.media.model.settings.post.id;
    var set_to_post_id = dig.logo;
    var bg_uploadimagebutton_left = jQuery('#bg_upload_image_button_left');
    bg_uploadimagebutton_left.click(function() {

        var v = jQuery(this).parent().find(".dig_url_img").val();

        if(v.length>0){
            if(isUrlValid(v)) {
                jQuery('#bg_image-preview_left').attr('src', "").trigger('change');
                jQuery('#bg_image_attachment_id_left').val("").trigger('change');
                bg_uploadimagebutton_left.text(dig.selectimage).trigger('change');

                return;
            }
        }
        if ( bg_file_frame_left ) {
            bg_file_frame_left.uploader.uploader.param( 'post_id', set_to_post_id );
            bg_file_frame_left.open();
            return;
        } else {
            wp.media.model.settings.post.id = set_to_post_id;
        }
        bg_file_frame_left = wp.media.frames.bg_file_frame_left = wp.media({
            title: dig.selectalogo,
            button: {text: dig.usethislogo},
            multiple: false
        });
        bg_file_frame_left.on( 'select', function() {
            attachment = bg_file_frame_left.state().get('selection').first().toJSON();
            wp.media.model.settings.post.id = bg_wp_media_post_id;
            jQuery( '#bg_image-preview_left' ).attr( 'src', attachment.url ).trigger('change');
            jQuery( '#bg_image_attachment_id_left' ).val( attachment.url ).trigger('change');
            bg_uploadimagebutton_left.text(dig.removeimage).trigger('change');

        });
        bg_file_frame_left.open();
    });









    var bg_file_frame_left_modal;
    var bg_wp_media_post_id = wp.media.model.settings.post.id;
    var set_to_post_id = dig.logo;
    var bg_uploadimagebutton_left_modal = jQuery('#bg_upload_image_button_left_modal');
    bg_uploadimagebutton_left_modal.click(function() {

        var v = jQuery(this).parent().find(".dig_url_img").val();

        if(v.length>0){
            if(isUrlValid(v)) {
                jQuery('#bg_image-preview_left_modal').attr('src', "").trigger('change');
                jQuery('#bg_image_attachment_id_left_modal').val("").trigger('change');
                bg_uploadimagebutton_left_modal.text(dig.selectimage).trigger('change');

                return;
            }
        }
        if ( bg_file_frame_left_modal ) {
            bg_file_frame_left_modal.uploader.uploader.param( 'post_id', set_to_post_id );
            bg_file_frame_left_modal.open();
            return;
        } else {
            wp.media.model.settings.post.id = set_to_post_id;
        }
        bg_file_frame_left_modal = wp.media.frames.bg_file_frame_left_modal = wp.media({
            title: dig.selectalogo,
            button: {text: dig.usethislogo},
            multiple: false
        });
        bg_file_frame_left_modal.on( 'select', function() {
            attachment = bg_file_frame_left_modal.state().get('selection').first().toJSON();
            wp.media.model.settings.post.id = bg_wp_media_post_id;
            jQuery( '#bg_image-preview_left_modal' ).attr( 'src', attachment.url ).trigger('change');
            jQuery( '#bg_image_attachment_id_left_modal' ).val( attachment.url ).trigger('change');
            bg_uploadimagebutton_left_modal.text(dig.removeimage).trigger('change');

        });
        bg_file_frame_left_modal.open();
    });
    
    


});