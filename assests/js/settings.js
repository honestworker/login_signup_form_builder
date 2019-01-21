jQuery(function() {

    jQuery("select").niceSelect();


    var dig_sort_fields = jQuery(".dig-reg-fields").find('tbody');

    if(dig_sort_fields.length) {
        var dig_sortorder = jQuery("#dig_sortorder");


        var sortorder = dig_sortorder.val().split(',');

        dig_sort_fields.find('tr').sort(function (a, b) {
            var ap = jQuery.inArray(a.id, sortorder);
            var bp = jQuery.inArray(b.id, sortorder);
            return (ap < bp) ? -1 : (ap > bp) ? 1 : 0;


        }).appendTo(dig_sort_fields);


        dig_sort_fields.sortable({
            update: function (event, ui) {
                var sortOrder = jQuery(this).sortable('toArray').toString();
                dig_sortorder.val(sortOrder);
                enableSave();
            }
        });
    }

    var offs = -1;


    var isBackEnabled = 0;
    var sb_back = jQuery(".dig_sb_back");
    var sb_head = jQuery(".dig_sb_head");
    var das = jQuery(".dig_ad_side");
    var btn = jQuery(".dig_op_wdz_btn");

    var dig_fields_options_main = jQuery(".dig_fields_options_main");

    var dpc = jQuery('#dig_purchasecode');

    function showDigMessage(message){

        if(jQuery(".dig_popmessage").length){
            jQuery(".dig_popmessage").find(".dig_lase_message").text(message);
        }else {
            jQuery("body").append("<div class='dig_popmessage'><div class='dig_firele'><img src='"+ digsetobj.face + "'></div><div class='dig_lasele'><div class='dig_lase_snap'>"+digsetobj.ohsnap+"</div><div class='dig_lase_message'>" + message + "</div></div><img class='dig_popdismiss' src='"+ digsetobj.cross + "'></div>");
            jQuery(".dig_popmessage").slideDown('fast');
        }

    }
    function hideDigMessage(){
        jQuery(".dig_popmessage").remove();
    }


    jQuery('.bg_color').wpColorPicker();


    jQuery("input[name='dig_page_type']").on('change',function(){
        var name = jQuery(this).attr('name');
        var v = jQuery("input[name='dig_page_type']:checked").val();

        jQuery(".dig_page_active").hide().removeClass("dig_page_active");
        jQuery("."+name+"_"+v).show().addClass("dig_page_active");

        var label = jQuery(".dig_page_type_1_2").find("th").find("label");
        var label_text = label.attr('data-type'+v);
        label.text(label_text);


    });



    jQuery("input[name='dig_modal_type']").on('change',function(){
        var name = jQuery(this).attr('name');
        var v = jQuery("input[name='dig_modal_type']:checked").val();

        jQuery(".dig_modal_active").hide().removeClass("dig_modal_active");
        jQuery("."+name+"_"+v).show().addClass("dig_modal_active");

        var label = jQuery(".dig_modal_type_1_2").find("th").find("label");
        var label_text = label.attr('data-type'+v);
        label.text(label_text);


    });

    jQuery(".dig_page_type_1").hide();
    jQuery(".dig_page_type_2").hide();
    jQuery("input[name='dig_page_type']").trigger('change');

    jQuery(".dig_modal_type_1").hide();
    jQuery(".dig_modal_type_2").hide();
    jQuery("input[name='dig_modal_type']").trigger('change');



    jQuery(".dig_presets_modal").prependTo('.dig_ad_left_side');
    jQuery("#dig_open_preset_box").on('click',function(){
        jQuery(".dig_ad_left_side_content").addClass('dig_blur_bg');
        jQuery(".dig_presets_modal").fadeIn('fast');

        jQuery("#dig_presets_list").slick({
            dots: false,
            infinite: true,
            speed: 300,
            slidesToShow: 3,
            centerMode: false,
            variableWidth: false,
            slidesToScroll: 1
        });

    })
    jQuery("#dig_presets_modal_head_close").on('click',function(){
        jQuery(".dig_ad_left_side_content").removeClass('dig_blur_bg');
        jQuery(".dig_presets_modal").fadeOut('fast');
    })




    var dig_tab_wrapper = jQuery(".dig-tab-wrapper");
    if(dig_tab_wrapper.length) {
        var dig_ad_submit = jQuery(".dig_ad_submit");
        var width_dig_ad_submit = dig_ad_submit.outerWidth(true) + 24;
        var dig_left_side = jQuery(".dig_ad_left_side");
        jQuery(window).load(function () {
            update_tab_width();
        });
        jQuery(window).resize(function () {
            update_tab_width();
            update_tab_sticky();
            update_tb_line();

        });

        var respon_win = 822;
        var tb_top = dig_tab_wrapper.offset().top;
        var ad_bar_height = jQuery("#wpadminbar").outerHeight(true);
        jQuery(window).scroll(function () {
            update_tab_sticky();
        });

        function update_tab_sticky(){
            var w_top = jQuery(window).scrollTop();
            var sb = tb_top-w_top;
            if(sb<=ad_bar_height && jQuery(window).width()>=respon_win){
                dig_tab_wrapper.addClass("dig-tab-wrapper-fixed").css({'top':ad_bar_height});
            }else {
                dig_tab_wrapper.removeClass("dig-tab-wrapper-fixed");
            }
        }
        function update_tab_width(){
            var w = dig_left_side.width();
            dig_tab_wrapper.outerWidth(w);
            dig_ad_submit.css({'left': dig_left_side.offset().left + w - 168 });

        }
        jQuery(window).trigger('scroll');

    }

    $mainNav = jQuery(".dig-tab-ul");

    jQuery(document).on("click", ".dig_popmessage", function() {

        jQuery(this).closest('.dig_popmessage').slideUp('fast', function() { jQuery(this).remove(); } );
    })

    var $el, leftPos, newWidth;

    $mainNav.append("<li id='dig-tab-magic-line'></li>");
    var $magicLine = jQuery("#dig-tab-magic-line");


    jQuery(".dig_big_preset_show").on('click',function(){
        jQuery(this).fadeOut('fast');
    })
    jQuery('.dig_preset_big_img').on('click',function(){

        var src = jQuery(this).parent().find('img').attr('src');

        var p = jQuery(".dig_big_preset_show");
        p.find('img').attr('src',src);
        p.fadeIn('fast');
        return false;
    });
    update_tb_line();
    function update_tb_line() {
        var dig_active_tab = jQuery(".dig-nav-tab-active");
        if(!dig_active_tab.length)return;
        var dig_active_tab_par_pos = dig_active_tab.parent().position();
        $magicLine
            .width(dig_active_tab.parent().width())
            .css({
                "left": dig_active_tab_par_pos.left,
                "top": dig_active_tab_par_pos.top + 21
            })
            .data("origLeft", $magicLine.position().left)
            .data("origWidth", $magicLine.width());
        if (dig_active_tab.hasClass("dig_ngmc")) {
            $magicLine.hide().css({'top': 45});
        }
    }
    jQuery(".updatetabview").click(function(){



        var c = jQuery(this).attr('tab');

        var acr = jQuery(this).attr('acr');

        if (typeof acr !== typeof undefined && acr !== false) {
            var inv = dpc.attr('invalid');
            if (dpc.val().length!=36 || inv==1) {

                showDigMessage(digsetobj.plsActMessage);
                if(jQuery("#dig_activatetab").length){
                    jQuery("#dig_activatetab").click();
                    dpc.focus();
                }
                return false;
            }
        }

        if(!jQuery(this).hasClass("dig_ngmc")) {
            $magicLine.show();
            $el = jQuery(this).parent();
            leftPos = $el.position().left;
            newWidth = $el.width();
            $magicLine.stop().animate({
                left: leftPos,
                width: newWidth,
                top: $el.position().top + 21
            },'fast');
        }else{
            $magicLine.hide();
        }


        jQuery(".digcurrentactive").removeClass("digcurrentactive").hide();

        var tab = jQuery("."+c);
        tab.fadeIn(150).addClass("digcurrentactive");


        if(jQuery(".dig-tab-wrapper-fixed").length)
            jQuery('html, body').animate({scrollTop: tab.offset().top - 90}, 220);


        jQuery(".dig-nav-tab-active").removeClass("dig-nav-tab-active");
        jQuery(this).addClass("dig-nav-tab-active");



        updateURL("tab",c.slice(0,-3));

        jQuery("#dig_call_test_api").find("#username").trigger('keyup');
        return false;
    });

    function updateURL(key,val){
        var url = window.location.href;
        var reExp = new RegExp("[\?|\&]"+key + "=[0-9a-zA-Z\_\+\-\|\.\,\;]*");

        if(reExp.test(url)) {
            // update
            var reExp = new RegExp("[\?&]" + key + "=([^&#]*)");
            var delimiter = reExp.exec(url)[0].charAt(0);
            url = url.replace(reExp, delimiter + key + "=" + val);
        } else {
            // add
            var newParam = key + "=" + val;
            if(!url.indexOf('?')){url += '?';}

            if(url.indexOf('#') > -1){
                var urlparts = url.split('#');
                url = urlparts[0] +  "&" + newParam +  (urlparts[1] ?  "#" +urlparts[1] : '');
            } else {
                url += "&" + newParam;
            }
        }
        window.history.pushState(null, document.title, url);
    }







    var dig_gs_nmb_ovr_spn = jQuery(".dig_gs_nmb_ovr_spn");
    dig_gs_nmb_ovr_spn.find("span").on('click',function(){
        jQuery(this).parent().find("input").focus();
    })

    dig_gs_nmb_ovr_spn.find("input").on('keyup change',function(){
        var inp = jQuery(this).val();
        var size  = inp.length;
        var spn_lbl = jQuery(this).parent().find("span");

        spn_lbl.stop().animate({'left' : Math.max(size*9 + 33,jQuery(this).attr('dig-min'))},'fast');
    });

    dig_gs_nmb_ovr_spn.find("input").trigger('keyup');

    var chn = false;
    jQuery(".digits_admim_conf textarea,.digits_admim_conf input").on('keyup',function(){
        if(!jQuery(this).attr("readonly") && !jQuery(this).attr('dig-save')) {
            var pcheck = jQuery(this).closest('.digcon');
            if(!pcheck.length)enableSave();
        }

    });
    jQuery(".digits_admim_conf input,.digits_admim_conf select,.dig_activation_form input").on('change',function(){
        if(!jQuery(this).attr("readonly") && !jQuery(this).attr('dig-save')) enableSave();
    });

    jQuery("#dig_purchasecode").on('keyup',function(){

        if(jQuery(this).attr('readonly'))return;

       if(jQuery(this).val().length==36 || jQuery(this).val().length==0){
           jQuery(".dig_prc_ver").hide();
           jQuery(".dig_prc_nover").hide();
       }else{
           invPC(-1);
       }
    });
    jQuery(".wp-color-picker").wpColorPicker(
        'option',
        'change',
        function(event, ui) {
            enableSave();
        }
    );

    function enableSave(){
        if(!chn){
            chn = true;
            jQuery(".dig_activation_form").find("button[type='submit']").removeAttr("disabled");
        }
    }


    jQuery(".digits_shortcode_tbs").find("input").click(function(){

        copyShortcode(jQuery(this));
    });

    jQuery(".dig_copy_shortcode").click(function(){
       var a = jQuery(this).parent();
        var i = a.find("input");
        copyShortcode(i);
    });

    function copyShortcode(i){
        if(i.attr("nocop")) return;
        i.select();
        document.execCommand("copy");
        var v = i.val();
        i.val(digsetobj.Copiedtoclipboard);
        setTimeout(
            function() {
                i.val(v);
            }, 800);
    }

    jQuery('.dig_drop_doc_check').each(function( index ) {
        jQuery(this).click(function(){
            var a = jQuery(this).closest('li');
            a.find('.dig_conf_doc').toggle();
            var b = a.find('h2').find('.dig_tgb');
            b.text(b.text() == '+' ? '-' : '+');
        });

    });

    dpc.mask('AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA');

    var digit_tapp = jQuery("#digit_tapp");

    var sgs = jQuery(".dig_load_overlay_gs");

    var se = sgs.length;


    var dig_test_api_status = 0;


    jQuery(".dig_request_server_addition").on('click',function(){
        var hr = jQuery(this).attr('href');
        window.open(hr,'_target');
    })

    var refreshCode = 0;
    jQuery(".dig_domain_type").find('button').on('click',function(){
        var value = jQuery(this).attr('val');
        jQuery("input[name='dig_license_type']").val(value);
        if(refreshCode!=1) {
            refreshCode = 0;
            jQuery("#dig_purchasecode").val('').removeAttr('readonly');
        }
        jQuery(".dig_prchcde").fadeIn('fast');
        jQuery(".dig_domain_type").hide();
        jQuery(".dig_btn_unregister").hide();

        if(value!=1){
            jQuery(".request_live_server_addition").show();
            jQuery(".request_testing_server_addition").hide();
        }else{
            jQuery(".request_live_server_addition").hide();
            jQuery(".request_testing_server_addition").show();
        }
    })
    jQuery(".dig_btn_unregister").on('click',function(){
        if(dig_test_api_status!=1) {
            sgs.find('.circle-loader').removeClass('load-complete');
            sgs.find('.checkmark').hide();
            sgs.fadeIn();
        }

        var code = dpc.val();
        jQuery.post('https://digits.unitedover.com/updates/verify.php',
            {
                code: code,
                request_site: encodeURIComponent(jQuery("input[name='dig_domain']").val()),
                license_type: jQuery("input[name='dig_license_type']").val(),
                unregister: 1,
                version: jQuery("input[name='dig_version']").val(),
                settings: 1,
            },function(data, status) {
            if(data==1) {
                jQuery(".dig_domain_type").fadeIn('fast');
                jQuery(".dig_prchcde").fadeOut();
                jQuery(".dig_prc_ver").fadeOut();
                jQuery(".dig_prc_nover").hide();
                jQuery("#dig_purchasecode").val('').removeAttr('readonly');
            }else{
                showDigMessage(data);
            }
                jQuery(".dig_activation_form").submit();

                return false;
            }
        );

    })


    var dac;
    jQuery(".dig_activation_form").on("submit",function(){
        dac = jQuery(this);


        hideDigMessage();

        var isOpt = false;
        var isPassdisEmailEnab = false;
        var dig_custom_field_login_j = jQuery(".dig_custom_field_login_j");
        if(dig_custom_field_login_j.length) {
            jQuery(".dig_custom_field_login_j").each(function (a, b) {
                var o = jQuery(this).attr('data-opt');
                var v = jQuery(this).val();
                if (o) {

                    if (v == 1) {
                        isOpt = true;
                        return true;
                    }
                }
                if (v == 0) {
                    var c = jQuery(this).attr('data-disable');

                    if (c) {

                        var ch = jQuery("select[name=" + c + "]").val();

                        if (ch == 1) {
                            isPassdisEmailEnab = true;
                        }
                    }
                }
            });

            if (!isOpt || isPassdisEmailEnab) {
                invPC();
                if (isPassdisEmailEnab) showDigMessage((digsetobj.cannotUseEmailWithoutPass));
                else if (!isOpt) showDigMessage(digsetobj.bothPassAndOTPCannotBeDisabled);
                return false;
            }
        }
        var fd = dac.serialize();


        if(dig_test_api_status!=1) {
            sgs.find('.circle-loader').removeClass('load-complete');
            sgs.find('.checkmark').hide();
            sgs.fadeIn();
        }

        var code = dpc.val();
        if(code.length==0){

            jQuery(".dig_prc_ver").hide();
            jQuery(".dig_prc_nover").hide();

            updateSettings(fd,-1);
            return false;
        }else if(code.length!=36){
            showDigMessage(digsetobj.invalidpurchasecode);

            jQuery(".dig_prc_ver").hide();
            jQuery(".dig_prc_nover").show();
            updateSettings(fd,-1);
            return false;
        }


        jQuery.post('https://digits.unitedover.com/updates/verify.php',
            {
                json: 1,
                code: code,
                request_site: encodeURIComponent(jQuery("input[name='dig_domain']").val()),
                license_type: jQuery("input[name='dig_license_type']").val(),
                version: jQuery("input[name='dig_version']").val(),
            },function(response, status) {


            var data = response.code;

            var type = response.type;
            refreshCode = 1;
            jQuery(".dig_domain_type").find('button[val='+type+']').trigger('click');
            fd = dac.serialize();

                if (data != 1) {
                    invPC(se);
                    dpc.attr('invalid', 1);


                } else {
                    jQuery(".dig_prc_ver").show();
                    jQuery(".dig_prc_nover").hide();
                    dpc.attr('invalid', 0);

                }

                if (data == 0) {
                    showDigMessage(digsetobj.invalidpurchasecode);
                    if (!sgs.attr("ajxsu")) {
                        updateSettings(fd, -1);
                    }

                } else if (data == 1) {

                    jQuery(".dig_btn_unregister").show();
                    jQuery("#dig_purchasecode").attr('readonly',true);

                    if (sgs.attr("ajxsu")) {
                        jQuery(".dig_activation_form").unbind("submit").submit();
                    } else {
                        updateSettings(fd, 1);
                    }
                }
                else {
                    if(data==-1){
                        showDigMessage("This purchase code is already being used on another site.");
                    }else showDigMessage(response.msg);


                    if (!sgs.attr("ajxsu")) {
                        updateSettings(fd, -1);
                    }
                }


            }
        );



       return false;
    });


    function invPC(se){
        jQuery("#dig_purchasecode").removeAttr('readonly');
        jQuery(".dig_prc_ver").hide();
        jQuery(".dig_prc_nover").show();
        if(se>0) sgs.hide();
    }
    function updateSettings(fd,activate){




        jQuery.ajax({
            type:    "POST",
            url:     digsetobj.ajax_url,
            data:    fd + '&action=digits_save_settings&pca='+ activate,
            success: function(data) {

                sgs.find('.circle-loader').addClass('load-complete');
                sgs.find('.checkmark').show();
                setTimeout(
                    function() {
                        sgs.fadeOut();
                        chn = false;
                        jQuery(".dig_activation_form").find("button[type='submit']").attr("disabled","disabled");
                        if(dig_test_api_status==1){
                            digCallTestApi();
                        }
                    }, 1500);


            },
            error: function() {
                invPC();
                showDigMessage(digsetobj.Error);
            }
        });

    }
    jQuery("#digits_setting_update button[type='submit']").click(function(e){
        var val = digit_tapp.value;
        var te = digit_tapp.find("option:selected").attr('han');

        if(te=="msg91"){
            if(jQuery("#msg91senderid").val().length<6){
                showDigMessage(digsetobj.Invalidmsg91senderid);
                return false;
            }
        }


        jQuery("."+te+"cred").find("input").each(function(){
            var input = jQuery(this);
            if(input.val().length==0){
                var optional = input.attr('dig-optional');
                if(optional && optional==1) return;

                showDigMessage(digsetobj.PleasecompleteyourAPISettings);
                e.preventDefault();
                return false;

            }
        });


        jQuery("#digits_setting_update").find("input").each(function(){
            var input = jQuery(this);
            if(input.val().length==0){
                var required = input.attr('required');
                if(!required) return;

                var tb = input.closest('.digtabview').attr('data-tab');
                jQuery("[tab='"+tb+"']").trigger('click');
                input.focus();
                showDigMessage(digsetobj.PleasecompleteyourSettings);
                e.preventDefault();
                return false;

            }
        });
        return true;
    });



    var rtl = jQuery("#is_rtl");


    var select_field_type = jQuery(".dig_sb_select_field");

    var field_options = jQuery(".dig_fields_options");
    jQuery(document).on("click", ".dig_sb_field_types", function() {
        show_field_options(jQuery(this).attr('data-val'),jQuery(this).attr('data-configure_fields'),null);
    });


    jQuery(document).on("click", ".dig_sb_field_wp_wc_types", function() {

        var data_val = jQuery(this).attr('data-val');
        var cff = jQuery(this).attr('data-configure_fields');
        var values = jQuery(this).attr('data-values');
        values = jQuery.parseJSON(values);

        show_field_options(data_val,cff,values);
        isUpdate = false;
    });







    var data_type = jQuery("#dig_custom_field_data_type");

    var dig_field_val_list = jQuery("#dig_field_val_list");

    var required_field_box = jQuery("#dig_field_required");
    var meta_key_box = jQuery("#dig_field_meta_key");
    var field_values = jQuery("#dig_field_options");
    var custom_class_box = jQuery("#dig_field_custom_class");


    var dig_field_label = jQuery("#dig_field_label");


    var isUpdate = false;
    var prevLabel;
    function show_field_options(type,options,values){
        isUpdate = false;
        show_create_new_field_panel();
        options = jQuery.parseJSON(options);


        sb_head.text(options.name);

        if(options.meta_key==1){
            meta_key_box.show();
        }else {
            meta_key_box.hide();
        }

        if(options.force_required==0){
            required_field_box.show();
        }else{
            required_field_box.hide();
        }

        if(options.options==1){
            field_values.show();
        }else{
            field_values.hide();
        }

        jQuery(".dig_sb_extr_fields").hide();
        if(options.slug!=null) jQuery(".dig_sb_field_"+options.slug).show();


        if(values!=null){
            isUpdate = true;
            prevLabel = values['label'];

            dig_field_label.find('input').val(values['label']);
            required_field_box.find('select').val(values['required']).niceSelect('update');
            meta_key_box.find('input').val(values['meta_key']);
            custom_class_box.find('input').val(values['custom_class']);
            if(values['options']!=null) {
                var dropValues = values['options'].toString();

                dropValues = dropValues.split(',');
                dig_field_val_list.empty();
            }

            if(options.slug!=null){
                jQuery(".dig_sb_field_"+options.slug).find('input').each(function(){
                    if(!jQuery(this).is(':checkbox')) {

                        var name = jQuery(this).attr('name');
                        jQuery(this).val(values[name]);
                    }else{

                        if(jQuery.inArray( jQuery(this).val(), values['options'] )!=-1){
                            jQuery(this).prop('checked',true);
                        }else{
                            jQuery(this).prop('checked',false);
                        }
                    }
                })
            }
            dig_cus_field_done.text('Done');

            isBackEnabled = 0;


            if(values['options']!=null) {
                for (var i = 0; i < dropValues.length; i++) {
                    addValueToValList(dropValues[i]);
                }
            }
        }else{
            var m = options.name+'_'+ jQuery.now();
            dig_field_label.find('input').val('');
            required_field_box.find('select').val(1).change();
            meta_key_box.find('input').val(m.toLowerCase());
            custom_class_box.find('input').val('');
            dig_field_val_list.empty();
            dig_cus_field_done.text('Add');
            isBackEnabled = 1;
            jQuery(".dig_chckbx_usrle").prop('checked',false);
        }

        data_type.val(type);
        dig_fields_options_main.show();
        dig_cus_field_done.show();
        select_field_type.slideUp('fast');
        field_options.fadeIn('fast');
    }



    function addValueToValList(value){
        dig_field_val_list.append('<li></li>').find("li:last-child").text(value).append('<div class="dig_delete_opt_custf"></div>').show();
    }

    var dig_field_sidebar = jQuery(".dig_side_bar");
    var dig_custom_foot = jQuery("#dig_cus_field_footer");
    var dig_ad_cancel = jQuery(".dig_ad_cancel");
    dig_ad_cancel.on('click',function(){


        if(isBackEnabled==1){
            isUpdate = false;
            show_create_new_field_panel();
        }else{
            hide_custom_panel();
        }

    });

    jQuery(".dig_sb_field_add_opt").on('click',function(){
        jQuery(".dig_sb_field_list_input").trigger('focusout');
    })
    jQuery(".dig_sb_field_list_input").keypress(function(event) {
        if (event.keyCode == 13 ) {
            event.preventDefault();
            jQuery(this).trigger('focusout');
        }
    });


    jQuery(document).keyup(function(e) {
        hideDigMessage();
        if (e.keyCode == 27) {
            hide_custom_panel()
        }
    });


    var dig_sb_field = jQuery(".dig_sb_field");
    var dig_cus_field_done = jQuery(".dig_cus_field_done");

    dig_sb_field.find('input').keydown(function(e) {
        if (e.keyCode == 13 && !jQuery(this).hasClass('dig_sb_field_list_input')) {
            dig_cus_field_done.click();
            e.preventDefault();
            return false;
        }
    });

    function getFormData($form){
        var unindexed_array = $form.serializeArray();
        var indexed_array = {};

        jQuery.map(unindexed_array, function(n, i){
            indexed_array[n['name']] = n['value'].replace(/<(?:.|\n)*?>/gm, '');
        });


        return indexed_array;
    }




    var reg_custom_field_input = jQuery("#dig_reg_custom_field_data");


    var dig_custom_field_data = jQuery.parseJSON(reg_custom_field_input.val());

    var custom_field_table = jQuery("#dig_custom_field_table").find('tbody');
    var is_newfield;
    dig_cus_field_done.on('click',function () {
        var error_msg = false;

        var isCheckList = 0;
        dig_sb_field.each(function(){
            var sb_field = jQuery(this);
            if(sb_field.is(":visible")){
                if(sb_field.attr('data-req')==1){
                    var is_list = sb_field.attr('data-list');


                    if(is_list==2){
                        isCheckList = 1;
                        var sb_list = sb_field.find("input:checked");

                        if ( sb_list.length == 0 ){
                            error_msg = digsetobj.PleasecompleteyourCustomFieldSettings;
                            return false;

                        }

                    }else if(is_list==1){


                        var sb_list = sb_field.find("ul");
                        if ( sb_list.find('li').length == 0 ){
                            error_msg = digsetobj.PleasecompleteyourCustomFieldSettings;
                            return false;

                        }

                    }else{
                        var sb_input = sb_field.find("input");

                        if(sb_input.length>0){
                            if(jQuery.trim(sb_input.val()).length==0){

                                error_msg = digsetobj.PleasecompleteyourCustomFieldSettings;
                                return false;
                            }
                        }
                    }



                }
            }
        });

        if(error_msg){
            showDigMessage(error_msg);return false;
        }

        var fields = getFormData(dig_field_sidebar.find("input,select"));

        var opt = [];

        if(isCheckList==1){
            jQuery(".dig_chckbx_usrle").each(function(){
                if(jQuery(this).is(":checked")){
                    var t = jQuery(this).val();
                    opt.push(t.replace(/<(?:.|\n)*?>/gm, ''));


                }
            });
        }else {
            dig_field_val_list.find("li").each(function () {
                var t = jQuery(this).text();
                opt.push(t.replace(/<(?:.|\n)*?>/gm, ''));
            });
        }

        fields['options'] = opt;
        fields['type'] = data_type.val();



        if(!isUpdate && dig_custom_field_data.hasOwnProperty(fields['label'])){
            showDigMessage(digsetobj.fieldAlreadyExist);
            return false;
        }

        var dataString;
        if(isUpdate){
            dig_custom_field_data[prevLabel] = fields;
            dataString = JSON.stringify(dig_custom_field_data);
            dataString = dataString.replace('"'+prevLabel+'":{','"'+fields['label']+'":{');
            dig_custom_field_data = JSON.parse(dataString);

        }else {
            dig_custom_field_data[fields['label']] = fields;

            dataString = JSON.stringify(dig_custom_field_data);
        }
        reg_custom_field_input.val(dataString);
        hide_custom_panel();


        var row = '' +
            '<tr id="dig_cs_'+ removeSpacesAndLowerCase(fields['label']) +'" dig-lab="'+ fields['label'] +'">\n' +
            '            <th scope="row"><label>'+fields['label']+' </label></th>\n' +
            '            <td>\n' +
            '                <div class="dig_custom_field_list">\n' +
            requireToString(fields['required']) +
            '                    <div class="dig_icon_customfield">\n' +
            '                        <div class="icon-shape icon-shape-dims dig_cust_field_delete"></div>\n' +
            '                        <div class="icon-gear icon-gear-dims dig_cust_field_setting"></div>\n' +
            '                        <div class="icon-drag icon-drag-dims dig_cust_field_drag"></div>\n' +
            '                    </div>\n' +
            '                </div>\n' +
            '            </td>\n' +
            '        </tr>' +
            '';


        if(isUpdate){
            jQuery('#dig_cs_'+ removeSpacesAndLowerCase(prevLabel)).replaceWith(row);
        }else custom_field_table.append(row);

        enableSave();

    })


    function removeSpacesAndLowerCase(str){
        str = jQuery.trim(str.replace(/\s/g, ''));
        return str.toLowerCase();
    }

    jQuery(document).on("click", ".dig_cust_field_setting", function() {
        var row = jQuery(this).closest('tr');
        var label = row.attr('dig-lab');
        var ftype = dig_custom_field_data[label]['type'];
        show_field_options(ftype,jQuery("#dig_cust_list_type_"+ftype).attr('data-configure_fields'),dig_custom_field_data[label]);
        enableSave();
    });




    jQuery(document).on("click", ".dig_delete_opt_custf", function() {
        jQuery(this).closest('li').remove();
    });



    jQuery(document).on("click", ".dig_cust_field_delete", function() {
        var row = jQuery(this).closest('tr');
        var label = row.attr('dig-lab');
        row.slideUp().remove();
        delete dig_custom_field_data[label];
        reg_custom_field_input.val(JSON.stringify(dig_custom_field_data));

        var sortOrder = dig_sort_fields.sortable('toArray').toString();
        dig_sortorder.val(sortOrder);

        enableSave();
    });


    function requireToString(value){
        switch (value){
            case "0":
                return digsetobj.string_optional;
            case "1":
                return digsetobj.string_required;
            default:
                return null;
        }
    }



    jQuery("#dig_add_new_reg_field").click(function () {


        if(dig_field_sidebar.is(':visible') && !isUpdate) {
            dig_ad_cancel.trigger('click');
        }else {
            isUpdate = false;
            show_create_new_field_panel();
        }
    })

    function  show_create_new_field_panel() {
        sb_head.text(digsetobj.selectatype);
        isBackEnabled = 0;
        select_field_type.show();
        dig_fields_options_main.hide();
        dig_cus_field_done.hide();
        dig_field_sidebar.show().animate({right: 0}, 'fast',function(){
        field_options.show();
            dig_custom_foot.show();

        });

    }

    function hide_custom_panel(){
        hideDigMessage();
        jQuery(".dig_sb_field_list_input").val('');
        var w = dig_field_sidebar.outerWidth(true);

        dig_custom_foot.hide();
        dig_field_sidebar.animate({right:-w},function () {
            dig_field_sidebar.hide();

        })
    }



    var el = document.getElementById('dig_field_val_list');
    if(el) {
        var sortable = Sortable.create(el);
    }

    jQuery(".dig_sb_field_list_input").focusout(function () {
        hideDigMessage();
        var optval = jQuery(this).val();
        var error = false;
        if(optval.length>0){
            dig_field_val_list.find("li").each(function() {
                if(jQuery(this).text()==optval) {
                    error = true;
                    return false;
                }

            });
            if(!error) {
                addValueToValList(optval);

                jQuery(this).val('');
                dig_field_sidebar.scrollTop(dig_field_sidebar[0].scrollHeight);
            }else{
                showDigMessage(digsetobj.duplicateValue);
            }


        }
    })




    var dig_api_test = jQuery(".dig_api_test");
    var dig_test_response = jQuery("#dig_call_test_response");
    var digit_test_api_btn = jQuery("#dig_call_test_api_btn");
    var digits_test_api_msg = jQuery("#dig_call_test_response_msg");
    var loader = jQuery(".dig_load_overlay");

    digit_test_api_btn.on('click',function () {


        var dig_test_cont = dig_api_test.find(".digcon");
        var mobile = dig_test_cont.find("#username").val();
        var countrycode = dig_test_cont.find(".dig_wc_logincountrycode").val();

        if(mobile.length==0 || !jQuery.isNumeric(mobile) || countrycode.length==0 || !jQuery.isNumeric(countrycode) ){
            showDigMessage(digsetobj.validnumber);
            return false;
        }

        dig_test_api_status = 1;

        loader.show();

        if(jQuery(".dig_activation_form").find("button[type='submit']").attr("disabled")){
            digCallTestApi();
        }else jQuery(".dig_activation_form").trigger("submit");




    });

    function digCallTestApi(){
        if(dig_test_api_status!=1)return;

        var dig_test_cont = dig_api_test.find(".digcon");
        var mobile = dig_test_cont.find("#username").val();
        var countrycode = dig_test_cont.find(".dig_wc_logincountrycode").val();

        dig_test_api_status = 0;
        jQuery.ajax({
            type: 'post',
            async:true,
            url: digsetobj.ajax_url,
            data: {
                action: 'digits_test_api',
                digt_mobile: mobile,
                digt_countrycode: countrycode
            },
            success: function (res) {
                showTestResponse(res);
            },
            error: function (res){
                showTestResponse(res);
            }
        });
    }
    function showTestResponse(msg){
        dig_test_api_status = 0;
        dig_test_response.show();
        digits_test_api_msg.text(msg);
        loader.hide();

    }

    jQuery("#digpassaccep").on('change',function(){

       var val = this.value;

        if(val==0) jQuery("#enabledisableforgotpasswordrow").hide();
        else jQuery("#enabledisableforgotpasswordrow").show();

    });

    var dig_otp_conf =
    digit_tapp.on('change', function() {
        var val = this.value;
        var te = digit_tapp.find("option:selected").attr('han');

        te = te.replace(".", "_");

        dig_test_response.hide();

        if(val==1 || val==13){

            dig_api_test.hide();
            jQuery(".disotp").hide();
            jQuery(".dig_current_gateway").hide();
        }else{
            dig_api_test.show();
            dig_api_test.find("#username").trigger('keyup');
            jQuery(".disotp").show();
            jQuery(".dig_current_gateway").show().find("span").text(digit_tapp.find("option:selected").text());
        }


        digit_tapp.find('option').each(function(index,element){
            var hanc = jQuery(this).attr("han");
            if(hanc!=te) {
                jQuery("." + hanc + "cred").each(function () {
                    jQuery(this).hide().find("input").removeAttr("required");
                });

            }
        });
        jQuery("."+te+"cred").each(function(){
            var input = jQuery(this).show().find("input");
            var optional = input.attr('dig-optional');
            if(optional && optional==1) return;

            input.attr("required","required");
        });

    })







});
