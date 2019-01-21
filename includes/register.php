<?php
/**
 * Process the registration form.
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


function digits_plugin_path() {

    // gets the absolute path to this plugin directory

    return ABSPATH.str_replace(site_url()."/","",plugins_url())."/";


}







    try {
        class NWC_Meta_Box_Product_Data {



            /**
             * Save the password/account details and redirect back to the my account page.
             */
            public static function save_account_details() {


                if ( 'POST' !== strtoupper( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
                    return;
                }

                if(function_exists('wc_nocache_headers') &&
                    function_exists('wc_get_var')) {
                    wc_nocache_headers();
                    $nonce_value = wc_get_var($_REQUEST['save-account-details-nonce'], wc_get_var($_REQUEST['_wpnonce'], ''));

                    if (!wp_verify_nonce($nonce_value, 'save_account_details')) {
                        return;
                    }
                }else {

                    if (empty($_POST['action']) || 'save_account_details' !== $_POST['action']
                        || empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_account_details')) {
                        return;
                    }
                }

                $errors       = new WP_Error();
                $user         = new stdClass();

                $user->ID     = (int) get_current_user_id();
                $current_user = get_user_by( 'id', $user->ID );

                if ( $user->ID <= 0 ) {
                    return;
                }

                $account_first_name = ! empty( $_POST['account_first_name'] ) ? wc_clean( $_POST['account_first_name'] ) : '';
                $account_last_name  = ! empty( $_POST['account_last_name'] ) ? wc_clean( $_POST['account_last_name'] ) : '';
                $account_email      = ! empty( $_POST['account_email'] ) ? wc_clean( $_POST['account_email'] ) : '';
                $pass_cur           = ! empty( $_POST['password_current'] ) ? $_POST['password_current'] : '';
                $pass1              = ! empty( $_POST['password_1'] ) ? $_POST['password_1'] : '';
                $pass2              = ! empty( $_POST['password_2'] ) ? $_POST['password_2'] : '';

                $phone = ! empty( $_POST['mobile/email'] ) ? sanitize_mobile_field_dig($_POST['mobile/email']) : '';


                $otp              = ! empty( $_POST['digit_ac_otp'] ) ? wc_clean($_POST['digit_ac_otp']) : '';

                $code              = ! empty( $_POST['code'] ) ? wc_clean($_POST['code']) : '';
                $csrf              = ! empty( $_POST['csrf'] ) ? wc_clean($_POST['csrf']) : '';


                $countrycode              = ! empty( $_POST['digt_countrycode'] ) ? wc_clean($_POST['digt_countrycode']) : '';


                $save_pass          = true;

                $user->first_name   = $account_first_name;
                $user->last_name    = $account_last_name;

                // Prevent emails being displayed, or leave alone.
                $user->display_name = $user->first_name;



                if(!empty($code)){
                    if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                        return;
                    }
                }

                // Handle required fields
                $required_fields = apply_filters( 'woocommerce_save_account_details_required_fields', array(
                    'account_first_name' => __( 'First Name', 'woocommerce' ),
                    'account_last_name'  => __( 'Last Name', 'woocommerce' ),
                ) );

                foreach ( $required_fields as $field_key => $field_name ) {
                    if ( empty( $_POST[ $field_key ] ) ) {
                        wc_add_notice( '<strong>' . esc_html( $field_name ) . '</strong> ' . __( 'is a required field.', 'woocommerce' ), 'error' );
                    }
                }



                $usr = getUserFromID($user->ID);

                if(!empty($code) || !empty($otp)) {



                    $digit_tapp = get_option('digit_tapp',1);
                    if($digit_tapp==1) {

                        $json = getUserPhoneFromAccountkit($code);
                        $phoneJson = json_decode($json, true);

                        $countrycode = $phoneJson['countrycode'];
                        $mob = $phoneJson['phone'];
                        $phone = $phoneJson['nationalNumber'];
                    }else{

                        if(verifyOTP($countrycode, $phone, $otp, true)){
                            $mob = $countrycode.$phone;
                        }else{
                            $mob = null;
                        }
                    }


                    if (!empty($mob)) {
                        $tempUser = getUserFromPhone($mob);
                        if($tempUser!=null) {
                            if ($tempUser->ID != get_current_user_id()) {
                                wc_add_notice( __( 'This Mobile number is already registered.', 'digits' ), 'error' );
                            }
                        }











                        update_user_meta(get_current_user_id(), 'digits_phone', $mob);
                        update_user_meta( get_current_user_id(), 'digt_countrycode', $countrycode );
                        update_user_meta( get_current_user_id(), 'digits_phone_no', $phone );

                        if(get_option('dig_mob_ver_chk_fields',1)==0) {
                            dig_updateBillingPhone($mob,get_current_user_id());
                        }
                    }


                }elseif(!$usr && !isValidEmail($account_email)){
                    wc_add_notice( __( 'Please provide a valid email address/Mobile number.', 'digits' ), 'error' );
                }

                if ( $account_email ) {
                    $account_email = sanitize_email( $account_email );
                    if ( ! is_email( $account_email ) ) {
                        wc_add_notice( __( 'Please provide a valid email address.', 'woocommerce' ), 'error' );
                    } elseif ( email_exists( $account_email ) && $account_email !== $current_user->user_email ) {
                        wc_add_notice( __( 'This email address is already registered.', 'woocommerce' ), 'error' );
                    }
                    $user->user_email = $account_email;
                }




                if ( ! empty( $pass_cur ) && empty( $pass1 ) && empty( $pass2 ) ) {
                    wc_add_notice( __( 'Please fill out all password fields.', 'woocommerce' ), 'error' );
                    $save_pass = false;
                } elseif ( ! empty( $pass1 ) && empty( $pass_cur ) ) {
                    wc_add_notice( __( 'Please enter your current password.', 'woocommerce' ), 'error' );
                    $save_pass = false;
                } elseif ( ! empty( $pass1 ) && empty( $pass2 ) ) {
                    wc_add_notice( __( 'Please re-enter your password.', 'woocommerce' ), 'error' );
                    $save_pass = false;
                } elseif ( ( ! empty( $pass1 ) || ! empty( $pass2 ) ) && $pass1 !== $pass2 ) {
                    wc_add_notice( __( 'New passwords do not match.', 'woocommerce' ), 'error' );
                    $save_pass = false;
                } elseif ( ! empty( $pass1 ) && ! wp_check_password( $pass_cur, $current_user->user_pass, $current_user->ID ) ) {
                    wc_add_notice( __( 'Your current password is incorrect.', 'woocommerce' ), 'error' );
                    $save_pass = false;
                }

                if ( $pass1 && $save_pass ) {
                    $user->user_pass = $pass1;
                }

                // Allow plugins to return their own errors.
                do_action_ref_array( 'woocommerce_save_account_details_errors', array( &$errors, &$user ) );

                if ( $errors->get_error_messages() ) {
                    foreach ( $errors->get_error_messages() as $error ) {
                        wc_add_notice( $error, __('Error','digits') );
                    }
                }

                if ( wc_notice_count( 'error' ) === 0 ) {

                    wp_update_user( $user ) ;

                    wc_add_notice( __( 'Account details changed successfully.', 'digits' ) );

                    do_action( 'woocommerce_save_account_details', $user->ID );

                    wp_safe_redirect( wc_get_page_permalink( 'myaccount' ) );
                    exit;
                }
                unset($_POST);
            }

            public static function process_login($disCaptcha = false)
            {

                $nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
                $nonce_value = isset( $_POST['woocommerce-login-nonce'] ) ? $_POST['woocommerce-login-nonce'] : $nonce_value;

                $dig_nonce = isset( $_POST['dig_nounce'] ) ? $_POST['dig_nounce'] : '';

                if (!wp_verify_nonce($dig_nonce, 'dig_form') && !wp_verify_nonce( $nonce_value, 'woocommerce-login' )) {
                    return;
                }


                if(isset($_POST['mobile/email']) && isset($_POST['password']) && isset($_POST['digt_countrycode'])) {
                    $username = sanitize_text_field($_POST['mobile/email']);
                    $password = sanitize_text_field($_POST['password']);


                    $countrycode = sanitize_text_field($_POST['digt_countrycode']);


                    $dig_login_details = digit_get_login_fields();

                    $emailaccep = $dig_login_details['dig_login_email'];
                    $passaccep = $dig_login_details['dig_login_password'];
                    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];

                    $captcha = $dig_login_details['dig_login_captcha'];
                    if($captcha==1 && !$disCaptcha){
                        if(!dig_validate_login_captcha()) {
                            unset($_POST);
                            $m = "<div class=\"loginerrordg dig_wc_log_msg\">" . __("Please enter a valid captcha", "digits") . "</div>";
                            wc_add_notice(apply_filters('login_errors', $m), 'error');
                            return;
                        }

                    }

                    $credentials = array();
                    $secure_cookie = false;

                    $isValid = true;
                    if (is_ssl()) {
                        $secure_cookie = true;
                    }

                    if (is_numeric($username) && $mobileaccp>0) {
                        $temp_uname = sanitize_mobile_field_dig($username);

                        $userfromName = getUserFromPhone($countrycode.$temp_uname);
                        if ($userfromName != null) {
                            $username = $userfromName->user_login;
                        } else {
                            $userfromName = getUserFromPhone($temp_uname);
                            if ($userfromName != null) {
                                $username = $userfromName->user_login;
                            }
                        }
                    } else if (isValidEmail($username) && $emailaccep == 1) {
                        //$user = get_user_by('email', $username);
                        //$username = $user->user_login;
                    } else if (!username_exists($username)) {
                        $isValid = false;
                    }




                    if ($isValid) {
                        $credentials['user_login'] = $username;
                        $credentials['user_password'] = $password;
                        $credentials['remember'] = true;
                        $user_obj = wp_signon($credentials, $secure_cookie);
                    }else{
                        $user_obj = new WP_Error();
                    }




                    if (is_wp_error($user_obj) ) {
                        unset($_POST);
                        $invalid_message = __("Invalid Credentials!","digits");

                        if(is_wp_error($user_obj)){
                            $invalid_message = $user_obj->get_error_message();
                        }

                        $m = "<div class=\"loginerrordg dig_wc_log_msg\">".$invalid_message."</div>";
                        /*$message = $user_obj->get_error_message();
                        $message = str_replace( '<strong>' . esc_html( $credentials['user_login'] ) . '</strong>', '<strong>' . esc_html( $username ) . '</strong>', $message );*/

                        wc_add_notice( apply_filters( 'login_errors', $m), 'error' );


                    } else {




                        if ( ! empty( $_POST['redirect'] ) ) {
                            $redirect = $_POST['redirect'];
                        } elseif ( wc_get_raw_referer() ) {
                            $redirect = wc_get_raw_referer();
                        } else {
                            $redirect = wc_get_page_permalink( 'myaccount' );
                        }

                        wp_redirect( wp_validate_redirect( apply_filters( 'woocommerce_login_redirect', $redirect, $user_obj ), wc_get_page_permalink( 'myaccount' ) ) );


                        exit();
                    }
                }

            }
            public static function process_registration()
            {
                $nonce_value = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '';
                $nonce_value = isset( $_POST['woocommerce-register-nonce'] ) ? $_POST['woocommerce-register-nonce'] : $nonce_value;

                if (!wp_verify_nonce( $nonce_value, 'woocommerce-register' )) {
                    return;
                }


                if (isset($_POST['dig_reg_mail'])){



                    $dig_reg_details = digit_get_reg_fields();


                    $nameaccep = $dig_reg_details['dig_reg_name'];
                    $usernameaccep = $dig_reg_details['dig_reg_uname'];
                    $emailaccep = $dig_reg_details['dig_reg_email'];
                    $passaccep = $dig_reg_details['dig_reg_password'];
                    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];




                    $nonce_value = isset($_POST['_wpnonce']) ? $_POST['_wpnonce'] : '';
                    $nonce_value = isset($_POST['woocommerce-register-nonce']) ? $_POST['woocommerce-register-nonce'] : $nonce_value;

                    $validation_error = new WP_Error();


                    if(isset($_POST['tem_billing_first_name'])) {
                        $name = sanitize_text_field($_POST['tem_billing_first_name']);
                    }else $name = '';

                    $mail = sanitize_email($_POST['dig_reg_mail']);

                    $password = sanitize_text_field($_POST['password']);

                    $p = 'no' === get_option( 'woocommerce_registration_generate_password' ) ? true : false;


                    if(!$p){
                        $password = wp_generate_password();
                    }

                    $code = sanitize_text_field($_POST['code']);
                    $csrf = sanitize_text_field($_POST['csrf']);

                    $otp = sanitize_text_field($_POST['reg_billing_otp']);

                    $error = "";
                    $page = 2;

                    // Anti-spam trap
                    if (!empty($_POST['email_2'])) {
                        exit;
                    }


                    if (empty($name) && $nameaccep==2) {
                        $validation_error->add("invalidname", __("Invalid Name!", "digits"));
                    }

                    $m = '';
                    $m1 = sanitize_text_field($_REQUEST['email']);
                    $m2 = sanitize_text_field($_REQUEST['secondmailormobile']);

                    

                    if(is_numeric($m1)) $m = $m1; else if(is_numeric($m2)) $m = $m2;

                    if($mobileaccp==2){
                        if (empty($m) || !is_numeric($m) || (empty($code) && empty($otp))) {
                            $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
                        }
                    }else if($mobileaccp==1 && !empty($m)){
                        if (!is_numeric($m) || (empty($code) && empty($otp))) {
                            $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
                        }
                    }

                    if($emailaccep==2){
                        if (empty($mail) || !isValidEmail($mail)) {
                            $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
                        }
                    }else if($emailaccep==1 && !empty($mail)){
                        if (!isValidEmail($mail)) {
                            $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
                        }
                    }

                    if($mobileaccp==1 &&  $emailaccep==1){
                        if (!is_numeric($m) && $emailaccep == 0) {
                            $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
                        }

                        if (empty($code) && empty($otp) && empty($mail)) {
                            $validation_error->add("invalidmailormob", __("Invalid Email or Mobile Number", "digits"));
                        }

                        if (!empty($mail) && !isValidEmail($mail)) {
                            $validation_error->add("Mail", __("Invalid Email!", "digits"));
                        }
                        if (!empty($mail) && email_exists($mail)) {
                            $validation_error->add("MailinUse", __("Email already in use!", "digits"));
                        }


                    }






                    $useMobAsUname = get_option('dig_mobilein_uname', 0);


                    $username = 'no' === get_option( 'woocommerce_registration_generate_username' ) ? $_POST['username'] : '';
                    if(!empty($username)){
                        $ulogin = sanitize_text_field($username);
                        $check = username_exists($ulogin);
                        if(!empty($check)){
                            $validation_error->add("UsernameInUse", __("Username is already in use!","digits"));
                        }

                    }else {



                        if($useMobAsUname==1 && !empty($m)){
                            $tname = $m;
                        }else if (!empty($name)) {
                            $tname = $name;
                        }


                        if(empty($tname)) {
                            if (!empty($mail)) $tname = strstr($mail, '@', true);
                            else if (!empty($m)) $tname = $m;

                            if(!empty($tname) && username_exists($tname)){
                                $validation_error->add("mobileemail", __("Mobile/Email is already in use!", "digits"));
                            }else{
                                $ulogin = $tname;
                            }


                            

                        }else {
                            $check = username_exists($tname);
                            if (!empty($check)) {
                                $suffix = 2;
                                while (!empty($check)) {
                                    $alt_ulogin = $tname . $suffix;
                                    $check = username_exists($alt_ulogin);
                                    $suffix++;
                                }
                                $ulogin = $alt_ulogin;
                            } else {
                                $ulogin = $tname;
                            }
                        }

                    }




                    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
                    $reg_custom_fields = json_decode($reg_custom_fields, true);
                    $validation_error = validate_digp_reg_fields($reg_custom_fields,$validation_error);



                    $validation_error = apply_filters( 'woocommerce_process_registration_errors', $validation_error, $username, $password,null);


                    if ((!empty($code) || !empty($otp)) && $mobileaccp>0) {




                        $digit_tapp = get_option('digit_tapp',1);

                        if($digit_tapp==1){
                            if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                                $validation_error->add("Error", "Error!");
                            }

                            $json = getUserPhoneFromAccountkit($code);

                            $phoneJson = json_decode($json, true);

                            if ($json == null) {
                                $validation_error->add("apifail", __("Invalid API credentials!","digits"));
                            }


                            $mob = $phoneJson['phone'];
                            $phone = $phoneJson['nationalNumber'];
                            $countrycode = $phoneJson['countrycode'];
                        }else{


                            $m = sanitize_text_field($_REQUEST['email']);
                            $m2 = sanitize_text_field($_REQUEST['secondmailormobile']);
                            if(is_numeric($m)){
                                $m = sanitize_mobile_field_dig($m);
                                $countrycode = sanitize_text_field($_REQUEST['digfcountrycode']);
                                if(verifyOTP($countrycode,$m,$otp,true)){
                                    $mob = $countrycode.$m;
                                    $phone = $m;
                                }
                            }else if(is_numeric($m2)){
                                $m2 = sanitize_mobile_field_dig($m2);
                                $countrycode = sanitize_text_field($_REQUEST['digsfcountrycode']);
                                if(verifyOTP($countrycode,$m2,$otp,true)){
                                    $mob = $countrycode.$m2;
                                    $phone = $m2;
                                }
                            }


                        }




                        if(empty($ulogin)) {

                                $mobu = str_replace("+", "", $mob);
                                $check = username_exists($mobu);
                                if (!empty($check)) {
                                    $validation_error->add("MobinUse", __("Mobile number already in use!", "digits"));
                                } else {
                                    $ulogin = $mobu;
                                }

                        }

                        if(empty($ulogin)){
                            $validation_error->add("username", __("Error while generating username!", "digits"));
                        }

                        $mobuser = getUserFromPhone($mob);
                        if ($mobuser != null) {
                            $validation_error->add("MobinUse", __("Mobile number already in use!","digits"));
                        } else if (username_exists($mob)) {
                            $validation_error->add("MobinUse", __("Mobile number already in use!","digits"));
                        }

                        if (!$validation_error->get_error_code()) {


                            if(empty($password)) $password = wp_generate_password();



                            $ulogin = sanitize_user($ulogin,true);
                            $new_customer = wp_create_user($ulogin, $password, $mail);

                            if (! is_wp_error( $new_customer ) ) {
                                update_user_meta($new_customer, 'digt_countrycode', $countrycode);
                                update_user_meta($new_customer, 'digits_phone_no', $phone);
                                update_user_meta($new_customer, 'digits_phone', $countrycode . $phone);
                                $page = 1;
                            }else{
                                $validation_error->add("Error", "Error");
                            }
                        } else {


                        }
                    } else {
                        if(empty($password) && $password==2){
                            $validation_error->add("invalidpassword", __("Invalid password","digits"));
                        }else if(empty($password)) {
                            $password = wp_generate_password();
                        }
                        if(empty($ulogin)){
                            $ulogin = strstr($mail, '@', true);
                            if(username_exists($ulogin))$validation_error->add("MailinUse", __("Email is already in use!","digits"));

                        }

                        if (!$validation_error->get_error_code()) {
                            $ulogin = sanitize_user($ulogin,true);
                            $new_customer = wp_create_user($ulogin, $password, $mail);
                            $login_message = "<span class='msggreen'>User registered successfully.</span>";

                            $page = 1;
                        } else {

                        }

                    }





                    if ($validation_error->get_error_code()) {
                        $e =implode('<br />', $validation_error->get_error_messages());

                        wc_add_notice( '<strong>' . __( 'Error:', 'woocommerce' ) . '</strong> ' . $e, 'error' );


                    }else{

                        if (! is_wp_error( $new_customer ) ) {
                            $defaultuserrole = get_option('defaultuserrole', "customer");



                            $userdata = array(
                                'ID' => $new_customer,
                                'user_login' => $ulogin,
                                'user_pass'  => $password,
                                'user_email' => $mail,
                                'role'       => $defaultuserrole);
                            if(!empty($name)){
                                $userdata['first_name'] = $name;
                                $userdata['display_name'] = $name;

                            }

                            $new_customer_data = apply_filters( 'woocommerce_new_customer_data',$userdata);


                            wp_update_user($new_customer_data);

                            apply_filters( 'woocommerce_registration_auth_new_customer', true, $new_customer );



                            do_action( 'woocommerce_created_customer', $new_customer, $new_customer_data, $password );


                            update_digp_reg_fields($reg_custom_fields,$new_customer);

                            wc_set_customer_auth_cookie($new_customer);


                            if ( ! empty( $_POST['redirect'] ) ) {
                                $redirect = wp_sanitize_redirect( $_POST['redirect'] );
                            } elseif ( wc_get_raw_referer() ) {
                                $redirect = wc_get_raw_referer();
                            } else {
                                $redirect = wc_get_page_permalink( 'myaccount' );
                            }






                            wp_redirect(
                                wp_validate_redirect( apply_filters( 'woocommerce_registration_redirect', $redirect ),
                                    wc_get_page_permalink( 'myaccount' ) )

                            );

                            exit;


                        }else{
                            $validation_error->add("Error", __("Please try again","digits"));
                        }

                    }












                }
                unset($_POST);

            }



        }

        if(class_exists("WooCommerce")) {
            include_once(digits_plugin_path() . "/woocommerce/includes/class-wc-form-handler.php");
            remove_action('wp_loaded', 'WC_Form_Handler::process_registration', 20);
        }



        add_action('wp_loaded', 'NWC_Meta_Box_Product_Data::process_login', 10);


        add_action('wp_loaded', 'NWC_Meta_Box_Product_Data::save_account_details', 10);


        add_action('wp_loaded', 'NWC_Meta_Box_Product_Data::process_registration', 10);
    }catch(Exception $e){}

/**
 * Handles sending password retrieval email to customer.
 *
 * Based on retrieve_password() in core wp-login.php.
 *
 * @uses $wpdb WordPress Database object
 * @return bool True: when finish. False: on error
 */
function wc_d_retrieve_password($login) {

    if ( empty( $login ) ) {

        wc_add_notice( __( 'Enter a username or email address.', 'woocommerce' ), 'error' );

        return false;

    } else {
        // Check on username first, as customers can use emails as usernames.
        $user_data = get_user_by( 'login', $login );
    }

    // If no user found, check if it login is email and lookup user based on email.
    if ( ! $user_data && is_email( $login ) && apply_filters( 'woocommerce_get_username_from_email', true ) ) {
        $user_data = get_user_by( 'email', $login );
    }

    $errors = new WP_Error();

    do_action( 'lostpassword_post', $errors );

    if ( $errors->get_error_code() ) {
        wc_add_notice( $errors->get_error_message(), 'error' );

        return false;
    }

    if ( ! $user_data ) {
        wc_add_notice( __( 'Invalid username or email.', 'woocommerce' ), 'error' );

        return false;
    }

    if ( is_multisite() && ! is_user_member_of_blog( $user_data->ID, get_current_blog_id() ) ) {
        wc_add_notice( __( 'Invalid username or email.', 'woocommerce' ), 'error' );

        return false;
    }

    // redefining user_login ensures we return the right case in the email
    $user_login = $user_data->user_login;

    do_action( 'retrieve_password', $user_login );

    $allow = apply_filters( 'allow_password_reset', true, $user_data->ID );

    if ( ! $allow ) {

        wc_add_notice( __( 'Password reset is not allowed for this user', 'woocommerce' ), 'error' );

        return false;

    } elseif ( is_wp_error( $allow ) ) {

        wc_add_notice( $allow->get_error_message(), 'error' );

        return false;
    }

    // Get password reset key (function introduced in WordPress 4.4).
    $key = get_password_reset_key( $user_data );

    // Send email notification
    WC()->mailer(); // load email classes
    do_action( 'woocommerce_reset_password_notification', $user_login, $key );

    return true;
}
