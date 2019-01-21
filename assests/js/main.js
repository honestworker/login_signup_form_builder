jQuery(function() {
    var precode;


    jQuery("#digit_emailaddress").closest("form").addClass("register");
    jQuery("#wc_code_dig").closest("form").addClass("login");
    jQuery("#digits_wc_code").closest("form").addClass("woocommerce-ResetPassword");

    if(dig_log_obj.dig_dsb==1) return;

    var loader = jQuery(".dig_load_overlay");
    var tokenCon;

    function loginuser(response) {
        if(precode==response.code){
            return false;
        }

        var rememberMe = 0;
        if(jQuery("#rememberme").length){
            rememberMe = jQuery("#rememberme:checked").length > 0;
        }
        precode = response.code;
        jQuery.ajax({
            type: 'post',
            url: dig_mdet.ajax_url,
            data: {
                action: 'digits_login_user',
                code: response.code,
                csrf: response.state,
                rememberMe: rememberMe,
            },
            success: function (res) {
                res = res.trim();
                loader.hide();
                if (res == "1") {

                    if(ihc_loginform==10)
                        document.location.href="/";
                    else
                        window.location.href = dig_mdet.uri;

                } else if(res==-1){
                    showDigMessage(dig_mdet.pleasesignupbeforelogginin);
                } else if(res==-9){
                    showDigMessage(dig_mdet.invalidapicredentials);
                } else{
                    showDigMessage(dig_mdet.invalidlogindetails);
                }

            }
        });

        return false;
    };


    function forgotihcCallback(response) {
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {

            jQuery("#digits_impu_code").val(response.code);
            jQuery("#digits_impu_csrf").val(response.csrf);

            jQuery("#digits_password_ihc_cont").show().find("input").attr("required", "required");
            jQuery("#digits_cpassword_ihc_cont").show().find("input").attr("required", "required");
            forgotpassihc = 2;


        }
    }
// login callback
    function loginCallback(response) {
        if (response.status === "PARTIALLY_AUTHENTICATED") {

            loginuser(response);

        }
        else if (response.status === "NOT_AUTHENTICATED") {
            loader.hide();
        }
        else if (response.status === "BAD_PARAMS") {
            loader.hide();
        }

    }

// phone form submission handler
    function smsLogin() {

    }

    function phonenumber(data) {
        var phoneno = /^\+?([0-9]{2})\)?[-. ]?([0-9]{4})[-. ]?([0-9]{4})$/;
        return !!(data.match(phoneno));
    }

    var reg_email = jQuery("#reg_email");
    var mailsecond = jQuery(".dig_wc_mailsecond");
    var mailSecondLabel = jQuery("#dig_secHolder");
    var secondmailormobile = jQuery("#secondmailormobile");

    var user_login = jQuery("#user_login");




    var ew = 30;


    jQuery('input[id="account_email"]').each(function(index){
        jQuery(this).parent().find('label').find('span').remove();
    });


    var dig_sortorder = dig_mdet.dig_sortorder;
    var register = jQuery(".woocommerce").find("form.register");
    if(dig_sortorder.length) {

        if(dig_mdet.mobile_accept>0){
            register.find("#reg_email").parent().attr({'id':'dig_cs_mobilenumber','class':'dig-custom-field woocommerce-FormRow--wide form-row form-row-wide'});
        }else{
            register.find("#reg_email").parent().attr({'id':'dig_cs_email','class':'dig-custom-field woocommerce-FormRow--wide form-row form-row-wide'});
        }

        var sortorder = dig_sortorder.split(',');
        var digits_register_inputs = register;
        digits_register_inputs.find('.dig-custom-field').sort(function (a, b) {
            var ap = jQuery.inArray(a.id, sortorder);
            var bp = jQuery.inArray(b.id, sortorder);
            return (ap < bp) ? -1 : (ap > bp) ? 1 : 0;
        }).prependTo(digits_register_inputs);
    }






    var c = jQuery(".ihc-form-create-edit");


    if(c.length && dig_mdet.mobile_accept>0){

        var f = jQuery(".iump-register-form").find("#createuser");
        var i_ccode = dig_mdet.uccode;


        f.find("input[name='phone']").attr({"data-dig-main":1,"reg":2,"data-skip-label":1,"id":"username","mob":1,"countryCode":i_ccode ,"nan":1});


        jQuery('<input type="hidden" id="dig_ihc_ea_code" name="code"/><input type="hidden" id="dig_ihc_ea_csrf" name="csrf"/><div id="dig_ihc_mobotp" class="iump-form-line-register iump-form-text" style="display:none;">' +
            '<input value="" id="digits_otp_ihc" name="digit_otp" placeholder="'+dig_mdet.OTP+'" type="text" style="padding-left:10px !important;">')
            .insertBefore(f.find("input[type='submit']").parent());

    }



    var wcform = jQuery("#wc_dig_reg_form").closest("form");
    var wc_checkout = jQuery(".woocommerce-form-login");
    if(wcform.length){
        wcform.find('input[type="password"]').closest(".woocommerce-FormRow").remove();
        wcform.find('input[name="login"]').remove();
        wcform.find(".woocommerce-LostPassword").remove();
        wcform.find('#rememberme').closest('label').remove();
        wcform.find("#username").attr('mob',1);

        if(wc_checkout.length){
            wc_checkout.find('input[type="password"]').parent().remove();
            wc_checkout.find(".form-row-first").removeClass("form-row-first");
            wc_checkout.find(".lost_password").remove();
            wc_checkout.find('#rememberme').closest('label').remove();
            wc_checkout.find('[name="login"]').remove();
            wc_checkout.find("#username").attr('mob',1);
        }
    }




    var uc = jQuery("#dig_wc_check_page");
    if(uc.length) {
        uc = uc.parent();
        var createAccount = uc.find(".create-account");
        createAccount = createAccount.last();
        if (createAccount.length) {
            createAccount.find("#username").attr({'f-mob': 1, 'reg': 1,'data-dig-mob':1});
            if(dig_mdet.mobile_accept==2){
                createAccount.find("#username").attr({'data-dig-mob':1});
            }
            jQuery(".wc_check_dig_custfields").appendTo(createAccount);
        }
    }
    var dismissLoader = false;
    var dig_billing_password = jQuery("#dig_billing_password");
    var bp_wc_val,sp_wc_val;
    if(dig_mdet.overwriteWcBillShipMob==1 || (uc.length && dig_mdet.mob_verify_checkout==0)) {
        var bp_wc = jQuery("#billing_phone");
        var sp_wc = jQuery("#shipping_phone");


        bp_wc_val = bp_wc.val();
        sp_wc_val = sp_wc.val();

        var tbp_wc = bp_wc_val;
        var tshp_wc = sp_wc_val;

        var ccdsl = 0;
        var ccdbp = 0;





        if (String(bp_wc_val).length > 0 || String(sp_wc_val).length > 0) {
            ccdbp = -1;
            ccdsl = -1;

            if (bp_wc.length > 0) if (String(bp_wc_val).length > 0)bp_wc_val = bp_wc_val.replace("+", "");
            if (sp_wc.length > 0) if (String(sp_wc_val).length > 0)sp_wc_val = sp_wc_val.replace("+", "");

            var cclist = jQuery(".digit_cs-list");
            cclist.find("li").each(function () {
                var data = jQuery(this).attr('value');

                if (bp_wc.length > 0) {
                    var bpMatch = bp_wc_val.indexOf(data) == 0;
                    if (bpMatch && data > ccdbp) ccdbp = data;
                }
                if (sp_wc.length > 0) {
                    var spMatch = sp_wc_val.indexOf(data) == 0;
                    if (spMatch && data > ccdsl) ccdsl = data;
                }

            });

            if (ccdbp != -1) {
                bp_wc_val = bp_wc_val.substring(String(ccdbp).length);
            }
            if (ccdsl != -1) {
                sp_wc_val = sp_wc_val.substr(String(ccdsl).length);
            }
        }


        bp_wc.attr({
            'value': bp_wc_val,
            'countryCode': ccdbp,
            'name': 'billing_phone_no',
            "id": 'username',
            'data-dig-main': 'billing_phone',
            'nan': 1
        }).parent().append('<input type="hidden" name="billing_phone" id="billing_phone" value="' + tbp_wc + '" />');

        sp_wc.attr({
            'value': sp_wc_val,
            'countryCode': ccdsl,
            'name': 'shipping_phone_no',
            "id": 'username',
            'data-dig-main': 'shipping_phone',
            'nan': 1
        }).parent().append('<input type="hidden" name="shipping_phone" id="shipping_phone" value="' + tshp_wc + '" />');

    }



//// Ultimate user
    var um_register = jQuery(".um-register");
    if(um_register.length){
        um_register.find('.um-field-mobile_number').find('input').attr('id','username');
    }
    var um_login = jQuery(".um-login");
    if(um_login.length){
        um_login.find('.um-field-mobile_number').find('input').attr('id','username');
    }

    jQuery('input[id="username"]').each(function(index){
        var $this = jQuery(this);
        update_username_field($this);
    })

    jQuery("#wc-pos-actions").find("#add_customer_to_register").on('click',function(){
        setTimeout(function(){
            update_username_field(jQuery('#username_field').find('#username'));
        },100);
    });


    function update_username_field($this){
        if(dig_mdet.login_mobile_accept==0){
            var fmob = $this.attr('f-mob');

            if(!fmob || fmob==0)  return;
        }
        if (dig_mdet.mobile_accept == 0){
            var reg = $this.attr('reg');

            if(reg==1)  return;
        }
        var usernameid = $this;

        var dig_main = usernameid.attr('data-dig-main');
        var ccd;
        if(!dig_main) {
            if($this.attr('data-dig-mob')==1){
                if($this.attr('countryCode')){
                    ccd = $this.attr('countryCode');
                }else{
                    ccd = dig_mdet.uccode;
                }

            }else if ($this.attr('mob') != 1) {

                var lb = dig_mdet.emailormobile;


                var reg = $this.attr('reg');

                if(!reg || reg==0) {
                    reg = 0;
                    if (dig_mdet.login_mobile_accept > 0 && dig_mdet.login_mail_accept > 0) {
                        lb = dig_mdet.emailormobile;
                    } else if (dig_mdet.login_mobile_accept > 0) {
                        lb = dig_mdet.MobileNumber;
                    } else if (dig_mdet.login_mail_accept > 0) {
                        lb = dig_mdet.email;
                    }
                }else if(reg==1){
                    if (dig_mdet.mobile_accept > 0 && dig_mdet.mail_accept > 0) {
                        lb = dig_mdet.emailormobile;
                    } else if (dig_mdet.mobile_accept > 0) {
                        lb = dig_mdet.MobileNumber;
                    } else if (dig_mdet.mail_accept > 0) {
                        lb = dig_mdet.email;
                    }
                }
                if(reg!=2) {
                    usernameid.prev().html(lb + " <span class=required>*</span>");
                    if (usernameid.attr('placeholder')) usernameid.attr('placeholder', lb);
                }
                ccd = dig_mdet.uccode;

            } else {
                usernameid.prev().html(dig_mdet.MobileNumber + " <span class=required>*</span>");
                if(usernameid.attr('placeholder')) usernameid.attr('placeholder',dig_mdet.MobileNumber);
                if($this.attr('countryCode')){
                    ccd = $this.attr('countryCode');
                }else{
                    ccd = dig_mdet.uccode;
                }




            }
        }

        var dig_ext = "";
        var dig_mainattr = "";

        var dig_ccd_name = "digt_countrycode";

        var dig_skip_label = $this.attr('data-skip-label');

        if(dig_skip_label){
            ccd = dig_mdet.uccode;
        }else if(dig_main){
            var tc = $this.attr('countryCode');
            if((tc==-1 && String(bp_wc_val).length==0) || (tc==-1 && String(sp_wc_val).length==0)) {
                ccd = dig_mdet.uccode;

            }else if(tc==-1){
                ccd = "+";
            }else{
                ccd = "+"+tc;
            }

            dig_ext = "dig_update_hidden ";
            dig_mainattr = 'data-dig-main="'+usernameid.attr('data-dig-main')+'"';
            dig_ccd_name = usernameid.attr('data-dig-main')+"_digt_countrycode";
        }


        usernameid.wrap('<div class="digcon"></div>').before('<div class="dig_wc_countrycodecontainer dig_wc_logincountrycodecontainer">' +
            '<input type="text" name="'+dig_ccd_name+'" class="'+dig_ext+'input-text countrycode dig_wc_logincountrycode" ' +
            'value="'+ ccd +'" maxlength="6" size="3" placeholder="'+ ccd +'" '+dig_mainattr+'/></div>');

        if(!usernameid.attr("nan"))usernameid.attr('name', "mobile/email");



        usernameid.bind("keyup change", function (e) {

            var dclcc = usernameid.parent().find('.dig_wc_countrycodecontainer');
            var dcllInput = dclcc.find('input');

            var dig_main = usernameid.attr('data-dig-main');
            if(dig_main){
                var ccd_dig = jQuery(this).closest('.digcon').find(".dig_update_hidden");

                var con = jQuery(this).val();
                var ccdval = ccd_dig.val();
                //ccdval = ccdval.replace("+", "");
                if (jQuery.isNumeric(con) && con.length>0 && jQuery.isNumeric(ccdval) && ccdval.length>0)
                    jQuery('#'+dig_main).val(ccdval + "" + con);
                else jQuery('#'+dig_main).val("");

            }
            if (jQuery.isNumeric(jQuery(this).val()) || jQuery(this).attr('only-mob')) {
                dclcc.css({"display": "inline-block"});


                dcllInput.trigger('keyup');
                if(usernameid.attr('data-show-btn')){
                    jQuery("."+usernameid.attr('data-show-btn')).show();
                }
            } else {
                dclcc.hide();
                jQuery(this).css({"padding-left": "0.75em"});

                if(usernameid.attr('data-show-btn')){
                    if(dig_mdet.mobile_accept!=2) jQuery("."+usernameid.attr('data-show-btn')).hide();
                }
            }
            digit_validateLogin(usernameid);

        });


        setTimeout(function() {
            usernameid.trigger('keyup');
        });
    }




    jQuery(".dig_update_hidden").on('keyup change',function(){
        var toUp = jQuery(this).attr("data-dig-main");
        var mob = jQuery(this).closest('.digcon').find("#username").val();

        var ccd = jQuery(this).val();
        // ccd = ccd.replace("+", "");
        if (jQuery.isNumeric(mob) && mob.length>0 && ccd.length>0 && jQuery.isNumeric(ccd)) jQuery('#'+toUp).val(ccd + "" + mob);
        else jQuery('#'+toUp).val("");

    });


    jQuery('input[id="reg_email"]').each(function(index){
        var reg_email = jQuery(this);
        var reg_input = reg_email.parent();

        var labe;


        var req = " <span class=required>*</span>";
        if (dig_mdet.mail_accept == 1 && dig_mdet.mobile_accept == 1) {
            labe = dig_mdet.emailormobile;
        } else if (dig_mdet.mobile_accept > 0) {
            labe = dig_mdet.MobileNumber;
            if(dig_mdet.mobile_accept==1) req = ' <span class=required>('+ dig_mdet.optional +')</span>';
        } else if (dig_mdet.mail_accept == 1) {
            labe = dig_mdet.email;
        } else{
            return;
        }



        reg_input.children("label").html(labe+ req);
        if(reg_email.attr('placeholder')) reg_email.attr('placeholder',labe);

        reg_email.wrap('<div class="digcon"></div>').before('<div class="dig_wc_countrycodecontainer dig_wc_registercountrycodecontainer"><input type="text" name="digfcountrycode" class="input-text countrycode dig_wc_registercountrycode" value="'+dig_mdet.uccode+'" maxlength="6" size="3" placeholder="'+dig_mdet.uccode+'"/></div>');


        reg_email.bind("keyup change", function (e) {
            var dclcc = reg_input.find('.dig_wc_countrycodecontainer');
            var dcllInput = dclcc.find('input');
            if (jQuery.isNumeric(reg_email.val())) {
                dclcc.css({"display": "inline-block"});
                dcllInput.trigger('keyup');
            } else {
                dclcc.hide();
                jQuery(this).css({"padding-left": "0.75em"});
            }
            updateMailSecondLabel(reg_email);
        });



        var parentForm = jQuery(this).closest('form');


        reg_email.attr({'type':'text'});

        setTimeout(function() {
            reg_email.trigger('keyup');
        });

    });


    var usernameid = jQuery("#username");


    var reg_input = reg_email.parent();


    user_login.parent().children("label").html(dig_mdet.emailormobile+" <span class=required>*</span>");







    jQuery('input[id="secondmailormobile"]').each(function (index) {
        if(dig_mdet.mail_accept==2 || dig_mdet.mobile_accept==2) return;
        sRegMail = jQuery(this);
        sRegMail.wrap('<div class="digcon"></div>').before('<div class="dig_wc_countrycodecontainer dig_wc_registersecondcountrycodecontainer"><input type="text" name="digsfcountrycode" class="input-text countrycode dig_wc_registersecondcountrycode" value="'+dig_mdet.uccode+'" maxlength="6" size="3" placeholder="'+dig_mdet.uccode+'"/></div>');


        if(sRegMail.attr('placeholder')) sRegMail.attr('placeholder',dig_mdet.emailormobile);
        sRegMail.bind("keyup change",function(){

            var dclcc = jQuery(this).parent().find('.dig_wc_registersecondcountrycodecontainer');

            var dcllInput = dclcc.find('input');

            if (jQuery.isNumeric(jQuery(this).val()) && !jQuery.isNumeric(reg_email.val())) {
                dclcc.css({"display": "inline-block"});
                dcllInput.trigger('keyup');

            } else {
                dclcc.hide();
                jQuery(this).css({"padding-left": "0.75em"});
            }
        });
        setTimeout(function() {
            sRegMail.trigger('keyup');
        });
    })


    jQuery('.dig_wc_registersecondcountrycode').bind("keyup change", function (e) {
        var dwccr = jQuery(this);
        var code = dwccr.val();
        var size = code.length;
        var curRegMail = dwccr.parent().parent().find('input#secondmailormobile');
        size++;
        if (size < 2) size = 2;
        dwccr.attr('size', size);

        if (code.trim().length == 0) {
            dwccr.val("+");
        }
        curRegMail.css({"padding-left": dwccr.outerWidth() + ew/2 + "px"}, 'fast', function () {});

    });



    if(!user_login.attr('disabled')){
        user_login.wrap('<div class="digcon"></div>').before('<div class="dig_wc_countrycodecontainer forgotcountrycodecontainer"><input type="text" name="dig_countrycodec" class="input-text countrycode forgotcountrycode" value="'+dig_mdet.uccode+'" maxlength="6" size="3" placeholder="'+dig_mdet.uccode+'"/></div>');


        setTimeout(function(){
            user_login.trigger('keyup');
        });
    }
    function digit_validateLogin(usernameid) {
        var form = usernameid.closest('form');
        if (jQuery.isNumeric(usernameid.val())) {
            var dclcc = usernameid.parent().find('.dig_wc_countrycodecontainer').find('input');
            form.find("#loginuname").val(dclcc.val() + usernameid.val());
        } else {

            form.find("#loginuname").val(usernameid.val());
        }
    }








    jQuery('.dig_wc_registercountrycode').bind("keyup change", function (e) {
        var rccBox = jQuery(this);
        var code = jQuery(this).val();
        var size = code.length;
        var curRegMail = rccBox.parent().parent().find('input#reg_email');
        size++;
        if (size < 2) size = 2;
        rccBox.attr('size', size);

        if (code.trim().length == 0) {
            rccBox.val("+");
        }
        curRegMail.css({"padding-left": rccBox.outerWidth() + ew/2 + "px"}, 'fast', function () {});

        updateMailSecondLabel(curRegMail);
    });




    user_login.bind("keyup change", function (e) {

        if (jQuery.isNumeric(jQuery(this).val())) {
            jQuery(".forgotcountrycodecontainer").css({"display": "inline-block"});
            jQuery(".forgotcountrycode").trigger('keyup');
        } else {
            jQuery(".forgotcountrycodecontainer").hide();
            jQuery(this).css({"padding-left": "0.75em"});
        }
    });

    jQuery('.forgotcountrycode').bind("keyup change", function (e) {
        var size = jQuery(this).val().length;
        size++;
        if (size < 2) size = 2;

        jQuery(this).attr('size', size);
        var code = jQuery(this).val();
        if (code.trim().length == 0) {
            jQuery(this).val("+");
        }

        user_login.css({"padding-left": jQuery('.forgotcountrycode').outerWidth() + ew/2 + "px"}, 'fast', function () {});
    });






    var isSecondMailVisible = false;
    var inftype = 0;
    function updateMailSecondLabel(reg_email) {

        var con = reg_email.val();

        var cPar = reg_email.closest('form');

        var digSecondCountryCode = cPar.find('.dig_wc_registersecondcountrycodecontainer');
        var regContainer = reg_email.parent();


        var secondmailormobile = cPar.find('.secondmailormobile');


        var mailSecondLabel = cPar.find("#dig_secHolder");

        if ( (jQuery.isNumeric(con) && inftype!=1) || dig_mdet.mail_accept==2   ) {
            inftype = 1;
            mailSecondLabel.html(dig_mdet.email);

            digSecondCountryCode.hide();

            secondmailormobile.css({"padding-left": "0.75em"});


        } else if(!jQuery.isNumeric(con) && inftype!=2 && dig_mdet.mobile_accept!=2) {
            inftype = 2;
            mailSecondLabel.html(dig_mdet.MobileNumber);


            digSecondCountryCode.css({"display": "inline-block"});

            secondmailormobile.css({"padding-left": digSecondCountryCode.find(".dig_wc_registersecondcountrycode").outerWidth() + ew/2 + "px"});
        }

        if(dig_mdet.mail_accept!=2 && dig_mdet.mobile_accept!=2) {

            if (con == "" || con.length == 0) {
                cPar.find(".dig_wc_mailsecond").stop().slideUp();
                isSecondMailVisible = false;
                return;
            }

            if (!isSecondMailVisible) {
                cPar.find(".dig_wc_mailsecond").stop().slideDown().show();
                isSecondMailVisible = true;
            } else return;
        }
    }




    jQuery(document).on("keyup", ".dig_wc_logincountrycode", function (e) {


        var rliBox = jQuery(this);
        var code = rliBox.val();
        var size = code.length;
        var curLogMail = rliBox.parent().parent().find('#username');



        size++;
        if (size < 2) size = 2;
        rliBox.attr('size', size);
        if (code.trim().length == 0) {
            rliBox.val("+");
        }
        curLogMail.attr("style","padding-left:" + (rliBox.outerWidth() + ew/2) + "px !important;");

        digit_validateLogin(curLogMail);
    });


    var max = 5;




    jQuery(".login .inline").each(function () {
        var form = jQuery(this).closest('form');
        form.find('.woocommerce-LostPassword').prepend(jQuery(this));

    });




    var registerstatus = 0;



    var regDone = 0;
    jQuery("form.register input").focusout(function () {
        if(regDone==1)return;
        jQuery("form.register input[type='submit']").each(function () {
            jQuery(this).removeAttr("disabled").removeClass("disabled");
        });
        regDone = 0;
    })



    var forgotDone = 0;

    jQuery(".woocommerce-ResetPassword input").focusin(function () {
        if(forgotDone==1) return;
        jQuery(".woocommerce-ResetPassword input[type='submit']").each(function () {
            jQuery(this).removeAttr('disabled').removeClass("disabled");
        });
        forgotDone = 1;
    });

    var forgotOutDone = 0;

    jQuery(".woocommerce-ResetPassword input").focusout(function () {
        if(forgotOutDone==1) return;
        jQuery(".woocommerce-ResetPassword input[type='submit']").each(function () {
            jQuery(this).removeAttr('disabled').removeClass("disabled");
        });
        forgotOutDone = 1;
    });

    var loginDone = 0;
    jQuery("form.login input").focusout(function () {
        if(loginDone==1) ;
        jQuery("form.login input[type='submit']").each(function () {
            jQuery(this).removeAttr("disabled").removeClass("disabled");
        });
        loginDone = 0;
    })


    var curRegForm;
    var passwcdo = 0;

    if(dig_mdet.pass_accept!=2 && dig_mdet.mobile_accept>0) {
        jQuery('form.register input[id="reg_password"]').each(function () {
            jQuery(this).parent().hide();
        });
    }


    jQuery("form.register .woocommerce-Button,form.register button[name='register']").each(function () {
        if(jQuery(this).attr('name')=='register') {
            if (!jQuery(this).hasClass("otp_reg_dig_wc")) {
                if(jQuery(".otp_reg_dig_wc").length)
                    jQuery(this).val(dig_mdet.RegisterWithPassword).addClass("wc_reg_pass_btn");
            }
        }
    });



    jQuery("form.register input[type='submit'],form.register .woocommerce-Button,form.register button[name='register']").click(function (e) {

        if(registerstatus==1)return true;
        curRegForm = jQuery(this).closest('form');


        update_time_button = jQuery(this);
        if(jQuery(this).hasClass("otp_reg_dig_wc")){
            curRegForm.find(".wc_reg_pass_btn").hide();
            curRegForm.find("#_wpnonce").parent().find("input[type='submit']").remove();
        }else if(passwcdo==0){
            passwcdo = 1;
            var a = curRegForm.find('#reg_password').parent();
            if ( a.css('display') == 'none' ){
                curRegForm.find(".otp_reg_dig_wc").hide();
                a.show();
                return false;
            }
        }



        var passf = curRegForm.find("#reg_password");


        var mail = jQuery.trim(curRegForm.find("#reg_email").val());
        var secmail = jQuery.trim(curRegForm.find("#secondmailormobile").val());


        if(passf.length>0) {
            var tpass = passf.val();
            if (dig_mdet.strong_pass == 1) {
                if (dig_mdet.pass_accept == 2 || tpass.length > 0) {
                    var strength = wp.passwordStrength.meter(tpass, ['black', 'listed', 'word'], tpass);
                    if (strength != null && strength < 3) {
                        showDigMessage(dig_mdet.useStrongPasswordString);
                        return false;
                    }
                }
            }
        }

        var error = false;


        jQuery(this).closest('form').find('input,textarea,select').each(function () {
            if(jQuery(this).attr('required')){

                if(!jQuery(this).is(':visible'))return;
                var $this = jQuery(this);

                if($this.is(':checkbox') || $this.is(':radio')){
                    if(!$this.is(':checked') && !jQuery('input[name="'+$this.attr('name')+'"]:checked').val()){
                        error = true;
                        return false;
                    }
                }else {
                    var value = $this.val();
                    if(value==null || value.length==0){
                        error = true;
                        return false;
                    }
                }

            }
        })

        if (error) {
            showDigMessage(dig_mdet.fillAllDetails);
            return false;
        }
        if(jQuery(".dig_opt_mult_con_tac").find('.dig_input_error').length){
            showDigMessage(dig_mdet.accepttac);
            return false;
        }

        if(dig_mdet.mobile_accept==0 && dig_mdet.mail_accept==0){
            return true;
        }

        if(passf.length>0) {
            var pass = passf.val();
            if (!jQuery(this).hasClass("otp_reg_dig_wc")) {

                if (pass.length == 0) {
                    showDigMessage(dig_mdet.invalidpassword);
                    return false;
                }
            }
            if (pass.length == 0 && validateEmail(mail) && validateEmail(secmail) && !jQuery.isNumeric(mail) && !jQuery.isNumeric(secmail)) {
                showDigMessage(dig_mdet.eitherentermoborusepass);
                return false;
            }
        }

        if(validateEmail(mail) && validateEmail(secmail) && secmail.length>0){
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return false;
        }
        if(jQuery.isNumeric(mail) && jQuery.isNumeric(secmail) && secmail.length>0){
            showDigMessage(dig_mdet.InvalidEmail);
            return false;
        }



        var dig_reg_mail = curRegForm.find("#dig_reg_mail");
        if(validateEmail(mail)){
            dig_reg_mail.val(mail);
        }else if(validateEmail(secmail)){
            dig_reg_mail.val(secmail);
        }


        if(dig_mdet.mail_accept==2 && (!validateEmail(secmail))){
            showDigMessage(dig_mdet.InvalidEmail);
            return false;
        }



        if(dig_mdet.mobile_accept==2 && !jQuery.isNumeric(mail) && !jQuery.isNumeric(secmail)){
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return false;
        }




        var otp = jQuery("#reg_billing_otp");

        if(regverify==1){
            if (jQuery.isNumeric(mail)) {
                verifyOtp(curRegForm.find(".dig_wc_registercountrycode").val(),mail,nounce.val(),otp.val(),2);
                return false;
            }else if(jQuery.isNumeric(secmail)){
                verifyOtp(curRegForm.find(".dig_wc_registersecondcountrycode").val(),secmail,nounce.val(),otp.val(),2);
                return false;
            }
            return false;
        }
        if(otp.length>0){
            if(curRegForm.find("#reg_username").length){
                username_reg_field = curRegForm.find("#reg_username").val();
            }
            if(curRegForm.find(".dig-custom-field-type-captcha").length){
                captcha_reg_field = curRegForm.find(".dig-custom-field-type-captcha").find("input[type='text']").val();
                captcha_ses_reg_field = curRegForm.find(".dig-custom-field-type-captcha").find(".dig_captcha_ses").val();
            }
            if (jQuery.isNumeric(mail)) {
                email_reg_field = secmail;
                verifyMobileNoLogin(curRegForm.find(".dig_wc_registercountrycode").val(),mail,nounce.val(),2);
                email_reg_field = mail;
                return false;
            }else if(jQuery.isNumeric(secmail)){
                verifyMobileNoLogin(curRegForm.find(".dig_wc_registersecondcountrycode").val(),secmail,nounce.val(),2);
                return false;
            }
        }else {
            if (jQuery.isNumeric(mail)) {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: curRegForm.find(".dig_wc_registercountrycode").val(), phoneNumber: formatMobileNumber(mail)}, // will use default values if not specified
                    registerWooCallBack);
                return false;

            } else if (jQuery.isNumeric(secmail)) {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: curRegForm.find(".dig_wc_registersecondcountrycode").val(), phoneNumber: formatMobileNumber(secmail)}, // will use default values if not specified
                    registerWooCallBack);
                return false;
            }
        }







    });




    function registerWooCallBack(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            registerstatus = 1;

            var code = response.code;
            var csrf = response.state;
            curRegForm.find("#register_code").val(code);
            curRegForm.find("#register_csrf").val(csrf);
            curRegForm.find('[type="submit"]').click();

        }
    }


    function validateEmail(email) {
        if((email.search('@')>=0)&&(email.search(/\./)>=0))
            if(email.search('@')<email.split('@')[1].search(/\./)+email.search('@')) return true;
            else return false;
        else return false;
    }


    var lastcountrycode,lastmobileNo,lastDtype;

    var username_reg_field = '';
    var email_reg_field = '';
    var captcha_reg_field = '';
    var captcha_ses_reg_field = '';
    function verifyMobileNoLogin(countrycode,mobileNo,csrf,dtype){

        if(lastcountrycode==countrycode && lastmobileNo==mobileNo && lastDtype==dtype){
            loader.hide();
            return;
        }
        dismissLoader = false;

        loader.show();
        hideDigMessage();

        lastcountrycode = countrycode;
        lastmobileNo = mobileNo;
        lastDtype = dtype;
        jQuery.ajax({
            type: 'post',
            async:true,
            url: dig_mdet.ajax_url,
            data: {
                action: 'digits_check_mob',
                countrycode: countrycode,
                mobileNo: mobileNo,
                csrf: csrf,
                login: dtype,
                username: username_reg_field,
                email: email_reg_field,
                captcha: captcha_reg_field,
                captcha_ses: captcha_ses_reg_field,
            },
            success: function (res) {
                username_reg_field = '';
                email_reg_field = '';

                captcha_reg_field = '';
                captcha_ses_reg_field = '';

                lastDtype=0;
                lastmobileNo=0;
                res = res.trim();
                loader.hide();

                if(res==9192){
                    showDigMessage("Username is already in use!");
                    return;
                }
                if(res==9193){
                    showDigMessage("Email is already in use!");
                    return;
                }

                if(res==9194){
                    showDigMessage("Please enter a valid captcha!");
                    return;
                }
                if(res==-99){
                    showDigMessage(dig_mdet.invalidcountrycode);
                    return;
                }
                if (res == -11) {
                    if(dtype==1) {
                        showDigMessage(dig_mdet.pleasesignupbeforelogginin);
                        return;
                    }else if(dtype==3){
                        showDigMessage(dig_mdet.Mobilenumbernotfound);
                        return;
                    }
                } else if (res == 0) {
                    showDigMessage(dig_mdet.error);
                    return;
                }
                if((res==-1 && dtype==2) || (res==-1 && dtype==11)){
                    showDigMessage(dig_mdet.MobileNumberalreadyinuse);
                    return;
                }

                mobileNo = mobileNo.replace(/^0+/, '');
                countrycode = countrycode.replace(/^0+/, '');


                if(dig_mdet.firebase==1){
                    dismissLoader = true;
                    loader.show();

                    var phone = countrycode + mobileNo;

                    var appVerifier = window.recaptchaVerifier;
                    firebase.auth().signInWithPhoneNumber(phone, appVerifier)
                        .then(function (confirmationResult) {


                            isDigFbAdd = 1;
                            loader.hide();
                            window.confirmationResult = confirmationResult;

                            verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype);
                        }).catch(function (error) {
                        loader.hide();
                        showDigMessage(dig_mdet.Invaliddetails);
                    });
                }else {
                    verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype);
                }
            }
        });
    }
    var isDigFbAdd = 0;

    jQuery(".woocommerce-checkout").find("#dig_billing_otp").on('blur',function(e){
        if(jQuery(this).val().length==0)return;
        if(isDigFbAdd==2) return;
        if(dig_mdet.firebase==1 && isDigFbAdd==1){
            loader.show();
            var frm = jQuery("#dig_wc_bill_code").closest('form');
            var otp = frm.find("#dig_billing_otp").val();
            window.confirmationResult.confirm(otp)
                .then(function (result) {

                    firebase.auth().currentUser.getIdToken( true).then(function(idToken) {
                        window.verifyingCode = false;
                        window.confirmationResult = null;
                        jQuery("#dig_ftok_fbase").remove();
                        frm.append("<input type='hidden' name='dig_ftoken' value='"+idToken+"' id='dig_ftok_fbase' />");
                        isDigFbAdd = 2;
                        loader.hide();
                    }).catch(function(error) {
                        loader.hide();
                        showDigMessage(error);

                    });


                }).catch(function (error) {
                loader.hide();
                showDigMessage(dig_mdet.InvalidOTP);
            });

        }

        return true;
    })


    loader.on('click',function(){
        if(dismissLoader) loader.hide();
    })


    function verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype){
        dismissLoader = false;

        if(dtype==1) {
            if (res == 1) {

                if(ihc_loginform==1){

                    ihc_loginform = 0;



                    updateTime(jQuery(".dig_impu_login_resend").attr({"countrycode":countrycode,
                        "mob":mobileNo,"csrf":csrf,"dtype":dtype}));


                    var otpin = jQuery("#impu-dig-otp");
                    otpin.show().find("input").attr("required", "required").focus();
                    verifyimpuotp = 1;

                }else if(subitumotp==1){

                    um_login.find(".digor").hide().remove();
                    um_login.find('.um-row').slideUp();
                    um_login.find('.um-col-alt').slideUp().remove();
                    um_login.find('.um-col-alt-b').hide().remove();
                    jQuery(".dig_otp_um_login").fadeIn().find("input").attr("required", "required").focus();


                    subitumotp = 2;

                    tokenCon = um_login.find('form');
                    updateTime(jQuery(".dig_um_login_resend").attr({"countrycode":countrycode,
                        "mob":mobileNo,"csrf":csrf,"dtype":dtype}));


                }else {

                    updateTime(jQuery(".dig_wc_login_resend").attr({"countrycode":countrycode,
                        "mob":mobileNo,"csrf":csrf,"dtype":dtype}));

                    hideloginpageitems();
                    logverify = 1;

                    var otpin = cuForm.find("#dig_wc_log_otp_container");
                    otpin.slideDown().find("input").attr("required", "required").focus();
                    jQuery("#username").closest("p").hide();
                }

            }
        }else if(dtype==2){

            if(billing_page==1){



                updateTime(jQuery(".dig_wcbil_bill_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));
                verfiybilling = 1;
                jQuery("#digorbilling").hide();
                jQuery(".dig_billing_otp_signup").hide();


                var otpin = jQuery("#dig_billing_otp");
                otpin.attr("required", "required").closest("p").slideDown();
                otpin.focus();

            }else if(dig_bp_btn==1){

                updateTime(jQuery(".dig_wcbil_bill_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));

                var otpin = jQuery("#dig_bp_reg_otp");
                otpin.show().find("input").attr("required", "required").focus();
                verifybpotp = 1;
                dig_bp_btn = 0;

            }else if(subitumotp==1){



                var otpin = jQuery(".dig_otp_um_reg");
                tokenCon = um_register.closest('form');
                otpin.slideDown().find("input").attr("required", "required").focus();
                subitumotp = 2;


                updateTime(jQuery(".dig_um_regis_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));


            }else {

                updateTime(jQuery(".dig_wc_register_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));


                curRegForm.find(".form-row").find("input[type='password']").each(function () {
                    jQuery(this).closest(".form-row").slideUp();

                });

                var otpin = curRegForm.find("#reg_billing_otp_container");
                otpin.slideDown().find("input").attr("required", "required").focus();

                regverify = 1;
            }

        }else if(dtype==3) {


            if(forgotpassihc==1){




                updateTime(jQuery(".dig_impu_forg_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));
                var otpin = jQuery("#impu-dig-otp");
                otpin.show().find("input").attr("required", "required").focus();
                forgotpassMobVerifiedihc = 1;
                forgotpassihc = 0;
            }else {
                updateTime(jQuery(".dig_wc_forgot_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));


                forgverify = 1;
                user_login.attr('name', 'forgotmail');
                var otpin = jQuery("#digit_forgot_otp_container");
                otpin.show().find("input").attr("required", "required").focus();
            }
        }else if(dtype==11){
            if(wpuseredit==1){

                jQuery("form#your-profile .form-table").find("input [type='password']").each(function () {
                    jQuery(this).closest(".form-table").hide();
                });

                var otpin = jQuery("#profile_update_otp_container");
                tokenCon = otpin.closest('form');
                otpin.slideDown().find("input").attr("required", "required").focus();
                editverify = 1;

            }else if(bpuseredit==1){
                var otpin = jQuery("#bp_otp_dig_ea");
                otpin.slideDown().find("input").attr("required", "required").focus();
                jQuery("#dig_bp_ac_ea_resend").show();
                updateTime(jQuery(".dig_bp_ac_ea_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));
                bpeditverify = 1;
            }else if(ihcedform==1){
                var otpin = jQuery("#dig_ihc_mobotp");
                tokenCon = otpin.closest('form');
                otpin.slideDown().find("input").attr("required", "required").focus();
                ihcedform = 2;
            }else {

                updateTime(jQuery(".dig_wc_acc_edit_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));
                var otpin = jQuery("#digit_ac_otp_container");
                otpin.slideDown().find("input").attr("required", "required").focus();
                editverify = 1;
            }
        }
    }

    var regverify = 0;
    var logverify = 0;
    var forgverify = 0;
    var editverify = 0;


    function hideloginpageitems(){

        jQuery(".digor").remove();
        cuForm.find(".lost_password").hide();
        cuForm.find("input[type='submit']").hide();

        cuForm.find(".dig-custom-field-type-captcha").hide();


        if(wc_checkout.length){
            wc_checkout.find('input[type="password"]').parent().remove();
            wc_checkout.find(".form-row-first").removeClass("form-row-first");
            wc_checkout.find('#rememberme').closest('label').remove();
            wc_checkout.find('[name="login"]').remove();
        }

        cuForm.find(".form-row").find("input[type='password']").each(function(index) {
            var mrow = jQuery(this).closest(".form-row");
            if(index!=1 && mrow.attr('otp')!=1)
                mrow.remove();
            else if(index==1){
                mrow.find("label").text(dig_mdet.MobileNumber + " *");
            }

        });
    }

    var cuForm;

    var nounce = jQuery(".dig_nounce");
    jQuery(".dig_wc_mobileLogin").click(function () {

        update_time_button = jQuery(this);
        cuForm = jQuery(this).closest('form');
        var countryCode = cuForm.find(".dig_wc_logincountrycode").val();
        var phoneNumber = cuForm.find("#username").val();

        if(phoneNumber=="" || countryCode==""){
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return;
        }

        var otp = jQuery("#dig_wc_log_otp");

        if(dig_mdet.captcha_accept==1 && otp.length){


            captcha_reg_field = cuForm.find("input[name='digits_reg_logincaptcha']").val();
            captcha_ses_reg_field = cuForm.find(".dig-custom-field-type-captcha").find(".dig_captcha_ses").val();
            if(captcha_reg_field.length==0){
                showDigMessage("Please enter a valid captcha!");
                return;
            }
        }


        if(!jQuery.isNumeric(phoneNumber) || !jQuery.isNumeric(phoneNumber)) {
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return;
        }

        if(logverify==1){
            verifyOtp(countryCode,phoneNumber,nounce.val(),jQuery("#dig_wc_log_otp").val(),1);
            return;
        }


        if(otp.length>0){
            if (jQuery.isNumeric(phoneNumber)) {

                verifyMobileNoLogin(countryCode,phoneNumber,nounce.val(),1);

            }
        }else {
            if (jQuery.isNumeric(phoneNumber)) {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode, phoneNumber: formatMobileNumber(phoneNumber)}, // will use default values if not specified
                    loginCallback);
            } else {
                loader.show();
                AccountKit.login("PHONE", {}, // will use default values if not specified
                    loginCallback);
            }
        }
    });


    var updateProfileStatus = 0;
    function updateProfileCallback(response) {
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            updateProfileStatus = 1;

            jQuery("form#your-profile #dig_prof_code").val(code);
            jQuery("form#your-profile #dig_prof_csrf").val(csrf);

            jQuery("form#your-profile input[type='submit']").click();
        }
        else if (response.status === "NOT_AUTHENTICATED") {
            // handle authentication failure
        }
        else if (response.status === "BAD_PARAMS") {
            //Need to update this
        }

    }

    function updateCheckoutDetails(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;


            jQuery(".dig_billing_otp_signup").hide();

            jQuery("#digorbilling").hide();
            jQuery("#dig_wc_bill_code").val(code);
            jQuery("#dig_wc_bill_csrf").val(csrf);


        }
    }

    var forgotPassChange = 0;
    var prv_forg_wc = -1;
    jQuery("form.lost_reset_password input[type='submit'],form.lost_reset_password button[type='submit']").click(function(){
        update_time_button = jQuery(this);
        if(prv_forg_wc==-1){
            if(jQuery(this).is(':input')) {
                prv_forg_wc = jQuery(this).val();
            }else{
                prv_forg_wc = jQuery(this).text();
            }
        }

        if(forgotPassChange==1){
            var pass = jQuery("#dig_wc_password").val();
            var cpass = jQuery("#dig_wc_cpassword").val();
            if(pass!=cpass) {
                showDigMessage(dig_mdet.Passwordsdonotmatch);
                return false;
            }
            return true;
        }
        var mom = user_login.val();
        var countryCode = jQuery("form.lost_reset_password .forgotcountrycode").val();
        var otp = jQuery("#digit_forgot_otp");

        if(forgverify==1){
            verifyOtp(countryCode,mom,nounce.val(),otp.val(),3);
            return false;
        }

        if(jQuery.isNumeric(mom)){

            jQuery("form.lost_reset_password").attr('action',window.location.pathname+"?login=true");



            if(otp.length){
                verifyMobileNoLogin(countryCode,mom,nounce.val(),3);
            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode, phoneNumber: formatMobileNumber(mom)}, // will use default values if not specified
                    forgotPasswordCallBack);
            }
            return false;
        }else{
            jQuery("form.lost_reset_password").removeAttr('action');
        }
        return true;
    });

    function forgotPasswordCallBack(response) {
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;
            forgotPassChange = 1;
            user_login.parent().parent().hide();
            user_login.attr('name','forgotmail');
            jQuery("#digits_wc_code").val(code);
            jQuery("#digits_wc_csrf").val(csrf);
            jQuery("form.lost_reset_password .changePassword").show();
        }
        else if (response.status === "NOT_AUTHENTICATED") {
            // handle authentication failure
        }
        else if (response.status === "BAD_PARAMS") {
            //Need to update this
        }

    }



    var lastotpcountrycode,lastotpmobileNo,lastotpDtype;
    function verifyOtp(countryCode,phoneNumber,csrf,otp,dtype) {
        dismissLoader = false;
        hideDigMessage();
        loader.show();

        if(dig_mdet.firebase==1) verify_firebase_otp(countryCode,phoneNumber,csrf,otp,dtype);
        else verify_cust_otp(countryCode,phoneNumber,csrf,otp,dtype,-1);


    }

    function verify_firebase_otp(countryCode,phoneNumber,csrf,otp,dtype) {
        phoneNumber = phoneNumber.replace(/^0+/, '');
        countryCode = countryCode.replace(/^0+/, '');

        window.confirmationResult.confirm(otp)
            .then(function (result) {

                firebase.auth().currentUser.getIdToken( true).then(function(idToken) {
                    window.verifyingCode = false;
                    window.confirmationResult = null;
                    jQuery("#dig_ftok_fbase").remove();
                    tokenCon.append("<input type='hidden' name='dig_ftoken' value='"+idToken+"' id='dig_ftok_fbase' />");
                    verify_cust_otp(countryCode,phoneNumber,csrf,otp,dtype,idToken);
                }).catch(function(error) {
                    loader.hide();
                    showDigMessage(error);
                });


            }).catch(function (error) {
            loader.hide();
            showDigMessage(dig_mdet.InvalidOTP);
        });
    }
    function verify_cust_otp(countryCode,phoneNumber,csrf,otp,dtype,idToken) {
        if(lastotpcountrycode==countryCode && lastotpmobileNo==phoneNumber && lastotpDtype==otp){
            loader.hide();
            return;
        }
        lastotpcountrycode = countryCode;
        lastotpmobileNo = phoneNumber;
        lastotpDtype = otp;

        var rememberMe = 0;
        if(jQuery("#rememberme").length){
            rememberMe = jQuery("#rememberme:checked").length > 0;
        }

        jQuery.ajax({
            type: 'post',
            async:true,
            url: dig_mdet.ajax_url,
            data: {
                action: 'digits_verifyotp_login',
                countrycode: countryCode,
                mobileNo: phoneNumber,
                otp:otp,
                dig_ftoken: idToken,
                csrf: csrf,
                dtype: dtype,
                rememberMe: rememberMe,
            },
            success: function (res) {
                res = res.trim();


                if (res != 11) loader.hide();

                if(res==1011){
                    showDigMessage(dig_mdet.error);
                    return;
                }

                if(res==1013){
                    showDigMessage(dig_mdet.error);
                    return;
                }

                if (res == -99) {
                    showDigMessage(dig_mdet.invalidcountrycode);
                    return;
                }

                if (res == 0) {
                    showDigMessage(dig_mdet.InvalidOTP);
                    return;
                } else if (res == 11) {

                    if (ihcloginform.length || subitumotp>0) {
                        document.location.href = "/";
                    } else
                        window.location.href = dig_mdet.uri;


                    return;
                } else if (res == -1 && dtype != 2 && dtype != 11) {
                    showDigMessage(dig_mdet.ErrorPleasetryagainlater);
                    return;
                } else if ((res == 1 && dtype == 2) || (res == 1 && dtype == 11)) {
                    showDigMessage(dig_mdet.MobileNumberalreadyinuse);
                    return;
                }
                if (dtype == 2) {

                    if (verifybpotp == 1) {
                        verifybpotp = 0;
                        dig_bp_btn = 2;
                        jQuery("#buddypress #signup_form").find("input[name='signup_submit']").click();
                    } else if (subitumotp == 2) {
                        submitumform = 1;
                        jQuery(".um-register").find("form").submit();
                    } else {
                        registerstatus = 1;
                        curRegForm.find('input[type="submit"]').click();
                    }

                } else if (dtype == 3) {


                    if (forgotpassihc == 1) {

                        jQuery("#digits_password_ihc_cont").show().find("input").attr("required", "required");
                        jQuery("#digits_cpassword_ihc_cont").show().find("input").attr("required", "required");
                        forgotpassihc = 2;
                    } else {
                        forgotPassChange = 1;
                        user_login.parent().parent().hide();
                        jQuery("#digit_forgot_otp_container").hide();
                        jQuery(".dig_wc_forgot_resend").hide();
                        user_login.attr('name', 'forgotmail');
                        jQuery("form.lost_reset_password .changePassword").show();
                        if(update_time_button.is(':input')) {
                            update_time_button.val(prv_forg_wc);
                        }else{
                            update_time_button.text(prv_forg_wc);
                        }

                    }
                    return;
                } else if (dtype == 11) {

                    if (wpuseredit == 1) {
                        updateProfileStatus = 1;
                        jQuery("form#your-profile input[type='submit']").click();

                    } else if (bpuseredit == 1) {
                        jQuery("#buddypress").find("form").unbind("submit").submit();
                    } else if (ihcedform == 2) {
                        submiticform = 1;
                        jQuery(".ihc-form-create-edit").submit();
                    } else {
                        updateAccountStatus = 1;
                        jQuery("form.woocommerce-EditAccountForm").submit();
                    }

                }


            }
        });
    }
    var updateAccountStatus = 0;

    jQuery("form.woocommerce-EditAccountForm input[type='submit'],form.woocommerce-EditAccountForm button[type='submit']").click(function () {

        update_time_button = jQuery(this);

        if(updateAccountStatus == 1) return true;

        var curForm = jQuery(this).closest('form');
        var oldMobile = curForm.find('#dig_wc_cur_phone').val();
        var curMobile = curForm.find('.dig_wc_nw_phone').val();
        var countryCode = curForm.find(".dig_wc_logincountrycode").val();
        if(curMobile.length==0) return true;

        if(oldMobile == curMobile) return true;

        if(jQuery.isNumeric(curMobile)){


            var otp = jQuery("#digit_ac_otp");

            if(editverify==1){

                verifyOtp(countryCode,curMobile,nounce.val(),otp.val(),11);

                return false;
            }
            if(otp.length){
                verifyMobileNoLogin(countryCode,curMobile,nounce.val(),11);

            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode, phoneNumber: formatMobileNumber(curMobile)}, // will use default values if not specified
                    updateAccountCallback);
            }
        }else{
            showDigMessage(dig_mdet.InvalidMobileNumber);
        }
        return false;

    });


    function updateAccountCallback(response) {
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            updateAccountStatus = 1;

            jQuery("form.woocommerce-EditAccountForm #dig_wc_prof_code").val(code);
            jQuery("form.woocommerce-EditAccountForm #dig_wc_prof_csrf").val(csrf);

            jQuery("form.woocommerce-EditAccountForm input[type='submit']").click();
        }
        else if (response.status === "NOT_AUTHENTICATED") {
            // handle authentication failure
        }
        else if (response.status === "BAD_PARAMS") {
            //Need to update this
        }

    }



    var wpuseredit = 0;

    jQuery("form#your-profile input[type='submit']").click(function(){
        wpuseredit = 1;
        update_time_button = jQuery(this);

        if(updateProfileStatus == 1) return true;
        if(dig_mdet.verify_mobile==1) {

            var phoneNumber = jQuery("form#your-profile #username").val();
            var countryCode = jQuery("form#your-profile .dig_wc_logincountrycode").val();

            var m = countryCode + phoneNumber;
            var curPhone = jQuery("form#your-profile #dig_cur_phone").val();
            if (phoneNumber.length == 0) return true;
            if (curPhone == m) return true;

            var otp = jQuery("#profile_update_otp");


            if (jQuery.isNumeric(phoneNumber)) {

                if (editverify == 1) {
                    verifyOtp(countryCode, phoneNumber, nounce.val(), otp.val(), 11);
                    return false;
                }
                if (otp.length) {
                    verifyMobileNoLogin(countryCode, phoneNumber, nounce.val(), 11);
                } else {
                    loader.show();
                    AccountKit.login("PHONE",
                        {countryCode: countryCode, phoneNumber: formatMobileNumber(phoneNumber)}, // will use default values if not specified
                        updateProfileCallback);
                }

            } else {
                showDigMessage(dig_mdet.InvalidMobileNumber);
            }

            return false;

        }
    });

    jQuery(document).on("click", "#dig_man_resend_otp_btn", function() {

        var dbbtn = jQuery(this);
        if(!jQuery(this).hasClass("dig_resendotp_disabled")){
            loader.show();
            jQuery.ajax({
                type: 'post',
                async: true,
                url: dig_mdet.ajax_url,
                data: {
                    action: 'digits_resendotp',
                    countrycode: dbbtn.attr("countrycode"),
                    mobileNo: dbbtn.attr("mob"),
                    csrf: dbbtn.attr("csrf"),
                    login: dbbtn.attr("dtype")
                },
                success: function (res) {
                    res = res.trim();
                    loader.hide();
                    if(res==0){
                        showDigMessage(dig_mdet.Pleasetryagain);
                    }else if(res==-99){
                        showDigMessage(dig_mdet.invalidcountrycode);
                    }else {
                        updateTime(dbbtn);
                    }
                }
            });
        }
    });


    var resendTime = dig_mdet.resendOtpTime;
    var update_time_button;
    function updateTime(time){

        tokenCon = time.closest('form');

        if(update_time_button) update_time_button.attr('value',dig_mdet.SubmitOTP).text(dig_mdet.SubmitOTP);



        time.attr("dis",1).addClass("dig_resendotp_disabled").show().find("span").show();
        var time_spam = time.find("span");
        time_spam.text(convToMMSS(resendTime));
        var counter = 0;
        var interval = setInterval(function() {
            var rem = resendTime - counter;
            time_spam.text(convToMMSS(rem));
            counter++;

            if (counter >= resendTime) {
                clearInterval(interval);
                time.removeAttr("dis").removeClass("dig_resendotp_disabled").find("span").hide();
                counter = 0;
            }

        }, 1000,true);
    }

    function convToMMSS(timeInSeconds) {
        var sec_num = parseInt(timeInSeconds, 10); // don't forget the second param
        var hours   = Math.floor(sec_num / 3600);
        var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
        var seconds = sec_num - (hours * 3600) - (minutes * 60);

        if (minutes < 10) {minutes = "0"+minutes;}
        if (seconds < 10) {seconds = "0"+seconds;}
        return "("+minutes+':'+seconds+")";
    }



    var verfiybilling = 0;
    var billing_page = 0;
    if(jQuery("#dig_wc_check_page").length && dig_mdet.mob_verify_checkout==1 && dig_mdet.mobile_accept>0) {
        jQuery("#dig_billing_otp").closest("p").hide();
        var regForm;
        var undigbill = jQuery("#dig_wc_check_page").parent().find("#username");

        var createAccount = undigbill.closest(".create-account");


        var digchbtn;





        if(dig_billing_password.length && dig_mdet.pass_accept==1) {
            digchbtn = "<input type='submit' class='dig_billing_pass_signup' id='dig_billing_pass_btn' onclick='verifyOTPbilling(2);return false;' value='"+ dig_mdet.signupwithpassword +"'/>" +
                "<div class='digor' id='digorbilling'>"+ dig_mdet.or +"</div>" +
                "<input type='submit' class='dig_billing_otp_signup' onclick='verifyOTPbilling(1);return false;' value='"+ dig_mdet.signupwithotp +"'/>";
        }else{

            var lb = dig_mdet.signupwithotp;
            var clss = 'dig_billing_otp_signup';
            if(dig_mdet.mobile_accept==0 || dig_mdet.pass_accept==2 || dig_mdet.pass_accept==0) {
                lb = dig_mdet.signup;
                clss = 'dig_billing_signup';
            }

            undigbill.attr('data-show-btn','dig_billing_otp_signup');
            digchbtn ="<input type='submit' class='dig_billing_otp_signup' onclick='verifyOTPbilling(1);return false;' value='"+ lb +"'/>";

        }

        createAccount.append("" +
            "<div class='dig_billing_wc_dv'>" +
            digchbtn +
            '</div><div  class="dig_resendotp dig_wcbil_bill_resend" id="dig_man_resend_otp_btn" dis="1">' + dig_mdet.resendOTP + ' <span>(00:<span>30</span>)</span></div>' +
            "<a id='dig_billing_validate_button' style='display:none;'></a><a id='dig_billing_signupwithpassword' style='display:none;'></a><br /> ");

        var tasc = 0;


        if(dig_billing_password.length && dig_mdet.pass_accept!=2) {
            jQuery("#dig_billing_password_field").hide();
            jQuery("#dig_billing_signupwithpassword").click(function () {
                jQuery("#digorbilling").hide();
                jQuery("#dig_billing_pass_btn").hide();
                jQuery("#dig_billing_password_field").show();
            });
        }

        jQuery("#dig_billing_validate_button").click(function () {
            update_time_button = jQuery(this);
            billing_page = 1;
            unbpchk = createAccount.find("#username");


            var error = false;


            createAccount.find('input,textarea,select').each(function () {
                if(jQuery(this).attr('required') || jQuery(this).attr('data-req')){

                    var $this = jQuery(this);

                    if($this.is(':checkbox') || $this.is(':radio')){

                        if(!$this.is(':checked') && !jQuery('input[name="'+$this.attr('name')+'"]:checked').val()){
                            error = true;
                            return false;
                        }
                    }else {
                        var value = $this.val();
                        if(value.length==0){
                            error = true;
                            return false;
                        }
                    }

                }
            })

            if (error) {
                showDigMessage(dig_log_obj.fillAllDetails);
                return false;
            }

            if(jQuery(".dig_opt_mult_con_tac").find('.dig_input_error').length){
                showDigMessage(dig_mdet.accepttac);
                return false;
            }

            if(dig_log_obj.mobile_accept==0 && dig_log_obj.mail_accept==0){
                return true;
            }

            var phone = unbpchk.val();
            var countrycode = createAccount.find(".dig_wc_logincountrycode").val();

            if(!jQuery.isNumeric(phone) && dig_mdet.mobile_accept!=2){
                showDigMessage(dig_mdet.InvalidMobileNumber);
                return false;
            }
            if(jQuery.isNumeric(phone)){

                var otp = jQuery("#dig_billing_otp");

                if(otp.length) {
                    verifyMobileNoLogin(countrycode, phone, nounce.val(), 2);
                }else{
                    loader.show();
                    AccountKit.login("PHONE",
                        {countryCode: countrycode, phoneNumber: formatMobileNumber(phone)}, // will use default values if not specified
                        updateCheckoutDetails);
                }
            }else{
                showDigMessage(dig_mdet.InvalidMobileNumber);
                return false;
            }
        })

    }

    var unbpchk;
    var ihcloginform = jQuery("#ihc_login_form");

    var acur = window.location.href;
    acur = acur.substring(0 , acur.indexOf('?'));


    if(ihcloginform.length && dig_mdet.login_mobile_accept>0){
        var usernameihc = ihcloginform.find("#iump_login_username");
        var passwordihc = ihcloginform.find("#iump_login_password");
        usernameihc.attr("placeholder",dig_mdet.UsernameMobileno);




        ihcloginform.attr("action",acur + "?login=true");

        var ccd = dig_mdet.uccode;


        ihcloginform.find("input[type='hidden']").val(dig_mdet.nonce).attr("name","dig_nounce");

        ihcloginform.append("<input type='hidden' value='true' name='isimpc' />");

        usernameihc.wrap('<div class="digcon"></div>').before('<div class="dig_ihc_countrycodecontainer dig_ihc_logincountrycodecontainer" style="display: none;">' +
            '<input type="text" name="countrycode" class="input-text countrycode dig_ihc_logincountrycode" ' +
            'value="'+ ccd +'" maxlength="6" size="3" placeholder="'+ ccd +'" style="position: absolute;top:0;"/></div>');
        usernameihc.attr("name","mobmail");
        passwordihc.attr("name","password");
        usernameihc.bind("keyup change", function (e) {
            if (jQuery.isNumeric(jQuery(this).val())) {
                jQuery(".dig_ihc_logincountrycodecontainer").css({"display": "inline-block"});
                jQuery(this).attr('style', "padding-left:" + (jQuery(".dig_ihc_logincountrycode").outerWidth(true)+ 10 ) + "px !important");
            } else {
                jQuery(".dig_ihc_logincountrycodecontainer").hide();
                jQuery(this).removeAttr('style');
            }
        });
        if(dig_mdet.auth!=1){
            jQuery('<div class="impu-form-line-fr impu-dig-otp" id="impu-dig-otp" style="display: none;">' +
                '<input value="" id="digits_otp_ihc" name="digit_otp" placeholder="'+dig_mdet.OTP+'" type="text" style="padding-left:10px !important;">')
                .insertBefore("#ihc_login_form .impu-form-submit");
        }

        jQuery('.dig_ihc_logincountrycode').bind("keyup change", function (e) {
            var size = jQuery(this).val().length;
            size++;
            if (size < 2) size = 2;
            jQuery(this).attr('size', size);
            var code = jQuery(this).val();
            if (code.trim().length == 0) {
                jQuery(this).val("+");
            }
            usernameihc.attr('style', "padding-left:" + (jQuery(".dig_ihc_logincountrycode").outerWidth(true) + 10) + "px !important");
        });




        jQuery('#ihc_login_form').unbind('submit');

        var remotp = 0;

        jQuery(document).on("click", "#impu_log_submit", function() {
            update_time_button = jQuery(this);
            if(verifyimpuotp==1){
                verifyOtp(jQuery(".dig_ihc_logincountrycode").val(),usernameihc.val(),dig_mdet.nonce,jQuery("#digits_otp_ihc").val(),1);
                return false;
            }

            if(jQuery.isNumeric(usernameihc.val())){
                ihc_loginform = 1;
                if(dig_mdet.auth!=1){
                    verifyMobileNoLogin(jQuery(".dig_ihc_logincountrycode").val(),usernameihc.val(),dig_mdet.nonce,1);
                }else{
                    ihc_loginform = 10;
                    AccountKit.login("PHONE",
                        {countryCode: jQuery(".dig_ihc_logincountrycode").val(),
                            phoneNumber: formatMobileNumber(usernameihc.val())
                        },
                        loginCallback);
                }
            }

            if(remotp==0) {
                remotp = 1;

                ihcloginform.find("#digorimp").hide();
                ihcloginform.find('.impu-form-submit').find("input:first").remove();

                ihcloginform.find("div").each(function (index) {
                    if (index > 1) {
                        if (!jQuery(this).hasClass("impu-form-submit") && !jQuery(this).hasClass("dig_ihc_logincountrycodecontainer") && !jQuery(this).hasClass("impu-dig-otp"))
                            jQuery(this).hide();
                    }
                });
            }
            return false;
        });
        if(dig_mdet.login_otp_accept>0) {
            ihcloginform.find(".impu-form-submit").append("<div id='digorimp'> OR<br /><br /></div>" +
                "<input type='submit' id='impu_log_submit' value='Login With OTP' />" +
                "<div class='dig_resendotp dig_impu_login_resend' id='dig_man_resend_otp_btn' dis='1'>" + dig_mdet.resendOTP + " <span>(00:<span>30</span>)</span></div></div>");
        }
    }
    var verifyimpuotp = 0;
    var ihc_loginform = 0;


    var ihcforgotpasswrap = jQuery(".ihc-pass-form-wrap");
    var ihforgaction = ihcforgotpasswrap.find("input[type='hidden']");
    var forgotpassMobVerifiedihc = 0;
    var forgotpassihc = 0;
    if(ihforgaction.val()=="reset_pass" && dig_mdet.forgot_pass>0){

        var ihcforgpassform = ihcforgotpasswrap.find("form");


        var ihcforgsub = ihcforgpassform.find("input[type='submit']");

        jQuery("<div class='dig_resendotp dig_impu_forg_resend' id='dig_man_resend_otp_btn' dis='1'>"+dig_mdet.resendOTP+" <span>(00:<span>30</span>)</span></div>")
            .insertAfter(ihcforgsub);

        ihcforgpassform.append("<input type='hidden' name='dig_nounce' value='" + dig_mdet.nonce +"' /><input type='hidden' name='ihc' value='true' />");

        var ihcForgotUsername = ihcforgotpasswrap.find("input[type='text']");


        ihcforgpassform.on('submit', function(e){
            update_time_button = jQuery(this);
            if(forgotpassihc==2){
                var pass = jQuery("#digits_password_ihc").val();
                var cpass = jQuery("#digits_cpassword_ihc").val();
                if(pass!=cpass){
                    showDigMessage(dig_mdet.Passwordsdonotmatch);
                    return false;
                }
                ihcforgpassform.unbind('submit').submit();
                return;
            }
            forgotpassihc = 1;
            if(jQuery.isNumeric(ihcForgotUsername.val())){


                ihcforgpassform.attr("action",acur + "?login=true");
                ihcForgotUsername.attr("name","forgotmail");

                var countrycode = jQuery(".dig_ihc_forgotcountrycode").val();

                if(dig_mdet.auth!=1) {
                    if (forgotpassMobVerifiedihc == 0) {
                        verifyMobileNoLogin(countrycode, ihcForgotUsername.val(), dig_mdet.nonce, 3);
                    } else {
                        forgotpassihc = 1;
                        verifyOtp(countrycode, ihcForgotUsername.val(), dig_mdet.nonce, jQuery("#digits_otp_forg_ihc").val(), 3)
                    }
                }else{
                    AccountKit.login("PHONE",
                        {countryCode:countrycode,
                            phoneNumber: formatMobileNumber(ihcForgotUsername.val())},
                        forgotihcCallback);
                }

                return false;
            }
            ihcForgotUsername.attr("name","email_or_userlogin");
            ihcforgpassform.removeAttr("action");
            return true;

        });



        var ccd = dig_mdet.uccode;

        ihcForgotUsername.wrap('<div class="digcon"></div>').before('<div class="dig_ihc_forgot_countrycodecontainer dig_ihc_forgot_logincountrycodecontainer" style="display: none;">' +
            '<input type="text" name="countrycode" class="input-text countrycode dig_ihc_forgotcountrycode" ' +
            'value="'+ ccd +'" maxlength="6" size="3" placeholder="'+ ccd +'" style="position: absolute;top:0;"/></div>');
        ihcForgotUsername.attr("placeholder",dig_mdet.UsernameMobileno);

        jQuery(
            '<div class="impu-form-line-fr" id="digits_password_ihc_cont" style="display: none;"><input value="" id="digits_password_ihc" name="digits_password" placeholder="'+dig_mdet.Password+'" type="password" style="padding-left:10px !important;"></div>' +
            '<div class="impu-form-line-fr" id="digits_cpassword_ihc_cont" style="display: none;"><input value="" id="digits_cpassword_ihc" name="digits_cpassword" placeholder="'+dig_mdet.ConfirmPassword+'" type="password" style="padding-left:10px !important;"></div>')
            .insertAfter(ihcForgotUsername.closest(".impu-form-line-fr"));
        if(dig_mdet.auth!=1){
            jQuery('<div class="impu-form-line-fr impu-dig-otp" id="impu-dig-otp" style="display: none;"><input value="" id="digits_otp_forg_ihc" name="dig_otp" placeholder="'+dig_mdet.OTP+'" type="text" style="padding-left:10px !important;"></div>')
                .insertAfter(ihcForgotUsername.closest(".impu-form-line-fr"));
        }else{
            jQuery('<input type="hidden" name="code" id="digits_impu_code"/><input type="hidden" name="csrf" id="digits_impu_csrf"/>')
                .insertAfter(ihcForgotUsername.closest(".impu-form-line-fr"));
        }

        ihcForgotUsername.bind("keyup change", function (e) {
            if (jQuery.isNumeric(jQuery(this).val())) {
                jQuery(".dig_ihc_forgot_countrycodecontainer").css({"display": "inline-block"});
                jQuery(this).attr('style', "padding-left:" + (jQuery(".dig_ihc_forgotcountrycode").outerWidth(true)+ 10 ) + "px !important");

            } else {
                jQuery(".dig_ihc_forgot_countrycodecontainer").hide();
                jQuery(this).removeAttr('style');

            }
        });

        jQuery('.dig_ihc_forgotcountrycode').bind("keyup change", function (e) {
            var size = jQuery(this).val().length;
            size++;
            if (size < 2) size = 2;
            jQuery(this).attr('size', size);
            var code = jQuery(this).val();
            if (code.trim().length == 0) {
                jQuery(this).val("+");
            }
            ihcForgotUsername.attr('style', "padding-left:" + (jQuery(".dig_ihc_forgotcountrycode").outerWidth(true) + 10) + "px !important");
        });

    }


    function showDigMessage(message){

        if(jQuery(".dig_popmessage").length){
            jQuery(".dig_popmessage").find(".dig_lase_message").text(message);
            if(!jQuery(".dig_popmessage").is(":visible")) jQuery(".dig_popmessage").slideDown('fast');
        }else {
            jQuery("body").append("<div class='dig_popmessage'><div class='dig_firele'><img src='"+ dig_mdet.face + "'></div><div class='dig_lasele'><div class='dig_lase_snap'>"+dig_mdet.ohsnap+"</div><div class='dig_lase_message'>" + message + "</div></div><img class='dig_popdismiss' src='"+ dig_mdet.cross + "'></div>");
            jQuery(".dig_popmessage").slideDown('fast');
        }

    }

    function hideDigMessage(){
        jQuery(".dig_popmessage").fadeOut(120);
    }

    jQuery(document).on("click", ".dig_popmessage", function() {

        jQuery(this).closest('.dig_popmessage').slideUp('fast', function() { jQuery(this).remove(); } );
    })

    if(jQuery(".dig_bp_enb").length){
        jQuery(".dig_bp_enb").each(function (index) {
            jQuery(this).remove();
        });
    }
    var dig_bp_btn = 0;
    var verfiyBPReg = 0;

    jQuery(document).on("click", "#signup_submit_pass_bp", function() {
        if(verfiyBPReg==1) return true;
        verfiyBPReg = 1;
        var bpForm = jQuery("#buddypress").find("form");

        bpForm.find("#dig_reg_bp_pass").show().find("input").attr("required","required");
        bpForm.find("#signup_submit_otp_bp").hide();
        return false;

    });
    jQuery("#buddypress #signup_form").on('submit',function(){
        update_time_button = jQuery(this).find('input[name="signup_submit"]');
        if(dig_bp_btn==2) return true;
        dig_bp_btn = 1;
        var bpForm = jQuery("#buddypress").find("form");
        var phone = bpForm.find("#username").val();
        var countrycode = bpForm.find(".dig_wc_logincountrycode").val();
        var otp = jQuery("#dig_bp_reg_otp");


        var pass = bpForm.find("#signup_password").val();


        if(dig_mdet.strong_pass==1){
            if(dig_mdet.pass_accept==2 || pass.length>0) {
                var strength = wp.passwordStrength.meter(pass, ['black', 'listed', 'word'], pass);
                if (strength != null && strength < 3) {
                    showDigMessage(dig_mdet.useStrongPasswordString);
                    return false;
                }
            }
        }


        if(verifybpotp==1){
            verifyOtp(countrycode,phone,nounce.val(),otp.find("input").val(),2);
        }else if(phone.length==0){
            showDigMessage(dig_mdet.pleaseentermobormail);
        }else if(pass.length==0 && !jQuery.isNumeric(phone)){
            showDigMessage(dig_mdet.eitherentermoborusepass);
        }else if(jQuery.isNumeric(phone)){


            if(bpForm.find("#signup_submit_otp_bp").is(':visible'))bpForm.find("#signup_submit_pass_bp").remove();




            if(otp.length) {
                verifyMobileNoLogin(countrycode, phone, nounce.val(), 2);
            }else{
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countrycode, phoneNumber: formatMobileNumber(phone)}, // will use default values if not specified
                    updateRegisterDetails);
            }

        }else if(validateEmail(phone)){
            return true;
        }else {
            showDigMessage(dig_mdet.Invaliddetails);
        }
        return false;
    });

    var verifybpotp = 0;
    function updateRegisterDetails(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            jQuery("#dig_bp_reg_code").val(code);
            jQuery("#dig_bp_reg_csrf").val(csrf);
            dig_bp_btn = 2;
            jQuery("#buddypress").find("form").submit();
        }
    }



    var bpuseredit = 0;
    var bpeditverify = 0;

    jQuery("#buddypress").find("form#settings-form").on('submit',function(){
        update_time_button = jQuery(this);
        var form = jQuery(this);
        var uname = form.find("#username").val();
        var ccode = form.find("#dig_wc_logincountrycode").val();

        if(jQuery("#dig_superadmin").length) return true;

        if(jQuery.isNumeric(uname)){
            if(uname==form.find("#dig_bp_current_mob")) return true;
            if(bpeditverify==1){
                var otp = jQuery("#bp_otp_dig_ea");
                verifyOtp(ccode,uname,nounce.val(),otp.find("input").val(),11);
            }else {
                bpuseredit = 1;
                if (form.find("#bp_otp_dig_ea").length) {
                    verifyMobileNoLogin(ccode, uname, nounce.val(), 11);
                } else {
                    loader.show();
                    AccountKit.login("PHONE",
                        {countryCode: ccode, phoneNumber: formatMobileNumber(uname)},
                        updateBPAccountDetails);
                }
            }
        }else return true;
        return false;
    });

    function updateBPAccountDetails(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            jQuery("#dig_bp_ea_code").val(code);
            jQuery("#dig_bp_ea_csrf").val(csrf);
            dig_bp_btn = 2;
            jQuery("#buddypress").find("form").unbind("submit").submit();
        }
    }

    function updateIHCAccountDetails(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            jQuery("#dig_ihc_ea_code").val(code);
            jQuery("#dig_ihc_ea_csrf").val(csrf);
            submiticform = 1;
            jQuery(".ihc-form-create-edit").submit();
        }
    }

    var submiticform = 0;
    var ihcedform = 0;
    if(c.length){

        var e = jQuery("#dig_ihc_mobcon");

        if(jQuery(".iump-register-form").find("#edituser").length && dig_mdet.mobile_accept>0){
            jQuery(c).prepend(e);
            jQuery(e.find("#dig_ihc_mobotp")).insertBefore(c.find("input[type='submit']").parent());
        }

        jQuery(".ihc-form-create-edit input[type=submit]").click(function(){
            update_time_button = jQuery(this);
            var form = jQuery(".ihc-form-create-edit");

            if(submiticform==1 || !form.find("#username").length) return true;
            var mob = form.find("#username").val();
            var ccode = form.find(".dig_wc_logincountrycode").val();

            if(mob==form.find("#dig_ihc_current_mob").val()) return true;

            if(jQuery.isNumeric(mob)){
                if(ihcedform==2){
                    var otp = form.find("#dig_ihc_mobotp");
                    verifyOtp(ccode,mob,nounce.val(),otp.find("input").val(),11);

                }else if(jQuery("#dig_ihc_mobotp").length && dig_mdet.auth!=1){
                    ihcedform = 1;
                    verifyMobileNoLogin(ccode, mob, nounce.val(), 11);
                }else{
                    loader.show();
                    AccountKit.login("PHONE",
                        {countryCode: ccode, phoneNumber: formatMobileNumber(mob)},
                        updateIHCAccountDetails);
                }
            }else if(mob.length>0){
                showDigMessage(dig_mdet.InvalidMobileNumber);
            }else return true;
            return false;
        })
    }


    var submitumform = 0;
    var subitumotp = 0;

    um_register.find("form").on('submit',function(){
        update_time_button = jQuery(this).find('#um-submit-btn');
        if(submitumform==1) return true;
        var form = jQuery(this);
        var uid = form.find("#username").val();
        var ccode = form.find(".dig_wc_logincountrycode").val();
        var c = form.find(".dig_otp_um_reg");
        if(uid.length==0) return true;
        if(form.find("#um_sub").length>0){

            return true;
        }
        if(!jQuery.isNumeric(uid)){
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return false;
        }
        loader.show();
        if(!c.length){
            AccountKit.login("PHONE",
                {countryCode: ccode, phoneNumber: formatMobileNumber(uid)},
                submitUMRegform);
        }else{
            if(subitumotp==2){
                verifyOtp(ccode,uid,nounce.val(),c.find("input").val(),2);
            }else{

                subitumotp = 1;
                verifyMobileNoLogin(ccode, uid, nounce.val(), 2);
            }
        }

        jQuery(".um-register").find("input[type='submit']").removeAttr('disabled');
        return false;
    });


    jQuery(".dig_um_loginviaotp").on('click',function(){
        update_time_button = jQuery(this);
        var phoneNumber = um_login.find("#username").val();
        var csrf = jQuery(".dig_nounce").val();
        var countryCode = um_login.find(".dig_wc_logincountrycode").val();
        if(phoneNumber=="" || countryCode==""){
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return false;
        }
        var otpin = jQuery(".dig_otp_um_login");


        if(!jQuery.isNumeric(phoneNumber) || !jQuery.isNumeric(phoneNumber)) {
            showDigMessage(dig_mdet.InvalidMobileNumber);
            return false;
        }

        if(subitumotp==2){
            verifyOtp(countryCode,phoneNumber,csrf,otpin.find("input").val(),1);
            return false;
        }
        if (jQuery.isNumeric(phoneNumber)) {

            if(otpin.length){
                subitumotp = 1;
                verifyMobileNoLogin(countryCode,phoneNumber,csrf,1);
            }else{
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode, phoneNumber: formatMobileNumber(phoneNumber)}, // will use default values if not specified
                    loginCallback);
            }

        } else if(phoneNumber.length>0) {
            showDigMessage(dig_log_obj.Thisfeaturesonlyworkswithmobilenumber);
        }else{

            if(otpin.length){
                verifyMobileNoLogin(countryCode,phoneNumber,csrf);
            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode}, // will use default values if not specified
                    loginCallback);
            }

        }
        return false;

    });
    function submitUMRegform(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            jQuery("#digits_um_code").val(code);
            jQuery("#digits_um_csrf").val(csrf);
            submitumform = 1;
            jQuery(".um-register").find("form").submit();
        }
    }

    if(jQuery("#dig_reg_mail").length>0){
        if(jQuery("#reg_email").attr('placeholder')!='' && jQuery("#reg_email").attr('placeholder')!=null){
            var fn_pld = jQuery("#reg_billing_first_name");
            fn_pld.attr('placeholder',jQuery.trim(fn_pld.parent().find('label').text()));
            jQuery(".register").find('.dig-custom-field').each(function(){
                var lb = jQuery.trim(jQuery(this).find('label').text());
                jQuery(this).find('input').attr('placeholder',lb);
            });
        }
    }


    function formatMobileNumber(number){
        return number.replace(/^0+/, '');
    }
});
function verifyOTPbilling(sen) {
    var l;
    if(sen==2){
        l = document.getElementById('dig_billing_signupwithpassword');
    }else {
        l = document.getElementById('dig_billing_validate_button');
    }
    l.click();

}
