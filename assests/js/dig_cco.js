jQuery(function(){
    var modcontainer = document.getElementById('dig-ucr-container');
    var opnmodcon = document.getElementById("DigCreateCustomer");
    var modclos = document.getElementsByClassName("dig-cont-close")[0];
    var digcan = document.getElementsByClassName("cancelccb")[0];




    var showalert = false;
    jQuery("#DigCreateCustomer,.DigCreateCustomer").click(function(){
        modcontainer.style.display = "block";
        showalert = !!jQuery(this).hasClass("noaction");
    });

    digcan.onclick = function () {
        modcontainer.style.display = "none";
    };
    modclos.onclick = function () {
        modcontainer.style.display = "none";
    };
    window.onclick = function (event) {
        if (event.target == modcontainer) {
            modcontainer.style.display = "none";
        }
    }
    jQuery(".dig_createcustomer").click(function () {
        var firstname = jQuery("#dig-cru-firstname").val();
        var lastname = jQuery("#dig-cru-lastname").val();
        var mailormob = jQuery(".dig-cru-mailormob").val();

        var countrycode = jQuery(".dig_wc_logincountrycode").val();
        if (firstname == "" || lastname == "" || mailormob == "") {
            alert(dig_cco_obj.enterallfields);
            return;
        }
        if (!isEmail(mailormob) && !jQuery.isNumeric(mailormob)) {
            alert(dig_cco_obj.invalidmailormobile);
            return;
        }

        jQuery.ajax({
            type: 'post',
            url: dig_cco_obj.ajax_url,
            data: {
                action: 'digits_create_user_order',
                firstname: firstname,
                lastname: lastname,
                mailormob: mailormob,
                countrycode: countrycode,
                csrf: dig_cco_obj.csrf
            },
            success: function (res) {

                if (res == "0") {
                    alert(dig_cco_obj.error);
                } else if (res == "-1") {
                    alert(dig_cco_obj.EmailMobileNumberAlreadyRegistered);
                } else {
                    var userdata = jQuery.parseJSON(res);

                    if(showalert){

                        alert(dig_cco_obj.userregisteredsuccessfully);
                    }else {
                        jQuery('#customer_user')
                            .select2({
                                data: [{id: userdata.ID, text: mailormob}]
                            }).val(userdata.ID).trigger("change");
                    }

                    modcontainer.style.display = "none";
                }

            }
        });


    })
    function isEmail(email) {
        var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
        return regex.test(email);
    }
});