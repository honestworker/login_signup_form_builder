jQuery(function($){


    function isEmpty( el ){
        return !jQuery.trim(el.text());
    }

    var tokenCon;



    jQuery(".digits-login-modal").each(function(){

            jQuery(this).parent().find("a").each(function(){
                if(!jQuery(this).hasClass("digits-login-modal")) {
                    if(isEmpty(jQuery(this)))
                    jQuery(this).remove();
                }
            });
    })




    var loader = jQuery(".dig_load_overlay");
    var modcontainer =  jQuery('.dig-box');

    var opnmodcon = document.getElementsByClassName("digits-login-modal")[0];
    var modclos = document.getElementsByClassName("dig-cont-close")[0];


    jQuery(".dig-cont-close").click(function(){
        modcontainer.css({'display':'none'});

        if(jQuery("#digits_redirect_page").length)
            jQuery("#digits_redirect_page").remove();
    });


    var isPlaceholder = 0;
    var leftPadding = 'unset';
    jQuery(".dig_pgmdl_2").find(".minput").each(function(){
       var inp = jQuery(this).find('input,textarea');
       if(inp.length){
           if(inp.attr('type')!="checkbox" && inp.attr('type')!="radio"){
               var lb = jQuery.trim(jQuery(this).find('label').text());
               inp.attr('placeholder',lb);
               isPlaceholder = 1;
               leftPadding = '1em';
           }
       }
    });
    jQuery(document).on('click', function(event) {
        if (jQuery(event.target).has('.dig-modal-con').length) {
            modcontainer.css({'display':'none'});
            if(jQuery("#digits_redirect_page").length)
                jQuery("#digits_redirect_page").remove();
        }
    });


    var login = jQuery(".dig_ma-box .digloginpage");
    var register = jQuery(".dig_ma-box .register");
    var forgot = jQuery(".dig_ma-box .forgot");

    var forgotpass = jQuery(".dig_ma-box #forgotpass");

    var dig_sortorder = dig_log_obj.dig_sortorder;
    if(dig_sortorder.length) {
        var sortorder = dig_sortorder.split(',');
        var digits_register_inputs = register.find(".dig_reg_inputs");
        digits_register_inputs.find('.minput').sort(function (a, b) {
            var ap = jQuery.inArray(a.id, sortorder);
            var bp = jQuery.inArray(b.id, sortorder);
            return (ap < bp) ? -1 : (ap > bp) ? 1 : 0;

        }).appendTo(digits_register_inputs);
    }



    var mailSecondLabel = jQuery("#dig_secHolder");
    var secondmailormobile = jQuery("#dig-secondmailormobile");


    var loginBoxTitle = jQuery(".dig-box-login-title");
    var isSecondMailVisible = false;
    var inftype = 0;

    var leftDis = dig_log_obj.left;



    var noanim = false;



    var triggered = 0;

    var dig_modal_conn = jQuery(".dig-modal-con");

    $.fn.digits_login_modal = function($this) {

        show_digits_login_modal($this);
        return false;
    };

    jQuery(document).on("click", ".digits-login-modal", function() {
        if(!jQuery(this).attr('attr-disclick')){
            show_digits_login_modal(jQuery(this));
        }
        return false;

    });
    function show_digits_login_modal($this){
        var windowWidth = jQuery(window).width();
        var type = $this.attr('type');


        if (typeof type === typeof undefined || type === false || type=="button") {
            type = 1;
        }

        if(type==10 || $this.attr('data-fal')==1){


            if($this.attr('href')) window.location.href = $this.attr('href');

            return true;
        }else {




            noanim = true;
            modcontainer.css({'display': 'block'});
            if(type==1 || type==4){


                modcontainer.find(".backtoLogin").click();
                register.find(".backtoLoginContainer").show();
                forgot.find(".backtoLoginContainer").show();

                dig_modal_conn.css({"height": login.outerHeight(true)+ 60});


                if(type==4) {
                    modcontainer.find(".signupbutton").hide();
                    modcontainer.find(".signdesc").hide();
                }else {
                    modcontainer.find(".signupbutton").show();
                    modcontainer.find(".signdesc").show();
                }
            } else if(type==2){
                if(register.length) {
                    modcontainer.find(".backtoLogin").click();
                    register.find(".backtoLoginContainer").hide();
                    modcontainer.find(".signupbutton").click();

                }else{
                    showDigMessage(dig_log_obj.Registrationisdisabled);
                    modcontainer.hide();
                    noanim = false;
                    return false;
                }
            } else if(type==3){
                if(forgot.length) {
                    modcontainer.find(".backtoLogin").click();
                    forgot.find(".backtoLoginContainer").hide();
                    modcontainer.find(".forgotpassworda").click();

                }else{
                    showDigMessage(dig_log_obj.forgotPasswordisdisabled);
                    modcontainer.hide();
                    noanim = false;
                    return false;
                }
            }

            noanim = false;

            jQuery("[tabindex='-1']").removeAttr('tabindex');

        }
        return false;
    };


    if(dig_log_obj.dig_dsb==1) return;


    var precode;
    function loginuser(response) {
        if(precode==response.code){
            return false;
        }
        precode = response.code;
        jQuery.ajax({
            type: 'post',
            async:true,
            url: dig_log_obj.ajax_url,
            data: {
                action: 'digits_login_user',
                code: response.code,
                csrf: response.state
            },
            success: function (res) {
                res = res.trim();
                loader.hide();
                if (res == "1") {

                    if(jQuery("#digits_redirect_page").length) {
                        window.location.href = jQuery("#digits_redirect_page").val();
                    }else window.location.href = dig_log_obj.uri;

                } else if(res==-1){
                    showDigMessage(dig_log_obj.pleasesignupbeforelogginin);
                } else if(res==-9){
                    showDigMessage(dig_log_obj.invalidapicredentials)
                }else{
                    showDigMessage(dig_log_obj.invalidlogindetails);
                }

            }
        });

        return false;
    };


// login callback
    function loginCallback(response) {
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;

            loginuser(response);

        }
        else if (response.status === "NOT_AUTHENTICATED") {
            loader.hide();
        }
        else if (response.status === "BAD_PARAMS") {
            loader.hide();
        }

    }

    jQuery(document).on("click", "#dig_lo_resend_otp_btn", function() {
        var dbbtn = jQuery(this);
       if(!jQuery(this).hasClass("dig_resendotp_disabled")){
           loader.show();

           if(dig_log_obj.firebase==1) {
               dismissLoader = true;
               loader.show();
               var phone = dbbtn.attr("countrycode") + dbbtn.attr("mob");

               grecaptcha.reset(window.recaptchaWidgetId);

               var appVerifier = window.recaptchaVerifier;
               firebase.auth().signInWithPhoneNumber(phone, appVerifier)
                   .then(function (confirmationResult) {
                       isDigFbAdd = 1;
                       loader.hide();
                       window.confirmationResult = confirmationResult;
                       updateTime(dbbtn);
                   }).catch(function (error) {
                   loader.hide();
                   showDigMessage(dig_mdet.Invaliddetails);
               });



           }else {
               jQuery.ajax({
                   type: 'post',
                   async: true,
                   url: dig_log_obj.ajax_url,
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
                       if (res == 0) {
                           showDigMessage(dig_log_obj.pleasetryagain);
                       } else if (res == -99) {
                           showDigMessage(dig_log_obj.invalidcountrycode);
                       } else {
                           updateTime(dbbtn);
                       }
                   }
               });
           }
       }
    });


    jQuery(document).on("click", ".dig_captcha", function() {
        var $this = jQuery(this);
        var cap = $this.parent().find(".dig_captcha_ses");
        var r = Math.random();
        $this.attr('src', $this.attr('cap_src')+'?r='+r+'&pr='+cap.val());
        cap.val(r);

    });

    jQuery('.dig_captcha').on('dragstart', function(event) { event.preventDefault(); });


    if(jQuery.isFunction(jQuery.fn.niceSelect)) jQuery(".dig-custom-field").find('select').niceSelect();

    var update_time_button;

    var resendTime = dig_log_obj.resendOtpTime;
    function updateTime(time){



        tokenCon = time.closest('form');
        if(update_time_button) update_time_button.attr('value',dig_log_obj.SubmitOTP).text(dig_log_obj.SubmitOTP);


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


    var dismissLoader = false;
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
        hideDigMessage();
        loader.show();
        lastcountrycode = countrycode;
        lastmobileNo = mobileNo;
        lastDtype = dtype;
        jQuery.ajax({
            type: 'post',
            async:true,
            url: dig_log_obj.ajax_url,
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
                    showDigMessage(dig_log_obj.invalidcountrycode);
                    return;
                }
                if (res == -11) {
                    if(dtype==1) {
                        showDigMessage(dig_log_obj.pleasesignupbeforelogginin);
                        return;
                    }else if(dtype==3){
                        showDigMessage(dig_log_obj.Mobilenumbernotfound);
                        return;
                    }
                } else if (res == 0) {
                    showDigMessage(dig_log_obj.Error);
                    return;
                }

                if(res==-1 && dtype==2){
                    showDigMessage(dig_log_obj.MobileNumberalreadyinuse);
                    return;
                }

                mobileNo = mobileNo.replace(/^0+/, '');
                countrycode = countrycode.replace(/^0+/, '');


                if(dig_log_obj.firebase==1 ) {

                    dismissLoader = true;
                    loader.show();

                    var phone = countrycode + mobileNo;


                    var appVerifier = window.recaptchaVerifier;
                    firebase.auth().signInWithPhoneNumber(phone, appVerifier)
                        .then(function (confirmationResult) {
                            loader.hide();
                            window.confirmationResult = confirmationResult;
                            verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype);

                        }).catch(function (error) {
                            loader.hide();
                            showDigMessage(error);

                    });
                }else {
                    verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype);
                }
            }
        });
    }

    loader.on('click',function(){
        if(dismissLoader) loader.hide();
    })




    update_req_fields();
    function update_req_fields() {
        if (dig_log_obj.show_asterisk == 1) {
            jQuery(".minput").each(function () {
                var par = jQuery(this);
                if(par.hasClass("dig-custom-field")) return;
                var inpu = par.find("input");

                if (inpu.attr('required') && !inpu.attr('aster')) {
                    par.find("label").append(" *");
                    inpu.attr('aster',1);
                }
            });
        }
    }

    if(dig_log_obj.firebase==1 && jQuery('form').length) {

        jQuery('form').append('<input type="hidden" value="1" id="dig_login_va_fr_otp" />');

        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('dig_login_va_fr_otp', {
            'size': 'invisible',
            'callback': function (response) {

            },
            'expired-callback': function() {
                loader.hide();
            },
            'error-callback': function() {
                loader.hide();
            }

        });
        firebase.auth().signOut();
    }


    jQuery("input[name='dig_otp']").on('keydown',function(e){
        if (jQuery.inArray(e.keyCode, [46, 8, 9, 27, 13, 110]) !== -1 ||
            // Allow: Ctrl+A, Command+A
            (e.keyCode === 65 && (e.ctrlKey === true || e.metaKey === true)) ||
            // Allow: home, end, left, right, down, up
            (e.keyCode >= 35 && e.keyCode <= 40)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
            e.preventDefault();
        }
    })

    var otp_box = 0;
    var otp_container = jQuery(".dig_verify_mobile_otp_container");
    var otp_submit_button = 0;
    function verifyMobNo_success(res,countrycode,mobileNo,csrf,dtype){

        dismissLoader = false;
        if(dtype==1) {
            if (res == 1) {
                updateTime(jQuery(".dig_logof_log_resend").attr({"countrycode":countrycode,
                    "mob":mobileNo,"csrf":csrf,"dtype":dtype}));
                jQuery(".digloginpage .minput").find("input[type='password']").each(function () {
                    jQuery(this).closest(".minput").slideUp();
                });
                var otpin = jQuery("#dig_login_otp");
                jQuery(".logforb").hide();
                otpin.slideDown().find("input").attr("required", "required").focus();

                otp_submit_button = jQuery(".loginviasms");
                otp_submit_button.attr("verify", 1);


                if(otp_container.length){
                    login.hide();
                    otp_box = otpin.find("input");
                    otp_container.show().find(".dig_verify_code_msg span").text(countrycode+mobileNo);
                    otp_container.find('input').focus();
                    otp_container.find("#dig_verify_otp").after(jQuery(".dig_logof_log_resend"));
                }
            }
        }else if(dtype==2){

            updateTime(jQuery(".dig_logof_reg_resend").attr({"countrycode":countrycode,
                "mob":mobileNo,"csrf":csrf,"dtype":dtype}));

            registerStatus = 1;
            jQuery(".digits_register .minput").find("input[type='password']").each(function () {
                jQuery(this).closest(".minput").slideUp();
            });
            var otpin = jQuery("#dig_register_otp");
            otpin.slideDown().find("input").attr("required", "required").focus();
            jQuery(".dig_ma-box #dig_reg_btn_password").hide();
            dig_otp_signup.show();

            jQuery(".dig_ma-box .registerbutton").attr("verify", 1);


            otpin.closest(".dig-container").addClass("dig-min-het");

            if(otp_container.length){
                otp_submit_button = dig_otp_signup;
                register.hide();
                otp_box = otpin.find("input");
                otp_container.show().find(".dig_verify_code_msg span").text(countrycode+mobileNo);
                otp_container.find('input').focus();
                otp_container.find("#dig_verify_otp").after(jQuery(".dig_logof_reg_resend"));
            }

        }else if(dtype==3) {

            updateTime(jQuery(".dig_logof_forg_resend").attr({"countrycode":countrycode,
                "mob":mobileNo,"csrf":csrf,"dtype":dtype}));

            var otpin = jQuery("#dig_forgot_otp");
            otpin.slideDown().find("input").attr("required", "required").focus();

            otp_submit_button = jQuery("div.forgot .forgotpassword");
            otp_submit_button.attr("verify", 1);


            if(otp_container.length){
                forgot.hide();
                otp_box = otpin.find("input");
                otp_container.show().find(".dig_verify_code_msg span").text(countrycode+mobileNo);
                otp_container.find('input').focus();
                otp_container.find("#dig_verify_otp").after(jQuery(".dig_logof_reg_resend"));
            }
        }
        setTimeout(function(){jQuery(window).trigger('resize');}, 350);
        update_req_fields();
        jQuery(window).trigger('resize');

    }


    jQuery("#dig_verify_otp_input").on('keyup',function(event){
        var keyCode = (event.keyCode ? event.keyCode : event.which);
        if (keyCode == 13) {
            jQuery("#dig_verify_otp").trigger('click');
        }

    });
    jQuery("#dig_verify_otp").on('click',function(){
        var dig_verify_otp = jQuery("#dig_verify_otp_input");
        var dig_verify_otp_input = dig_verify_otp.val();
        if(dig_verify_otp_input.length==0){
            dig_verify_otp.addClass("dig_input_error").closest('.minput').append(requiredTextElement);
            return false;
        }
        otp_box.val(dig_verify_otp_input);
        otp_submit_button.trigger('click');
    })



    jQuery(".dig_ma-box .loginviasms").click(function(){

        update_time_button = jQuery(this);
        var countryCode = jQuery(".dig_ma-box .logincountrycode").val();
        var csrf = jQuery(".dig_nounce").val();
        var phoneNumber = usernameid.val();

/*
        if(dig_log_obj.captcha_accept==1)
        {
            jQuery(".digloginpage").find("input[type='password']").closest(".minput").hide();
        }*/

        if(phoneNumber=="" || countryCode==""){
            showDigMessage(dig_log_obj.InvalidMobileNumber);
            return;
        }


        if(!jQuery.isNumeric(phoneNumber) || !jQuery.isNumeric(phoneNumber)) {
            showDigMessage(dig_log_obj.InvalidMobileNumber);
            return;
        }


        var dig_otp = jQuery("#dig_login_otp");
        if(dig_log_obj.captcha_accept==1 && dig_otp.length){

            var digloginpage = jQuery(".digloginpage");
            captcha_reg_field = digloginpage.find("input[name='digits_reg_logincaptcha']").val();
            captcha_ses_reg_field = digloginpage.find(".dig-custom-field-type-captcha").find(".dig_captcha_ses").val();
            if(captcha_reg_field.length==0){
                showDigMessage("Please enter a valid captcha!");
                return;
            }
        }

        if(jQuery(this).attr('verify')==1){
            var otpin = jQuery("#dig_login_otp");
            verifyOtp(countryCode,phoneNumber,csrf,otpin.find("input").val(),1);
            return;
        }


        if (jQuery.isNumeric(phoneNumber)) {

            if(dig_otp.length){
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

            if(dig_otp.length){
                verifyMobileNoLogin(countryCode,phoneNumber,csrf);
            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: countryCode}, // will use default values if not specified
                    loginCallback);
            }

        }
    });





    var lastotpmobileNo,lastotpcountrycode,lastotpDtype;
    function verifyOtp(countryCode,phoneNumber,csrf,otp,dtype) {
        dismissLoader = false;
        hideDigMessage();
        loader.show();

        if(dig_log_obj.firebase==1) verify_firebase_otp(countryCode,phoneNumber,csrf,otp,dtype);
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
                showDigMessage(error);
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


        jQuery.ajax({
            type: 'post',
            async:true,
            url: dig_log_obj.ajax_url,
            data: {
                action: 'digits_verifyotp_login',
                countrycode: countryCode,
                mobileNo: phoneNumber,
                otp:otp,
                dig_ftoken: idToken,
                csrf: csrf,
                dtype: dtype
            },
            success: function (res) {
                res = res.trim();
                if(res!=11)loader.hide();

                if(res==1011){
                    showDigMessage(dig_log_obj.error);
                    return;
                }

                if(res==1013){
                    showDigMessage(dig_log_obj.error);
                    return;
                }

                if(res==-99){
                    showDigMessage(dig_log_obj.invalidcountrycode);
                    return;
                }

                if(res==0){
                    showDigMessage(dig_log_obj.InvalidOTP);
                    return;
                }else if(res==11){
                    if(jQuery("#digits_redirect_page").length) {
                        window.location.href = jQuery("#digits_redirect_page").val();
                    }else window.location.href = dig_log_obj.uri;

                    return;
                }else if(res==-1 && dtype!=2){
                    showDigMessage(dig_log_obj.ErrorPleasetryagainlater);
                    return;
                }else if(res==1 && dtype==2){
                    showDigMessage(dig_log_obj.MobileNumberalreadyinuse);
                    return;
                }
                if(dtype==2){
                    registerStatus = 1;
                    jQuery(".dig_ma-box .registerbutton").attr("verify",3).click();

                }else if(dtype==3){
                    jQuery(".dig_ma-box .changepassword .minput").each(function(){
                       jQuery(this).show();
                    });
                    jQuery(".dig_ma-box #dig_forgot_otp").slideUp();
                    jQuery(".dig_ma-box .forgotpasscontainer").slideUp();
                    jQuery(".dig_ma-box .changepassword").slideDown();
                    jQuery(".dig_ma-box #digits_csrf").val(csrf);
                    jQuery(".dig_logof_forg_resend").hide();
                    update_time_button.val(prv);
                    passchange = 1;
                    if(otp_container.length){
                        otp_container.hide();
                        forgot.show();
                    }
                }
            }
        });
    }


    var prv = -1;
    var forgotpass = jQuery(".dig_ma-box #forgotpass");
    var passchange = 0;


    if(jQuery("#digits_forgotPassChange").length){
        passchange = 1;
    }

    jQuery(".dig_ma-box .forgotpassword").click(function(){
        update_time_button = jQuery(this);

        if(prv==-1) prv = jQuery(this).val();
        var forgot = jQuery.trim(forgotpass.val());
        var countryCode = jQuery(".dig_ma-box .forgotcountrycode").val();
        var csrf = jQuery(".dig_nounce").val();

        if(jQuery(this).attr("verify")==1 && passchange!=1){
                var otpin = jQuery("#dig_forgot_otp");
                verifyOtp(countryCode,forgot,csrf,otpin.find("input").val(),3);
                return false;

        }
        var passBox = jQuery(".dig_ma-box #digits_password");
        var cpassBox = jQuery(".dig_ma-box #digits_cpassword");
        if(passchange==1) {
            var pass = passBox.val();
            var cpass = cpassBox.val();
            if(pass!=cpass){
                showDigMessage(dig_log_obj.Passworddoesnotmatchtheconfirmpassword);
                return false;
            }


            return true;
        }

        if(validateEmail(forgot) && forgot!=""){
            passBox.removeAttr('required');
            cpassBox.removeAttr('required');
            return true;
        }else{


            var countryCode = jQuery(".dig_ma-box .forgotcountrycode").val();

            if(forgot=="" || countryCode==""){
                return;
            }
            if (jQuery.isNumeric(forgot)) {

                if (jQuery("#dig_forgot_otp").length) {
                    verifyMobileNoLogin(countryCode, forgot, csrf, 3);
                } else {

                    loader.show();
                    AccountKit.login("PHONE",
                        {countryCode: countryCode, phoneNumber: formatMobileNumber(forgot)}, // will use default values if not specified
                        forgotCallBack);

                }

            }else{
                showDigMessage(dig_log_obj.Invaliddetails);
            }


        }

        return false;
    });


    var digPassReg = jQuery(".dig_ma-box #digits_reg_password");
    var dig_pass_signup = jQuery(".dig_ma-box .dig-signup-password");
    var dig_otp_signup = jQuery(".dig_ma-box .dig-signup-otp");


    var dig_log_reg_button = 0;

    dig_pass_signup.click(function(){

        var dis = jQuery(this).attr('attr-dis');

        if(dis == 0){
            return false;
        }

        digPassReg.attr("required","");
        dig_otp_signup.hide();


        digPassReg.parent().show();
        digPassReg.parent().parent().fadeIn();


        jQuery(this).addClass('registerbutton');
        jQuery(this).attr('attr-dis',0);
        dig_log_reg_button = 0;

        return false;
    });


    var requiredTextElement = "<span class='dig_field_required_text'>Required</span>";
    var registerStatus = 0;


    jQuery(".dig_opt_mult").find('input[type="checkbox"],input[type="radio"]').on('change',function(){
        var $this = jQuery(this);


        if($this.is(':radio')) {
            $this.closest(".dig_opt_mult_con").find(".selected").removeClass('selected');
        }

        if(!$this.is(':checked')){
            $this.parent().removeClass('selected');
        }else{
            $this.parent().addClass('selected');
        }
    })
    jQuery(document).on('keyup change', '.dig_input_error', function(){
        var minput = jQuery(this).closest('.minput');
        minput.find(".dig_input_error").removeClass('dig_input_error');
        minput.find(".dig_field_required_text").remove();
    })
    jQuery(".dig_ma-box .registerbutton").click(function(){

        update_time_button = jQuery(this);

        var name,mail,pass,secmail;
        name = jQuery.trim(jQuery(".dig_ma-box #digits_reg_name").val());
        secmail = jQuery.trim(secondmailormobile.val());
        mail = jQuery.trim(digits_reg_email.val());
        pass = jQuery.trim(digPassReg.val());


        if(dig_log_obj.strong_pass==1){
            if(dig_log_obj.pass_accept==2 || pass.length>0) {
                var strength = wp.passwordStrength.meter(pass, ['black', 'listed', 'word'], pass);
                if (strength != null && strength < 3) {
                    showDigMessage(dig_log_obj.useStrongPasswordString);
                    return false;
                }
            }
        }
        var dis = jQuery(this).attr('attr-dis');
        var csrf = jQuery(".dig_nounce").val();

        var error = false;



        jQuery(this).closest('form').find('input,textarea,select').each(function () {
            if(jQuery(this).attr('required') || jQuery(this).attr('data-req')){

                var $this = jQuery(this);

                if($this.is(':checkbox') || $this.is(':radio')){

                    if(!$this.is(':checked') && !jQuery('input[name="'+$this.attr('name')+'"]:checked').val()){
                        error = true;
                        $this.addClass('dig_input_error').closest('.minput').append(requiredTextElement);
                    }
                }else {
                    var value = $this.val();
                    if(value==null || value.length==0){
                        error = true;
                        if($this.is("select"))
                            $this.next().addClass('dig_input_error');

                        $this.addClass('dig_input_error').closest('.minput').append(requiredTextElement);
                    }
                }

            }
        })


        if (error) {
            showDigMessage(dig_log_obj.fillAllDetails);
            return false;
        }

        if(jQuery(".dig_opt_mult_con_tac").find('.dig_input_error').length){
            showDigMessage(dig_log_obj.accepttac);
            return false;
        }

        if(dig_log_obj.mobile_accept==0 && dig_log_obj.mail_accept==0){
            return true;
        }



        if(dis == 1 && dig_otp_signup.length){
            digPassReg.attr("required","");
            dig_otp_signup.hide();


            digPassReg.parent().show();
            digPassReg.parent().parent().fadeIn();


            jQuery(this).attr('attr-dis',-1);
            dig_log_reg_button = 0;
            jQuery(window).trigger('resize');
            return false;
        }else if(!dis){

            if(dig_log_obj.pass_accept==2 && pass.length==0){
                showDigMessage(dig_log_obj.Invaliddetails);
                return false;
            }
            if(dig_log_obj.pass_accept>0 && pass.length==0 && validateEmail(mail) && validateEmail(secmail) && !jQuery.isNumeric(mail) && !jQuery.isNumeric(secmail)){
                showDigMessage(dig_log_obj.eitherenterpassormob);
                return false;
            }
        }



        if(jQuery(this).attr("verify")==1){
            var otp = jQuery(".dig_ma-box #dig_register_otp").find("input").val();
            if(jQuery.isNumeric(mail)){
                verifyOtp(jQuery(".dig_ma-box .registercountrycode").val(),mail,csrf,otp,2);
                return false;
            }else if(jQuery.isNumeric(secmail)){
                verifyOtp(jQuery(".dig_ma-box .registersecondcountrycode").val(),secmail,csrf,otp,2);
                return false;
            }
            return false;
        }
        if(registerStatus==1){return true;}
        var dis = jQuery(this).attr('attr-dis');




            if (mail.length == 0 && (dig_log_obj.mobile_accept==2 || dig_log_obj.mobile_accept==1 && dig_log_obj.mail_accept==1)) {
                showDigMessage(dig_log_obj.Invaliddetails);
                return false;
            }



            if (jQuery.isNumeric(mail) && jQuery.isNumeric(secmail) && secmail.length > 0) {
                showDigMessage(dig_log_obj.InvalidEmail);
                return false;
            }

            if(jQuery("#disable_email_digit").length){
             if(!jQuery.isNumeric(mail)){
                 showDigMessage(dig_log_obj.Invaliddetails);
                 return false;
             }

            }else{
                if (validateEmail(mail) && validateEmail(secmail) && secmail.length > 0) {
                   showDigMessage(dig_log_obj.Invaliddetails);
                   return false;
             }
                var dig_reg_mail = jQuery(".dig_ma-box #dig_reg_mail");
                if (validateEmail(mail)) {
                    dig_reg_mail.val(mail);
                } else if (validateEmail(secmail)) {
                    dig_reg_mail.val(secmail);
                }


                if(dig_log_obj.mail_accept==2 && !validateEmail(secmail) && !validateEmail(mail)){
                    showDigMessage(dig_log_obj.InvalidEmail);

                    return false;
                }

            }

        if(jQuery("#disable_password_digit").length) {
            if (!jQuery.isNumeric(digits_reg_email.val()) && !jQuery.isNumeric(secondmailormobile.val())) {

                if (dig_log_obj.pass_accept>0 && pass.length == 0) {
                    showDigMessage(dig_log_obj.eitherenterpassormob);
                    return false;
                }

            }
        }



        if(dig_log_obj.mobile_accept==2 && !jQuery.isNumeric(mail) && !jQuery.isNumeric(secmail)){
            showDigMessage(dig_log_obj.InvalidMobileNumber);
                return false;
        }

        if(jQuery("#digits_reg_username").length){
            username_reg_field = jQuery("#digits_reg_username").val();
        }
        var curRegForm = jQuery(this).closest('form');
        if(curRegForm.find(".dig-custom-field-type-captcha").length){
            captcha_reg_field = curRegForm.find(".dig-custom-field-type-captcha").find("input[type='text']").val();
            captcha_ses_reg_field = curRegForm.find(".dig-custom-field-type-captcha").find(".dig_captcha_ses").val();
        }


        if(jQuery.isNumeric(mail)){


            if(jQuery("#dig_register_otp").length){
                email_reg_field = secmail;
                verifyMobileNoLogin(jQuery(".dig_ma-box .registercountrycode").val(),mail,csrf,2);
            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: jQuery(".dig_ma-box .registercountrycode").val(), phoneNumber: formatMobileNumber(mail)}, // will use default values if not specified
                    registerCallBack);
            }
            return false;



        }else if(jQuery.isNumeric(secmail)){

            if(jQuery("#dig_register_otp").length){
                email_reg_field = mail;
                verifyMobileNoLogin(jQuery(".dig_ma-box .registersecondcountrycode").val(),secmail,csrf,2);
            }else {
                loader.show();
                AccountKit.login("PHONE",
                    {countryCode: jQuery(".dig_ma-box .registersecondcountrycode").val(), phoneNumber: formatMobileNumber(secmail)}, // will use default values if not specified
                    registerCallBack);
            }
            return false;


        }






        return true;

    });

    function registerCallBack(response){
        loader.hide();

        if (response.status === "PARTIALLY_AUTHENTICATED") {
            var code = response.code;
            var csrf = response.state;
            jQuery(".dig_ma-box #register_code").val(code);
            jQuery(".dig_ma-box #register_csrf").val(csrf);

            registerStatus = 1;
            jQuery(".dig_ma-box .registerbutton").click();

        }
    }

    function forgotCallBack(response){
        loader.hide();
        if (response.status === "PARTIALLY_AUTHENTICATED") {
            passchange = 1;
            var code = response.code;
            var csrf = response.state;
            jQuery(".dig_ma-box .forgotpasscontainer").slideUp();
            jQuery(".dig_ma-box .changepassword").slideDown();
            jQuery(".dig_ma-box #digits_code").val(code);
            jQuery(".dig_ma-box #digits_csrf").val(csrf);
        }
    }
    function validateEmail(email) {
        var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
    }





    var lef = leftDis*3;
    leftDis = lef*2-9;
    jQuery(".dig_ma-box .backtoLogin").click(function () {
        if(loginBoxTitle){
            loginBoxTitle.text(dig_log_obj.login);
        }


        if(!noanim) {
            login.fadeIn('fast');
        }else{
            login.show();
        }

        register.hide();
        forgot.hide();
        dig_modal_conn.css({"height": login.outerHeight(true)});


    });
    jQuery(".dig_ma-box .signupbutton").click(function () {
        hideLogin();
        if(loginBoxTitle){
            loginBoxTitle.text(dig_log_obj.signup);
        }

        if(!noanim) {
            register.fadeIn('fast');
        }else{
            register.show();
        }

        dig_modal_conn.css({"height": register.outerHeight(true)});

    });

    jQuery(window).resize(function () {
        if(register.is(":visible")){
            dig_modal_conn.css({"height": register.outerHeight(true)});
        }else if(dig_modal_conn.is(":visible")){
            dig_modal_conn.css({"height": login.outerHeight(true)});
        }
    });

    jQuery(".dig_ma-box .forgotpassworda").click(function () {
        if(loginBoxTitle){
            loginBoxTitle.text(dig_log_obj.ForgotPassword);
        }
        hideLogin();

        if(!noanim) {
            forgot.fadeIn('fast');
        }else{
            forgot.show();
        }
        dig_modal_conn.css({"height":login.outerHeight(true)});
    });
    function hideLogin(){
        login.hide();

    }


    var usernameid = jQuery(".dig_ma-box #dig-mobmail");

    var digits_reg_email = jQuery(".dig_ma-box #digits_reg_email");

    var ew = 12;
    usernameid.bind("keyup change", function (e) {
        if(dig_log_obj.login_mobile_accept==0) return;
        if (jQuery.isNumeric(jQuery(this).val())) {
            jQuery(".dig_ma-box .logincountrycodecontainer").css({"display": "inline-block"});
            jQuery('.dig_ma-box .logincountrycode').trigger('keyup');
        } else {
            jQuery(".dig_ma-box .logincountrycodecontainer").hide();
            jQuery(this).css({"padding-left": leftPadding});
        }
        digit_validateLogin();
    });


    function digit_validateLogin() {
        if (jQuery.isNumeric(usernameid.val())) {
            jQuery(".dig_ma-box #loginuname").val(jQuery(".dig_ma-box .logincountrycode").val() + usernameid.val());
        } else {
            jQuery(".dig_ma-box #loginuname").val(usernameid.val());
        }
    }

    jQuery('.dig_ma-box .logincountrycode').bind("keyup change", function (e) {
        var size = jQuery(this).val().length;
        size++;
        if (size < 2) size = 2;
        jQuery(this).attr('size', size);
        var code = jQuery(this).val();
        if (code.trim().length == 0) {
            jQuery(this).val("+");
        }
        
        usernameid.stop().animate({"padding-left": jQuery(".dig_ma-box .logincountrycode").outerWidth() + ew/2 + "px"}, 'fast', function () {
        });
        digit_validateLogin();
    });




    digits_reg_email.bind("keyup change", function (e) {

        if(dig_log_obj.mobile_accept==0)return;
        if (jQuery.isNumeric(jQuery(this).val())) {
            jQuery(".registercountrycodecontainer").css({"display": "inline-block"});
            jQuery('.registercountrycode').trigger('keyup');
        } else {
            jQuery(".registercountrycodecontainer").hide();
            jQuery(this).css({"padding-left": leftPadding});
        }
        updateMailSecondLabel();
    });



    setTimeout(function() {
        usernameid.trigger("keyup");
        digits_reg_email.trigger("keyup");
    });


    jQuery('.registercountrycode').bind("keyup change", function (e) {

        var size = jQuery(this).val().length;
        size++;
        if (size < 2) size = 2;
        jQuery(this).attr('size', size);
        var code = jQuery(this).val();
        if (code.trim().length == 0) {
            jQuery(this).val("+");
        }
        digits_reg_email.stop().animate({"padding-left": jQuery(".registercountrycode").outerWidth() + ew/2 + "px"}, 'fast', function () {});

        updateMailSecondLabel();
    });


    secondmailormobile.bind("keyup change", function (e) {
        if(dig_log_obj.mail_accept==2 || dig_log_obj.mobile_accept==2) return;

        if (jQuery.isNumeric(jQuery(this).val()) && !jQuery.isNumeric(digits_reg_email.val())){
            jQuery(".secondregistercountrycodecontainer").css({"display": "inline-block"});
             jQuery(".registersecondcountrycode").trigger('keyup');

        } else {
            jQuery(".secondregistercountrycodecontainer").hide();
            jQuery(this).css({"padding-left": leftPadding});
        }
        updateMailSecondLabel();
    });




    jQuery('.registersecondcountrycode').bind("keyup change", function (e) {
        var size = jQuery(this).val().length;
        size++;
        if (size < 2) size = 2;
        jQuery(this).attr('size', size);
        var code = jQuery(this).val();
        if (code.trim().length == 0) {
            jQuery(this).val("+");
        }
        secondmailormobile.stop().animate({"padding-left": jQuery(".registersecondcountrycode").outerWidth() + ew/2 + "px"}, 'fast', function () {});

        updateMailSecondLabel();
    });



    forgotpass.bind("keyup change", function (e) {
        if (jQuery.isNumeric(jQuery(this).val())) {
            jQuery(".forgotcountrycodecontainer").css({"display": "inline-block"});
            jQuery('.forgotcountrycode').trigger('keyup');

        } else {
            jQuery(".forgotcountrycodecontainer").hide();
            jQuery(this).css({"padding-left": leftPadding});
        }
        updateMailSecondLabel();
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
        forgotpass.stop().animate({"padding-left": jQuery(this).outerWidth() + ew/2 + "px"}, 'fast', function () {});

        updateMailSecondLabel();
    });





    var prevInftype = 0;
    function updateMailSecondLabel() {
        var con = digits_reg_email.val();
        if(!con)return;
        if ((jQuery.isNumeric(con) && inftype!=1) || dig_log_obj.mail_accept==2) {
            inftype = 1;

            mailSecondLabel.html(dig_log_obj.Email);
        } else if(!jQuery.isNumeric(con) && inftype!=2 && dig_log_obj.mobile_accept!=2) {
            inftype = 2;
            mailSecondLabel.html(dig_log_obj.Mobileno);
        }

        if(isPlaceholder==1 && prevInftype!=inftype){
            prevInftype = inftype;
            secondmailormobile.attr('placeholder',mailSecondLabel.parent().text());
        }

        if(dig_log_obj.mail_accept!=2 && dig_log_obj.mobile_accept!=2) {

            if (con == "" || con.length == 0) {
                jQuery(".dig-mailsecond").hide();
                if(isSecondMailVisible) jQuery(window).trigger('resize');
                isSecondMailVisible = false;
                return;
            }

            if (!isSecondMailVisible) {
                jQuery(".dig-mailsecond").fadeIn();
                jQuery(window).trigger('resize');
                isSecondMailVisible = true;
            } else return;
        }
    }


    var minputs = jQuery('.minput').find("input,textarea");
    minputs.blur(function(){
        tmpval = jQuery(this).val();
        if(tmpval == '') {
            jQuery(this).addClass('empty').removeClass('not-empty');
        } else {
            jQuery(this).addClass('not-empty').removeClass('empty');
        }
    });


    minputs.each(function () {
     jQuery(this).triggerHandler('blur');
    });



    function formatMobileNumber(number){
        return number.replace(/^0+/, '');
    }

    var elem = jQuery(".digit_cs-list");
    var cur_countrycode = jQuery(".countrycode");

    var isShown = 0;
    cur_countrycode.focus(function(){
        var $this = jQuery(this);
        var parentForm = $this.parent('div');
        parentForm.append(elem);

        var nextNode = elem.find('li.selected');
        highlight(nextNode);


        var thisOset = $this.position();
        var parrentFormOset = parentForm.position();

        var olset = thisOset.left - parrentFormOset.left;



        var margin = parseInt( $this.css("marginBottom") );
        elem.css({'top': $this.outerHeight(true) - margin, 'left' : olset});

        elem.show();

        isShown = 1;
    });
    cur_countrycode.focusout(function(){
        elem.hide();
        isShown = 0;
    });

    cur_countrycode.keydown(function(e) {
        if(isShown==0)cur_countrycode.trigger('focus');
        switch (e.which) {
            case 38: // Up
                var visibles = elem.find('li.dig-cc-visible:not([disabled])');
                var nextNode = elem.find('li.selected').prev();
                var nextIndex = visibles.index(nextNode.length > 0 ? nextNode : visibles.last());
                highlight(nextIndex);
                e.preventDefault();
                return false;
                break;
            case 40:

                var visibles = elem.find('li.dig-cc-visible:not([disabled])');
                var nextNode = elem.find('li.selected').next();

                var nextIndex = visibles.index(nextNode.length > 0 ? nextNode : visibles.first());
                highlight(nextIndex);
                e.preventDefault();
                return false;
                break;
            case 13:
                selectCode();
                return false;
                break;
            case 9:  // Tab
            case 27: //ESC
                elem.hide();
                break;
            default:
                var hiddens = 0;
                var curInput = jQuery(document.activeElement);
                var input  = curInput.val().toLowerCase().trim().replace(/[^a-z]+/gi, "");

                jQuery(".digit_cs-list li").each(function(index){
                    var attr = jQuery(this).attr('country');
                    if(attr.startsWith(input)){
                        highlight(index);
                        return false;
                    }
                });



                break;
        }


    });


    function selectCode(){

        if (elem.is(':visible')) {
            var selEle = elem.find('li.selected');

            var curInput = jQuery(document.activeElement);
            curInput.val("+" + selEle.attr('value'));
            curInput.trigger('keyup');
            elem.hide();
            isShown = 0;
        }
    }
    function highlight(index) {
        setTimeout(function () {

            var visibles         = elem.find('li.dig-cc-visible');
            var oldSelected      = elem.find('li.selected').removeClass('selected');
            var oldSelectedIndex = visibles.index(oldSelected);

            if (visibles.length > 0) {
                var selectedIndex = (visibles.length + index) % visibles.length;
                var selected      = visibles.eq(selectedIndex);

                var top = 0;
                if(selected.length>0) {
                    top = selected.position().top;
                    selected.addClass('selected');
                }
                if (selectedIndex < oldSelectedIndex && top < 0) {
                    elem.scrollTop(elem.scrollTop() + top);
                }
                if (selectedIndex > oldSelectedIndex && top + selected.outerHeight() > elem.outerHeight()) {
                    elem.scrollTo(".selected");


                }

            }
        });
    };

    elem.on('mousemove', 'li:not([disabled])', function () {

            elem.find('.selected').removeClass('selected');
            jQuery(this).addClass('selected');

        })
        .on('mousedown', 'li', function (e) {
            if (elem.is('[disabled]')) e.preventDefault();
            else elem.select(jQuery(this));
            selectCode();
        })
        .on('mouseup', function () {
            elem.find('li.selected').removeClass('selected');
        });





    function showDigMessage(message){

        if(jQuery(".dig_popmessage").length){
            jQuery(".dig_popmessage").find(".dig_lase_message").text(message);
            if(!jQuery(".dig_popmessage").is(":visible")) jQuery(".dig_popmessage").slideDown('fast');
        }else {
            jQuery("body").append("<div class='dig_popmessage'><div class='dig_firele'><img src='"+ dig_log_obj.face + "'></div><div class='dig_lasele'><div class='dig_lase_snap'>"+dig_log_obj.ohsnap+"</div><div class='dig_lase_message'>" + message + "</div></div><img class='dig_popdismiss' src='"+ dig_log_obj.cross + "'></div>");
            jQuery(".dig_popmessage").slideDown('fast');
        }

    }
    function hideDigMessage(){
        jQuery(".dig_popmessage").fadeOut(120);
    }
    jQuery(document).on("click", ".dig_popmessage", function() {
        jQuery(this).closest('.dig_popmessage').slideUp('fast', function() { jQuery(this).remove(); } );
    })

});