<?php

add_action ('bp_signup_pre_validate', 'dig_bp_signup_pre_validate', 20);
function dig_bp_signup_pre_validate() {
    global $bp;

    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    $mobile = sanitize_text_field($_POST['mobile/email']);
    $otp = sanitize_text_field($_POST['digit_ac_otp']);
    $csrf = sanitize_text_field($_POST['csrf']);

    $validateMob = dig_validateMobileNumber($countrycode,$mobile,$otp,$csrf,2);

    if(!empty($validateMob))
        $bp->signup->errors['signup_mobile'] = $validateMob;


}

function dig_bp_add_user_meta($user_id, $user_login, $user_password, $user_email, $usermeta){
    if(!is_wp_error($user_id)){
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $mobile = sanitize_text_field($_POST['mobile/email']);
        update_user_meta($user_id, 'digt_countrycode', $countrycode);
        update_user_meta($user_id, 'digits_phone_no', $mobile);
        update_user_meta($user_id, 'digits_phone', $countrycode . $mobile);
    }
}
add_action('bp_core_signup_user','dig_bp_add_user_meta', 10 ,5);

function dig_bp_validation_error(){
    global $bp;

    if(!isset($bp->signup->errors['signup_mobile'])) return;
    echo '<div class="error">'.$bp->signup->errors['signup_mobile'].'</div>';
}

add_action('bp_account_details_fields','dig_bp_show_mobile_number');

function dig_bp_show_mobile_number(){


    ?>
    <div>
    <label for="username"><?php _e( 'Mobile Number', 'digits' ); ?> <?php _e( '(required)', 'buddypress' ); ?></label>
    <input type="text" name="username" id="username" value="" mob="1" />
    </div>
    <?php dig_bp_validation_error(); ?>


    <?php
}


add_action('bp_account_details_fields','dig_otp_bp_reg',1000);
function dig_otp_bp_reg(){?>
    <?php
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp!=1) {
        ?>
        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <div id="dig_bp_reg_otp">
            <label for="digit_ac_otp"><?php _e("OTP","digits");?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp"/>
        </div>
        <?php
    }

    //echo "<div class=\"dig_bp_enb\" style='display:none;'>";

}


function dig_createUser($name,$mobileormail,$csrf,$code){

}

//add_action('bp_account_details_fields','dig_bp_reg_end',1);
function dig_bp_reg_end(){
    //echo "</div>";
}

add_action('bp_before_registration_submit_buttons','dig_bp_sub_reg',1);
function dig_bp_sub_reg(){

    ?>



    <input type="hidden" name="code" id="dig_bp_reg_code">
    <input type="hidden" name="csrf" id="dig_bp_reg_csrf">

    <?php
}






add_action('bp_after_registration_submit_buttons','dig_bp_reg_end',1);


add_action('bp_core_general_settings_before_submit','addCurrentmobHidden');
function addCurrentmobHidden(){
    ?>

<label><?php _e("Mobile Number","digits");?></label>
<input type="text" name="bp_edit_user_mobile" id="username" mob="1"  countryCode="<?php echo esc_attr(get_the_author_meta('digt_countrycode', get_current_user_id())); ?>"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>" />


    <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
    <input type="hidden" name="code" id="dig_bp_ea_code" />
    <input type="hidden" name="csrf" id="dig_bp_ea_csrf" />
    <?php if ( is_super_admin() ) : ?>
    <input type="hidden" id="dig_superadmin">
    <?php endif; ?>
    <input type="hidden" name="current_mob" id="dig_bp_current_mob" value="<?php echo esc_attr( get_the_author_meta( 'digits_phone_no', get_current_user_id() ) ); ?>" />
    <?php
    $digit_tapp = get_option('digit_tapp',1);
        if($digit_tapp!=1) {
        ?>
        <div id="bp_otp_dig_ea" style="display: none;"><label for="digit_ac_otp"><?php _e("OTP","digits");?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp"/>
            </div><?php
        }
}

add_action('bp_core_general_settings_after_submit','add_dig_otp_bp');
function add_dig_otp_bp(){

    $digit_tapp = get_option('digit_tapp',1);
        if($digit_tapp!=1) {
            echo "<div  class=\"dig_resendotp dig_bp_ac_ea_resend\" id=\"dig_man_resend_otp_btn\" style='text-align: inherit;' dis='1'>".__('Resend OTP','digits')." <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>";
        }
}



add_action( 'bp_actions', 'dig_bp_settings_action_general' );
function dig_bp_settings_action_general(){

    if(isset($_POST['mobile/email']) && isset($_POST['dig_nounce']) && is_user_logged_in()){
        $phone = sanitize_mobile_field_dig($_POST['mobile/email']);
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $digit_tapp = get_option('digit_tapp',1);
        if(empty($phone) || !is_numeric($phone))  return;

        $otp = sanitize_text_field($_POST['digit_ac_otp']);
        $code = sanitize_text_field($_POST['code']);
        $csrf = sanitize_text_field($_POST['csrf']);
        if ( !is_super_admin() ){
        if($digit_tapp==1){
            if(empty($code) || !wp_verify_nonce($csrf, 'crsf-otp')) return;
            $json = getUserPhoneFromAccountkit($code);
            $phoneJson = json_decode($json, true);
            if ($json == null) {$phone = 0;return;}

            $mob = $phoneJson['phone'];
            $phone = $phoneJson['nationalNumber'];
            $countrycode = $phoneJson['countrycode'];

        }else{
            if(empty($otp))return;
            if(verifyOTP($countrycode,$phone,$otp,true)){

                $mob = $countrycode.$phone;
            }else{
            $phone = 0;
            return;
            }
        }
        }

        $user = getUserFromPhone($mob);
        if($phone!=0 && $user==null){
         update_user_meta(get_current_user_id(), 'digt_countrycode', $countrycode);
         update_user_meta(get_current_user_id(), 'digits_phone_no', $phone);
         update_user_meta(get_current_user_id(), 'digits_phone', $countrycode . $phone);
         }

    }
}