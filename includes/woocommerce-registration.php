<?php


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}




add_action('user_register','digits_add_custom_reg_fields_wp_new_user');

function digits_add_custom_reg_fields_wp_new_user($user_id){

    if(is_user_logged_in() && current_user_can('edit_user')) {
        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);
        update_digp_reg_fields($reg_custom_fields, $user_id);
    }
}

add_action( "user_new_form", "digits_add_new_userpage" );

function digits_add_new_userpage(){

    if(is_rtl()){ echo '<style>.digcon{float: right;}</style>';}?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
           var createuser = jQuery("#createuser");
            createuser.find("#email").closest(".form-required").removeClass("form-required").find(".description").remove();

            var ul = createuser.find("#user_login");
            ul.attr('id',"#wp_user_login").closest('tr').find('label').attr('for','wp_user_login');

            ul.closest("tr").after('<tr class="form-field">' +
                '<th scope="row">' +
                '<label for="user_login"><?php _e("Mobile Number","digits")?></label>' +
                '</th>' +
                '<td><input name="dig_user_mobile" id="user_login" value="" type="text"></td>' +
                '</tr>');

            
        });
    </script>

    <?php
    echo '<table class="form-table">';
        show_digp_reg_fields(3,null, 0);
    echo '</table>';
    digits_add_style();
    digits_add_scripts();

}




add_action( 'show_user_profile', 'dig_show_extra_profile_fields',100,10);
add_action( 'edit_user_profile', 'dig_user_profile_update' );

function dig_user_profile_update($user){
    dig_show_extra_profile_fields($user,true);


}


function dig_show_extra_profile_fields( $user, $admin = false) { ?>
    <h3><?php _e('Extra Profile Information','digits'); ?></h3>
    <table class="form-table">
        <tr>
            <th>
                <label for="phone"><?php _e('Mobile Number','digits'); ?></label>
            </th>
            <td style="position: relative;">

                <input type="hidden" name="code" id="dig_prof_code">
                <input type="hidden" name="csrf" id="dig_prof_csrf">
                <input type="hidden" name="dig_old_phone" id="dig_cur_phone" value="<?php echo esc_attr( get_the_author_meta( 'digits_phone', $user->ID ) ); ?>" />
                <input type="text" autocomplete="off" countryCode="<?php echo esc_attr( get_the_author_meta( 'digt_countrycode', $user->ID ) ); ?>" data-dig-mob="1" name="digits_phone" id="username" value="<?php echo esc_attr( get_the_author_meta( 'digits_phone_no', $user->ID ) ); ?>" class="regular-text" f-mob="1"/>
                <?php if(is_rtl()) echo '<br /><br />'; ?><span class="description"><?php _e('Please enter your Mobile Number.','digits'); ?></span>
            </td>
        </tr>
        <?php show_digp_reg_fields(3,null, $user->ID); ?>


    </table>

    <?php
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp!=1) {

        ?>
        <table class="form-table" dis="no"  id="profile_update_otp_container" style="display: none;">
            <tr>
                <th>
                    <label for="profile_update_otp"><?php _e("OTP","digits");?></label>
                </th>
                <td>
                    <input type="hidden" name="dig_nounce" class="dig_nounce"
                           value="<?php echo wp_create_nonce('dig_form') ?>">
                    <input type="text" name="profile_update_otp" id="profile_update_otp" class="regular-text"/>
                </td>
            </tr>
        </table>
        <?php
    }
    ?>
    <?php if(is_rtl()){ echo '<style>.digcon{float: right;}</style>';}?>
    <?php

    wp_enqueue_style('digits-login-style', plugins_url('digits/assests/css/login.css'), array(), null, 'all');

    digCountry();
    digits_add_style();
    digits_add_scripts(get_the_author_meta( 'digt_countrycode', $user->ID ));
    wp_register_script('digits-login-script', plugins_url('digits/assests/js/login.js'), array('jquery'), null, true);


    $verify_mobile = 0;
    if(!$admin){
        $verify_mobile = 1;
    }

    $app = get_option('digit_api');
    $appid = "";
    if ($app !== false) {
        $appid = $app['appid'];
    }


    $jsData = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'appId' => $appid,
        'uri' => '',
        'state' => wp_create_nonce('crsf-otp'),
        'left' => 0,
        'verify_mobile' => $verify_mobile
    );
    wp_localize_script('digits-login-script', 'dig_log_obj', $jsData);


    wp_enqueue_script('digits-login-script');


}


add_action( 'user_profile_update_errors', 'validate_info' ,10 , 3);
function validate_info($errors, $update = null, $user  = null)
{



    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);

    $validation_error = new WP_Error();
    $validation_error = validate_digp_reg_fields($reg_custom_fields,$validation_error,false);
    if ($validation_error->get_error_code()){
        $errors->add("incompletedetails", $validation_error->get_error_message());
    }




    if( (!isset($_POST['dig_user_mobile']) || !isset($_POST['dig_countrycodec'])) && !isset($_POST['mobile/email'])) {

        $errors->add('MobileNo', "<strong>".__("Error","digits")."</strong>: ".__("Invalid Mobile Number!","digits"));

    }else if ((!isset($_POST['digt_countrycode']) || !isset($_POST['mobile/email']) || !isset($_POST['dig_old_phone'])) && !isset($_POST['dig_user_mobile']) )
    {
        $errors->add('MobileNo', "<strong>".__("Error","digits")."</strong>: ".__("Invalid Mobile Number!","digits"));
    }

    if(isset($_POST['dig_user_mobile'])){
        $countrycode = sanitize_text_field($_POST['dig_countrycodec']);
        $phone = sanitize_text_field($_POST['dig_user_mobile']);


    }else if(isset($_POST['mobile/email'])){
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $phone = sanitize_text_field($_POST['mobile/email']);
        $dig_old_phone = sanitize_text_field($_POST['dig_old_phone']);
    }

    if(empty($phone) && !empty($errors->get_error_message('empty_email'))) $errors->add('mailormobile', "<strong>".__("Error","digits")."</strong>: ".__("Please enter your email or Mobile Number!","digits"));






    $errors->remove('empty_email');

    if(empty($phone))return;




    $tempUser = getUserFromPhone($countrycode.$phone);

    if($tempUser!=null) {

            if (!array_key_exists('ID',$user)){
                $errors->add('MobileNoAlreadyInUse', "<strong>".__("Error","digits")."</strong>: ".__("Mobile Number already in use!","digits"));
            }else if($tempUser->ID != $user->ID) {
                $errors->add('MobileNoAlreadyInUse', "<strong>".__("Error","digits")."</strong>: ".__("Mobile Number already in use!","digits"));
            }
    }


}





add_filter( 'woocommerce_checkout_fields' , 'digits_override_checkout_fields',2);

function digits_override_checkout_fields( $fields ) {
    $dig_reqfieldbilling = get_option("dig_reqfieldbilling",0);
    if($dig_reqfieldbilling==1){
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_email']['required'] = false;

    }else if($dig_reqfieldbilling==2){
        $fields['billing']['billing_email']['required'] = true;
        $fields['billing']['billing_phone']['required'] = false;

    }







    $dig_reg_details = digit_get_reg_fields();
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    if(get_option('dig_mob_ver_chk_fields',1)==1 && $mobileaccp>0) {


        $fields['account']['mobile/email']['required'] = true;
        $fields['account']['mobile/email']['id'] = "username";


        if($mobileaccp==2){
            $fields['account']['mobile/email']['placeholder'] = __("Mobile Number", "digits");
            $fields['account']['mobile/email']['label'] = __("Mobile Number", "digits");
        }else {
            $fields['account']['mobile/email']['placeholder'] = __("Email/Mobile Number", "digits");
            $fields['account']['mobile/email']['label'] = __("Email/Mobile Number", "digits");
        }
        $fields['billing']['billing_email']['label'] = __("Billing Email", "digits");
        $fields['billing']['billing_phone']['label'] = __("Billing Mobile Number", "digits");

    }


    $password = 'no' === get_option( 'woocommerce_registration_generate_password' ) ? false : true;

    if(!$password) {
        $fields['account']['account_password']['placeholder'] = __("Password", "digits");

        $isPassRequired = $dig_reg_details['dig_reg_password'];
        if($isPassRequired==2){
            $fields['account']['account_password']['required'] = true;
        }else {
            $fields['account']['account_password']['required'] = false;
        }

        
        $fields['account']['account_password']['id'] = "dig_billing_password";
        $fields['account']['account_password']['type'] = "password";
        $fields['account']['account_password']['label'] = __("Password", "digits");
    }



    if(get_option('dig_mob_ver_chk_fields',1)==1 && !is_user_logged_in()) {
        $digit_tapp = get_option('digit_tapp', 1);
        if ($digit_tapp != 1 && $mobileaccp>0) {
            $fields['account']['digit_ac_otp']['placeholder'] = __("OTP", "digits");
            $fields['account']['digit_ac_otp']['required'] = false;
            $fields['account']['digit_ac_otp']['id'] = "dig_billing_otp";
            $fields['account']['digit_ac_otp']['type'] = "text";
            $fields['account']['digit_ac_otp']['label'] = __("OTP", "digits");
        }
    }


    return $fields;
}

if ( ! function_exists( 'wc_create_new_customer' ) ) {
    /**
     * Create a new customer.
     *
     * @param  string $email Customer email.
     * @param  string $username Customer username.
     * @param  string $password Customer password.
     * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
     */
    function wc_create_new_customer($email, $username = '', $password = '')
    {

        $validation_error = new WP_Error();
        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);
        $validation_error = validate_digp_reg_fields($reg_custom_fields,$validation_error);
        if ($validation_error->get_error_code()) {return $validation_error;}


        if(get_option('dig_mob_ver_chk_fields',1)==1 || true) {
            $phone_number = sanitize_mobile_field_dig($_POST['mobile/email']);
            $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        }else {
            $phone_number = sanitize_mobile_field_dig($_POST['billing_phone_no']);
            $countrycode = sanitize_text_field($_POST['billing_phone_digt_countrycode']);
        }






        $otp = sanitize_text_field($_POST['digit_ac_otp']);
        $code = sanitize_text_field($_POST['code']);
        $csrf = sanitize_text_field($_POST['csrf']);



        $dig_reg_details = digit_get_reg_fields();


        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];




        if($mobileaccp==2 && !is_numeric($phone_number)){
            return new WP_Error('error', __('Please enter a valid Mobile Number!', 'digits'));
        }
        if($emailaccep==2 && (!isValidEmail($phone_number) && !isValidEmail($email)) ){
            return new WP_Error('error', __('Please enter a valid Email!', 'digits'));
        }


        if(isValidEmail($phone_number)){
            $email = $phone_number;
        }
        if (is_numeric($phone_number)) {



            if(dig_get_checkout_otp_verification()==1 ) {
                if (empty($otp) && empty($code)) {
                    return new WP_Error('error', __('Please verify your Mobile Number!', 'digits'));
                }
            }
                if (!checkwhitelistcode($countrycode)) {
                    return new WP_Error('error', __('Invalid Country Code!', 'digits'));
                }
                if (empty($phone_number) && empty($countrycode)) {
                    return new WP_Error('error', __('Invalid Mobile Number!', 'digits'));
                }
                if (is_numeric($phone_number) && empty($countrycode)) {
                    return new WP_Error('error', __('Invalid Country Code!', 'digits'));
                }
            if(dig_get_checkout_otp_verification()==1) {


                $digit_tapp = get_option('digit_tapp', 1);

                if ($digit_tapp != 1) {

                    if (is_numeric($phone_number) && empty($otp)) {
                        return new WP_Error('error', __('Invalid OTP!', 'digits'));
                    }
                    if (!verifyOTP($countrycode, $phone_number, $otp, false)) {
                        return new WP_Error('error', __('Unable to verify OTP!', 'digits'));
                    }
                }
            }
            if (getUserFromPhone($countrycode . $phone_number)) {
                return new WP_Error('error',__('Mobile Number already in use!','digits'));
            }
        }else if (empty($email)) {
            return new WP_Error('registration-error-invalid-email', __('Please provide a valid email address.', 'woocommerce'));
        }



        if (!is_numeric($phone_number) && !is_email($email)) {
            return new WP_Error('registration-error-invalid-email', __('Please provide a valid email address.', 'woocommerce'));
        }


        if (email_exists($email)) {
            return new WP_Error('registration-error-email-exists', __('An account is already registered with your email address. Please login.', 'woocommerce'));
        }


        if(empty($username)){

            $useMobAsUname = get_option('dig_mobilein_uname',0);



            $isMobUsed = 0;
            if (is_numeric($phone_number) && $useMobAsUname==1 ) {
                $username = str_replace("+","",$countrycode).$phone_number;
                $isMobUsed = 1;
            } else {
                $username = sanitize_user(current(explode('@', $email)), true);
                $isMobUsed = 2;
            }

            if(!isValidEmail($email) && !is_numeric($phone_number)){
                return new WP_Error('error',__('Invalid Mobile Number or email','digits'));
            }
            if(empty($username)){
                $username = str_replace("+","",$countrycode).$phone_number;
                $isMobUsed = 1;
            }


            $append = 1;
            $o_username = $username;

            if(username_exists($username)){


                if (is_numeric($phone_number) && $isMobUsed==2) {
                    $username = str_replace("+","",$countrycode).$phone_number;
                    $isMobUsed = 1;
                }else{
                    $username = sanitize_user(current(explode('@', $email)), true);
                    $isMobUsed = 2;
                }
            }

            if($isMobUsed==2 && username_exists($username) && $usernameaccep<2){
                $tname = $username;
                $check = username_exists($tname);

                if (!empty($check)) {
                    $suffix = 2;
                    while (!empty($check)) {
                        $alt_ulogin = $tname . $suffix;
                        $check = username_exists($alt_ulogin);
                        $suffix++;
                    }
                    $username = $alt_ulogin;
                } else {
                    $username = $tname;
                }
            }
        }

        if(username_exists($username)){
            return new WP_Error('error',__("Username is already in use!","digits"), "error");
        }






        // Handle password creation.
        if (empty($password)) {
            $password = wp_generate_password();

            $passaccep  = get_option("digpassaccep",1);
            if($passaccep==0){
                $password_generated = false;
            }else $password_generated = true;
        } else {
            $password_generated = false;
        }






        // Use WP_Error to handle registration errors.
        $errors = new WP_Error();

        $defaultuserrole = get_option('defaultuserrole');


        if (!empty($otp) || !empty($code) || dig_get_checkout_otp_verification()==0) {


            $digit_tapp = get_option('digit_tapp', 1);

            if(dig_get_checkout_otp_verification()==0) {
                $mob = $countrycode . $phone_number;
            }else if ($digit_tapp == 1) {

                if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                    return new WP_Error("Error", __("Error verifying Mobile Number","digits"));
                }

                $json = getUserPhoneFromAccountkit($code);



                if ($json == null) {
                    return new WP_Error("Error", __("Error verifying Mobile Number","digits"));
                }

                $phoneJson = json_decode($json, true);

                $countrycode = $phoneJson['countrycode'];
                $mob = $phoneJson['phone'];
                $phone_number = $phoneJson['nationalNumber'];

            } else {
                if (verifyOTP($countrycode, $phone_number, $otp, true)) {
                    $mob = $countrycode . $phone_number;
                } else {
                    $mob = null;
                }
            }


            if (!empty($mob)) {
                $username = sanitize_user($username,true);
                $customer_id = wp_create_user($username, $password,$email);

                update_user_meta($customer_id, 'digits_phone', $mob);
                update_user_meta($customer_id, 'digt_countrycode', $countrycode);
                update_user_meta($customer_id, 'digits_phone_no', $phone_number);

                $cd = array('ID'=>$customer_id,'role' => $defaultuserrole);
                wp_update_user($cd);


            } else {
                return new WP_Error(__("Mobile number verification failed!","digits"), "error");
            }
        } else {
            $new_customer_data = apply_filters('woocommerce_new_customer_data', array(
                'user_login' => $username,
                'user_pass' => $password,
                'user_email' => $email,
                'role' => $defaultuserrole,
            ));
            $customer_id = wp_insert_user($new_customer_data);
        }


        $new_customer_data = array(
            'ID' => $customer_id,
            'user_login' => $username,
            'user_pass' => $password,
            'user_email' => $email,
            'role' => $defaultuserrole,
        );


        if (is_wp_error($customer_id)) {

            return new WP_Error('registration-error', '<strong>' . __('ERROR', 'woocommerce') . '</strong>: ' . __('Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', 'woocommerce'));
        }


        $new_customer_data = apply_filters( 'woocommerce_new_customer_data',$new_customer_data);

        update_digp_reg_fields($reg_custom_fields,$customer_id);


        wp_update_user($new_customer_data);

        apply_filters( 'woocommerce_registration_auth_new_customer', true, $customer_id );



        do_action( 'woocommerce_created_customer', $customer_id, $new_customer_data, $password_generated );


        return $customer_id;
    }

}


function add_dig_cust_field_wc_check(){
    if(!is_user_logged_in()){
        echo '<div class="wc_check_dig_custfields">';
            show_digp_reg_fields(2);
        echo '</div>';
    }
}

add_action('woocommerce_after_checkout_registration_form', 'add_dig_cust_field_wc_check');

function dig_updateBillingPhone($phone,$customer_id){
    $phone = str_replace("+","",$phone);
    update_user_meta( $customer_id, 'billing_phone', $phone);

    $load_address = "billing";


    $customer = new WC_Customer( $customer_id );
    if ( $customer ) {
        $key = "billing_phone";

        if ( is_callable( array( $customer, "set_$key" ) ) ) {
            $customer->{"set_$key"}( wc_clean( $phone) );

        } else {
            $customer->update_meta_data( $key, wc_clean( $phone ) );
        }
        if ( WC()->customer && is_callable( array( WC()->customer, "set_$key" ) ) ) {

            WC()->customer->{"set_$key"}( wc_clean( $phone ) );
        }

        $customer->update_meta_data( 'billing_phone',$phone );
        $customer->save();
    }

    /*do_action( 'woocommerce_after_save_address_validation', $customer_id, $load_address, $address );
    if ( 0 === wc_notice_count( 'error' ) ) {
        do_action( 'woocommerce_customer_save_address', $customer_id, $load_address );
    }*/

}

add_action('woocommerce_checkout_process', 'validate_digits_wc_billing');



function dig_get_checkout_otp_verification(){
    if (current_user_can('view_register')){
        return 0;
    }else{
        return get_option('dig_mob_ver_chk_fields',1);
    }
}


function validate_digits_wc_billing() {


    if(dig_get_checkout_otp_verification()==0 || is_user_logged_in() || 'yes' !== get_option( 'woocommerce_enable_signup_and_login_from_checkout' )   || empty($_POST['mobile/email'])) return;
    $phone_number = sanitize_mobile_field_dig($_POST['mobile/email']);
    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    $otp = sanitize_text_field($_POST['digit_ac_otp']);


    $code = sanitize_text_field($_POST['code']);
    $csrf = sanitize_text_field($_POST['csrf']);


        if(is_numeric($phone_number)) {


        if (!checkwhitelistcode($countrycode)) {
            wc_add_notice(__('Invalid country code!'), 'error');
        }
        if(!empty($phone_number) && is_numeric($phone_number) && !empty($code) && !empty($csrf)){
            return;
        }
            if(getUserFromPhone($countrycode.$phone_number)){
                wc_add_notice(__('Mobile Number already in use!','digits'), 'error');
            }

        if (empty($phone_number) && empty($countrycode)) {
            wc_add_notice(__('Please enter Mobile Number!','digits'), 'error');
        }
        if (is_numeric($phone_number) && empty($countrycode)) {
            wc_add_notice(__('Please enter country code!','digits'), 'error');
        }



                $digit_tapp = get_option('digit_tapp', 1);

                if ($digit_tapp != 1) {
                    if (is_numeric($phone_number) && empty($otp)) {
                        wc_add_notice(__('Please enter OTP!', 'digits'), 'error');
                    }
                    if (!verifyOTP($countrycode, $phone_number, $otp, false)) {
                        wc_add_notice(__('Invalid OTP!', 'digits'), 'error');
                    }
                }






    }else if(!isValidEmail($phone_number)){
        wc_add_notice(__('Invalid  Email!','digits'), 'error');
    }

    if (email_exists($phone_number)) {
        wc_add_notice(__('Email already in use!','digits'), 'error');
    }

}


add_action('woocommerce_before_checkout_registration_form','digit_before_checkout_registration_form');

function digit_before_checkout_registration_form(){
    ?>
    <input type="hidden" name="isPassEnab" id="dig_wc_check_page">
    <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
    <input type="hidden" name="code" id="dig_wc_bill_code">
    <input type="hidden" name="csrf" id="dig_wc_bill_csrf">
    <?php

}



add_action( 'personal_options_update', 'dig_update_user_profile' );
add_action( 'edit_user_profile_update', 'dig_extra_profile_fields' );
function dig_update_user_profile( $user_id ) {


    if ( !current_user_can( 'edit_user', $user_id ) )
        return false;

    $digit_tapp = get_option('digit_tapp',1);


    update_dig_profile_fields($user_id);


    $p = sanitize_mobile_field_dig($_POST['mobile/email']);

    if(empty($p) && empty($_POST['email']) && !isValidEmail($_POST['email']))return;


    if(empty($p)){
        delete_user_meta( $user_id, 'digt_countrycode');
        delete_user_meta( $user_id, 'digits_phone_no');
        delete_user_meta( $user_id, 'digits_phone');
        return;
    }


    if(!current_user_can('edit_user') && !current_user_can('administrator')) {
        if ($digit_tapp == 1) {
            $csrf = sanitize_text_field($_POST['csrf']);
            $code = sanitize_text_field($_POST['code']);
            if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                return false;
            }
            $json = getUserPhoneFromAccountkit($code);
            $phoneJson = json_decode($json, true);
            $phone = $phoneJson['nationalNumber'];
            $countrycode = $phoneJson['countrycode'];
        } else {
            $otp = sanitize_text_field($_POST['profile_update_otp']);
            $nounce = $_POST['dig_nounce'];
            if (!wp_verify_nonce($nounce, 'dig_form')) {
                return;
            } else {
                $countrycode = sanitize_text_field($_POST['digt_countrycode']);
                $p = sanitize_text_field($_POST['mobile/email']);
                if (verifyOTP($countrycode, $p, $otp, true)) {
                    $phone = $p;
                }
            }

        }
    }else{
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);
        $phone = sanitize_mobile_field_dig($_POST['mobile/email']);
    }






    if(is_numeric($phone) && is_numeric($countrycode)) {
        if (getUserFromPhone($countrycode . $phone)) {

        } else if ($phone != null) {

            update_user_meta($user_id, 'digt_countrycode', $countrycode);
            update_user_meta($user_id, 'digits_phone_no', $phone);
            update_user_meta($user_id, 'digits_phone', $countrycode . $phone);


            if (get_option('dig_mob_ver_chk_fields', 1) == 0) {
                dig_updateBillingPhone($countrycode . $phone, $user_id);

            }
        }
    }

}

add_action('user_register','dig_wp_update_user');
function dig_wp_update_user( $user_id ) {

    if(!current_user_can('edit_user') && !current_user_can('administrator'))
        return false;



    if(!isset($_POST['dig_countrycodec']) || !isset($_POST['dig_user_mobile']))return;

    $countrycode = sanitize_text_field($_POST['dig_countrycodec']);
    $phone = sanitize_text_field($_POST['dig_user_mobile']);



    if(empty($phone) && empty($_POST['email']) && !isValidEmail($_POST['email']))return;

    if(empty($phone)){
        delete_user_meta( $user_id, 'digt_countrycode');
        delete_user_meta( $user_id, 'digits_phone_no');
        delete_user_meta( $user_id, 'digits_phone');
        return;
    }
    if(getUserFromPhone($countrycode.$phone)) {
        return;
    }
    if(!is_numeric($countrycode) || !is_numeric($phone))return;
    update_user_meta( $user_id, 'digt_countrycode', $countrycode );
    update_user_meta( $user_id, 'digits_phone_no', $phone );
    update_user_meta( $user_id, 'digits_phone', $countrycode.$phone );




    if(dig_get_checkout_otp_verification()==0) {
        dig_updateBillingPhone($countrycode.$phone,$user_id);
    }
}



function dig_extra_profile_fields( $user_id ) {

    if(!current_user_can('edit_user') && !current_user_can('administrator'))
        return false;





    update_dig_profile_fields($user_id);

    if(!isset($_POST['digt_countrycode']) || !isset($_POST['mobile/email']))return;

    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    $phone = sanitize_text_field($_POST['mobile/email']);

    if(empty($phone)){
        delete_user_meta( $user_id, 'digt_countrycode');
        delete_user_meta( $user_id, 'digits_phone_no');
        delete_user_meta( $user_id, 'digits_phone');
        return;
    }
    if(getUserFromPhone($countrycode.$phone)) {
        return;
    }
    if(!is_numeric($countrycode) || !is_numeric($phone))return;
    update_user_meta( $user_id, 'digt_countrycode', $countrycode );
    update_user_meta( $user_id, 'digits_phone_no', $phone );
    update_user_meta( $user_id, 'digits_phone', $countrycode.$phone );




    if(dig_get_checkout_otp_verification()==0) {
        dig_updateBillingPhone($countrycode.$phone,$user_id);
    }
}

function update_dig_profile_fields($user_id){

    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);

    $errors = new WP_Error();
    $errors = validate_digp_reg_fields($reg_custom_fields,$errors,false);

    if ($errors->get_error_code()) {return;}
    update_digp_reg_fields($reg_custom_fields,$user_id);

}



function addNewUserNameInLogin(){

    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp!=1) {

        ?>

        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide" id="dig_wc_log_otp_container" otp="1" style="display: none;">
            <label for="dig_wc_log_otp"><?php _e("OTP","digits");?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="dig_wc_log_otp" id="dig_wc_log_otp"/>
        </p>

        <?php
    }
    ?>

    <input type="hidden" name="username" id="loginuname" value="" />
    <?php
}
add_action('woocommerce_login_form_start','addNewUserNameInLogin');

/**
 * Modify the string on the login page to prompt for username or email address
 */

function wooc_extra_login() {


    $dig_login_details = digit_get_login_fields();
    $passaccep  = $dig_login_details['dig_login_password'];
    $otpaccep  = $dig_login_details['dig_login_otp'];
    ?>




    <input type="hidden" id="wc_login_cd" val="1">

    <p class="form-row form-row-wide">
    <input type="hidden" id="wc_code_dig" val="<?php if(isset($_POST['digt_countrycode'])) echo sanitize_text_field($_POST['digt_countrycode']) ?>">
        <?php
        if($otpaccep==1 || $passaccep==1) {
            ?>
            <div class="loginViaContainer">
                <?php

                if ($passaccep == 1  && $otpaccep==1) {
                    ?>
                    <span class="digor"><?php _e("OR", "digits"); ?><br/><br/></span>
                    <?php
                } else if($passaccep==0){
                    echo '<input type="hidden" value="1" id="wc_dig_reg_form" />';
                }
                if($otpaccep==1) {
                    ?>
                    <Button onclick="return false" class="woocommerce-Button button dig_wc_mobileLogin"
                            name="loginviasms"><?php _e('Login With OTP', 'digits'); ?></Button>
                    <?php
                }
                    ?>
            </div>
            <?php

            $digit_tapp = get_option("digit_tapp", 1);
            if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_wc_login_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . " <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>"; ?>
            </p>

            <?php
        }
}
add_action( 'woocommerce_login_form_end', 'wooc_extra_login' );


function wooc_extra_captcha(){
    $dig_login_details = digit_get_login_fields();
    $captcha = $dig_login_details['dig_login_captcha'];
    if($captcha==1) {
        dig_show_login_captcha(2);
    }

}
add_action( 'woocommerce_login_form', 'wooc_extra_captcha' );


/**
 * Add new register fields for WooCommerce registration.

 */
function wooc_extra_register_fields() {

    $dig_reg_details = digit_get_reg_fields();

    $nameaccep = $dig_reg_details['dig_reg_name'];

    if($nameaccep>0) {
        ?>


        <p id="dig_cs_name" class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide dig-custom-field">
            <label for="reg_billing_first_name"><?php _e('First Name', 'digits'); ?> <span
                        class="required">*</span></label>
            <input style="padding-left: 0.75em;" type="text" class="input-text"
                    name="tem_billing_first_name"
                   id="reg_billing_first_name"
                   value="<?php if (!empty($_POST['billing_first_name'])) esc_attr_e($_POST['billing_first_name']); ?>" <?php if($nameaccep==2) echo'required';?> />
        </p>
        <?php
    }
        ?>




    <input type="hidden" id="digit_name" name="billing_first_name" />
    <input type="hidden" id="digit_emailaddress" name="emailaddress" />
    <input type="hidden" id="digit_mobile" name="mobile" />






    <?php
}


function wooc_extra_register_fields_custom(){
    show_digp_reg_fields(2);
}

add_action('woocommerce_register_form','wooc_extra_register_fields');

add_action( 'woocommerce_register_form', 'wooc_add_extra_otp_reg_field',1000,1);

function wooc_add_extra_otp_reg_field()
{

    $digit_tapp = get_option('digit_tapp', 1);
    if ($digit_tapp != 1) {

        ?>

        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide"
           id="reg_billing_otp_container" style="display: none;">
            <label for="reg_billing_otp"><?php _e("OTP", "digits"); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="reg_billing_otp" id="reg_billing_otp"/>
        </p>

        <?php
    }

}
add_action( 'woocommerce_register_form', 'wooc_add_extra_reg_field');
function wooc_add_extra_reg_field(){
    $dig_reg_details = digit_get_reg_fields();



    $emailaccep = $dig_reg_details['dig_reg_email'];
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    $reqoropt = __("*",'digits');
    if($emailaccep==1){
        $reqoropt = "(".__("Optional",'digits').")";
    }

    ?>

    <input type="hidden" name="code" id="register_code" />
    <input type="hidden" name="csrf" id="register_csrf" />
    <input type="hidden" name="dig_reg_mail" id="dig_reg_mail">

    <?php
    if ($emailaccep > 0 && $mobileaccp > 0) {


        $emailmob = __('Email/Mobile Number','digits');

        if($emailaccep==2 || $mobileaccp==2){
            $emailmob = __('Email', 'digits');

        }
            ?>
        <div id="dig_cs_email" class="dig_wc_mailsecond dig-custom-field" <?php if($emailaccep>1 || $mobileaccp>1)  echo 'style="display:block;"'?>>
            <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
                <label for="secondmailormobile"><span
                        id="dig_secHolder"><?php echo $emailmob; ?></span><span> <?php echo $reqoropt; ?></span></label>
                <input class="woocommerce-Input woocommerce-Input--text input-text secmailormob"
                       name="secondmailormobile" id="secondmailormobile"
                       type="text" <?php if ($emailaccep == 2) echo "required" ?>>
            </p>
        </div>
        <?php
    }
    wooc_extra_register_fields_custom();
}




add_action("woocommerce_lostpassword_form","digit_lostpass");
function digit_lostpass(){
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp!=1) {

        ?>

        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <p class="woocommerce-form-row form-row" id="digit_forgot_otp_container" style="display: none;">
            <label for="digit_forgot_otp"><?php _e("OTP","digits");?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="dig_otp" id="digit_forgot_otp" />
            <?php
            $digit_tapp = get_option("digit_tapp",1);
            if($digit_tapp>1) echo "<div  class=\"dig_resendotp dig_wc_forgot_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>".__('Resend OTP','digits')." <span>(00:<span>". dig_getOtpTime() ."</span>)</span></div>"; ?>
        </p>

        <?php
    }
}

/*}
add_action('woocommerce_register_form_end','addNewSubmitButton');
*/

add_action('woocommerce_edit_account_form_start','wc_edit_act');
function wc_edit_act()
{
    $user = wp_get_current_user();
    ?>
    <input type="hidden" name="code" id="dig_wc_prof_code">
    <input type="hidden" name="csrf" id="dig_wc_prof_csrf">
    <input type="hidden" name="dig_old_phone" id="dig_wc_cur_phone"
           value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', $user->ID)); ?>"/>

    <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">

        <label for="account_email"><?php _e("Mobile Number","digits");?></label>
        <input type="text" class="woocommerce-Input woocommerce-Input--email input-text dig_wc_nw_phone"
               name="account_email" id="username" mob="1"
               countryCode="<?php echo esc_attr(get_the_author_meta('digt_countrycode', $user->ID)); ?>"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', $user->ID)); ?>">

    </p>

    <?php
}

add_action('woocommerce_edit_account_form','wc_edit_ac_end');

function wc_edit_ac_end(){
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp!=1) {
        ?>
        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide" id="digit_ac_otp_container"
           style="display: none;">
            <label for="digit_ac_otp"><?php _e("OTP","digits");?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="digit_ac_otp" id="digit_ac_otp"/>
        </p>
        <?php
    }
}

add_action('woocommerce_edit_account_form_end','wc_edit_ac_end_add_resend');
function wc_edit_ac_end_add_resend()
{
    $digit_tapp = get_option('digit_tapp', 1);
    if ($digit_tapp != 1) {
        if($digit_tapp>1) echo "<div  class=\"dig_resendotp dig_wc_acc_edit_resend\" style='text-align:center;' id=\"dig_man_resend_otp_btn\" dis='1'>".__('Resend OTP','digits')." <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>";
    }
}



add_action('woocommerce_register_form_end','add_dig_otp_wc');
function add_dig_otp_wc(){

    $dig_reg_details = digit_get_reg_fields();
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    if($mobileaccp==0) return;
    if($dig_reg_details['dig_reg_password']==1)echo '<input class="woocommerce-Button button otp_reg_dig_wc" name="register" value="'.__('Register with OTP','digits').'" type="submit" >';

    $digit_tapp = get_option("digit_tapp",1);
    if($digit_tapp>1) echo "<div  class=\"dig_resendotp dig_wc_register_resend\" id=\"dig_man_resend_otp_btn\" dis='1'>".__('Resend OTP','digits')." <span>(00:<span>".dig_getOtpTime()."</span>)</span></div>";
}



add_action('woocommerce_lostpassword_form','wc_dig_lost_pass');
function wc_dig_lost_pass(){
    ?>

    <input type="hidden" name="code" id="digits_wc_code"/>
    <input type="hidden" name="csrf" id="digits_wc_csrf"/>
    <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">

    <div class="changePassword" style="display: none;">
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
            <label for="reg_password"><?php _e('Password','digits');?> <span class="required">*</span></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text" name="digits_password" id="dig_wc_password" type="password">
        </p>
        <p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">
            <label for="reg_password"><?php _e('Confirm Password','digits');?> <span class="required">*</span></label>
            <input class="woocommerce-Input woocommerce-Input--text input-text" name="digits_cpassword" id="dig_wc_cpassword" type="password">
        </p>
    </div>
    <?php
}
/**
 * Validate the extra register fields.
 *
 * @param WP_Error $validation_errors Errors.
 * @param string   $username          Current username.
 * @param string   $email             Current email.
 *
 * @return WP_Error
 */

function wooc_validate_extra_register_fields( $errors, $username, $email ) {
    if ( isset( $_POST['digits_phone'] ) && empty( $_POST['digits_phone'] ) ) {
        $errors->add( 'digits_phone_error', '<strong>'.__("Error","digits") .'</strong>:'. __("Mobile Number is required!",'digits' ));
    }else{

    }
    return $errors;
}
add_filter( 'woocommerce_registration_errors', 'wooc_validate_extra_register_fields', 10, 3 );






function dig_woocommerce_lost_password_message($var){

    if(isset($_GET['reset-link-sent'])) return $var;
    return __('Lost your password? Please enter your mobile number to receive OTP or email address to get a link to create a new password.','digits');
}

add_action('woocommerce_lost_password_message','dig_woocommerce_lost_password_message');


function register_digits_exporter( $exporters ) {
    $exporters['digits-customer-data'] = array(
        'exporter_friendly_name' => __( 'Digits' ),
        'callback' => 'digits_personal_data_exporter',
    );
    return $exporters;
}

add_filter('wp_privacy_personal_data_exporters', 'register_digits_exporter',10);


function digits_personal_data_exporter( $email_address) {
    $email_address = trim( $email_address );
    $export_items = array();
    $export_data = array();

    $user = get_user_by("email",$email_address);

    if (!$user) {
        return array(
            'data' => array(),
            'done' => true,
        );
    }

    $user_id = $user->ID;
    $mob = get_the_author_meta( 'digits_phone', $user_id);

    if(!empty($mob)) {
        $export_data[] = array(
            'name' => __('Mobile Number', 'digits'),
            'value' => $mob
        );
    }

    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    foreach ($reg_custom_fields as $label => $values) {
        $label = cust_dig_filter_string($label);
        $e_value = get_user_meta($user_id, $label, true);
        if(!empty($e_value)){
           if(is_array($e_value))  $e_value = implode(", ",$e_value);
            $export_data[] = array(
                    'name' => $label,
                    'value' => $e_value
                    );
        }
    }
    if(!array_filter($export_data)) {
        return array(
            'data' => array(),
            'done' => true,
        );
    }


    $export_items[] = array(
        'group_id'    => 'user',
        'group_label' => __( 'User' ),
        'item_id'     => "user-{$user->ID}",
        'data' => $export_data,
    );

    return array(
        'data' => $export_items,
        'done' => true,
    );

}


function register_digits_plugin_eraser( $erasers ) {
    $erasers['digits-customer-data'] = array(
        'eraser_friendly_name' => __( 'Digits Data Eraser' ),
        'callback'             => 'digits_data_eraser',
    );
    return $erasers;
}
add_filter('wp_privacy_personal_data_erasers', 'register_digits_plugin_eraser', 10);

function digits_data_eraser($email_address){
    if ( empty( $email_address ) ) {
        return array(
            'items_removed'  => false,
            'items_retained' => false,
            'messages'       => array(),
            'done'           => true,
        );
    }

    $email_address = trim( $email_address );


    $user = get_user_by("email",$email_address);
    $user_id = $user->ID;


    $items_removed = 0;
    $items_retained = 0;
    $messages = array();

    $mob = get_the_author_meta( 'digits_phone', $user_id);

    if(!empty($mob)) {

        delete_user_meta($user_id,"digt_countrycode");
        delete_user_meta($user_id,"digits_phone_no");
        delete_user_meta($user_id,"digits_phone");
        $messages[] = __("Removed","digits")." user ".__("Mobile Number","digits");
        $items_removed++;
    }


    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    foreach ($reg_custom_fields as $label => $values) {
        $label = cust_dig_filter_string($label);
        $e_value = get_user_meta($user_id, $label, true);
        if(!empty($e_value)){
            delete_user_meta($user_id,$label);
            $items_removed++;
            $messages[] = __("Removed","digits")." user ".$label;
        }
    }




    $done = true;



    return array(
        'items_removed'  => $items_removed,
        'items_retained' => $items_retained,
        'messages'       => $messages,
        'done'           => $done,
    );

}



function wscu_sanitize_user ($username, $raw_username, $strict)
{
    $username = preg_replace('/\s+/', '', $username);

    $username = wp_strip_all_tags ($raw_username);

    $username = remove_accents ($username);

    $username = preg_replace ('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);

    //Kill entities
    $username = preg_replace ('/&.+?;/', '', $username);

    if ($strict)
    {
        $username = preg_replace ('|[^a-z\p{Arabic}\p{Cyrillic}0-9 _.\-@]|iu', '', $username);
    }

    $username = trim ($username);


    //Done
    return $username;
}

add_filter ('sanitize_user', 'wscu_sanitize_user', 10, 3);