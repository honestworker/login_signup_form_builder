<?php

add_action('um_submit_form_errors_hook__registration','um_dig_validate_mobileno', 999, 1);
function um_dig_validate_mobileno( $args ) {
    global $ultimatemember;


    if(count(UM()->form()->errors)>0) {
        return;
    }


    $mobile = sanitize_text_field($_POST['mobile/email']);
    $countrycode = sanitize_text_field($_POST['digt_countrycode']);



    $code = sanitize_text_field($_POST['code']);
    $csrf = sanitize_text_field($_POST['csrf']);
    $otp = sanitize_text_field($_POST['digit_ac_otp']);

    $nounce = sanitize_text_field($_POST['dig_nounce']);

    $mobile = sanitize_mobile_field_dig($mobile);
    $countrycode = sanitize_text_field($countrycode);

    $mobuser = getUserFromPhone($countrycode.$mobile);
    if ($mobuser != null) {
        UM()->form()->add_error("MobinUse", __("Mobile number already in use!","digits"));
    } else if (username_exists($countrycode.$mobile)) {
        UM()->form()->add_error("MobinUse", __("Mobile number already in use!","digits"));
    }


    if(empty($mobile) || empty($countrycode) || !is_numeric($mobile)) {
        UM()->form()->add_error( 'user_mobileno', __('Please enter Mobile Number.','digits') );
        return;
    }

    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp==1){

        if (empty($code)){
            UM()->form()->add_error( 'user_mobileno', __('Unable to verify Mobile number','digits') );
            return;
        }else{
            $json = getUserPhoneFromAccountkit($code);
            $phoneJson = json_decode($json, true);

            $mob = $phoneJson['phone'];
            $phone = $phoneJson['nationalNumber'];
            $ccode = $phoneJson['countrycode'];

            if (($json == null) || ($mob!=$mobile && $countrycode!=$ccode)) {
                UM()->form()->add_error( 'user_mobileno', __('Unable to verify Mobile number','digits') );
                return;
            }else{

            }


        }
    }else{
        if (empty($otp)){
            UM()->form()->add_error( 'user_mobileno', __('Unable to verify Mobile number','digits') );
            return;
        }else{

            if(verifyOTP($countrycode,$mobile,$otp,true)){
                $mob = $countrycode.$mobile;
                $phone = $mobile;
            }else{
                UM()->form()->add_error( 'user_mobileno', __('Unable to verify Mobile number','digits') );
                return;
            }

        }
    }

}


add_action('um_registration_complete','um_update_digits_data', 10, 2);
function um_update_digits_data($user_id,$args){

    $phone = sanitize_text_field($args['mobile/email']);
    $countrycode = sanitize_text_field($args['digt_countrycode']);


    if(!empty($phone) && !empty($countrycode)) {
        update_user_meta($user_id, 'digt_countrycode', $countrycode);
        update_user_meta($user_id, 'digits_phone_no', $phone);
        update_user_meta($user_id, 'digits_phone', $countrycode . $phone);
    }
}

//add_action('um_main_register_fields','um_add_mobile_field',1000);
function um_add_mobile_field($args){
    global $ultimatemember;

    $dig_reg_details = digit_get_reg_fields();
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];
    if($mobileaccp==0) return;
    $mobile = sanitize_text_field($_POST['mobile/email']);
    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    ?>
    <div class="um-field" data-key="user_mobileno">
        <div class="um-field-label"><label for="digit_ac_otp-62"><?php _e("Mobile Number","digits"); ?></label>
            <div class="um-clear"></div></div>
        <div class="um-field-area">
            <input type="text" class="input-text" data-dig-mob="1" name="dig_mob" id="username" data-key="user_mobileno" countryCode="<?php echo $countrycode;?>" value="<?php echo $mobile; ?>"/>
        </div>
        <?php  if(isset($error['user_mobileno'])){?>
        <div class="um-field-error"><span class="um-field-arrow"><i class="um-faicon-caret-up"></i></span>
            <?php echo $error['user_mobileno']; ?></div><?php } ?>
    </div>
    <?php
}

add_action('um_after_form_fields','um_add_dig_otp_fields');
function um_add_dig_otp_fields($args){
    global $ultimatemember;
    ?>
    <input type="hidden" name="code" id="digits_um_code" value=""/>
    <input type="hidden" name="csrf" id="digits_um_csrf" value=""/>
    <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
    <?php
    $digit_tapp = get_option('digit_tapp',1);
    if(!empty($otp) || !empty($code)) echo '<input type="hidden" value="1" id="um_sub" />';
    if($digit_tapp!=1) {
//if(empty($otp))
        ?>

        <div class="um-field dig_otp_um_reg" style="<?php  echo 'display: none;';?>">
            <div class="um-field-label"><label for="digit_ac_otp"><?php _e("OTP","digits"); ?></label>
                <div class="um-clear"></div></div>
            <div class="um-field-area">
                <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp" data-key="user_otp" value="" />
            </div>
        </div><br />
        <?php
        echo "<div  class=\"dig_resendotp dig_um_regis_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>";

    }


}


function dig_ump_otp(){
    $dig_login_details = digit_get_login_fields();
    $passaccep  = $dig_login_details['dig_login_password'];
    $otpaccep  = $dig_login_details['dig_login_otp'];
if($otpaccep!=1)return;
    ?>
    <br /><div class="digor"><?php _e("OR", "digits"); ?><br/></div><br/>
    <input class="um-button dig_um_loginviaotp" value="<?php _e('Login With OTP', 'digits'); ?>" type="submit"/>

    <?php
    $digit_tapp = get_option("digit_tapp", 1);
    if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_um_login_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>";

}
add_action( 'um_after_login_fields', 'dig_ump_otp', 1001 );

function dig_otp_bx_um(){
    ?>
    <div class="um-field dig_otp_um_login" style="<?php  echo 'display: none;';?>">
        <div class="um-field-label"><label for="digit_ac_otp"><?php _e("OTP","digits"); ?></label>
            <div class="um-clear"></div></div>
        <div class="um-field-area">
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp" data-key="user_otp" value="" />
        </div>
    </div>

    <?php
}

add_action( 'um_after_login_fields', 'dig_otp_bx_um', 10 );

function dig_um_login($args){
    $_POST['password'] = $args['user_password'];
    NWC_Meta_Box_Product_Data::process_login(true);
}
add_action('um_submit_form_errors_hook_login','dig_um_login');