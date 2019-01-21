<?php

/*
 * Plugin Name: Login & Signup Form Builder
 * Description: Login and signup form builder.
 * Version: 1.0
 * Author: Honestworker
 * Text Domain: azlogin
 * Requires PHP: 5.5
 * Domain Path: /languages
 */



if (!defined('ABSPATH')) {
    exit;
}
require_once('includes/functionUnicode.php');
require_once('includes/woocommerce-registration.php');
require_once('includes/userdata.php');
require_once('includes/login.php');
require_once('includes/register.php');
require_once('includes/bp.php');
require_once('update/plugin-update-checker.php');
require_once('includes/um.php');
require_once('includes/wcs.php');
require_once('includes/ihu.php');
require_once('includes/dig_geo.php');

add_action('init', function () {
    if (!session_id() || session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['dig_code']) && empty($_SESSION['dig_code'])){
        $_SESSION['dig_code'] = getCountry();
    }
}, 1);


function digits_load_plugin_textdomain()
{
    load_plugin_textdomain('digits', FALSE, dirname(plugin_basename(__FILE__)) . '/languages/');
}

add_action('plugins_loaded', 'digits_load_plugin_textdomain');


function dig_create_user_menu($admin_bar)
{
    if (!user_can(get_current_user_id(), "create_users")) return;

    $enable_createcustomeronorder = get_option('enable_createcustomeronorder');
    if ($enable_createcustomeronorder == 0) {
        return;
    }

    $args = array(
        'id' => 'dig-create-user',
        'title' => __('+ Add User', 'digits'),
        'href' => '#',
        'meta' => array(
            'target' => '_self',
            'title' => __('Add new user', 'digits'),
            'class' => 'DigCreateCustomer noaction',
        ),
    );
    $admin_bar->add_menu($args);

    createCustomerOnOrderPage(true);
}

add_action('admin_bar_menu', 'dig_create_user_menu', 100); // 10 = Position on the admin bar

function getCountry()
{
    $ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"])? $_SERVER["HTTP_CF_CONNECTING_IP"]: $_SERVER["REMOTE_ADDR"];

    $countryname = '';
    if (class_exists('WC_Geolocation')) {
        $location = WC_Geolocation::geolocate_ip('', true, false);
        $countrycode = $location['country'];
        $countryname = dig_countrycodetocountry($countrycode);
     } else {
        if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) { // WPCS: input var ok, CSRF ok.
            $country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) ); // WPCS: input var ok, CSRF ok.
        } elseif ( ! empty( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) { // WPCS: input var ok, CSRF ok.
            // WP.com VIP has a variable available.
            $country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['GEOIP_COUNTRY_CODE'] ) ) ); // WPCS: input var ok, CSRF ok.
        } elseif ( ! empty( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) { // WPCS: input var ok, CSRF ok.
            // VIP Go has a variable available also.
            $country_code = strtoupper( sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_COUNTRY_CODE'] ) ) ); // WPCS: input var ok, CSRF ok.
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);

            curl_setopt($ch, CURLOPT_URL, 'https://tools.keycdn.com/geo.json?host=' . $ip);
            $result = curl_exec($ch);
            curl_close($ch);

            $uinfo = json_decode($result);

            if (isset($uinfo->data->geo)) $uinfo = $uinfo->data->geo;
            if (isset($uinfo->country_name) && !empty($uinfo->country_name)) {
                $countryname = $uinfo->country_name;
            }
        }
    }
    if (empty($countryname)) {
        $countrycode = getCountryCode(get_option("dig_default_ccode"));
    } else {
        $countrycode = getCountryCode($countryname);
    }

    return $countrycode;
}

function getUserCountryCodeFunction()
{
    if (!isset($_SESSION['dig_code']) && empty($_SESSION['dig_code'])) {
        return getCountry();
    } else {
        return $_SESSION['dig_code'];
    }
}

function getUserCountryCode()
{
    $code = getUserCountryCodeFunction();

    if ($code == null) return "+" . getCountryCode(get_option("dig_default_ccode"));
    else return '+' . $code;
}


add_action('woocommerce_admin_order_data_after_order_details', 'dig_ccreateCustomerOnOrderPage');

function dig_ccreateCustomerOnOrderPage()
{
    createCustomerOnOrderPage(false);
}

function createCustomerOnOrderPage($noui = false)
{
    $enable_createcustomeronorder = get_option('enable_createcustomeronorder');
    $defaultuserrole = get_option('defaultuserrole');

    if ($enable_createcustomeronorder == 0) {
        return;
    }

    if (!$noui) {
        ?>

        <div class="digit-crncw button" id="DigCreateCustomer">
            <?php _e('Create New Customer', 'digits'); ?>
        </div>
    <?php }

    $dir = 'ltr';
    if (is_rtl()) $dir = 'rtl';
    ?>
    <div id="dig-ucr-container" class="dig-box">
        <div class="dig-content">
            <?php _e('Create Customer', 'digits'); ?>

            <span class="dig-cont-close">&times;</span>
            <p>
                <input type="text" id="dig-cru-firstname" name="firstname"
                       placeholder="<?php _e('First Name', 'digits'); ?>" autocomplete="off"
                       style="direction: <?php echo $dir ?>;"/>
                <input type="text" id="dig-cru-lastname" name="lastname"
                       placeholder="<?php _e('Last Name', 'digits'); ?>" autocomplete="off"
                       style="direction: <?php echo $dir ?>;"/>
                <input type="text" id="username" class="dig-cru-mailormob" name="emailormobilenumber"
                       placeholder="<?php _e('Email/Mobile Number', 'digits'); ?>"
                       autocomplete="off"/><br/>
            <div class="cancelccb button"><?php _e('Cancel', 'digits'); ?></div>
            <div class="createcustomer dig_createcustomer button button-primary"><?php _e('Create Customer', 'digits'); ?></div>
            <br/>
            </p>
        </div>
    </div>

    <?php

    wp_register_script('digits-cco', plugins_url('/assests/js/dig_cco.js', __FILE__, array('jquery'), null, true));

    $jsData = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'csrf' => wp_create_nonce('dig-create-user-order'),
        'enterallfields' => __('Enter all fields!', 'digits'),
        'invalidmailormobile' => __('Invalid Email or mobile number!', 'digits'),
        'error' => __('Error', 'digits'),
        'EmailMobileNumberAlreadyRegistered' => __('Email/Mobile number has already registered', 'digits'),
        'userregisteredsuccessfully' => __("User registered successfully", 'digits')
    );
    wp_localize_script('digits-cco', 'dig_cco_obj', $jsData);

    wp_enqueue_script('digits-cco');

    wp_enqueue_style('digits-cco-style', plugins_url('digits/assests/css/dig_cco.css',__FILE__), array(), null, 'all');

    wp_enqueue_style('digits-login-style', plugins_url('digits/assests/css/login.min.css',__FILE__), array(), null, 'all');

    digCountry();
    digits_add_style();
    digits_add_scripts();
    wp_register_script('digits-login-script', plugins_url('/assests/js/login.min.js', __FILE__, dig_deps_scripts(), null, true));

    $app = get_option('digit_api');
    $appid = "";
    if ($app !== false) {
        $appid = $app['appid'];
    }

    $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = str_replace("login=true", "", $current_url);

    $t = get_option("digits_loginred");
    if (!empty($t)) $current_url = $t;

    $dig_reg_details = digit_get_reg_fields();

    $firebase = 0;
    if (get_option('digit_tapp', 1) == 13) {
        $firebase = 1;
    }
    $dig_login_details = digit_get_login_fields();

    $jsData = array(
        'dig_sortorder' => get_option("dig_sortorder"),
        'dig_dsb' => get_option('dig_dsb',-1),
        'show_asterisk' => get_option('dig_show_asterisk',0),
        'login_mobile_accept' => $dig_login_details['dig_login_mobilenumber'],
        'login_mail_accept' => $dig_login_details['dig_login_email'],
        'login_otp_accept' => $dig_login_details['dig_login_otp'],
        'captcha_accept' => $dig_login_details['dig_login_captcha'],
        "Passwordsdonotmatch" => __("Passwords do not match!", "digits"),
        'fillAllDetails' => __('Please fill all the required details.', 'digits'),
        'accepttac' => __('Please accept terms & conditions.', 'digits'),
        'resendOtpTime' => dig_getOtpTime(),
        'useStrongPasswordString' => __('Please enter a stronger password.', 'digits'),
        'strong_pass' => dig_useStrongPass(),
        'firebase' => $firebase,
        'mail_accept' => $dig_reg_details['dig_reg_email'],
        'pass_accept' => $dig_reg_details['dig_reg_password'],
        'mobile_accept' => $dig_reg_details['dig_reg_mobilenumber'],
        'username_accept' => $dig_reg_details['dig_reg_uname'],
        'ajax_url' => admin_url('admin-ajax.php'),
        'appId' => $appid,
        'uri' => $current_url,
        'state' => wp_create_nonce('crsf-otp'),
        'left' => 0,
        'verify_mobile' => 0,
        'face' => plugins_url('digits/assests/images/face.png'),
        'cross' => plugins_url('digits/assests/images/cross.png'),
        'Registrationisdisabled' => __('Registration is disabled', 'digits'),
        'forgotPasswordisdisabled' => __('Forgot Password is disabled', 'digits'),
        'invalidlogindetails' => __("Invalid login credentials!", 'digits'),
        'invalidapicredentials' => __("Invalid API credentials!", 'digits'),
        'pleasesignupbeforelogginin' => __("Please signup before logging in.", 'digits'),
        'pleasetryagain' => __("Please try again!", 'digits'),
        'invalidcountrycode' => __("Invalid Country Code!", "digits"),
        "Mobilenumbernotfound" => __("Mobile number not found!", "digits"),
        "MobileNumberalreadyinuse" => __("Mobile Number already in use!", "digits"),
        "Error" => __("Error", "digits"),
        'Thisfeaturesonlyworkswithmobilenumber' => __('This features only works with mobile number', 'digits'),
        "InvalidOTP" => __("Invalid OTP!", "digits"),
        "ErrorPleasetryagainlater" => __("Error! Please try again later", "digits"),
        "Passworddoesnotmatchtheconfirmpassword" => __("Password does not match the confirm password!", "digits"),
        "Invaliddetails" => __("Invalid details!", "digits"),
        "InvalidEmail" => __("Invalid Email!", "digits"),
        "InvalidMobileNumber" => __("Invalid Mobile Number!", "digits"),
        "eitherenterpassormob" => __("Either enter your mobile number or click on sign up with password", "digits"),
        "login" => __("Log In", "digits"),
        "signup" => __("Sign Up", "digits"),
        "ForgotPassword" => __("Forgot Password", "digits"),
        "Email" => __("Email", "digits"),
        "Mobileno" => __("Mobile Number", "digits"),
        "ohsnap" => __("Oh Snap!", "digits"),
        "submit" => __("Submit", "digits"),
        'Submit OTP' => __('Submit OTP', 'digits')

    );
    wp_localize_script('digits-login-script', 'dig_log_obj', $jsData);

    wp_enqueue_script('digits-login-script');
}


function get_current_user_role()
{
    global $wp_roles;

    $current_user = wp_get_current_user();
    $roles = $current_user->roles;
    $role = array_shift($roles);

    return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role]) : FALSE;
}


function createUserOnOrder()
{
    if (!current_user_can('edit_user') && !current_user_can('administrator')) {
        die('0');
    }

    check_ajax_referer('dig-create-user-order', 'csrf', true);
    $enable_createcustomeronorder = get_option('enable_createcustomeronorder');
    $defaultuserrole = get_option('defaultuserrole');

    $firstname = sanitize_text_field($_REQUEST['firstname']);
    $lastname = sanitize_text_field($_REQUEST['lastname']);
    $phone = sanitize_text_field($_REQUEST['mailormob']);

    $countrycode = sanitize_text_field($_REQUEST['countrycode']);

    if (isValidMobile($phone)) {
        $mailormob = $countrycode . $phone;
    } else $mailormob = $phone;

    if ($firstname == "" || $lastname == "" || $mailormob == "") {
        die("0");
    }
    if (!isValidMobile($phone) && !isValidEmail($mailormob)) {
        die("0");
    }

    if (empty($pass)) $pass = wp_generate_password();

    $useMobAsUname = get_option('dig_mobilein_uname', 0);

    if ($useMobAsUname == 1 && isValidMobile($phone)) {
        $mobu = str_replace("+", "", $mailormob);
        $check = username_exists($mobu);
        if (!empty($check)) {
            die("0");
        } else {
            $ulogin = $mobu;
        }
    } else {
        $check = username_exists($firstname);
        if (!empty($check)) {
            $suffix = 2;
            while (!empty($check)) {
                $alt_ulogin = $firstname . $suffix;
                $check = username_exists($alt_ulogin);
                $suffix++;
            }
            $ulogin = $alt_ulogin;
        } else {
            $ulogin = $firstname;
        }
    }

    if (isValidMobile($phone)) {
        $user1 = getUserFromPhone($mailormob);
        if ($user1) {
            die("-1");
        }
        $ulogin = sanitize_user($ulogin, true);
        $new_customer = wp_create_user($ulogin, $pass);

        update_user_meta($new_customer, 'digits_phone', $mailormob);
        update_user_meta($new_customer, 'digt_countrycode', $countrycode);
        update_user_meta($new_customer, 'digits_phone_no', $phone);

        update_user_meta($new_customer, "billing_phone", $phone);
    } else {
        if (email_exists($mailormob)) {
            die("-1");
        }
        $ulogin = sanitize_user($ulogin, true);
        $new_customer = wp_create_user($ulogin, $pass, $mailormob);
        update_user_meta($new_customer, "billing_email", $mailormob);
    }

    if (is_wp_error($new_customer)) {
        die("0");
    }
    update_user_meta($new_customer, 'last_name', $lastname);
    update_user_meta($new_customer, 'first_name', $firstname);
    update_user_meta($new_customer, "billing_last_name", $lastname);
    update_user_meta($new_customer, "billing_first_name", $firstname);
    update_user_meta($new_customer, "shipping_last_name", $lastname);
    update_user_meta($new_customer, "shipping_first_name", $firstname);

    wp_update_user(array(
        'ID' => $new_customer,
        'role' => $defaultuserrole,
        'first_name' => $firstname,
        'last_name' => $lastname,
        'display_name' => $firstname));

    do_action('register_new_user', $new_customer);
    $newuser->success = "1";
    $newuser->ID = $new_customer;
    $newuser->url = get_edit_user_link($new_customer);
    echo json_encode($newuser);

    die();
}
add_action("wp_ajax_digits_create_user_order", "createUserOnOrder");

if (!function_exists('isValidMobile')) {
    function isValidMobile($mobile)
    {
        return preg_match('/^[0-9]+$/', $mobile);
    }
}
if (!function_exists('isValidEmail')) {
    function isValidEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL)
            && preg_match('/@.+\./', $email);
    }
}

/**
 * Add a settings to plugin_action_links
 */
function dig_add_plugin_action_links($links, $file)
{
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $uri = admin_url("options-general.php?page=digits_settings");
        $wsl_links = '<a href="' . $uri . '">' . __("Settings") . '</a>';

        array_unshift($links, $wsl_links);
    }

    return $links;
}
add_filter('plugin_action_links', 'dig_add_plugin_action_links', 10, 2);


add_action('wp_footer', function () {
    if(function_exists('dig_custom_modal_temp')) return;

    $users_can_register = get_option('dig_enable_registration', 1);
    $registerContent = '';
    $dig_style = 'style="display: none; opacity: 0; left: 31px; z-index: 2;top:0;"';
    $dig_main_re = "dig-modal-con-reno";

    $userCountryCode = getUserCountryCode();

    $color = get_option('digit_color');
    $bgcolor = "#4cc2fc";
    $fontcolor = 0;
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];
        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
        }
    }
    $theme = "dark";
    $themevar = "light";
    $themee = "lighte";
    $bgtype = "bgdark";
    $bgtransbordertype = "bgtransborderdark";
    $arrow = plugins_url('assests/images/left_arrow_dark.png', __FILE__);

    $color = get_option('digit_color_modal');

    $bgcolor = "rgba(6, 6, 6, 0.8)";
    $fontcolor = 0;

    $loginboxcolor = "rgba(255,255,255,1)";
    $sx = 0;
    $sy = 0;
    $sspread = 0;
    $sblur = 20;
    $scolor = "rgba(0, 0, 0, 0.3)";
    $page_type = 1;

    $fontcolor1 = "rgba(20,20,20,1)";
    $fontcolor2 = "rgba(255,255,255,1)";
    $sradius = 4;
    $left_bg_position = 'Center Center';
    $left_bg_size = 'auto';

    if ($color !== false) {
        $bgcolor = $color['bgcolor'];

        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            }
        }
        if (isset($color['sx'])) {
            $sx = $color['sx'];
            $sy = $color['sy'];
            $sspread = $color['sspread'];
            $sblur = $color['sblur'];
            $scolor = $color['scolor'];
            $fontcolor1 = $color['fontcolor1'];
            $fontcolor2 = $color['fontcolor2'];
            $loginboxcolor = $color['loginboxcolor'];
            $sradius = $color['sradius'];
        }
    } else {
        $color = get_option('digit_color');
        $loginboxcolor = $color['bgcolor'];
        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            } else {
                $fontcolor2 = "rgba(20,20,20,1)";
                $fontcolor1 = "rgba(255,255,255,1)";
            }
        }
    }

    if(isset($color['type'])){
        $page_type = $color['type'];
        if($page_type==2) {
            $left_color = $color['left_color'];
        }

        $input_bg_color = $color['input_bg_color'];
        $input_border_color = $color['input_border_color'];
        $input_text_color = $color['input_text_color'];
        $button_bg_color = $color['button_bg_color'];
        $signup_button_color = $color['signup_button_color'];
        $signup_button_border_color = $color['signup_button_border_color'];
        $button_text_color = $color['button_text_color'];
        $signup_button_text_color = $color['signup_button_text_color'];
        $left_bg_position = $color['left_bg_position'];
        $left_bg_size = $color['left_bg_size'];
    }

    $left = 9;

    $bg = get_option('digits_bg_image_modal');
    $url = "";
    if (!empty($bg)) {
        if (is_numeric($bg)) {
            $bg = wp_get_attachment_url($bg);
        }
        $url = ", url('" . $bg . "')";
    }

    $custom_css = get_option('digit_custom_css');
    $custom_css = str_replace(array("\'",'/"'),array("'",'"'),$custom_css);

    ?>

    <style>
        <?php echo $custom_css;?>
        .dig-box {
            background-color: <?php echo $bgcolor;?>;
        }

        .dig-custom-field-type-radio .dig_opt_mult_con .selected:before,
        .dig-custom-field-type-radio .dig_opt_mult_con label:before,
        .dig-custom-field-type-tac .dig_opt_mult_con .selected:before,
        .dig-custom-field-type-checkbox .dig_opt_mult_con .selected:before,
        .dig-custom-field-type-tac .dig_opt_mult_con label:before,
        .dig-custom-field-type-checkbox .dig_opt_mult_con label:before{
            background-color: <?php echo $fontcolor1;?>;
        }
        .dig-modal-con {
            border-radius: <?php echo $sradius; ?>px;
            box-shadow: <?php echo $sx."px ".$sy."px ".$sblur."px ".$sspread."px ".$scolor; ?>;
            background: linear-gradient(<?php echo $loginboxcolor; ?>,<?php echo $loginboxcolor; ?>)<?php echo $url; ?>;
            background-size: cover;

        }
        <?php
        if($page_type==2){?>
        .dig_ul_left_side {
            background: <?php echo $left_color;?>;
        }

        <?php
        $input_bg_color = $color['input_bg_color'];
        $input_border_color = $color['input_border_color'];
        $input_text_color = $color['input_text_color'];
        $button_bg_color = $color['button_bg_color'];
        $signup_button_color = $color['signup_button_color'];
        $signup_button_border_color = $color['signup_button_border_color'];
        $button_text_color = $color['button_text_color'];
        $signup_button_text_color = $color['signup_button_text_color'];
        $left_bg_position = $color['left_bg_position'];
        $left_bg_size = $color['left_bg_size'];

        ?>      
        .dig_ul_left_side{
            background-repeat: no-repeat;
            background-size: <?php echo $left_bg_size;?>;
            background-position: <?php echo $left_bg_position;?>;
        }
        .dig_ma-box .bgtransborderdark {
            color: <?php echo $signup_button_text_color; ?> !important;
        }
        .dig_ma-box .dark input[type="submit"], .dig_ma-box .lighte {
            color: <?php echo $button_text_color; ?> !important;
        }

        .dig_ma-box .dark .nice-select span,.dig_ma-box .dark a, .dig_ma-box .dark .dig-cont-close, .dig_ma-box .dark, .dig_ma-box .dark label, .dig_ma-box .dark input, .dig_ma-box .darke ,
        .dig_pgmdl_2 label{
            color: <?php echo $fontcolor1;?> !important;
        }

        .dig_pgmdl_2 .dark .nice-select span{
            color: <?php echo $input_text_color;?> !important;
        }
        .dig-custom-field .nice-select{
            background: <?php echo $input_bg_color;?>;
            padding-left: 1em;
            border: 1px solid <?php echo $input_border_color; ?> !important;
        }
        .dig_pgmdl_2 .nice-select::after {
            border-bottom: 2px solid <?php echo $input_border_color; ?> !important;
            border-right: 2px solid <?php echo $input_border_color; ?> !important;
        }
        .dig_ma-box .bgdark , .dig_ma-box .bgdark[type="submit"] {
            background-color: <?php echo $button_bg_color; ?> !important;
        }

        .dig_ma-box .bgtransborderdark {
            border: 1px solid <?php echo $signup_button_border_color; ?>;
            background: <?php echo $signup_button_color;?>;
        }

        #dig-ucr-container input[type="date"]:focus,
        #dig-ucr-container input[type="email"]:focus,
        #dig-ucr-container input[type="number"]:focus,
        #dig-ucr-container input[type="password"]:focus,
        #dig-ucr-container input[type="search"]:focus,
        #dig-ucr-container input[type="text"]:focus{
            background-color: <?php echo $input_bg_color;?> !important;
        }

        .dig_pgmdl_2 .minput .countrycodecontainer input,
        .dig_pgmdl_2 .minput input[type='date'],
        .dig_pgmdl_2 .minput input[type='number'],
        .dig_pgmdl_2 .minput input[type='password'],
        .dig_pgmdl_2 .minput textarea,
        .dig_pgmdl_2 .minput input[type='text']{
            color: <?php echo $input_text_color;?> !important;
            background: <?php echo $input_bg_color;?>;
        }

        .dig_pgmdl_2 .minput .countrycodecontainer input,
        .dig_pgmdl_2 .minput input[type='date'],
        .dig_pgmdl_2 .minput input[type='number'],
        .dig_pgmdl_2 .minput textarea,
        .dig_pgmdl_2 .minput input[type='password'],
        .dig_pgmdl_2 .minput input[type='text'],
        .dig_pgmdl_2 input:focus:invalid:focus,
        .dig_pgmdl_2 textarea:focus:invalid:focus,
        .dig_pgmdl_2 select:focus:invalid:focus{
            border: 1px solid <?php echo $input_border_color;?> !important;
        }
        .dig_ma-box .countrycodecontainer .dark{
            border-right: 1px solid <?php echo $input_border_color; ?> !important;
        }

        .dig_pgmdl_2 .minput .countrycodecontainer .dig_input_error,
        .dig_pgmdl_2 .minput .dig_input_error,
        .dig_pgmdl_2 .minput .dig_input_error[type='date'],
        .dig_pgmdl_2 .minput .dig_input_error[type='number'],
        .dig_pgmdl_2 .minput .dig_input_error[type='password'],
        .dig_pgmdl_2 .minput .dig_input_error[type='text'],
        .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
        .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
        .dig_pgmdl_2 .dig_input_error:focus:invalid:focus{
            border: 1px solid #E00000 !important;
        }
        <?php }else{ ?>

        .dig_ma-box .dark .nice-select span,.dig_ma-box .dark a, .dig_ma-box .dark .dig-cont-close, .dig_ma-box .dark, .dig_ma-box .dark label, .dig_ma-box .dark input, .dig_ma-box .darke {
            color: <?php echo $fontcolor1; ?> !important;
        }

        .dig_ma-box .dark input[type="submit"], .dig_ma-box .lighte {
            color: <?php echo $fontcolor2; ?> !important;
        }

        .dig_ma-box .bglight {
            background-color: <?php echo $fontcolor1; ?>;
        }

        .dig_ma-box .bgtransborderlight {
            border: 1px solid<?php echo $fontcolor1; ?>;
            background: transparent;
        }

        .dig_ma-box .bgdark, .dig_ma-box .bgdark[type="submit"] {
            background-color: <?php echo $fontcolor1; ?>;
        }

        .dig-custom-field .nice-select {
            border-bottom: 1px solid<?php echo $fontcolor1; ?>;
        }

        .dig_ma-box .bgtransborderdark {
            border: 1px solid <?php echo $fontcolor1; ?>;
            background: transparent;
        }

        .dig_ma-box .countrycodecontainer .dark {
            border-right: 1px solid <?php echo $fontcolor1; ?> !important;
        }
        #dig-ucr-container input[type="date"]:focus,
        #dig-ucr-container input[type="email"]:focus,
        #dig-ucr-container input[type="number"]:focus,
        #dig-ucr-container input[type="password"]:focus,
        #dig-ucr-container input[type="search"]:focus,
        #dig-ucr-container input[type="text"]:focus{
            background-color: transparent !important;
            background: transparent !important;;

        }

        <?php }
        if(is_rtl()){
            ?>
        .minput label {
            right: 0 !important;
            left: auto !important;
        }

        .dig_ma-box input[type="checkbox"], .dig_ma-box input[type="radio"] {
            margin-left: 4px;
        }

        <?php
        }
        ?>

    </style>
    <div class="dig_load_overlay">
        <div class="dig_load_content">
            <div class="dig_spinner">
                <div class="dig_double-bounce1"></div>
                <div class="dig_double-bounce2"></div>
            </div>
            <?php
            $digit_tapp = get_option("digit_tapp", 1);

            if ($digit_tapp == 1) {
                echo '<div class="dig_overlay_text">' . __('Please check the Pop-up.', 'digits') . '</div>';
            }

            $url = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

            $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $query = parse_url($url, PHP_URL_QUERY);
            if ($query) {
                $url .= '&login=true';
            } else {
                $url .= '?login=true';
            }

            ?>
        </div>
    </div>
    <?php if (!is_user_logged_in()) { ?>
        <div id="dig-ucr-container" class="dig_ma-box dig-box <?php echo $dig_main_re; if($page_type==2) echo ' dig_pgmdl_2';?>" style="display:none;">
            <div class="dig-content dig-modal-con <?php if($page_type==2) echo 'dig_ul_divd'; echo ' '.$theme; ?>">
                <?php if($page_type==2){
                    $bg_left = get_option('digits_left_bg_image_modal');

                    if (!empty($bg_left)) {
                        if (is_numeric($bg_left)) {
                            $bg_left = wp_get_attachment_url($bg_left);
                        }
                    }
                    ?>
                    <div class="dig_ul_left_side" style="background-image: url('<?php echo $bg_left; ?>');">
                    </div>

                <?php } ?>
                <div class="dig_bx_cnt_mdl">
                <span class="dig-box-login-title"><?php _e('Log In', 'digits'); ?></span>
                <span class="dig-cont-close">&times;</span>

                <?php
                $dig_login_details = digit_get_login_fields();

                $emailaccep = $dig_login_details['dig_login_email'];
                $passaccep = $dig_login_details['dig_login_password'];
                $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
                $otpaccp = $dig_login_details['dig_login_otp'];
                $captcha = $dig_login_details['dig_login_captcha'];
                if ($emailaccep == 1 && $mobileaccp == 1) {
                    $emailaccep = 2;
                }

                if ($emailaccep == 2) {
                    $emailmob = __("Email/Mobile Number", "digits");
                } else if ($mobileaccp == 1) {
                    $emailmob = __("Mobile Number", "digits");
                } else if ($emailaccep > 0) {
                    $emailmob = __("Email", "digits");
                } else {
                    $emailmob = __("Username", "digits");
                }

                if($page_type==2) dig_verify_otp_box();

                ?>
                <div class="dig-log-par">
                    <div
                            class="digloginpage">
                        <form method="post" action="<?php echo $url; ?>">
                            <div class="minput">
                                <input type="text" name="mobmail" id="dig-mobmail" value="<?php if (isset($username)) {
                                    echo $username;
                                } ?>" required/>

                                <div class="countrycodecontainer logincountrycodecontainer">
                                    <input type="text" name="countrycode"
                                           class="input-text countrycode logincountrycode <?php echo $theme; ?>"
                                           value="<?php if (isset($countrycode)) {
                                               echo $countrycode;
                                           } else echo $userCountryCode; ?>"
                                           maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"/>
                                </div>

                                <label><?php echo $emailmob; ?></label>
                                <span class="<?php echo $bgtype; ?>"></span></div>

                            <?php
                            $digit_tapp = get_option("digit_tapp", 1);
                            if ($digit_tapp > 1) {
                                ?>
                                <div class="minput" id="dig_login_otp" style="display: none;">
                                    <input type="text" name="dig_otp" id="dig-login-otp"/>
                                    <label><?php _e('OTP', 'digits'); ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                                <?php
                            }

                            if ($passaccep == 1) {
                                ?>
                                <div class="minput">
                                    <input type="password" name="password" required/>
                                    <label><?php _e('Password', 'digits'); ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>
                                <?php
                            }

                            if($captcha==1) {
                                dig_show_login_captcha(1,$bgtype);
                            }
                            ?>

                            <input type="hidden" name="dig_nounce" class="dig_nounce"
                                   value="<?php echo wp_create_nonce('dig_form') ?>">

                            <?php
                            if ($passaccep == 1) { ?>
                                <div class="logforb">
                                    <input type="submit" class="<?php echo $themee; ?> <?php echo $bgtype; ?> button"
                                           value="<?php _e('Log In', 'digits'); ?>"/>
                                    <?php
                                    $digforgotpass = get_option('digforgotpass', 1);
                                    if ($digforgotpass == 1) {
                                        ?>
                                        <div class="forgotpasswordaContainer"><a
                                                    class="forgotpassworda"><?php _e('Forgot your password?', 'digits'); ?></a>
                                        </div>
                                    <?php } ?>
                                </div>
                                <?php
                            }
                            if ($mobileaccp == 1 && $otpaccp == 1) {
                                ?>
                                <div id="dig_login_va_otp"
                                     class=" <?php echo $themee; ?> <?php echo $bgtype; ?> button loginviasms"><?php _e('Login With OTP', 'digits'); ?></div>
                                <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_log_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'> " . __('Resend OTP', 'digits') . "<span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
                                <?php
                            }

                            if ($users_can_register == 1) { ?>
                                <div class="signdesc"><?php _e('Don\'t have an account?', 'digits'); ?></div>
                                <div class="signupbutton transupbutton <?php echo $bgtransbordertype; ?>"><?php _e('Sign Up', 'digits'); ?></div>
                            <?php } ?>
                            <?php do_action('login_form'); ?>
                        </form>
                    </div>

                    <?php

                    if ($users_can_register == 1) {
                        $dig_reg_details = digit_get_reg_fields();

                        $nameaccep = $dig_reg_details['dig_reg_name'];
                        $usernameaccep = $dig_reg_details['dig_reg_uname'];
                        $emailaccep = $dig_reg_details['dig_reg_email'];
                        $passaccep = $dig_reg_details['dig_reg_password'];
                        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

                        if ($emailaccep == 1 && $mobileaccp == 1) {
                            $emailmob = __("Email/Mobile Number", "digits");
                        } else if ($mobileaccp > 0) {
                            $emailmob = __("Mobile Number", "digits");
                        } else if ($emailaccep > 0) {
                            $emailmob = __("Email", "digits");
                        } else if ($usernameaccep == 0) {
                            $usernameaccep = 1;
                            $emailmob = __("Username", "digits");
                        }

                        if ($emailaccep == 0) {
                            echo "<input type=\"hidden\" value=\"1\" id=\"disable_email_digit\" />";
                        }
                        if ($passaccep == 0) {
                            echo "<input type=\"hidden\" value=\"1\" id=\"disable_password_digit\" />";
                        }
                        ?>
                        <div class="register">
                            <form method="post" class="digits_register" action="<?php echo $url; ?>">
                                <div class="dig_reg_inputs">
                                <?php
                                if ($nameaccep > 0) {
                                    ?>
                                    <div id="dig_cs_name" class="minput">
                                        <input type="text" name="digits_reg_name" id="digits_reg_name"
                                               value="<?php if (isset($name)) {
                                                   echo $name;
                                               } ?>" <?php if ($nameaccep == 2) echo "required"; ?>/>
                                        <label><?php _e("Name", "digits"); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                <?php }
                                if ($usernameaccep > 0) {
                                    ?>
                                    <div id="dig_cs_username" class="minput">
                                        <input type="text" name="digits_reg_username" id="digits_reg_username"
                                               value="<?php if (isset($username)) {
                                                   echo $username;
                                               } ?>" <?php if ($usernameaccep == 2) echo "required"; ?>/>
                                        <label><?php _e("Username", "digits"); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                <?php }

                                $reqoropt = "";

                                if ($emailaccep > 0 || $mobileaccp > 0) {
                                    ?>
                                    <div id="dig_cs_mobilenumber" class="minput">
                                        <input type="text" name="digits_reg_mail" id="digits_reg_email"
                                               value="<?php if (isset($mob) || $emailaccep == 2 || $mobileaccp == 2) {
                                                   if ($mobileaccp == 1) $reqoropt = "(" . __("Optional", 'digits') . ")";

                                               } else if (isset($mail)) {
                                                   echo $mail;
                                               } ?>" <?php if (empty($reqoropt)) echo 'required' ?>/>
                                        <div class="countrycodecontainer registercountrycodecontainer">
                                            <input type="text" name="digregcode"
                                                   class="input-text countrycode registercountrycode  <?php echo $theme; ?>"
                                                   value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                                   placeholder="<?php echo $userCountryCode; ?>" <?php if ($emailaccep == 2 || $mobileaccp == 2) echo 'required'; ?>/>
                                        </div>
                                        <label><?php if ($emailaccep == 2 && $mobileaccp == 2) echo __('Mobile Number', 'digits'); else echo $emailmob; ?><?php echo $reqoropt; ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                    <?php
                                }
                                if ($emailaccep > 0 && $mobileaccp > 0) {
                                    $emailmob = __('Email/Mobile Number', 'digits');

                                    $reqoropt = "";
                                    if ($emailaccep == 1) {
                                        $reqoropt = "(" . __("Optional", 'digits') . ")";
                                    }
                                    if ($emailaccep == 2 || $mobileaccp == 2) {
                                        $emailmob = __('Email', 'digits');

                                    }

                                    ?>
                                    <div id="dig_cs_email" class="minput dig-mailsecond" <?php if ($emailaccep != 2 && $mobileaccp != 2) {
                                        echo 'style="display: none;"';
                                    } ?>>
                                        <input type="text" name="mobmail2"
                                               id="dig-secondmailormobile" <?php if ($emailaccep == 2) echo "required"; ?>/>
                                        <div class="countrycodecontainer secondregistercountrycodecontainer">
                                            <input type="text" name="digregscode"
                                                   class="input-text countrycode registersecondcountrycode  <?php echo $theme; ?>"
                                                   value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                                   placeholder="<?php echo $userCountryCode; ?>"/>
                                        </div>
                                        <label><span
                                                    id="dig_secHolder"><?php echo $emailmob; ?></span> <?php echo $reqoropt; ?>
                                        </label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                    <?php
                                }

                                if ($passaccep > 0) {
                                    ?>
                                    <div id="dig_cs_password" class="minput" <?php if ($passaccep == 1) echo 'style="display: none;"'; ?>>
                                        <input type="password" name="digits_reg_password"
                                               id="digits_reg_password" <?php if ($passaccep == 2) echo "required"; ?>/>
                                        <label><?php _e("Password", "digits"); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                <?php }

                                show_digp_reg_fields(1, $bgtype);

                                echo '</div>';
                                $digit_tapp = get_option("digit_tapp", 1);
                                if ($digit_tapp > 1) {
                                    ?>
                                    <div class="minput" id="dig_register_otp" style="display: none;">
                                        <input type="text" name="dig_otp" id="dig-register-otp"
                                               value="<?php if (isset($_POST['dig_otp'])) echo dig_filter_string($_POST['dig_otp']); ?>"/>
                                        <label><?php _e("OTP", "digits"); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                    <?php
                                }
                                ?>

                                <input type="hidden" name="code" id="register_code"/>
                                <input type="hidden" name="csrf" id="register_csrf"/>
                                <input type="hidden" name="dig_reg_mail" id="dig_reg_mail">
                                <input type="hidden" name="dig_nounce" class="dig_nounce"
                                       value="<?php echo wp_create_nonce('dig_form') ?>">
                                <?php
                                if ($mobileaccp > 0 || $passaccep == 0 || $passaccep == 2) {
                                    if (($passaccep == 0 && $mobileaccp == 0) || $passaccep == 2 || ($passaccep==0 && $mobileaccp>0)) {
                                        $subVal = __("Signup", "digits");
                                    } else {
                                        $subVal = __("Signup With OTP", "digits");
                                    }
                                    ?>

                                    <button class="<?php echo $themee . ' ' . $bgtype; ?> button dig-signup-otp registerbutton"
                                            value="<?php echo $subVal; ?>" type="submit"><?php echo $subVal; ?></button>
                                    <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_reg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . __("Resend OTP", "digits") . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
                                <?php } ?>

                                <?php if ($passaccep == 1) { ?>
                                    <button class="<?php echo $themee . ' ' . $bgtype; ?> button registerbutton"
                                           id="dig_reg_btn_password" attr-dis="1"
                                            value="<?php _e("Signup With Password", "digits"); ?>" type="submit">
                                        <?php _e("Signup With Password", "digits"); ?>
                                    </button>


                                <?php } ?>

                                <div class="backtoLoginContainer"><a class="backtoLogin"><?php _e("Back to login", "digits"); ?></a>
                                </div>

                                <?php
                                do_action('register_form');
                                ?>
                            </form>
                        </div>
                        <?php
                    }

                    $digforgotpass = get_option('digforgotpass', 1);

                    if ($digforgotpass == 1 && $dig_login_details['dig_login_password'] == 1) {

                        $emailmob = __("Email/Mobile Number", "digits");

                        ?>
                        <div class="forgot">
                            <form method="post" action="<?php echo $url; ?>">
                                <div class="minput forgotpasscontainer">
                                    <input type="text" name="forgotmail" id="forgotpass" required/>
                                    <div class="countrycodecontainer forgotcountrycodecontainer">
                                        <input type="text" name="countrycode"
                                               class="input-text countrycode forgotcountrycode  <?php echo $theme; ?>"
                                               value="<?php echo $userCountryCode; ?>"
                                               maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"/>
                                    </div>
                                    <label><?php echo $emailmob; ?></label>
                                    <span class="<?php echo $bgtype; ?>"></span>
                                </div>

                                <?php
                                if ($digit_tapp > 1) {
                                    ?>
                                    <div class="minput" id="dig_forgot_otp" style="display: none;">
                                        <input type="text" name="dig_otp" id="dig-forgot-otp"/>
                                        <label><?php _e('OTP', 'digits'); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                    <?php
                                }
                                ?>

                                <input type="hidden" name="code" id="digits_code"/>
                                <input type="hidden" name="csrf" id="digits_csrf"/>
                                <input type="hidden" name="dig_nounce" class="dig_nounce"
                                       value="<?php echo wp_create_nonce('dig_form') ?>">
                                <div class="changepassword">
                                    <div class="minput">
                                        <input type="password" id="digits_password" name="digits_password" required/>
                                        <label><?php _e('Password', 'digits'); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>

                                    <div class="minput">
                                        <input type="password" id="digits_cpassword" name="digits_cpassword" required/>
                                        <label><?php _e('Confirm Password', 'digits'); ?></label>
                                        <span class="<?php echo $bgtype; ?>"></span>
                                    </div>
                                </div>
                                <button type="submit"
                                       class="<?php echo $themee; ?> <?php echo $bgtype; ?> button forgotpassword"
                                        value="<?php _e('Reset Password', 'digits'); ?>"><?php _e("Reset Password", "digits"); ?></button>
                                <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_forg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . __('Resend OTP', 'digits') . "<span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
                                <div class="backtoLoginContainer"><a
                                            class="backtoLogin"><?php _e("Back to login", "digits"); ?></a>
                                </div>
                            </form>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
            $digpc = dig_get_option('dig_purchasecode');
            $ms = '';
            if (empty($digpc)) $ms = "<a class='digmsg-pow' href='#'> " . __('Powered by <span>CellphoneLogin</span>', 'digits') . "</a>";
            if (empty($digpc)) echo '<div class="dig_overlay_text dig_overlay_pwrd" style="display: block;margin-top:10px;color:#fff;">' . $ms . '</div>';
            ?>
        </div>
        </div>
        <?php
    }

    digCountry();
}
);

function digCountry()
{
    $countryList = getCountryList();
    $valCon = "";
    $currentCountry = getUserCountryCode();
    $whiteListCountryCodes = get_option("whitelistcountrycodes");

    $size = 0;
    if (is_array($whiteListCountryCodes)) $size = sizeof($whiteListCountryCodes);

    foreach ($countryList as $key => $value) {
        $ac = "";

        if (is_array($whiteListCountryCodes)) {
            if ($size > 0) {
                if (!in_array($key, $whiteListCountryCodes)) {
                    continue;
                }
            }
        }

        if ($currentCountry == '+' . $value) {
            $ac = "selected";
        }
        $valCon .= '<li class="dig-cc-visible ' . $ac . '" value="' . $value . '" country="' . strtolower($key) . '">(+' . $value . ') ' . $key . '</li>';
    }

    echo '
<ul class="digit_cs-list" style="display: none;">
' . $valCon . '
</ul>';
}

function dig_login_contents($modal, $type = 1, $page = false)
{
    $left = 9;
    $element = '';
    $registerButton = '';

    $modalBox = '';

    $dtype = 1;
    if (!$modal) {
        $dtype = 10;
    }
    $element = 'onclick="jQuery(\'this\').digits_login_modal(jQuery(this));return false;" attr-disclick="1" class="digits-login-modal"';

    wp_enqueue_style('digits-login-style', plugins_url('/assests/css/login.min.css', __FILE__), array(), null, 'all');
    wp_enqueue_script('digits-login-script', plugins_url('/assests/js/login.min.js', __FILE__, dig_deps_scripts(), null, true));
    $app = get_option('digit_api');
    $appid = "";
    if ($app !== false) {
        $appid = $app['appid'];
    }

    $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = str_replace("login=true", "", $current_url);

    $t = get_option("digits_loginred");
    if (!empty($t)) $current_url = $t;

    $diglogintrans = get_option("diglogintrans", "Login / Register");
    $digregistertrans = get_option("digregistertrans", "Register");
    $digforgottrans = get_option("digforgottrans", "Forgot your Password?");
    $digmyaccounttrans = get_option("digmyaccounttrans", "My Account");

    $digonlylogintrans = get_option("digonlylogintrans",__("Login","digits"));

    $opatt = "";
    if ($page) {
        $opatt = "data-fal='1'";
    }
    if (!is_user_logged_in()) {
        if ($type == 1) {
            return '<a href="?login=true" ' . $element . ' ' . $opatt . ' type="' . $dtype . '"><span>' . $diglogintrans . '</span></a>' . $modalBox;
        } else if ($type == 2) {
            return '<a href="?login=true&page=2" ' . $element . ' ' . $opatt . ' type="2"><span>' . $digregistertrans . '</span></a>' . $modalBox;
        } else if ($type == 3) {
            return '<a href="?login=true&page=3" ' . $element . ' ' . $opatt . ' type="3"><span>' . $digforgottrans . '</span></a>' . $modalBox;
        }else if($type==4){
            return '<a href="?login=true&page=4" ' . $element . ' ' . $opatt . ' type="4"><span>' . $digonlylogintrans . '</span></a>' . $modalBox;
        }
    } else if ($type == 1) {
        if (class_exists('WooCommerce')) {
            $url = get_permalink(get_option('woocommerce_myaccount_page_id'));
        } else if (function_exists('bp_is_active')) {
            $url = bp_core_get_user_domain(get_current_user_id()) . 'profile/';
        } else {
            $url = get_author_posts_url(get_current_user_id());
        }
        return '<a href=' . $url . ' ' . $element . ' type="10"><span>' . $digmyaccounttrans . '</span></a>';
    }
}

add_filter('wp_nav_menu_items', 'do_shortcode');

function digits_login_button()
{
    return dig_login_contents(false);
}

add_shortcode('digits-login', 'digits_login_button');

add_shortcode('dm-page', 'digits_login_button');

function digits_logout()
{
    if (is_user_logged_in()) {
        $url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $query = parse_url($url, PHP_URL_QUERY);

// Returns a string if the URL has parameters or NULL if not
        if ($query) {
            $url .= '&logout=true&lnounce=' . wp_create_nonce("lnounce");
        } else {
            $url .= '?logout=true&lnounce=' . wp_create_nonce("lnounce");
        }

        $logouttrans = get_option('diglogouttrans','Logout');
        return "<a href='" . $url . "' type='10' class=\"digits-login-modal\"><span>" . __($logouttrans, "digits") . "</span></a>";
    }
}

add_shortcode('digits-logout', 'digits_logout');
add_shortcode('dm-logout', 'digits_logout');

//add_shortcode("digits-ihc-reg-mob","dig_addregmob");
function dig_addregmob()
{
    ?>
    <div id="dig_ihc_mobcon">
        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <input type="hidden" name="code" id="dig_ihc_ea_code"/>
        <input type="hidden" name="csrf" id="dig_ihc_ea_csrf"/>
        <input type="hidden" id="dig_ihc_reg_cqr"/>

        <input type="hidden" name="current_mob" id="dig_ihc_current_mob"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>"/>
        <?php
        $digit_tapp = get_option('digit_tapp', 1);
        if ($digit_tapp != 1) {
            ?>
            <div id="dig_ihc_mobotp" class="iump-form-line-register iump-form-text" style="display:none;">
                <input type="text" id="dig_ihc_otp" name="dig_ihc_mobileno"
                       placeholder="<?php _e("OTP", "digits"); ?>"/>
            </div>
        <?php } ?>
    </div>
    <?php
}

function dig_addmobile()
{
    ?>
    <div id="dig_ihc_mobcon">
        <input type="hidden" name="dig_nounce" class="dig_nounce" value="<?php echo wp_create_nonce('dig_form') ?>">
        <input type="hidden" name="code" id="dig_ihc_ea_code"/>
        <input type="hidden" name="csrf" id="dig_ihc_ea_csrf"/>

        <input type="hidden" name="dig_ihc_current_mob" id="dig_ihc_current_mob"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>"/>
        <div class="iump-form-line-register iump-form-text">
            <label style="display:none;"><?php _e("Mobile Number", "digits"); ?></label>
            <input type="text" id="username" name="dig_ihc_mobileno"
                   placeholder="<?php _e("Mobile Number", "digits"); ?>" mob="1"
                   countryCode="<?php echo esc_attr(get_the_author_meta('digt_countrycode', get_current_user_id())); ?>"
                   value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>"/>
        </div>

        <input type="hidden" name="current_mob" id="dig_bp_current_mob"
               value="<?php echo esc_attr(get_the_author_meta('digits_phone_no', get_current_user_id())); ?>"/>
        <?php
        $digit_tapp = get_option('digit_tapp', 1);
        if ($digit_tapp != 1) {
            ?>
            <div id="dig_ihc_mobotp" class="iump-form-line-register iump-form-text" style="display:none;">
                <input type="text" id="dig_ihc_otp" name="dig_ihc_otp" placeholder="<?php _e("OTP", "digits"); ?>"/>
            </div>
        <?php } ?>
    </div>
    <?php
}

function digits_modal_login()
{
    return dig_login_contents(true);
}

add_shortcode('digits-modal-login', 'digits_modal_login');

add_shortcode('dm-modal', 'digits_modal_login');

function digits_modal_registration()
{
    return dig_login_contents(true, 2);

}

function digits_modal_forgotpass()
{
    return dig_login_contents(true, 3);
}

add_shortcode('digits-registration', 'digits_modal_registration');
add_shortcode('digits-forgot-password', 'digits_modal_forgotpass');

add_shortcode('dm-signup-modal', 'digits_modal_registration');
add_shortcode('dm-registration-modal', 'digits_modal_registration');
add_shortcode('dm-forgot-password-modal', 'digits_modal_forgotpass');

function digits_page_registration()
{
    return dig_login_contents(true, 2, true);

}

function digits_page_forgotpass()
{
    return dig_login_contents(true, 3, true);
}

add_shortcode('digits-page-registration', 'digits_page_registration');
add_shortcode('digits-page-forgot-password', 'digits_page_forgotpass');

add_shortcode('dm-signup-page', 'digits_page_registration');
add_shortcode('dm-registration-page', 'digits_page_registration');
add_shortcode('dm-forgot-password-page', 'digits_page_forgotpass');

function digits_modal_onlylogin()
{
    return dig_login_contents(true, 4);
}

function digits_page_onlylogin()
{
    return dig_login_contents(false, 4,true);
}

add_shortcode('dm-login-modal', 'digits_modal_onlylogin');
add_shortcode('dm-login-page', 'digits_page_onlylogin');

add_action('admin_menu', 'digits_admin_menus');
add_action('admin_init', 'digits_setup_wizard');

add_action('admin_init', 'digits_redirect');

register_activation_hook(__FILE__, 'digits_activate');

function digits_activate()
{
    if (version_compare(PHP_VERSION, '5.5', '<') && is_admin()) {
        $version_required = sprintf('<div><p>You are currently using outdated version of PHP %1$s. Please update your PHP to newer version, Digits requires PHP v5.5 or higher to work. </p></div>', PHP_VERSION);
        wp_die($version_required);
    }

    if (!function_exists('curl_version')) {
        wp_die(__('<div><p><b>Fatal Error</b>: Digits requires curl to work correctly. </p></div>','digits'));
    }

    dig_pcd_act();
    add_option('digits_do_activation_redirect', true);
}

function digits_redirect()
{
    if (get_option('digits_do_activation_redirect', false) == true) {
        update_option('digits_do_activation_redirect', false);
        wp_redirect(esc_url_raw(admin_url("index.php?page=digits-setup&step=page")));
    }
}

function add_digits_setting_page()
{
    $m = add_submenu_page(
        'options-general.php',
        'Azlogin',
        'Azlogin',
        'manage_options',
        'digits_settings',
        'digits_plugin_settings'
    );
    add_action( 'admin_print_styles-' . $m, 'dig_add_gs_css' );
}
add_action("admin_menu", "add_digits_setting_page");

function dig_add_gs_css(){
    wp_enqueue_style('google-roboto-regular', dig_fonts());
    nice_select_scr();
    wp_enqueue_style('digits-gs-style', plugins_url('/assests/css/gs.css', __FILE__), array('google-roboto-regular', 'nice-select'), null, 'all');
    digits_add_style();
}

function digits_plugin_settings()
{
    ?>
    <style>.update-nag, .updated{
            display: none;
        }</style>
    <div class="digits_admim_conf">
        <?php
        if (isset($_GET['tab'])) {
            $active_tab = sanitize_text_field($_GET['tab']);
        } else {
            $active_tab = 'apisettings';
        } // end if

        digits_update_data(0);

        $digpc = dig_get_option('dig_purchasecode');

        if (empty($digpc)) if ($active_tab == "customize") $active_tab = 'activate';
        ?>

        <div class="dig_load_overlay">
            <div class="dig_load_content">
                <div class="dig_spinner">
                    <div class="dig_double-bounce1"></div>
                    <div class="dig_double-bounce2"></div>
                </div>
            </div>
        </div>

        <div class="dig_big_preset_show">
            <img src="" />
            </div>
        <div class="dig_load_overlay_gs">
            <div class="dig_load_content">

                <div class="circle-loader">
                    <div class="checkmark draw"></div>
                </div>

            </div>
        </div>

        <div class="dig_log_setge">
            <div class="dig_ad_left_side">
                <div class="dig_ad_left_side_content">

                    <?php
                    if (!empty($digpc)) echo '<input type="hidden" id="dig_activated" value="1" />'; ?>

                    <div class="dig-tab-wrapper">

                        <ul class="dig-tab-ul">
                        <li><a href="?page=digits_settings&tab=apisettings"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'apisettings' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="apisettingstab"><?php _e('Gateway', 'digits'); ?></a></li>

                        <li><a href="?page=digits_settings&tab=configure"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'configure' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="configuretab"><?php _e('General', 'digits'); ?></a></li>

                        <li><a href="?page=digits_settings&tab=customfields"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'customfields' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="customfieldstab"><?php _e('Form', 'digits'); ?></a></li>

                        <li><a href="?page=digits_settings&tab=customize"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'customize' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="customizetab" acr="1"><?php _e('Style', 'digits'); ?></a></li>

                        <li><a href="?page=digits_settings&tab=translations"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'translations' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="translationstab"><?php _e('Translations', 'digits'); ?></a></li>

                        <li><a href="?page=digits_settings&tab=shortcodes"
                        class="updatetabview dig-nav-tab <?php echo $active_tab == 'shortcodes' ? 'dig-nav-tab-active' : ''; ?>"
                        tab="shortcodestab"><?php _e('Shortcodes', 'digits'); ?></a></li>
                        </ul>
                    </div>

                    <form method="post" autocomplete="off" id="digits_setting_update" class="dig_activation_form"
                        enctype="multipart/form-data">
                        <div data-tab="apisettingstab" class="dig_ad_in_pt apisettingstab digtabview <?php echo $active_tab == 'apisettings' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digits_api_settings();
                            ?>
                        </div>
                        <div data-tab="configuretab" class="dig_ad_in_pt configuretab digtabview <?php echo $active_tab == 'configure' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php
                            digits_configure_settings();
                            ?>
                        </div>

                        <div data-tab="customizetab" class="dig_ad_in_pt customizetab digtabview <?php echo $active_tab == 'customize' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php

                            digit_customize(false);

                            ?>

                        </div>

                        <div data-tab="translationstab" class="dig_ad_in_pt translationstab digtabview <?php echo $active_tab == 'translations' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digit_shortcodes_translations(); ?>
                        </div>
                        <div data-tab="shortcodestab" class="dig_ad_in_pt shortcodestab digtabview <?php echo $active_tab == 'shortcodes' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digit_shortcodes(false); ?>

                        </div>
                        <div data-tab="activatetab" class="dig_ad_in_pt activatetab digtabview <?php echo $active_tab == 'activate' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digit_activation(false); ?>
                        </div>

                        <div data-tab="customfieldstab" class="dig_ad_in_pt customfieldstab digtabview <?php echo $active_tab == 'customfields' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digit_customfields(); ?>
                        </div>

                        <div data-tab="addonstab" class="dig_ad_in_pt addonstab digtabview <?php echo $active_tab == 'addons' ? 'digcurrentactive' : '" style="display:none;'; ?>">
                            <?php digit_addons(); ?>
                        </div>

                        <Button type="submit" class="dig_ad_submit" disabled><?php _e('Save Changes', 'digits'); ?></Button>
                    </form>
                    <?php

                    wp_register_script('digits-upload-script', plugins_url('/assests/js/upload.js', __FILE__, array('jquery'), null, true));

                    $jsData = array(
                        'logo' => get_option('digits_logo_image'),
                        'selectalogo' => __('Select a logo', 'digits'),
                        'usethislogo' => __('Use this logo', 'digits'),
                        'changeimage' => __('Change Image', 'digits'),
                        'selectimage' => __('Select Image', 'digits'),
                        'removeimage' => __('Remove Image', 'digits'),
                    );
                    wp_localize_script('digits-upload-script', 'dig', $jsData);

                    wp_enqueue_script('digits-upload-script');
                    wp_enqueue_media();

                    dig_config_scripts();
                    ?>
                </div>
            </div>
        </div>
        <?php
        if (is_rtl()) {
            echo '<input type="hidden" id="is_rtl" value="1"/>';
        }
        ?>
        <style type="text/css">
            <?php if(is_rtl()){
            ?>
            .digits_admim_conf .dig_ad_side {
                position: absolute;
                direction: ltr;
            }

            .dig_ad_flt_btn {
                position: relative !important;
                top: 0;
                right: 0;
                direction: ltr;
            }

            .digits_admim_conf .dig_ad_submit[type="submit"] {
                left: 45px !important;
            }

            <?php
            }?>
            #wpbody-content {
                padding-bottom: 10px;
            }

            #wpfooter {
                display: none;;
            }
        </style>
    </div><!-- /.wrap -->
    <?php

} // end

function dig_fonts()
{
    $fonts = array(
        "Roboto:700,500,500i,400,200,300"
    );

    $fonts_collection = add_query_arg(array(

        "family" => urlencode(implode("|", $fonts)),

    ), 'https://fonts.googleapis.com/css');

    return $fonts_collection;
}

function getGatewayName($digit_tapp)
{
    switch ($digit_tapp) {
        case 2:
            return "Twilio";
            break;
        case 3:
            return "Msg91";
            break;
        case 4:
            return "Yunpian";
            break;
    }
}


function getGateWayArray()
{
    $smsgateways = array(
        'Clickatell' => array('value' => 5, 'inputs' => array(__('API Key') => array('text' => true, 'name' => 'api_key'), __('From') => array('text' => true, 'name' => 'from', 'optional' => 1))),
        'ClickSend' => array('value' => 6, 'inputs' => array(__('API Username') => array('text' => true, 'name' => 'apiusername'), __('API Key') => array('text' => true, 'name' => 'apikey'), __('From') => array('text' => true, 'name' => 'from'))),
        'ClockWork' => array('value' => 7, 'inputs' => array(__('ClockWork API') => array('text' => true, 'name' => 'clockworkapi'), __('From') => array('text' => true, 'name' => 'from'))),
        'Kaleyra' => array('value' => 15, 'inputs' => array(__('API Key') => array('text' => true, 'name' => 'api_key'), __('Sender ID') => array('text' => true, 'name' => 'sender_id'))),
        'MessageBird' => array('value' => 8, 'inputs' => array(__('Access Key') => array('text' => true, 'name' => 'accesskey'), __('Originator') => array('text' => true, 'name' => 'originator'))),
        'Mobily.ws' => array('value' => 9, 'inputs' => array(__('Mobile') => array('text' => true, 'name' => 'mobile'), __('Password') => array('text' => true, 'name' => 'password'), __('Sender') => array('text' => true, 'name' => 'sender'))),
        'Nexmo' => array('value' => 10, 'inputs' => array(__('API Key') => array('text' => true, 'name' => 'api_key'), __('API Secret') => array('text' => true, 'name' => 'api_secret'), __('From') => array('text' => true, 'name' => 'from'))),
        'Pilvo' => array('value' => 11, 'inputs' => array(__('Auth ID') => array('text' => true, 'name' => 'auth_id'), __('Auth Token') => array('text' => true, 'name' => 'auth_token'), __('Sender') => array('text' => true, 'name' => 'sender_id'))),
        'SMSAPI' => array('value' => 12, 'inputs' => array(__('Token') => array('text' => true, 'name' => 'token'), __('From') => array('text' => true, 'name' => 'from'))),
        'FireBase' => array('value' => 13, 'inputs' =>
            array(__('API Key') => array('text' => true, 'name' => 'api_key'),
                __('Auth Domain') => array('text' => true, 'name' => 'authdomain'),
                __('Database URL') => array('text' => true, 'name' => 'databaseurl'),
                __('Project ID') => array('text' => true, 'name' => 'projectid'),
                __('Storage Bucket') => array('text' => true, 'name' => 'storagebucket'),
                __('Messaging Sender ID') => array('text' => true, 'name' => 'messagingsenderid'))),
        'Unifonic' => array('value' => 14, 'inputs' =>
            array(__('AppSid') => array('text' => true, 'name' => 'appsid'),
                __('Sender ID') => array('text' => true, 'name' => 'senderid', 'optional' => 1))),

        'Melipayamak' => array('value' => 16, 'inputs' => array(__('Username') => array('text' => true, 'name' => 'username'),
            __('Password') => array('text' => true, 'name' => 'password'),
            __('From') => array('text' => true, 'name' => 'from')
        )),

//        'LimeCellular'=>array('value'=>999,'inputs'=>array(__('User Name')=>array('text'=>true,'name'=>'user'), __('API ID')=>array('text'=>true,'name'=>'api_id'), __('Short Code')=>array('text'=>true,'name'=>'short_code','optional'=>1))),
    );

    return $smsgateways;
}

function digits_api_settings()
{
    $smsgateways = getGateWayArray();

    $digit_tapp = get_option('digit_tapp', 1);
    $app = get_option('digit_api');
    $appid = "";
    $appsecret = "";
    $accountkitversion = "";
    if ($app !== false) {
        $appid = $app['appid'];
        $appsecret = $app['appsecret'];
        if (isset($app['accountkitversion'])) {
            $accountkitversion = $app['accountkitversion'];
        } else $accountkitversion = "v1.1";
    }

    $tiwilioapicred = get_option('digit_twilio_api');
    $twiliosid = "";
    $twiliotoken = "";
    $twiliosenderid = "";

    if ($tiwilioapicred !== false) {
        $twiliosid = $tiwilioapicred['twiliosid'];
        $twiliotoken = $tiwilioapicred['twiliotoken'];
        $twiliosenderid = $tiwilioapicred['twiliosenderid'];
    }

    $msg91apicred = get_option('digit_msg91_api');
    $msg91authkey = "";
    $msg91senderid = "";

    $msg91route = 1;
    if ($msg91apicred !== false) {
        $msg91authkey = $msg91apicred['msg91authkey'];
        $msg91senderid = $msg91apicred['msg91senderid'];

        $msg91route = $msg91apicred['msg91route'];

        if (empty($msg91route)) {
            $msg91route = 2;
        }
    }

    $yunpianapi = get_option('digit_yunpianapi');

    $gatewayName = getGatewayName($digit_tapp);

    ?>
    <table class="form-table">
        <tr>
            <th scope="row" valign="top" style="vertical-align: top;"><label
                        for="digit_tapp"><?php _e('SMS Gateway', 'digits'); ?> </label></th>
            <td>
                <select name="digit_tapp" class="digit_tapp" id="digit_tapp" autocomplete="off">
                    <option value="1" <?php if ($digit_tapp == 1) {
                        echo 'selected="selected"';
                    } ?> han="facebook" data-test="0">Facebook (<?php _e('Free', 'digits'); ?>)
                    </option>
                    <option value="13" <?php if ($digit_tapp == 13) {
                        echo 'selected="selected"';
                    } ?> han="firebase" data-test="0">FireBase (<?php _e('Free', 'digits'); ?>)
                    </option>

                    <option value="2" <?php if ($digit_tapp == 2) {
                        echo 'selected="selected"';
                    } ?> han="twilio">Twilio
                    </option>
                    <option value="3" <?php if ($digit_tapp == 3) {
                        echo 'selected="selected"';
                    } ?> han="msg91">Msg91
                    </option>
                    <?php
                    foreach ($smsgateways as $name => $details) {
                        $sel = "";
                        $value = $details['value'];
                        if ($value == 13) continue;
                        if ($value == $digit_tapp) {
                            $gatewayName = $name;
                            $sel = 'selected="selected"';
                        }
                        echo '<option value="' . $value . '" ' . $sel . ' han="' . strtolower(str_replace(".", "_", strtolower($name))) . '">' . $name . '</option>';
                    }
                    ?>
                    <option value="4" <?php if ($digit_tapp == 4) {
                        echo 'selected="selected"';
                    } ?> han="yunpian">Yunpian
                    </option>
                </select><br/>
                <div><span style="<?php if ($digit_tapp == 1 || $digit_tapp == 13) echo 'display:none;'; ?>"
                           class="dig_current_gateway"><?php printf(__('You should have paid <span>%s</span> plan to use this.', 'digits'), $gatewayName); ?></span>
                </div>
            </td>
        </tr>


        <tr class="facebookcred" <?php if ($digit_tapp != 1) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="appid"><?php _e('App ID', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="appid" name="appid" class="regular-text" value="<?php echo $appid; ?>"
                       placeholder="<?php _e('App ID', 'digits'); ?>"
                       autocomplete="off" <?php if ($digit_tapp == 1) echo 'required'; ?> />
            </td>
        </tr>
        <tr class="facebookcred" <?php if ($digit_tapp != 1) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="appsecret"><?php _e('AccountKit App Secret', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="appsecret" name="appsecret" class="regular-text"
                       value="<?php echo $appsecret; ?>" autocomplete="off"
                       placeholder="<?php _e('App Secret', 'digits'); ?>" <?php if ($digit_tapp == 1) echo 'required'; ?>/>
            </td>
        </tr>

        <tr class="twiliocred" <?php if ($digit_tapp != 2) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="twiliosid"><?php _e('Account SID', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="twiliosid" name="twiliosid" class="regular-text"
                       value="<?php echo $twiliosid; ?>"
                       placeholder="<?php _e('Account SID', 'digits'); ?>"
                       autocomplete="off" <?php if ($digit_tapp == 2) echo 'required'; ?>/>
            </td>
        </tr>
        <tr class="twiliocred" <?php if ($digit_tapp != 2) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="twiliotoken"><?php _e('Auth Token', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="twiliotoken" name="twiliotoken" class="regular-text"
                       value="<?php echo $twiliotoken; ?>" autocomplete="off"
                       placeholder="<?php _e('Auth Token', 'digits'); ?>" <?php if ($digit_tapp == 2) echo 'required'; ?>/>
            </td>
        </tr>
        <tr class="twiliocred" <?php if ($digit_tapp != 2) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="twiliosenderid"><?php _e('Sender ID', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="twiliosenderid" name="twiliosenderid" class="regular-text"
                       value="<?php echo $twiliosenderid; ?>" autocomplete="off"
                       placeholder="<?php _e('Sender ID', 'digits'); ?>" <?php if ($digit_tapp == 2) echo 'required'; ?>/>
            </td>
        </tr>

        <tr class="msg91cred" <?php if ($digit_tapp != 3) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="msg91authkey"><?php _e('Authentication Key', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="msg91authkey" name="msg91authkey" class="regular-text"
                       value="<?php echo $msg91authkey; ?>" autocomplete="off"
                       placeholder="<?php _e('Authentication Key', 'digits'); ?>" <?php if ($digit_tapp == 3) echo 'required'; ?>/>
            </td>
        </tr>
        <tr class="msg91cred" <?php if ($digit_tapp != 3) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="msg91route"><?php _e('ROUTE', 'digits'); ?> </label></th>
            <td>
                <select name="msg91route">
                    <option value="1" <?php if ($msg91route == 1) echo "selected='selected'"; ?>><?php _e('SendOTP', 'digits'); ?></option>
                    <option value="2" <?php if ($msg91route == 2) echo "selected='selected'"; ?>><?php _e('Transactional', 'digits'); ?></option>
                </select>
                <p class="dig_ecr_desc">
                    If your website users are only from <b>India</b> then you can use <b>Transactional</b> or
                    <b>SendOTP</b> route. But if your users are from any other <b>country than India</b> then you should
                    only use <b>SendOTP</b> route.
                </p>
            </td>
        </tr>
        <tr class="msg91cred" <?php if ($digit_tapp != 3) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="msg91senderid"><?php _e('Sender ID', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="msg91senderid" name="msg91senderid" class="regular-text"
                       value="<?php echo $msg91senderid; ?>" autocomplete="off"
                       placeholder="<?php _e('Sender ID', 'digits'); ?>"
                       maxlength="6" <?php if ($digit_tapp == 3) echo 'required'; ?>/>
            </td>
        </tr>

        <tr class="yunpiancred" <?php if ($digit_tapp != 4) {
            echo 'style="display:none;"';
        } ?> >
            <th scope="row"><label for="yunpianapikey"><?php _e('API Key', 'digits'); ?> </label></th>
            <td>
                <input type="text" id="yunpianapikey" name="yunpianapikey" class="regular-text"
                       value="<?php echo $yunpianapi; ?>" autocomplete="off"
                       placeholder="<?php _e('API Key', 'digits'); ?>" <?php if ($digit_tapp == 4) echo 'required'; ?>/>
                <p class="dig_ecr_desc"><?php _e('Please keep this message template similar to the one on Yunpian, just replace #code# with %OTP% otherwise messages will not be sent.', 'digits'); ?></p>
            </td>
        </tr>

        <?php
        foreach ($smsgateways as $name => $details) {
            $value = $details['value'];
            $name = str_replace(".", "_", strtolower($name));

            $gatewayCreds = get_option('digit_' . strtolower($name));

            foreach ($details['inputs'] as $inputLabel => $input) {
                $inputname = $name . "_" . $input['name'];
                $inputValue = $gatewayCreds[$input['name']];

                $optional = 0;
                if (isset($input['optional'])) $optional = $input['optional'];

                ?>
                <tr class="<?php echo $name; ?>cred" <?php if ($digit_tapp != $value) {
                    echo 'style="display:none;"';
                } ?> >
                    <th scope="row"><label for="<?php echo $inputname; ?>"> <?php _e($inputLabel, 'digits');
                            if ($optional == 1) echo ' (Optional)'; ?> </label></th>
                    <td>
                        <input type="text" id="<?php echo $inputname; ?>" name="<?php echo $inputname; ?>"
                               class="regular-text"
                               value="<?php echo $inputValue; ?>" autocomplete="off"
                               placeholder="<?php _e($inputLabel, 'digits'); ?>" <?php if ($digit_tapp == $value && $optional == 0) echo 'required'; ?>
                               dig-optional="<?php echo $optional; ?>"/>
                    </td>
                </tr>
                <?php
            }
        }
        ?>
    </table>

    <?php
    if(isset($_GET['page']) && $_GET['page']=='digits-setup'){
        return;
    }
    ?>

    <div class="dig_api_test" <?php if($digit_tapp==1 || $digit_tapp==13) echo 'style="display:none;"'?>>
        <div class="dig_desc_sep_pc"></div>

        <div id="dig_call_test_api">
            <div><?php _e('TEST GATEWAY SETTINGS','digits'); ?></div>
            <div class="dig_test_mob_ho">

                <input data-dig-mob="1" countryCode="<?php echo esc_attr( get_the_author_meta( 'digt_countrycode', get_current_user_id()) ); ?>" dig-save="0" id="username" f-mob="1" reg="2" only-mob="1" type="text" placeholder="<?php _e('Your Mobile Number','digits');?>" id="dig_test_number"
                        value="<?php echo esc_attr( get_the_author_meta( 'digits_phone_no', get_current_user_id() ) ); ?>"/>
                <div id="dig_call_test_api_btn"><?php _e('Test','digits');?></div>
            </div>
        </div>

        <div id="dig_call_test_response">
            <div id="dig_call_test_response_head"><?php _e('Response','digits');?></div>
            <div id="dig_call_test_response_msg"></div>
        </div>
    </div>
    <?php
}

function digit_customize($isWiz = true)
{
    $color = get_option('digit_color');
    $bgcolor = "#4cc2fc";
    $fontcolor = 0;

    $loginboxcolor = "rgba(255,255,255,1)";
    $sx = 0;
    $sy = 2;
    $sspread = 0;
    $sblur = 4;
    $scolor = "rgba(0, 0, 0, 0.5)";

    $fontcolor2 = "rgba(255,255,255,1)";
    $fontcolor1 = "rgba(20,20,20,1)";

    $sradius = 4;

    $color_modal = get_option('digit_color_modal');

    $input_bg_color = "rgba(0,0,0,0)";
    $input_border_color = "rgba(0,0,0,0)";
    $input_text_color = "rgba(0,0,0,0)";
    $button_bg_color = "rgba(0,0,0,0)";
    $signup_button_color = "rgba(0,0,0,0)";
    $signup_button_border_color = "rgba(0,0,0,0)";
    $button_text_color = "rgba(0,0,0,0)";
    $signup_button_text_color = "rgba(0,0,0,0)";

    $page_type = 1;
    $modal_type = 1;
    $leftcolor = "rgba(255,255,255,1)";

    $left_bg_position = 'Center Center';
    $left_bg_size = 'auto';
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];

        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            }
        }
        if (isset($color['sx'])) {
            $sx = $color['sx'];
            $sy = $color['sy'];
            $sspread = $color['sspread'];
            $sblur = $color['sblur'];
            $scolor = $color['scolor'];
            $fontcolor1 = $color['fontcolor1'];
            $fontcolor2 = $color['fontcolor2'];
            $loginboxcolor = $color['loginboxcolor'];
            $sradius = $color['sradius'];
            $backcolor = $color['backcolor'];
        }
        if(isset($color['type'])){
            $page_type = $color['type'];
            if($page_type==2){
                $leftcolor = $color['left_color'];
            }
            $modal_type = $color_modal['type'];

            $input_bg_color = $color['input_bg_color'];
            $input_border_color = $color['input_border_color'];
            $input_text_color = $color['input_text_color'];
            $button_bg_color = $color['button_bg_color'];
            $signup_button_color = $color['signup_button_color'];
            $signup_button_border_color = $color['signup_button_border_color'];
            $button_text_color = $color['button_text_color'];
            $signup_button_text_color = $color['signup_button_text_color'];
            $left_bg_position = $color['left_bg_position'];
            $left_bg_size = $color['left_bg_size'];
        }
    }
    if ($isWiz) echo '<form method="post" enctype="multipart/form-data">';

    $positions_bg = array('Left Top', 'Left Center', 'Left Bottom', 'Center Top', 'Center Center', 'Center Bottom', 'Right Top', 'Right Center', 'Right Bottom');
    $size_bg = array('auto','cover','contain');

    $preset = get_option('dig_preset', 1);

    ?>

    <div class="dig_ad_head"><span><?php _e('PRESET DESIGN', 'digits'); ?></span></div>

    <div class="dig_presets_modal">
        <div id="dig_presets_modal_box">
            <div id="dig_presets_modal_head">
                <div id="dig_presets_modal_head_title"><?php _e('PRESET LIBRARY','digits');?></div>
                <div id="dig_presets_modal_head_close"><?php _e('CLOSE','digits');?></div>
            </div>

            <?php
            $presets_array = array(
                    '0' => array('name' => __('CUSTOM','digits')),
                    '1' => array('name' => 'CLAVIUS'),
                    '2' => array('name' => 'APOLLO'),
                    '3' => array('name' => 'ARISTARCHUS'),
                    '4' => array('name' => 'SHACKLETON'),
                    '5' => array('name' => 'ALPHONSUS'),
                    '6' => array('name' => 'THEOPHILUS'),
            );
            ?>
            <input type="radio" id="dig_preset_custom" class="dig_preset" name="dig_preset" style="display: none;" value="0" data-lab="<?php _e('CUSTOM','digits');?>" <?php if($preset==0) echo 'checked';?> />


            <div id="dig_presets_modal_body">
                <div id="dig_presets_list">

                    <?php
                    foreach ($presets_array as $key => $preset_v) {
                        if($key==0) continue;
                        ?>
                        <div class="dig_preset_item">
                        <label for="preset<?php echo $key;?>">
                        <div class="dig_preset_item_list">
                            <input class="dig_preset" name="dig_preset" id="preset<?php echo $key;?>" value="<?php echo $key;?>" type="radio" <?php if($key==$preset) echo 'checked';?>>
                            <div class="dig_preset_sel">
                                <img class="dig_preset_sel_tick" src="<?php echo plugins_url('/assests/images/preset-tick.svg', __FILE__); ?>"
                                     draggable="false"/>
                            </div>
                            <div class="dig_preset_img_smp">
                                <img src="<?php echo plugins_url('/assests/images/preset'.$key.'.jpg', __FILE__); ?>"
                                     draggable="false"/>

                                     <a class="dig_preset_big_img" big-href="<?php echo plugins_url('/assests/images/preset'.$key.'.jpg', __FILE__); ?>"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" aria-labelledby="title"
aria-describedby="desc" role="img" xmlns:xlink="http://www.w3.org/1999/xlink">
  <path data-name="layer2"
  fill="none" stroke="#ffffff" stroke-miterlimit="10" stroke-width="2" d="M16 22h32v20H16z"
  stroke-linejoin="round" stroke-linecap="round"></path>
  <path data-name="layer1" fill="none" stroke="#ffffff" stroke-miterlimit="10"
  stroke-width="2" d="M2 16V2h20m40 14V2H42M2 49v13h20m40-14v14H42" stroke-linejoin="round"
  stroke-linecap="round"></path>
</svg></a>
                            </div>
                            <div class="dig_preset_name"><?php echo $preset_v['name']; ?></div>
                        </div>
                        </label>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <table class="form-table">
        <tr>
            <th scope="row"><label for="dig_preset"><?php _e('Preset', 'digits'); ?> </label></th>
            <td class="dig_prst_btns">
                <input class="dig_prst_name" type="text" readonly value="<?php if(array_key_exists($preset,$presets_array))echo $presets_array[$preset]['name'];?>">
                <Button id="dig_open_preset_box" type="button" class="button"><?php _e('Select Preset','digits'); ?></Button>

                <input type="hidden"
                       value='{"dig_modal_type" : "1", "dig_page_type":"1","bg_image_attachment_id_modal":"","bg_image_attachment_id" :"","backcolor": "#fff","bg_color": "#2ac5fc","lbxbg_color": "#fff","lb_x": "0","lb_y": "2","lb_blur": "4","lb_spread": "0","lb_radius": "4","lb_color": "rgba(0, 0, 0, 0.5)","fontcolor2": "rgba(255,255,255,1)","fontcolor1": "rgba(20,20,20,1)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "#fff","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "0","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(20,20,20,1)","fontcolor2_modal": "rgba(255,255,255,1)"}'
                       id="dig_preset1"/>
                <input type="hidden"
                       value='{"dig_modal_type" : "1", "dig_page_type":"1","bg_image_attachment_id_modal":"","bg_image_attachment_id" :"","backcolor": "#fff","bg_color": "#050210","lbxbg_color": "rgba(0,0,0,0)","lb_x": "0","lb_y": "0","lb_blur": "0","lb_spread": "0","lb_radius": "0","lb_color": "rgba(0, 0, 0, 0)","fontcolor2": "rgba(20,20,20,1)","fontcolor1": "rgba(255,255,255,1)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "#050210","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "0","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(255,255,255,1)","fontcolor2_modal": "rgba(20,20,20,1)"}'
                       id="dig_preset2"/>
                <input type="hidden"
                       value='{"dig_modal_type" : "1", "dig_page_type":"1","bg_image_attachment_id_modal":"","bg_image_attachment_id":"<?php echo plugins_url('/assests/images/bg.jpg', __FILE__); ?>", "backcolor": "#fff","bg_color": "rgba(0,0,0,0)","lbxbg_color": "rgba(17,17,17,0.87)","lb_x": "0","lb_y": "2","lb_blur": "4","lb_spread": "0","lb_radius": "4","lb_color": "rgba(0, 0, 0, 0.5)","fontcolor2": "rgba(51,51,51,1)","fontcolor1": "rgba(255,255,255,1)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "#111","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "4","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(255,255,255,1)","fontcolor2_modal": "rgba(51,51,51,1)"}'
                       id="dig_preset3"/>
                <input type="hidden"
                       value='{"dig_modal_type" : "1", "dig_page_type":"1","bg_image_attachment_id_modal":"","bg_image_attachment_id" :"","backcolor": "#fff","bg_color": "#0d0d0d","lbxbg_color": "#fff","lb_x": "0","lb_y": "2","lb_blur": "4","lb_spread": "0","lb_radius": "0","lb_color": "rgba(0, 0, 0, 0.5)","fontcolor2": "rgba(255,255,255,1)","fontcolor1": "rgba(20,20,20,1)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "#fff","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "0","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(20,20,20,1)","fontcolor2_modal": "rgba(255,255,255,1)"}'
                       id="dig_preset4"/>

                <input type="hidden"
                       value='{"dig_modal_type" : "1", "dig_page_type":"1","bg_image_attachment_id_modal":"","bg_image_attachment_id" :"","backcolor": "#0d0d0d","bg_color": "#fff","lbxbg_color": "#fff","lb_x": "0","lb_y": "2","lb_blur": "4","lb_spread": "0","lb_radius": "0","lb_color": "rgba(0, 0, 0, 0.5)","fontcolor2": "rgba(255,255,255,1)","fontcolor1": "rgba(20,20,20,1)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "#fff","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "0","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(20,20,20,1)","fontcolor2_modal": "rgba(255,255,255,1)"}'
                       id="dig_preset5"/>

                <input type="hidden"
                       value='{"dig_modal_type" : "2", "dig_page_type":"2","bg_image_attachment_id_modal":"","bg_image_attachment_id" :"","bg_image_attachment_id_left":"<?php echo plugins_url('/assests/images/cart.png', __FILE__); ?>","bg_image_attachment_id_left_modal":"<?php echo plugins_url('/assests/images/cart.png', __FILE__); ?>", "backcolor": "rgba(0, 0, 0, 0.75)","bg_color": "rgba(237, 230, 234, 1)","lbxbg_color": "rgba(255, 255, 255, 1)","fontcolor1": "rgba(109, 109, 109, 1)","lb_x": "0","lb_y": "3","lb_blur": "6","lb_spread": "0","lb_radius": "4","lb_color": "rgba(0, 0, 0, 0.16)","bg_color_modal": "rgba(6, 6, 6, 0.8)","lbxbg_color_modal": "rgba(250, 250, 250, 1)","lb_x_modal": "0","lb_y_modal": "0","lb_blur_modal": "20","lb_spread_modal": "0","lb_radius_modal": "4","lb_color_modal": "rgba(0, 0, 0, 0.3)","fontcolor1_modal": "rgba(109, 109, 109, 1)","fontcolor2_modal": "rgba(51,51,51,1)","left_color":"rgba(165, 62, 96, 1)","left_color_modal":"rgba(165, 62, 96, 1)","input_bg_color":"rgba(255, 255, 255, 1)","input_border_color":"rgba(153, 153, 153, 1)","input_text_color":"rgba(0, 0, 0, 1)","button_bg_color":"rgba(255, 188, 0, 1)","signup_button_color":"rgba(242, 242, 242, 1)","signup_button_border_color":"rgba(214, 214, 214, 1)","button_text_color":"rgba(255, 255, 255, 1)","signup_button_text_color":"rgba(109, 109, 109, 1)","input_bg_color_modal":"rgba(255, 255, 255, 1)","input_border_color_modal":"rgba(153, 153, 153, 1)","input_text_color_modal":"rgba(0, 0, 0, 1)","button_bg_color_modal":"rgba(255, 188, 0, 1)","signup_button_color_modal":"rgba(242, 242, 242, 1)","signup_button_border_color_modal":"rgba(214, 214, 214, 1)","button_text_color_modal":"rgba(255, 255, 255, 1)","signup_button_text_color_modal":"rgba(109, 109, 109, 1)"}'
                       id="dig_preset6"/>
            </td>
        </tr>
    </table>

    <div class="dig_ad_head dig_prst_clse_scrl"><span><?php _e('FORM TYPE', 'digits'); ?></span></div>
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e('Page', 'digits'); ?> </label></th>
            <td>
                <div class="digits-form-type dig_trans">

                    <label class="dig_type_item" for="dig_page_type1">
                        <div class="dig_style_types_gs">
                            <input value="1" name="dig_page_type" id="dig_page_type1" class="dig_type" type="radio" <?php if($page_type==1) echo 'checked';?> />
                            <div class="dig_preset_sel">
                                <img class="dig_preset_sel_tick" src="<?php echo plugins_url('/assests/images/preset-tick.svg', __FILE__); ?>"
                                     draggable="false"/>
                            </div>
                            <div class="dig-page-type1 dig-type-dims"></div>
                        </div>
                    </label>
                    <label class="dig_type_item" for="dig_page_type2">
                        <div class="dig_style_types_gs">
                            <input value="2" name="dig_page_type" id="dig_page_type2" class="dig_type" type="radio" <?php if($page_type==2) echo 'checked';?> />
                            <div class="dig_preset_sel">
                                <img class="dig_preset_sel_tick" src="<?php echo plugins_url('/assests/images/preset-tick.svg', __FILE__); ?>"
                                     draggable="false"/>
                            </div>
                            <div class="dig-page-type2 dig-type-dims"></div>
                        </div>
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label><?php _e('Modal', 'digits'); ?> </label></th>
            <td>
                <div class="digits-form-type">
                    <label class="dig_type_item" for="dig_modal_type1">
                        <div class="dig_style_types_gs">
                            <input value="1" name="dig_modal_type" id="dig_modal_type1" class="dig_type" type="radio" <?php if($modal_type==1) echo 'checked';?> />
                            <div class="dig_preset_sel">
                                <img class="dig_preset_sel_tick" src="<?php echo plugins_url('/assests/images/preset-tick.svg', __FILE__); ?>"
                                     draggable="false"/>
                            </div>
                            <div class="dig-modal-type1 dig-type-dims"></div>
                        </div>
                    </label>

                    <label class="dig_type_item" for="dig_modal_type2">
                        <div class="dig_style_types_gs">
                            <input value="2" name="dig_modal_type" id="dig_modal_type2" class="dig_type" type="radio" <?php if($modal_type==2) echo 'checked';?> />
                            <div class="dig_preset_sel">
                                <img class="dig_preset_sel_tick" src="<?php echo plugins_url('/assests/images/preset-tick.svg', __FILE__); ?>"
                                     draggable="false"/>
                            </div>
                            <div class="dig-modal-type2 dig-type-dims"></div>
                        </div>
                    </label>
                </div>
            </td>
        </tr>
    </table>

    <div class="dig_ad_head"><span><?php _e('Page', 'digits'); ?></span></div>

    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e('Logo', 'digits'); ?> </label></th>
            <td>
                <?php
                $imgid = get_option('digits_logo_image');
                $remstyle = "";
                if (empty($imgid)) {
                    $imagechoose = __("Select image", 'digits');
                    $remstyle = 'style="display:none;"';
                } else {
                    $imagechoose = __("Remove Image", 'digits');
                }

                $wid = "";
                if (is_numeric($imgid)) $wid = wp_get_attachment_url($imgid);
                ?>
                <div class='image-preview-wrapper'>
                    <img id='image-preview' src='<?php if (is_numeric($imgid)) echo $wid; else echo $imgid; ?>'
                         style="max-height:100px;max-width:250px;">
                </div>

                <input type="text" name="image_attachment_id" id='image_attachment_id'
                       value='<?php if (is_numeric($imgid)) {
                           if ($wid) echo $wid;
                       } else echo $imgid; ?>' placeholder="<?php _e("URL", "digits"); ?>" class="dig_url_img"/>
                <Button id="upload_image_button" type="button" class="button dig_img_chn_btn dig_imsr"
                       ><?php echo $imagechoose; ?></Button>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label for="bgcolor"><?php _e('Login Page Left Background Color', 'digits'); ?> </label></th>
            <td>
                <input name="left_color" type="text" class="bg_color" value="<?php echo $leftcolor; ?>" autocomplete="off"
                       required data-alpha="true">

            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Login Page Left Image', 'digits'); ?> </label></th>
            <td>
                <?php
                $imgid = get_option('digits_left_bg_image');
                $remstyle = "";
                if (empty($imgid)) {
                    $imagechoose = __("Select image", 'digits');
                    $remstyle = 'style="display:none;"';
                } else {
                    $imagechoose = __("Remove Image", 'digits');
                }
                $wid = "";
                if (is_numeric($imgid)) $wid = wp_get_attachment_url($imgid);
                ?>
                <div class='image-preview-wrapper'>
                    <img id='bg_image-preview_left' src='<?php if (is_numeric($imgid)) echo $wid; else echo $imgid; ?>'
                         style="max-height:100px;">
                </div>

                <input type="text" name="bg_image_attachment_id_left" id='bg_image_attachment_id_left'
                       value='<?php if (is_numeric($imgid)) {
                           if ($wid) echo $wid;
                       } else echo $imgid; ?>' placeholder="<?php _e("URL", "digits"); ?>" class="dig_url_img"/>

                <Button id="bg_upload_image_button_left" type="button" class="button dig_img_chn_btn dig_imsr"
                ><?php echo $imagechoose; ?></Button>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Login Page Left Background Size', 'digits'); ?></label></th>
            <td>
                <select name="left_bg_size">
                    <?php
                    foreach($size_bg as $size){
                        $sel = '';
                        if($left_bg_size==$size){
                            $sel = 'selected';
                        }
                        echo '<option value="'.$size.'" '.$sel.'>'.$size.'</option>';

                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Login Page Left Background Position', 'digits'); ?></label></th>
            <td>
               <select name="left_bg_position">
                   <?php
                   foreach($positions_bg as $position){
                       $sel = '';
                       if($left_bg_position==$position){
                           $sel = 'selected';
                       }
                       echo '<option value="'.$position.'" '.$sel.'>'.$position.'</option>';

                   }
                   ?>
               </select>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label for="login_page_footer"><?php _e('Login Page Footer', 'digits'); ?> </label></th>
            <td>
            <textarea name="login_page_footer" id="login_page_footer" type="text" rows="3"><?php
             $footer = trim(get_option('login_page_footer'));
             if(!empty($footer)){
                 echo str_replace("<br />", "\n", base64_decode($footer));
             }
             ?></textarea>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label for="bgcolor"><?php _e('Login Page Footer Text Color', 'digits'); ?> </label></th>
            <td>
            <input name="login_page_footer_text_color" type="text" class="bg_color" value="<?php echo get_option('login_page_footer_text_color','rgba(255,255,255,1)'); ?>" autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="bgcolor"><?php _e('Login Page Background Color', 'digits'); ?> </label></th>
            <td>
                <input name="bg_color" type="text" class="bg_color" value="<?php echo $bgcolor; ?>" autocomplete="off"
                       required data-alpha="true">

            </td>
        </tr>

        <tr>
            <th scope="row"><label><?php _e('Login Page Background Image', 'digits'); ?> </label></th>
            <td>

                <?php
                $imgid = get_option('digits_bg_image');
                $remstyle = "";
                if (empty($imgid)) {
                    $imagechoose = __("Select image", 'digits');
                    $remstyle = 'style="display:none;"';
                } else {
                    $imagechoose = __("Remove Image", 'digits');
                }
                $wid = "";
                if (is_numeric($imgid)) $wid = wp_get_attachment_url($imgid);
                ?>
                <div class='image-preview-wrapper'>
                    <img id='bg_image-preview' src='<?php if (is_numeric($imgid)) echo $wid; else echo $imgid; ?>'
                         style="max-height:100px;">
                </div>

                <input type="text" name="bg_image_attachment_id" id='bg_image_attachment_id'
                       value='<?php if (is_numeric($imgid)) {
                           if ($wid) echo $wid;
                       } else echo $imgid; ?>' placeholder="<?php _e("URL", "digits"); ?>" class="dig_url_img"/>

                <Button id="bg_upload_image_button" type="button" class="button dig_img_chn_btn dig_imsr"
                        ><?php echo $imagechoose; ?></Button>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="lbxbgcolor"><?php _e('Login Box Background Color', 'digits'); ?> </label></th>
            <td>
                <input name="lbxbg_color" type="text" class="bg_color" value="<?php echo $loginboxcolor; ?>"
                       autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lb_x"><?php _e('Login Box Shadow', 'digits'); ?> </label></th>
            <td>

                <table class="digotlbr">
                    <tr class="dignochkbxra">
                        <td><input id="lb_x" name="lb_x" type="number" value="<?php echo $sx; ?>" autocomplete="off"
                                   required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_x"><?php _e('X', 'digits'); ?></label></div>
                        </td>
                        <td><input id="lb_y" name="lb_y" type="number" value="<?php echo $sy; ?>" autocomplete="off"
                                   required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_y"><?php _e('Y', 'digits'); ?></label></div>
                        </td>
                        <td><input id="lb_blur" name="lb_blur" type="number" value="<?php echo $sblur; ?>"
                                   autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_blur"><?php _e('Blur', 'digits'); ?></label></div>
                        </td>
                        <td><input id="lb_spread" name="lb_spread" type="number" value="<?php echo $sspread; ?>"
                                   autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_spread"><?php _e('Spread', 'digits'); ?></label>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="lb_color"><?php _e('Login Box Shadow Color', 'digits'); ?> </label></th>
            <td>
                <input name="lb_color" class="bg_color" type="text" value="<?php echo $scolor; ?>" autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="bgcolor"><?php _e('Login Box Radius', 'digits'); ?> </label></th>
            <td>
                <div class="dig_gs_nmb_ovr_spn">
                    <input class="dignochkbx" name="lb_radius" type="number" value="<?php echo $sradius; ?>"
                           autocomplete="off" required maxlength="2" dig-min="42" placeholder="0">
                    <span style="left:42px;">px</span>
                </div>
            </td>
        </tr>

        <tr class="dig_page_type_1_2">
            <th scope="row"><label data-type1="<?php _e('Text and Button Color', 'digits'); ?>" data-type2="<?php _e('Text Color', 'digits'); ?>">Color</label></th>
            <td>
                <input type="text" name="fontcolor1" class="bg_color" value="<?php echo $fontcolor1; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_page_type_1">
            <th scope="row"><label><?php _e('Button Font Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="fontcolor2" class="bg_color" value="<?php echo $fontcolor2; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label><?php _e('Back/Cancel Button Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="backcolor" class="bg_color" value="<?php echo $backcolor; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Input Background Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_bg_color" class="bg_color" value="<?php echo $input_bg_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Input Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_border_color" class="bg_color" value="<?php echo $input_border_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Input Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_text_color" class="bg_color" value="<?php echo $input_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Button Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="button_bg_color" class="bg_color" value="<?php echo $button_bg_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Button Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="button_text_color" class="bg_color" value="<?php echo $button_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Signup Button Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_color" class="bg_color" value="<?php echo $signup_button_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Signup Button Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_border_color" class="bg_color" value="<?php echo $signup_button_border_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Signup Button Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_text_color" class="bg_color" value="<?php echo $signup_button_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
    </table>

    <?php
    $color = $color_modal;
    $bgcolor = "rgba(6, 6, 6, 0.8)";
    $fontcolor = 0;

    $loginboxcolor = "rgba(255,255,255,1)";
    $sx = 0;
    $sy = 0;
    $sspread = 0;
    $sblur = 20;
    $scolor = "rgba(0, 0, 0, 0.3)";

    $fontcolor1 = "rgba(255,255,255,1)";
    $fontcolor2 = "rgba(20,20,20,1)";

    $input_bg_color = "rgba(0,0,0,0)";
    $input_border_color = "rgba(0,0,0,0)";
    $input_text_color = "rgba(0,0,0,0)";
    $button_bg_color = "rgba(0,0,0,0)";
    $signup_button_color = "rgba(0,0,0,0)";
    $signup_button_border_color = "rgba(0,0,0,0)";
    $button_text_color = "rgba(0,0,0,0)";
    $signup_button_text_color = "rgba(0,0,0,0)";

    $left_bg_position = 'Center Center';
    $left_bg_size = 'auto';

    $leftcolor = 'rgba(0,0,0,1)';
    $sradius = 0;
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];

        $col = get_option('digit_color');
        if (isset($col['fontcolor'])) {
            $fontcolor = $col['fontcolor'];
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            }
        }

        if (isset($color['sx'])) {
            $sx = $color['sx'];
            $sy = $color['sy'];
            $sspread = $color['sspread'];
            $sblur = $color['sblur'];
            $scolor = $color['scolor'];
            $fontcolor1 = $color['fontcolor1'];
            $fontcolor2 = $color['fontcolor2'];
            $loginboxcolor = $color['loginboxcolor'];
            $sradius = $color['sradius'];

            if(isset($color['type'])){
                $page_type = $color['type'];
                if($page_type==2){
                    $leftcolor = $color['left_color'];
                }
                $modal_type = $color_modal['type'];

                $input_bg_color = $color['input_bg_color'];
                $input_border_color = $color['input_border_color'];
                $input_text_color = $color['input_text_color'];
                $button_bg_color = $color['button_bg_color'];
                $signup_button_color = $color['signup_button_color'];
                $signup_button_border_color = $color['signup_button_border_color'];
                $button_text_color = $color['button_text_color'];
                $signup_button_text_color = $color['signup_button_text_color'];
                $left_bg_position = $color['left_bg_position'];
                $left_bg_size = $color['left_bg_size'];
            }
        }
    }
    ?>

    <div class="dig_ad_head"><span><?php _e('Modal', 'digits'); ?></span></div>
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e('Modal Overlay Color', 'digits'); ?> </label></th>
            <td>
                <input name="bg_color_modal" type="text" class="bg_color" value="<?php echo $bgcolor; ?>"
                       autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>

        <tr>
            <th scope="row"><label><?php _e('Login Modal Background Image', 'digits'); ?> </label></th>
            <td>
                <?php
                $imgid = get_option('digits_bg_image_modal');
                $remstyle = "";
                if (empty($imgid)) {
                    $imagechoose = __("Select image", 'digits');
                    $remstyle = 'style="display:none;"';
                } else {
                    $imagechoose = __("Remove Image", 'digits');
                }

                $wid = "";
                if (is_numeric($imgid)) $wid = wp_get_attachment_url($imgid);
                ?>
                <div class='image-preview-wrapper'>
                    <img id='bg_image-preview_modal' src='<?php if (is_numeric($imgid)) echo $wid; else echo $imgid; ?>'
                         style="max-height:100px;">
                </div>

                <input type="text" name="bg_image_attachment_id_modal" id='bg_image_attachment_id_modal'
                       value='<?php if (is_numeric($imgid)) {
                           if ($wid) echo $wid;
                       } else echo $imgid; ?>' placeholder="<?php _e("URL", "digits"); ?>" class="dig_url_img"/>

                <Button id="bg_upload_image_button_modal" type="button" class="button dig_img_chn_btn dig_imsr"
                ><?php echo $imagechoose; ?></Button>
            </td>
        </tr>

        <tr class="dig_modal_type_2">
            <th scope="row"><label for="bgcolor"><?php _e('Login Box Left Background Color', 'digits'); ?> </label></th>
            <td>
                <input name="left_color_modal" type="text" class="bg_color" value="<?php echo $leftcolor; ?>" autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>
        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Login Box Left Image', 'digits'); ?> </label></th>
            <td>
                <?php
                $imgid = get_option('digits_left_bg_image_modal');
                $remstyle = "";
                if (empty($imgid)) {
                    $imagechoose = __("Select image", 'digits');
                    $remstyle = 'style="display:none;"';
                } else {
                    $imagechoose = __("Remove Image", 'digits');
                }
                $wid = "";
                if (is_numeric($imgid)) $wid = wp_get_attachment_url($imgid);
                ?>
                <div class='image-preview-wrapper'>
                    <img id='bg_image-preview_left_modal' src='<?php if (is_numeric($imgid)) echo $wid; else echo $imgid; ?>'
                         style="max-height:100px;">
                </div>

                <input type="text" name="bg_image_attachment_id_left_modal" id='bg_image_attachment_id_left_modal'
                       value='<?php if (is_numeric($imgid)) {
                           if ($wid) echo $wid;
                       } else echo $imgid; ?>' placeholder="<?php _e("URL", "digits"); ?>" class="dig_url_img"/>

                <Button id="bg_upload_image_button_left_modal" type="button" class="button dig_img_chn_btn dig_imsr"
                ><?php echo $imagechoose; ?></Button>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Login Page Left Background Size', 'digits'); ?></label></th>
            <td>
                <select name="left_bg_size_modal">
                    <?php
                    foreach($size_bg as $size){
                        $sel = '';
                        if($left_bg_size==$size){
                            $sel = 'selected';
                        }
                        echo '<option value="'.$size.'" '.$sel.'>'.$size.'</option>';

                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr class="dig_page_type_2">
            <th scope="row"><label><?php _e('Login Page Left Background Position', 'digits'); ?></label></th>
            <td>
                <select name="left_bg_position_modal">
                    <?php
                    foreach($positions_bg as $position){
                        $sel = '';
                        if($left_bg_position==$position){
                            $sel = 'selected';
                        }
                        echo '<option value="'.$position.'" '.$sel.'>'.$position.'</option>';

                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row"><label><?php _e('Login Modal Background Color', 'digits'); ?> </label></th>
            <td>
                <input name="lbxbg_color_modal" type="text" class="bg_color" value="<?php echo $loginboxcolor; ?>"
                       autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lb_x_modal"><?php _e('Login Modal Shadow', 'digits'); ?> </label></th>
            <td>
                <table class="digotlbr">
                    <tr class="dignochkbxra">
                        <td><input id="lb_x_modal" name="lb_x_modal" type="number" value="<?php echo $sx; ?>"
                                   autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_x_modal"><?php _e('X', 'digits'); ?></label></div>
                        </td>
                        <td><input id="lb_y_modal" name="lb_y_modal" type="number" value="<?php echo $sy; ?>"
                                   autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_y_modal"><?php _e('Y', 'digits'); ?></label></div>
                        </td>
                        <td><input id="lb_blur_modal" name="lb_blur_modal" type="number" value="<?php echo $sblur; ?>"
                                   autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label for="lb_blur_modal"><?php _e('Blur', 'digits'); ?></label>
                            </div>
                        </td>
                        <td><input id="lb_spread_modal" name="lb_spread_modal" type="number"
                                   value="<?php echo $sspread; ?>" autocomplete="off" required maxlength="2">
                            <div class="digno-tr_dt"><label
                                        for="lb_spread_modal"><?php _e('Spread', 'digits'); ?></label></div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <tr>
            <th scope="row"><label><?php _e('Login Modal Shadow Color', 'digits'); ?> </label></th>
            <td>
                <input name="lb_color_modal" class="bg_color" type="text" value="<?php echo $scolor; ?>"
                       autocomplete="off"
                       required data-alpha="true">
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="lb_radius_modal"><?php _e('Login Modal Radius', 'digits'); ?> </label></th>
            <td>
                <div class="dig_gs_nmb_ovr_spn">
                    <input class="dignochkbx" name="lb_radius_modal" id="lb_radius_modal" type="number"
                           value="<?php echo $sradius; ?>" autocomplete="off" dig-min="42" required maxlength="2" placeholder="0">
                    <span style="left:42px;">px</span>
                </div>
            </td>
        </tr>

        <tr class="dig_modal_type_1_2">
            <th scope="row"><label data-type1="<?php _e('Text and Button Color', 'digits'); ?>" data-type2="<?php _e('Text Color', 'digits'); ?>">Color</label></th>
            <td>
                <input type="text" name="fontcolor1_modal" class="bg_color" value="<?php echo $fontcolor1; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_modal_type_1">
            <th scope="row"><label><?php _e('Button Font Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="fontcolor2_modal" class="bg_color" value="<?php echo $fontcolor2; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Input Background Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_bg_color_modal" class="bg_color" value="<?php echo $input_bg_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Input Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_border_color_modal" class="bg_color" value="<?php echo $input_border_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Input Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="input_text_color_modal" class="bg_color" value="<?php echo $input_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Button Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="button_bg_color_modal" class="bg_color" value="<?php echo $button_bg_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Button Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="button_text_color_modal" class="bg_color" value="<?php echo $button_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Signup Button Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_color_modal" class="bg_color" value="<?php echo $signup_button_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Signup Button Border Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_border_color_modal" class="bg_color" value="<?php echo $signup_button_border_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>

        <tr class="dig_modal_type_2">
            <th scope="row"><label><?php _e('Signup Button Text Color', 'digits'); ?> </label></th>
            <td>
                <input type="text" name="signup_button_text_color_modal" class="bg_color" value="<?php echo $signup_button_text_color; ?>"
                       data-alpha="true"/>
            </td>
        </tr>
    </table>

    <div class="dig_ad_head"><span><?php _e('Advanced Options', 'digits'); ?></span></div>
    <?php
    $custom_css = get_option('digit_custom_css');
    $custom_css = str_replace(array("\'",'/"'),array("'",'"'),$custom_css);
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="dig_custom_css"><?php _e('Custom CSS', 'digits'); ?> </label></th>
            <td><textarea name="digit_custom_css" rows="6"
                          class="dig_inp_wid28" id="dig_custom_css"><?php echo $custom_css; ?></textarea></td>
        </tr>
    </table>
    <?php

    if ($isWiz) {
        ?>
        <p class="digits-setup-action step">
            <input type="submit" value="<?php _e("Continue", "digits"); ?>"
                   class="button-primary button button-large button-next"/>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=apisettings'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
        </form>
        <?php
    }
    ?>
    <?php
}

function digits_configure_settings()
{
    $enable_createcustomeronorder = get_option('enable_createcustomeronorder');

    $dig_bill_ship_fields = get_option('dig_bill_ship_fields', 0);
    $defaultuserrole = get_option('defaultuserrole', "customer");

    $dig_mob_ver_chk_fields = get_option('dig_mob_ver_chk_fields', 1);

    $digforgotpass = get_option('digforgotpass', 1);

    $mobInUname = get_option("dig_mobilein_uname", 0);

    $dig_mob_otp_resend_time = get_option('dig_mob_otp_resend_time', 30);
    $dig_use_strongpass = get_option('dig_use_strongpass', 0);

    $dig_messagetemplate = get_option("dig_messagetemplate", "Your OTP for %NAME% is %OTP%");
    $dig_otp_size = get_option("dig_otp_size", 5);
    ?>
    <div class="dig_ad_head"><span><?php _e('Basic', 'digits'); ?></span></div>
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e('Use Mobile Number as Username', 'digits'); ?> </label></th>
            <td>
                <select name="dig_mobilein_uname">
                    <option value="1" <?php if ($mobInUname == 1) echo 'selected="selected"'; ?>><?php _e('Yes', 'digits'); ?></option>
                    <option value="0" <?php if ($mobInUname == 0) echo 'selected="selected"'; ?>><?php _e('No', 'digits'); ?></option>
                </select>
            </td>
        </tr>

        <tr id="enabledisableforgotpasswordrow">
            <th scope="row"><label><?php _e('Enable Forgot Password', 'digits'); ?> </label></th>
            <td>
                <select name="dig_enable_forgotpass">
                    <option value="1" <?php if ($digforgotpass == 1) echo 'selected="selected"'; ?>><?php _e('Yes', 'digits'); ?></option>
                    <option value="0" <?php if ($digforgotpass == 0) echo 'selected="selected"'; ?>><?php _e('No', 'digits'); ?></option>
                </select>

                <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This function only works on Digits Login/Signup Modal and Page', 'digits'); ?></p>
            </td>
        </tr>

        <tr id="enabledisablestrongpasswordrow">
            <th scope="row"><label><?php _e('Enable Strong Password for Registration', 'digits'); ?> </label></th>
            <td>
                <select name="dig_enable_strongpass">
                    <option value="1" <?php if ($dig_use_strongpass == 1) echo 'selected="selected"'; ?>><?php _e('Yes', 'digits'); ?></option>
                    <option value="0" <?php if ($dig_use_strongpass == 0) echo 'selected="selected"'; ?>><?php _e('No', 'digits'); ?></option>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row" style="vertical-align:top;"><label
                        for="defaultuserrole"><?php _e('Default User Role', 'digits'); ?></label></th>
            <td>
                <select name="defaultuserrole" id="defaultuserrole">
                    <?php

                    foreach (wp_roles()->roles as $rkey => $rvalue) {
                        if ((isset($rvalue['capabilities']['level_3']) && $rvalue['capabilities']['level_3'] == 1)    || isset($rvalue['capabilities']['edit_users'])) continue;

                        if ($rkey == $defaultuserrole) {
                            $sel = 'selected=selected';
                        } else $sel = '';
                        echo '<option value="' . $rkey . '" ' . $sel . '>' . $rkey . '</option>';
                    }

                    ?>
                </select>
                <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('The default role which will be assigned to new user created.', 'digits'); ?></p>
            </td>
        </tr>
    </table>
    <div class="dig_ad_head"><span><?php _e('OTP SMS', 'digits'); ?></span></div>
    <table class="form-table">
        <tr>
            <th scope="row"><label><?php _e('Default Country Code', 'digits'); ?> </label></th>
            <td>
                <select name="default_ccode" class="dig_inp_wid3 dig_inp_wid_wil">
                    <?php
                    $countryList = getCountryList();
                    $valCon = "";
                    $currentCountry = get_option("dig_default_ccode");

                    $whiteListCountryCodes = get_option("whitelistcountrycodes");

                    $size = 0;
                    if(is_array($whiteListCountryCodes)) {
                        $size = sizeof($whiteListCountryCodes);
                    }
                    foreach ($countryList as $key => $value) {
                        $ac = "";

                        if ($size > 0 && is_array($whiteListCountryCodes)) {
                            if (!in_array($key, $whiteListCountryCodes)) {
                                continue;
                            }
                        }

                        if ($currentCountry == $key) {
                            $ac = "selected=selected";
                        }
                        echo '<option class="dig-cc-visible" ' . $ac . ' value="' . $key . '" country="' . strtolower($key) . '">' . $key . ' (+' . $value . ')</option>';
                    }
                    ?>
                </select>
            </td>
        </tr>

        <tr>
            <th scope="row" style="vertical-align:top;"><label
                        for="whitelistcountrycodes"><?php _e('Country Codes Whitelist', 'digits'); ?></label></th>
            <td>
                <select name="whitelistcountrycodes[]" class="whitelistcountrycodeslist" multiple="multiple">
                    <?php
                    $whiteListCountryCodes = get_option("whitelistcountrycodes");

                    foreach ($countryList as $key => $value) {
                        $ac = "";
                        if ($whiteListCountryCodes) {
                            if (in_array($key, $whiteListCountryCodes)) {
                                $ac = "selected=selected";
                            }
                        }
                        echo '<option value="' . $key . '" ' . $ac . '>' . $key . ' (+' . $value . ')</option>';
                    }
                    ?>
                </select><br/>
                <p class="dig_ecr_desc"><?php _e('Sign In/Sign Up will be allowed for phone numbers with these country codes. To allow Sign In/Sign Up for all country codes, leave this blank.', 'digits'); ?></p>
            </td>
        </tr>

        <?php
        $disp = "";
        $dispotp = '';
        $digit_tapp = get_option('digit_tapp', 1);
        if ($digit_tapp == 1) {
            $dispotp = "style='display:none;'";
        }
        ?>

        <tr class="disotp" <?php echo $dispotp; ?>>
            <th scope="row" style="vertical-align:top;"><label
                        for="dig_messagetemplate"><?php _e('Message Template', 'digits'); ?></label></th>
            <td>
                <input type="text" name="dig_messagetemplate" value="<?php echo $dig_messagetemplate; ?>"
                       placeholder="Message Template" class="dig_inp_wid3"
                       maxlength="<?php echo 128 - strlen(get_option('blogname')); ?>" required>
                <p class="dig_ecr_desc">Max char: 140<br />
                    <?php _e('Site Name', 'digits'); ?> - %NAME%<Br/><?php _e('OTP', 'digits'); ?> -
                    %OTP%</p>

            </td>
        </tr>
        <tr class="disotp" <?php echo $dispotp; ?>>
            <th scope="row" style="vertical-align:top;"><label for="dig_otp_size"><?php _e('OTP size', 'digits'); ?>
                    </label></th>
            <td>
                <select name="dig_otp_size">
                    <option value="4" <?php if ($dig_otp_size == 4) echo "selected='selected'"; ?>>4</option>
                    <option value="5" <?php if ($dig_otp_size == 5) echo "selected='selected'"; ?>>5</option>
                    <option value="6" <?php if ($dig_otp_size == 6) echo "selected='selected'"; ?>>6</option>
                    <option value="7" <?php if ($dig_otp_size == 7) echo "selected='selected'"; ?>>7</option>
                    <option value="8" <?php if ($dig_otp_size == 8) echo "selected='selected'"; ?>>8</option>

                </select>
            </td>
        </tr>

        <tr>
            <th scope="row" style="vertical-align:top;"><label
                        for="dig_mob_otp_resend_time"><?php _e('OTP Resend Time', 'digits'); ?></label>
            </th>
            <td>
                <div class="dig_gs_nmb_ovr_spn">
                <input dig-min="51" type="number" name="dig_mob_otp_resend_time" value="<?php echo $dig_mob_otp_resend_time; ?>"
                       placeholder="<?php _e('0', 'digits'); ?>" class="dig_inp_wid3" min="20" required/>
                    <span style="left:51px;"><?php _e('Seconds','digits');?></span>
                </div>
            </td>
        </tr>
    </table>

    <?php
    $dig_reqfieldbilling = get_option("dig_reqfieldbilling", 0);

    $showWC = '';
    if (!class_exists('WooCommerce')) {
        $showWC = 'style="display:none;"';
    }
    ?>

    <div <?php echo $showWC; ?> class="dig_ad_head"><span><?php _e('WooCommerce Settings', 'digits'); ?></span></div>

    <table <?php echo $showWC; ?> class="form-table">
        <tr>
            <th scope="row"><label for="enable_createcustomeronorder"><?php _e('Create Customer Button', 'digits'); ?>
                    </label></th>
            <td>
                <select name="enable_createcustomeronorder" id="enable_createcustomeronorder">
                    <option value="1" <?php if ($enable_createcustomeronorder == 1) echo 'selected=selected'; ?> ><?php _e('Yes', 'digits'); ?>
                    </option>
                    <option value="0" <?php if ($enable_createcustomeronorder == 0) echo 'selected=selected'; ?> ><?php _e('No', 'digits'); ?>
                    </option>
                </select>
                <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('Add customer on Add Order Page on dashboard using Modal', 'digits'); ?></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="dig_reqfieldbilling"><?php _e('Required field for billing info', 'digits'); ?>
                    </label></th>
            <td>
                <select name="dig_reqfieldbilling" id="dig_reqfieldbilling" class="dig_inp_wid3">
                    <option value="0" <?php if ($dig_reqfieldbilling == 0) echo 'selected=selected'; ?> ><?php _e('Mobile Number and Email', 'digits'); ?></option>
                    <option value="1" <?php if ($dig_reqfieldbilling == 1) echo 'selected=selected'; ?> ><?php _e('Mobile Number', 'digits'); ?></option>
                    <option value="2" <?php if ($dig_reqfieldbilling == 2) echo 'selected=selected'; ?> ><?php _e('Email', 'digits'); ?></option>
                </select>
            </td>
        </tr>

        <?php
        if (class_exists('DIGITSExtWCCheckout')) {
            ?>
        <tr>
            <th scope="row"><label
                        for="dig_bill_ship_fields"><?php _e('Override Billing and Shipping Mobile Fields', 'digits'); ?>
                    </label></th>
            <td>
                <select name="dig_bill_ship_fields" id="dig_bill_ship_fields">
                    <option value="1" <?php if ($dig_bill_ship_fields == 1) echo 'selected=selected'; ?> ><?php _e('Yes', 'digits'); ?>
                    </option>
                    <option value="0" <?php if ($dig_bill_ship_fields == 0) echo 'selected=selected'; ?> ><?php _e('No', 'digits'); ?>
                    </option>
                </select>
                <p class="dig_ecr_desc dig_sel_erc_desc"><?php _e('This will add country code to WooCommerce mobile fields', 'digits'); ?></p>
            </td>
        </tr>
            <tr>
                <th scope="row"><label
                            for="dig_mob_ver_chk_fields"><?php _e('Mobile verification during checkout', 'digits'); ?>
                        </label></th>
                <td>
                    <select name="dig_mob_ver_chk_fields" id="dig_mob_ver_chk_fields">
                        <option value="1" <?php if ($dig_mob_ver_chk_fields == 1) echo 'selected=selected'; ?> ><?php _e('Yes', 'digits'); ?>
                        </option>
                        <option value="0" <?php if ($dig_mob_ver_chk_fields == 0) echo 'selected=selected'; ?> ><?php _e('No', 'digits'); ?>
                        </option>
                    </select>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <div class="dig_ad_head"><span><?php _e('Redirection', 'digits'); ?></span></div>

    <table class="form-table dig_cs_re">
        <tr>
            <th scope="row"><label for="digits_loginred"><?php _e('Login Redirect', 'digits'); ?></label></th>
            <td>

                <input type="url" id="digits_loginred" name="digits_loginred"
                       value="<?php echo get_option("digits_loginred"); ?>"
                       placeholder="<?php _e("URL", "digits"); ?>"/>
                <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="digits_regred"><?php _e('Register Redirect', 'digits'); ?></label></th>
            <td>
                <input type="url" id="digits_regred" name="digits_regred"
                       value="<?php echo get_option("digits_regred"); ?>" placeholder="<?php _e("URL", "digits"); ?>"/>
                <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="digits_forgotred"><?php _e('Forgot Password Redirect', 'digits'); ?></label>
            </th>
            <td>
                <input type="url" id="digits_forgotred" name="digits_forgotred"
                       value="<?php echo get_option("digits_forgotred"); ?>"
                       placeholder="<?php _e("URL", "digits"); ?>"/>
                <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?> </p>
            </td>
        </tr>
        <tr class="dig_csmargn">
            <th scope="row"><label for="digits_logoutred"><?php _e('Logout Redirect', 'digits'); ?></label></th>
            <td>
                <input type="url" id="digits_logoutred" name="digits_logoutred"
                       value="<?php echo get_option("digits_logoutred"); ?>"
                       placeholder="<?php _e("URL", "digits"); ?>"/>
                <p class="dig_ecr_desc"><?php _e('Leave blank for auto redirect', 'digits'); ?>
                    <br /><b><?php _e('Note:', 'digits'); ?></b>&nbsp;<?php _e('Custom Redirect only works on Digits Login/Signup Modal and Page', 'digits'); ?>
                </p>
            </td>
        </tr>

    </table>

    <style>
        select {
            min-width: 120px;
            line-height: 20px;
            border-radius: 3px;
        }

        .select2 ul li {
            padding: 0 5px !important;
        }

        .select2-container--default.select2-container--focus .select2-selection--multiple, .select2-container--default .select2-selection--multiple {
            border: solid #ddd 1px;
        }

        .select2-container--default .select2-search--inline .select2-search__field {
            min-width: 277px;
        }

        .digits_admim_conf .form-table .dig_csmargn td, .digits_admim_conf .form-table .dig_csmargn th {
            padding-bottom: 20px;
        }
    </style>
    <script>
        jQuery(document).ready(function () {
            var createCustomerEnabler = jQuery('#enable_createcustomeronorder');
            updatesetBox(createCustomerEnabler.val());
            createCustomerEnabler.on('change', function () {
                updatesetBox(this.value);
            })

            function updatesetBox(val) {
                if (val == 1) {
                    jQuery(".dig-ccor").each(function (index) {
                        jQuery(this).fadeIn();
                    });
                } else {
                    jQuery(".dig-ccor").each(function (index) {
                        jQuery(this).fadeOut();
                    });
                }
            }

            jQuery(".whitelistcountrycodeslist").select2();
        });
    </script>
    <?php
}

$DigitsUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    '#',
    __FILE__,
    'digits'
);
$DigitsUpdateChecker->addQueryArgFilter('dig_filter_update_checks');
function dig_filter_update_checks($queryArgs)
{
    $digpc = dig_get_option('dig_purchasecode');
    if (!empty($digpc))
        $queryArgs['license_key'] = dig_get_option('dig_purchasecode');

    $queryArgs['request_site'] = network_home_url();

    $queryArgs['license_type'] = dig_get_option('dig_license_type', 1);

    $plugin_data = get_plugin_data( __FILE__ );
    $plugin_version = $plugin_data['Version'];

    $queryArgs['version'] = $plugin_version;

    return $queryArgs;
}


function nice_select_scr()
{
    wp_enqueue_style('nice-select', plugins_url('/assests/css/nice-select.css', __FILE__), array(), null, 'all');
    wp_enqueue_script('nice-select', plugins_url('/assests/js/jquery.nice-select.min.js', __FILE__), array('jquery'), null);
}

function select2js()
{
    if (isset($_GET['page'])) {
        $cp = $_GET['page'];
        if (isset($_GET['tab']) || isset($_GET['step']) || $cp == "digits_settings") {
            echo '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css" rel="stylesheet" />
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js"></script>';
        }
    }
}

// Add hook for admin <head></head>
add_action('admin_head', 'select2js');
// Add hook for front-end <head></head>

add_action('admin_enqueue_scripts', 'digits_add_color_picker');
function digits_add_color_picker($hook)
{
    if (!isset($_GET['page'])) return;
    if ($_GET['page'] != 'digits_settings') return;

    if (is_admin()) {
        // Add the color picker css__FILE__
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('google-roboto-regular', dig_fonts());
        nice_select_scr();

        wp_enqueue_script('rubaxa-sortable', plugins_url('/assests/js/sortable.min.js', __FILE__), null);

        wp_enqueue_script('slick', plugins_url('/assests/js/slick.min.js', __FILE__), null);

        // Include our custom jQuery__FILE__ with WordPress Color Picker dependency
        wp_register_script('digits-script', plugins_url('/assests/js/settings.js', __FILE__), array('jquery', 'wp-color-picker', 'rubaxa-sortable','slick'), false, true);

        $settings_array = array(
            'plsActMessage' => __('Please activate your plugin to change the look and feel of your Login page and Popup', 'digits'),
            'cannotUseEmailWithoutPass' => __('Oops! You cannot enable email without password for login', 'digits'),
            'bothPassAndOTPCannotBeDisabled' => __('Both Password and OTP cannot be disabled', 'digits'),
            'selectatype' => __('Select a type', 'digits'),
            "Invalidmsg91senderid" => __("Invalid msg91 sender id!", 'digits'),
            "invalidpurchasecode" => __("Invalid Purchase Code", 'digits'),
            "Error" => __("Error! Please try again later", "digits"),
            "PleasecompleteyourSettings" => __("Please complete your Settings", 'digits'),
            "PleasecompleteyourAPISettings" => __("Please complete your API Settings", 'digits'),
            "PleasecompleteyourCustomFieldSettings" => __("Please complete your Custom Field Settings", 'digits'),
            "Copiedtoclipboard" => __("Copied to clipboard", "digits"),
            'ajax_url' => admin_url('admin-ajax.php'),
            'face' => plugins_url('digits/assests/images/face.png'),
            'fieldAlreadyExist' => __('Field Already exist', 'digits'),
            'duplicateValue' => __('Duplicate Value', 'digits'),
            'cross' => plugins_url('digits/assests/images/cross.png'),
            "ohsnap" => __("Oh Snap!", "digits"),
            "string_no" => __("No", "digits"),
            "string_optional" => __("Optional", "digits"),
            "string_required" => __("Required", "digits"),
            "validnumber" => __("Please enter a valid mobile number", "digits"),

        );
        wp_localize_script('digits-script', 'digsetobj', $settings_array);

        wp_enqueue_script('digits-script');

        wp_register_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js', array('jquery'), null, false);
        wp_print_scripts('jquery-mask');

        digits_add_style();
        digits_add_scripts();
    }
}


function digits_add_style()
{
    wp_register_style('digits-style', plugins_url('/assests/css/main.min.css', __FILE__), array(), null, 'all');

    wp_enqueue_style('digits-login-style', plugins_url('digits/assests/css/login.min.css'), array(), null, 'all');
    wp_enqueue_style('digits-style');

    if (is_rtl()) {
        $rtl_wc = "
                #woocommerce-order-data .address p:nth-child(3) a,.woocommerce-customer-details--phone{
                    text-align:right;
                    }";
        wp_add_inline_style('digits-style', $rtl_wc);
    }
}

add_action('wp_enqueue_scripts', 'digits_add_style');
add_action('admin_enqueue_scripts', 'digits_add_style');

function getCurrentGateway()
{
    return get_option('digit_tapp', 1);
}

function iniAccInit()
{
    $app = get_option('digit_api');
    $appid = $app['appid'];

    if (empty($appid)) $appid = 0;

    $csrf = wp_create_nonce('crsf-otp');
    $data = "AccountKit_OnInteractive = function () {AccountKit.init({appId:'" . $appid . "',state:'" . $csrf . "',version:'v1.1'})}";
    return $data;
}

function digits_reg_firebase_script()
{
    $handle = 'firebase';
    $list = 'enqueued';

    if (wp_script_is($handle, $list)) {
        return;
    }
    wp_register_script('firebase', 'https://www.gstatic.com/firebasejs/5.5.5/firebase-app.js', array(), null, false);
    wp_register_script('firebase-auth', 'https://www.gstatic.com/firebasejs/5.5.5/firebase-auth.js', array('firebase'), null, false);

    wp_enqueue_script('firebase');
    wp_enqueue_script('firebase-auth');

    $firebaseAuth = iniFireBaseinit();
    if(!empty($firebaseAuth)) {
        wp_add_inline_script('firebase-auth',$firebaseAuth);
    }
}

function iniFireBaseinit()
{
    $firebase = get_option('digit_firebase');    
    
    if(empty($firebase['api_key']))return;
    $data = 'var config = { 
            "apiKey": "' . $firebase['api_key'] . '",
            "authDomain": "' . $firebase['authdomain'] . '",
            "databaseURL": "' . $firebase['databaseurl'] . '",
            "projectId": "' . $firebase['projectid'] . '",
            "storageBucket": "' . $firebase['storagebucket'] . '",
            "messagingSenderId": "' . $firebase['messagingsenderid'] . '"
        }; 
        firebase.initializeApp(config);
        firebase.auth().languageCode = "' . get_locale() . '"';
    return $data;
}


function digits_in_script()
{
    $app = get_option('digit_api');
    $appid = "";
    $handle = 'account-kit-ini';
    $list = 'enqueued';

    $digit_tapp = get_option('digit_tapp', 1);
    if ($app !== false && $digit_tapp == 1 && !wp_script_is($handle, $list)) {
        $appid = $app['appid'];

        if (empty($appid)) $appid = 0;

        $csrf = wp_create_nonce('crsf-otp');

        if (isset($app['accountkitversion'])) {
            $accountkitversion = $app['accountkitversion'];
        } else $accountkitversion = "v1.1";

        ?>
        <script type="text/javascript">
            AccountKit_OnInteractive = function () {
                AccountKit.init(
                    {
                        appId: "<?php echo $appid; ?>",
                        state: "<?php echo $csrf; ?>",
                        version: "<?php echo $accountkitversion; ?>"
                    }
                );
            };
        </script>
        <?php
    }
    if (isset($_GET['ihc_ap_menu'])) {
        if ($_GET['ihc_ap_menu'] == "profile") {
            dig_addmobile();
        }
    }
}

add_action('wp_footer', 'digits_in_script');

function dig_deps_scripts()
{
    $d = getCurrentGateway();

    $re = array('jquery', 'scrollTo', 'nice-select', 'password-strength-meter');
    if ($d == 13) {
        digits_reg_firebase_script();
        array_push($re, 'firebase-auth');
    } else if ($d == 1) {
        array_push($re, 'account-kit');
    }
    return $re;
}

function dig_get_locale($locale, $supportedLocales)
{
    foreach ($supportedLocales as $v) {
        ;
        if (stripos(strtolower($v), strtolower($locale)) !== false) return $v;
    }
    return false;
}

function dig_get_accountkit_locale()
{
    $locale = get_locale();
    $supportedLocaleArray = array('af_ZA', 'af_AF', 'ar_AR', 'bn_IN', 'my_MM', 'zh_CN', 'zh_HK', 'zh_TW', 'hr_HR', 'cs_CZ', 'da_DK', 'nl_NL', 'en_GB', 'en_US', 'fi_FI', 'fr_FR', 'de_DE', 'el_GR', 'gu_IN', 'he_IL', 'hi_IN', 'hu_HU', 'id_ID', 'it_IT', 'ja_JP', 'ko_KR', 'cb_IQ', 'ms_MY', 'ml_IN', 'mr_IN', 'nb_NO', 'pl_PL', 'pt_BR', 'pt_PT', 'pa_IN', 'ro_RO', 'ru_RU', 'sk_SK', 'es_LA', 'es_ES', 'sw_KE', 'sv_SE', 'tl_PH', 'ta_IN', 'te_IN', 'th_TH', 'tr_TR', 'ur_PK', 'vi_VN');

    if (in_array($locale, $supportedLocaleArray)) $gl = $locale;
    else $gl = dig_get_locale($locale, $supportedLocaleArray);

    if ($gl) return $gl;
    else return 'en_US';
}


function digits_add_scripts($usercode = 0)
{
    if ($usercode == 0) {
        $usercode = getUserCountryCode();
    }

    $digit_tapp = get_option('digit_tapp', 1);

    if ($digit_tapp == 1) {
        wp_register_script('account-kit', 'https://sdk.accountkit.com/' . dig_get_accountkit_locale() . '/sdk.js', array(), null, false);
    }

    wp_register_script('scrollTo', plugins_url('/assests/js/scrollTo.js', __FILE__, array('jquery'), null, true));

    wp_register_script('digits-main-script', plugins_url('/assests/js/main.min.js', __FILE__, dig_deps_scripts(), null, true));

    if (class_exists('WooCommerce') && is_checkout()) {
        $uri = $_SERVER['REQUEST_URI'];
    } else {
        $uri = site_url();
    }

    $app = get_option('digit_api');
    $appid = "";
    if ($app !== false) {
        $appid = $app['appid'];
    }

    $dig_reg_details = digit_get_reg_fields();

    $dig_login_details = digit_get_login_fields();

    $nameaccep = $dig_reg_details['dig_reg_name'];
    $usernameaccep = $dig_reg_details['dig_reg_uname'];
    $emailaccep = $dig_reg_details['dig_reg_email'];
    $passaccep = $dig_reg_details['dig_reg_password'];
    $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

    $emailormobile = __("Email/Mobile Number", "digits");

    $firebase = 0;
    if ($digit_tapp == 13) {
        $firebase = 1;
    }

    $verify_c = 0;

    if (!current_user_can('edit_user') && !current_user_can('administrator')) {
        $verify_c = 1;
    }

    $jsData = array(
        'dig_sortorder' => get_option("dig_sortorder"),
        'dig_dsb' => get_option('dig_dsb',-1),
        "Passwordsdonotmatch" => __("Passwords do not match!", "digits"),
        'fillAllDetails' => __('Please fill all the required details.', 'digits'),
        'accepttac' => __('Please accept terms & conditions.', 'digits'),
        'resendOtpTime' => dig_getOtpTime(),
        'useStrongPasswordString' => __('Please enter a stronger password.', 'digits'),
        'strong_pass' => dig_useStrongPass(),
        'firebase' => $firebase,
        'forgot_pass' => get_option('digforgotpass', 1),
        'mail_accept' => $dig_reg_details['dig_reg_email'],
        'pass_accept' => $dig_reg_details['dig_reg_password'],
        'mobile_accept' => $dig_reg_details['dig_reg_mobilenumber'],
        'login_mobile_accept' => $dig_login_details['dig_login_mobilenumber'],
        'login_mail_accept' => $dig_login_details['dig_login_email'],
        'login_otp_accept' => $dig_login_details['dig_login_otp'],
        'captcha_accept' => $dig_login_details['dig_login_captcha'],
        'ajax_url' => admin_url('admin-ajax.php'),
        'appId' => $appid,
        'uri' => $uri,
        'state' => wp_create_nonce('crsf-otp'),
        'uccode' => $usercode,
        'nonce' => wp_create_nonce('dig_form'),
        'auth' => get_option('digit_tapp', 1),
        'face' => plugins_url('digits/assests/images/face.png'),
        'cross' => plugins_url('digits/assests/images/cross.png'),
        'pleasesignupbeforelogginin' => __("Please signup before logging in.", 'digits'),
        'invalidapicredentials' => __("Invalid API credentials!", 'digits'),
        'invalidlogindetails' => __("Invalid login credentials!", 'digits'),
        'emailormobile' => $emailormobile,
        "RegisterWithPassword" => __("Register With Password", "digits"),
        "Invaliddetails" => __("Invalid details!", "digits"),
        'invalidpassword' => __("Invalid Password", "digits"),
        "InvalidMobileNumber" => __("Invalid Mobile Number!", "digits"),
        "InvalidEmail" => __("Invalid Email!", "digits"),
        'invalidcountrycode' => __("Invalid Country Code!", "digits"),
        "Mobilenumbernotfound" => __("Mobile number not found!", "digits"),
        "MobileNumberalreadyinuse" => __("Mobile Number already in use!", "digits"),
        "MobileNumber" => __("Mobile Number", "digits"),
        "InvalidOTP" => __("Invalid OTP!", "digits"),
        "Pleasetryagain" => __("Please try again", "digits"),
        "ErrorPleasetryagainlater" => __("Error! Please try again later", "digits"),
        "UsernameMobileno" => __("Username/Mobile Number", "digits"),
        "OTP" => __("OTP", "digits"),
        "resendOTP" => __("Resend OTP", "digits"),
        "verify_mobile" => $verify_c,
        "Password" => __("Password", "digits"),
        "ConfirmPassword" => __("Confirm Password", "digits"),
        "ohsnap" => __("Oh Snap!", "digits"),
        "pleaseentermobormail" => __("Please enter your Mobile Number/Email", "digits"),
        "eitherentermoborusepass" => __("Either enter your Mobile Number or use Password!", "digits"),
        "submit" => __("Submit", "digits"),
        "overwriteWcBillShipMob" => get_option('dig_bill_ship_fields', 0),
        "signupwithpassword" => __('SIGN UP WITH PASSWORD', 'digits'),
        "signupwithotp" => __('SIGN UP WITH OTP', 'digits'),
        "signup" => __('SIGN UP', 'digits'),
        "or" => __('OR', 'digits'),
        "email" => __('Email', 'digits'),
        "optional" => __('Optional', 'digits'),
        "error" => __('Error', 'digits'),
        "mob_verify_checkout" => get_option('dig_mob_ver_chk_fields', 1),
        'SubmitOTP' => __('Submit OTP', 'digits'),
        'Registrationisdisabled' => __('Registration is disabled', 'digits'),
        'forgotPasswordisdisabled' => __('Forgot Password is disabled', 'digits'),
    );
    wp_localize_script('digits-main-script', 'dig_mdet', $jsData);

    wp_register_script('digits-login-script', plugins_url('/assests/js/login.min.js', __FILE__, dig_deps_scripts(), null, true));

    $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $current_url = str_replace("login=true", "", $current_url);

    $t = get_option("digits_loginred");
    if (!empty($t)) $current_url = $t;

    $dig_login_details = digit_get_login_fields();

    $jsData = array(
        'dig_sortorder' => get_option("dig_sortorder"),
        'dig_dsb' => get_option('dig_dsb',-1),
        'show_asterisk' => get_option('dig_show_asterisk',0),
        'login_mobile_accept' => $dig_login_details['dig_login_mobilenumber'],
        'login_mail_accept' => $dig_login_details['dig_login_email'],
        'login_otp_accept' => $dig_login_details['dig_login_otp'],
        'captcha_accept' => $dig_login_details['dig_login_captcha'],
        "Passwordsdonotmatch" => __("Passwords do not match!", "digits"),
        'fillAllDetails' => __('Please fill all the required details.', 'digits'),
        'accepttac' => __('Please accept terms & conditions.', 'digits'),
        'resendOtpTime' => dig_getOtpTime(),
        'useStrongPasswordString' => __('Please enter a stronger password.', 'digits'),
        'strong_pass' => dig_useStrongPass(),
        'firebase' => $firebase,
        'mail_accept' => $dig_reg_details['dig_reg_email'],
        'pass_accept' => $dig_reg_details['dig_reg_password'],
        'mobile_accept' => $dig_reg_details['dig_reg_mobilenumber'],
        'username_accept' => $dig_reg_details['dig_reg_uname'],
        'ajax_url' => admin_url('admin-ajax.php'),
        'appId' => $appid,
        'uri' => $current_url,
        'state' => wp_create_nonce('crsf-otp'),
        'left' => 0,
        'verify_mobile' => 0,
        'face' => plugins_url('digits/assests/images/face.png'),
        'cross' => plugins_url('digits/assests/images/cross.png'),
        'Registrationisdisabled' => __('Registration is disabled', 'digits'),
        'forgotPasswordisdisabled' => __('Forgot Password is disabled', 'digits'),
        'invalidlogindetails' => __("Invalid login credentials!", 'digits'),
        'invalidapicredentials' => __("Invalid API credentials!", 'digits'),
        'pleasesignupbeforelogginin' => __("Please signup before logging in.", 'digits'),
        'pleasetryagain' => __("Please try again!", 'digits'),
        'invalidcountrycode' => __("Invalid Country Code!", "digits"),
        "Mobilenumbernotfound" => __("Mobile number not found!", "digits"),
        "MobileNumberalreadyinuse" => __("Mobile Number already in use!", "digits"),
        "Error" => __("Error", "digits"),
        'Thisfeaturesonlyworkswithmobilenumber' => __('This features only works with mobile number', 'digits'),
        "InvalidOTP" => __("Invalid OTP!", "digits"),
        "ErrorPleasetryagainlater" => __("Error! Please try again later", "digits"),
        "Passworddoesnotmatchtheconfirmpassword" => __("Password does not match the confirm password!", "digits"),
        "Invaliddetails" => __("Invalid details!", "digits"),
        "InvalidEmail" => __("Invalid Email!", "digits"),
        "InvalidMobileNumber" => __("Invalid Mobile Number!", "digits"),
        "eitherenterpassormob" => __("Either enter your mobile number or click on sign up with password", "digits"),
        "login" => __("Log In", "digits"),
        "signup" => __("Sign Up", "digits"),
        "ForgotPassword" => __("Forgot Password", "digits"),
        "Email" => __("Email", "digits"),
        "Mobileno" => __("Mobile Number", "digits"),
        "ohsnap" => __("Oh Snap!", "digits"),
        "submit" => __("Submit", "digits"),
        'SubmitOTP' => __('Submit OTP', 'digits')
    );
    wp_localize_script('digits-login-script', 'dig_log_obj', $jsData);

    wp_enqueue_script('jquery');
    if (getCurrentGateway() == 1) {
        wp_enqueue_script('account-kit');
        wp_add_inline_script('account-kit', iniAccInit());
    }

    wp_enqueue_script('scrollTo');
    wp_enqueue_script('digits-main-script');
    wp_enqueue_script('digits-login-script');
    wp_enqueue_style('google-roboto-regular', dig_fonts());
}

add_action('wp_enqueue_scripts', 'digits_add_scripts', 9999);

function digits_add_admin_scripts(){
    digits_add_scripts();

    wp_print_scripts('scrollTo');
    wp_print_scripts('digits-main-script');
    wp_print_scripts('digits-login-script');
    wp_print_scripts('google-roboto-regular', dig_fonts());
?>
    <style>
        .woocommerce-input-wrapper .dig_wc_countrycodecontainer{
            position: absolute;
        }
    </style>
    <?php
}
add_action('admin_print_footer_scripts', 'digits_add_admin_scripts');

/**
 * Show the signin/signup page.
 */
function removeParam($url, $param)
{
    $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*$/', '', $url);
    $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*&/', '$1', $url);
    return $url;
}

function digits_login()
{
    if (isset($_GET['logout']) && isset($_GET['lnounce'])) {
        if (!empty($_GET['logout']) || 'true' == $_GET['logout']) {
            $nounce = wp_verify_nonce($_GET['lnounce'], 'lnounce');
            if (is_user_logged_in() && $nounce) {
                $current_url = get_option("digits_logoutred");
                if (empty($current_url)) {
                    $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    $current_url = str_replace("?logout=true", "", $current_url);

                    $current_url = removeParam($current_url, "logout");
                    $current_url = removeParam($current_url, "lnounce");
                }
                wp_logout();
                wp_safe_redirect($current_url);
                exit();
            } else if ($_GET['logout'] && $_GET['lnounce']) {
                $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                $current_url = removeParam($current_url, "logout");
                $current_url = removeParam($current_url, "lnounce");
                wp_safe_redirect($current_url);
                exit();
            }
        }
    }
    if (!isset($_GET['login'])) return;
    if (empty($_GET['login']) || 'true' !== $_GET['login'] || is_user_logged_in()) {
        return;
    }
    function fs_get_wp_config_path()
    {
        $base = dirname(__FILE__);
        $path = false;

        if (@file_exists(dirname(dirname($base)) . "/wp-load.php")) {
            $path = dirname(dirname($base)) . "/wp-load.php";
        } else
            if (@file_exists(dirname(dirname(dirname($base))) . "/wp-load.php")) {
                $path = dirname(dirname(dirname($base))) . "/wp-load.php";
            } else
                $path = false;

        if ($path != false) {
            $path = str_replace("\\", "/", $path);
        }
        return $path;
    }

    // Redirect to https login if forced to use SSL
    if (force_ssl_admin() && !is_ssl()) {
        if (0 === strpos($_SERVER['REQUEST_URI'], 'http')) {
            wp_redirect(set_url_scheme($_SERVER['REQUEST_URI'], 'https'));
            exit();
        } else {
            wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
    require(fs_get_wp_config_path());

    $digforgotpass = get_option('digforgotpass', 1);
    $users_can_register = get_option('dig_enable_registration', 1);

    $page = !empty($_GET['page']) ? sanitize_text_field($_GET['page']) : '1';
    if (($users_can_register == 0 && $page == 2) || ($digforgotpass == 0 && $page == 3)) {
        $page = 1;
    }
    if ($page > 1 && $page > 3) {
        $page = 1;
    }

    if (isset($_POST['mobmail']) && $_POST['password']) {
        $page = 1;

        $dig_login_details = digit_get_login_fields();

        $emailaccep = $dig_login_details['dig_login_email'];
        $passaccep = $dig_login_details['dig_login_password'];
        $mobileaccp = $dig_login_details['dig_login_mobilenumber'];

        if ($passaccep == 0) return;

        if (isset($_POST['isimpc']) && defined('IHC_PATH')) {
            $emailaccep = 1;
        }

        $nounce = $_POST['dig_nounce'];
        if (!wp_verify_nonce($nounce, 'dig_form')) {
            return;
        }

        $username = sanitize_text_field($_POST['mobmail']);
        $password = sanitize_text_field($_POST['password']);

        $countrycode = sanitize_text_field($_POST['countrycode']);

        $credentials = array();
        $secure_cookie = false;
        $isValid = true;

        if (is_ssl()) {
            $secure_cookie = true;
        }

        if (is_numeric($username) && $mobileaccp == 1) {
            $temp_uname = sanitize_mobile_field_dig($username);
            $userfromName = getUserFromPhone("$countrycode$temp_uname");

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

        $invalid_message = __("Invalid Credentials!", "digits");
        $captcha = $dig_login_details['dig_login_captcha'];
        if($captcha==1){
            if(!dig_validate_login_captcha()) {
                if(!isset($_POST['isimpc'])){
                    $invalid_message = __("Please enter a valid captcha", "digits");
                    $isValid = false;
                }
            }
        }

        if ($isValid) {
            $credentials['user_login'] = $username;
            $credentials['user_password'] = $password;
            $credentials['remember'] = true;

            $user_obj = wp_signon($credentials, $secure_cookie);
        }

        if (is_wp_error($user_obj) || !$isValid) {
            if(is_wp_error($user_obj)){
                $invalid_message = $user_obj->get_error_message();
            }

            $login_message = "<span class=\"loginerrordg\">" . $invalid_message . "</span>";

            if (isset($_POST['isimpc'])) {
                $current_url = "//" . $_SERVER['HTTP_HOST'];

                //$current_url = dig_removeStringParameter($current_url, "login");
               // $current_url = dig_removeStringParameter($current_url, "page");
               // $current_url = $current_url . "/?ihc_login_fail=true";
                wp_safe_redirect($current_url);
                exit();
            }
        } else {


            if (isset($_POST['isimpc'])) {
                $current_url = "//" . $_SERVER['HTTP_HOST'];
            } else {
                $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                $current_url = dig_removeStringParameter($current_url, "login");
                $current_url = dig_removeStringParameter($current_url, "page");
            }

            $t = get_option("digits_loginred");
            if (!empty($t)) $current_url = $t;
            wp_safe_redirect($current_url);

            exit();
        }
    }

    function recoverpassword($user_login)
    {
        if ( class_exists( 'WooCommerce' ) ) {
            return wc_d_retrieve_password($user_login);
        }
        $errors = new WP_Error();
        if (empty($user_login)) {
            return false;
        } else if (strpos($user_login, '@')) {
            $user_data = get_user_by('email', trim($user_login));
            if (empty($user_data))
                return false;
        } else {
            $login = trim($user_login);
            $user_data = get_user_by('login', $login);
        }
        /**
         * Fires before errors are returned from a password reset request.
         *
         * @since 2.1.0
         * @since 4.4.0 Added the `$errors` parameter.
         *
         * @param WP_Error $errors A WP_Error object containing any errors generated
         *                         by using invalid credentials.
         */
        do_action('lostpassword_post', $errors);

        if ($errors->get_error_code())
            return false;

        if (!$user_data) {
            return false;
        }

        // Redefining user_login ensures we return the right case in the email.
        $user_login = $user_data->user_login;
        $user_email = $user_data->user_email;
        $key = get_password_reset_key($user_data);

        if (is_wp_error($key)) {
            return false;
        }

        $logo = get_option('digits_logo_image');
        if (!empty($logo)) {
            if (is_numeric($logo)) {
                $logo =  wp_get_attachment_url($logo);
            }
        }

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $subject = 'Password Reset - '.get_bloginfo('name', 'display');

        $activation_link = dig_fp_create_token($user_data->ID);

        $message = dig_curl(plugins_url(dig_get_template(3), __FILE__));
        $message = str_replace(array('{{firstname}}','{{passwordresetlink}}','{{sitename}}'),array($user_data->first_name,$activation_link,get_bloginfo('name', 'display')),$message);

        if ($message && !wp_mail($user_email, wp_specialchars_decode($subject), $message,$headers)) {
            wp_die(__('The email could not be sent.') . "<br />\n" . __('Possible reason: your host may have disabled the mail() function.'));
        }
        return true;
    }

    $invalid_token = "<span class='loginerrordg'>".__("Your password reset link appears to be expired or invalid. Please request a new link.",'digits')."</span>";

    $show_changepass_fields = false;

    if(isset($_GET['token']) && !isset($_POST['dig_token'])){
        if(!isset($_GET['user'])){
            unset($_GET['token']);
        }else{
            $user_login = sanitize_text_field($_GET['user']);
            $token = sanitize_text_field($_GET['token']);

            $user = get_user_by('login',$user_login);

            if($user==null || !$user){
                $forgmessage = $invalid_token;
                $page = 3;
                unset($_GET['token']);
            }else{

                if(dig_fp_verify_token($token,$user->ID)){
                    $page = 3;
                    $show_changepass_fields = true;

                    $refresh_token = dig_fp_refresh_token($user->ID);
                    if(!$refresh_token){
                        $forgmessage = $invalid_token;
                        unset($_GET['token']);
                    }else{
                        $_GET['token'] = $refresh_token;
                    }
                }else{
                    $forgmessage = $invalid_token;
                    $page = 3;
                    unset($_GET['token']);
                }
            }
        }
    }

    if (isset($_POST['forgotmail']) && $digforgotpass == 1) {

        $nounce = $_POST['dig_nounce'];
        if (!wp_verify_nonce($nounce, 'dig_form')) {
            return;
        }

        $code = sanitize_text_field($_POST['code']);
        $dig_otp = sanitize_text_field($_POST['dig_otp']);

        if (empty($code) && empty($dig_otp)) {
            $user_login = sanitize_text_field($_POST['forgotmail']);
            $forgotsuccess = recoverpassword($user_login);
            if ($forgotsuccess) {
                $forgmessage = "<span class='msggreen'>" . __("A password reset email has been sent to the email address, but may take several minutes to show up in your inbox. Please wait at least 10 minutes before attempting another reset.", "digits") . "</span>";
            } else {
                $forgmessage = "<span class='loginerrordg'>" . __("Invalid e-mail!", "digits") . "</span>";
            }
            $page = 3;
        }
    }

    //$page = 2;

    if ($digforgotpass == 1  && isset($_POST['digits_password'])
        && isset($_POST['digits_cpassword']) && isset($_POST['dig_token'])) {
        $nounce = $_POST['dig_nounce'];
        if (!wp_verify_nonce($nounce, 'dig_form')) {
            return;
        }

        $user_login = sanitize_text_field($_POST['forgotmail']);
        if(empty($user_login)) $user_login = sanitize_text_field($_POST['user']);

        if(empty($user_login))return;

        $password = sanitize_text_field($_POST['digits_password']);
        $cpassword = sanitize_text_field($_POST['digits_cpassword']);
        $code = sanitize_text_field($_POST['code']);
        $csrf = sanitize_text_field($_POST['csrf']);

        $token = sanitize_text_field($_POST['dig_token']);
        $user = get_user_by('login',$user_login);

        if (isset($_POST['dig_countrycodec'])) {
            $countrycode = sanitize_text_field($_POST['dig_countrycodec']);
        } else {
            $countrycode = sanitize_text_field($_POST['countrycode']);
        }

        $otp = sanitize_text_field($_POST['dig_otp']);

        if ($password != $cpassword || strlen($password)<6){
            $page = 3;
            $forgmessage = "<span class='loginerrordg'>".__('Passwords do not match!','digits'). "</span>";
            if(strlen($password)<6){
                $forgmessage = "<span class='loginerrordg'>".__('Please use a stronger password!','digits'). "</span>";
            }
        }else if(!empty($token)) {
            if ($user == null || !$user) {
                $login_message = $invalid_token;
                $page = 3;
                unset($_GET['token']);
            } else {
                if (dig_fp_verify_one_time_token($token, $user->ID)) {
                    dig_fp_changepass_redirect($user,$password);
                    die();
            } else {
                    $login_message = $invalid_token;
                    $page = 3;
                    unset($_GET['token']);
                }
            }
        }else if (!empty($code) || !empty($otp)) {
            $page = 3;
            if ($password != $cpassword) {
                $forgmessage = "<span class='loginerrordg'>" . __("Passwords do not match!") . "</span>";
                return;
            }

            $digit_tapp = get_option("digit_tapp", 1);
            if ($digit_tapp > 1) {
                $user_login = sanitize_mobile_field_dig($user_login);
                if (!empty($otp) && verifyOTP($countrycode, $user_login, $otp, true)) {
                    $phone = $countrycode . $user_login;
                } else {
                    $forgmessage = "<span class='loginerrordg'>" . __("Error", "digits") . "</span>";
                    return;
                }
            } else {
                if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                    $forgmessage = "<span class='loginerrordg'>" . __("Error", "digits") . "</span>";
                    return;
                }
                $json = getUserPhoneFromAccountkit($code);

                $phoneJson = json_decode($json, true);

                $phone = $phoneJson['phone'];
            }

            $userd = getUserFromPhone($phone);

            if ($userd != null) {
                wp_set_password($password, $userd->ID);

                if ($_POST['ihc']) {
                    $current_url = "//" . $_SERVER['HTTP_HOST'];
                } else {
                    $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

                    $current_url = dig_removeStringParameter($current_url, "login");
                    $current_url = dig_removeStringParameter($current_url, "page");
                }
                wp_set_current_user($userd->ID, $userd->user_login);
                wp_set_auth_cookie($userd->ID);

                $t = get_option("digits_forgotred");
                if (!empty($t)) $current_url = $t;
                wp_safe_redirect($current_url);
                die();
                $page = 1;
                $login_message = "<span class='msggreen'>" . __("Password changed successfully.", "digits") . "</span>";
            } else {
                $page = 3;
                $forgmessage = "<span class='loginerrordg'>" . __("Error! User not found.", "digits") . "</span>";
            }
        }
    }

    $validation_error = new WP_Error();

    if (isset($_POST['digits_reg_mail']) && isset($_POST['dig_nounce']) && $users_can_register == 1) {
        $nounce = $_POST['dig_nounce'];
        if (!wp_verify_nonce($nounce, 'dig_form')) {
            return;
        }

        $page = 2;

        $dig_reg_details = digit_get_reg_fields();

        $nameaccep = $dig_reg_details['dig_reg_name'];
        $usernameaccep = $dig_reg_details['dig_reg_uname'];
        $emailaccep = $dig_reg_details['dig_reg_email'];
        $passaccep = $dig_reg_details['dig_reg_password'];
        $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

        if ($emailaccep == 1 && $mobileaccp == 1) {
            $emailmob = __("Email/Mobile Number", "digits");
        } else if ($mobileaccp > 0) {
            $emailmob = __("Mobile Number", "digits");
        } else if ($emailaccep > 0) {
            $emailmob = __("Email", "digits");
        } else if ($usernameaccep == 0) {
            $usernameaccep = 1;
            $emailmob = __("Username", "digits");
        }

        $m = '';
        $name = '';
        $mail = '';
        $password = '';
        $username = '';

        if ($nameaccep > 0) $name = sanitize_text_field($_POST['digits_reg_name']);
        if ($emailaccep > 0) $mail = sanitize_email($_POST['dig_reg_mail']);
        if ($passaccep > 0) $password = sanitize_text_field($_POST['digits_reg_password']);
        if ($usernameaccep > 0) $username = sanitize_text_field($_POST['digits_reg_username']);

        $code = sanitize_text_field($_POST['code']);
        $csrf = sanitize_text_field($_POST['csrf']);
        $otp = sanitize_text_field($_POST['dig_otp']);

        if ($mobileaccp > 0) $m = sanitize_text_field($_REQUEST['digits_reg_mail']);

        if (empty($name) && $nameaccep == 2) {
            $validation_error->add("invalidname", __("Invalid Name!", "digits"));
        }

        if (empty($username) && $usernameaccep == 2) {
            $validation_error->add("invalidusername", __("Invalid Username!", "digits"));
        }

        if ($passaccep == 0) {
            $password = wp_generate_password();
        } else if ($passaccep == 2 && empty($password)) {
            $validation_error->add("invalidpassword", __("Invalid Password!", "digits"));
        } else {
            if (empty($code) && empty($otp) && empty($password) && $passaccep > 0) {
                $validation_error->add("invalidpassword", __("Invalid Password!", "digits"));
            } else {
                if (empty($password)) $password = wp_generate_password();
            }
        }

        if ($mobileaccp == 1 && !is_numeric($m) && $m == $mail) $m = '';

        if ($mobileaccp == 2) {
            if (empty($m) || !is_numeric($m) || (empty($code) && empty($otp))) {
                $validation_error->add("Mobile", __("Please enter as valid Mobile Number!", "digits"));
            }
        } else if ($mobileaccp == 1 && !empty($m)) {
            if (!is_numeric($m) || (empty($code) && empty($otp))) {
                $validation_error->add("Mobile", __("Please enter a valid Mobile Number!", "digits"));
            }
        }

        if ($emailaccep == 2) {
            if (empty($mail) || !isValidEmail($mail)) {
                $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
            }
        } else if ($emailaccep == 1 && !empty($mail)) {
            if (!isValidEmail($mail)) {
                $validation_error->add("Mail", __("Please enter a valid Email!", "digits"));
            }
        }

        if ($mobileaccp == 1 && $emailaccep == 1) {
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

        if (empty($username)) {
            if ($useMobAsUname == 1 && !empty($m)) {
                $tname = $m;
            } else if (!empty($name)) {
                $tname = $name;
            }

            if (empty($tname)) {
                if (!empty($mail)) $tname = strstr($mail, '@', true);
                else if (!empty($m)) $tname = $m;

                if (empty($tname)) {
                    $validation_error->add("username", __("Error while generating username!", "digits"));
                } else if (username_exists($tname)) {
                    $validation_error->add("mobileemail", __("Mobile/Email is already in use!", "digits"));
                } else {
                    $ulogin = $tname;
                }
            } else {
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
        } else {
            if (username_exists($username)) {
                $validation_error->add("UsernameinUse", __("Username is already in use!", "digits"));
            } else {
                $ulogin = $username;
            }
        }

        $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
        $reg_custom_fields = json_decode($reg_custom_fields, true);
        $validation_error = validate_digp_reg_fields($reg_custom_fields, $validation_error);

        if ((!empty($code) || !empty($otp)) && $mobileaccp > 0) {
            $digit_tapp = get_option("digit_tapp", 1);
            if ($digit_tapp == 1) {
                if (!wp_verify_nonce($csrf, 'crsf-otp')) {
                    $validation_error->add("Error", __("Error", "digits"));
                }
                $json = getUserPhoneFromAccountkit($code);

                $phoneJson = json_decode($json, true);

                $mob = $phoneJson['phone'];
                $phone = $phoneJson['nationalNumber'];
                $countrycode = $phoneJson['countrycode'];

                if ($json == null) {
                    $validation_error->add("apifail", __("Invalid API credentials!", "digits"));

                }
            } else {
                $m = sanitize_text_field($_REQUEST['digits_reg_mail']);
                $m2 = sanitize_text_field($_REQUEST['mobmail2']);
                if (is_numeric($m)) {
                    $m = sanitize_mobile_field_dig($m);
                    $countrycode = sanitize_text_field($_REQUEST['digregcode']);
                    if (verifyOTP($countrycode, $m, $otp, true)) {
                        $mob = $countrycode . $m;
                        $phone = $m;
                    }
                } else if (is_numeric($m2)) {
                    $countrycode = sanitize_text_field($_REQUEST['digregscode']);
                    $m2 = sanitize_mobile_field_dig($m2);
                    if (verifyOTP($countrycode, $m2, $otp, true)) {
                        $mob = $countrycode . $m2;
                        $phone = $m2;
                    }
                }
            }

            if (empty($ulogin)) {
                $mobu = str_replace("+", "", $mob);
                $check = username_exists($mobu);
                if (!empty($check)) {
                    $validation_error->add("MobinUse", __("Mobile number already in use!", "digits"));
                } else {
                    $ulogin = $mobu;
                }
            }

            $mobuser = getUserFromPhone($mob);
            if ($mobuser != null) {
                $validation_error->add("MobinUse", __("Mobile Number already in use!", "digits"));
            } else if (username_exists($mob)) {
                $validation_error->add("MobinUse", __("Mobile Number already in use!", "digits"));
            } else if ($mob == null) {
                $validation_error->add("MobinUse", __("Invalid Mobile Number", "digits"));
            }

            if (empty($ulogin)) $validation_error->add("username", __("Error while generating username!", "digits"));

            if (!$validation_error->get_error_code()) {
                $ulogin = sanitize_user($ulogin, true);
                $user_id = wp_create_user($ulogin, $password, $mail);
                $userd = get_user_by('ID', $user_id);

                if (!is_wp_error($user_id)) {
                    update_user_meta($user_id, 'digits_phone', $mob);
                    update_user_meta($user_id, 'digt_countrycode', $countrycode);
                    update_user_meta($user_id, 'digits_phone_no', $phone);
                } else {
                    $validation_error->add("Error", __("Error", "digits"));
                }

                $page = 2;
            }
        } else if ($emailaccep > 0) {
            if (empty($ulogin)) {
                $ulogin = strstr($mail, '@', true);
                if (username_exists($ulogin)) $validation_error->add("MailinUse", __("Email is already in use!", "digits"));
            }
            if (!$validation_error->get_error_code()) {
                $ulogin = sanitize_user($ulogin, true);
                $user_id = wp_create_user($ulogin, $password, $mail);
                $userd = get_user_by('ID', $user_id);

                $page = 2;
            }
        } else {
            if (empty($ulogin)) {
                $validation_error->add("username", __("Invalid Username!", "digits"));
            }
            if (!$validation_error->get_error_code()) {
                $ulogin = sanitize_user($ulogin, true);
                $user_id = wp_create_user($ulogin, $password);
                $userd = get_user_by('ID', $user_id);
            }
        }
        $page = 2;

        if (!is_wp_error($user_id) && !$validation_error->get_error_code()) {

            $defaultuserrole = get_option('defaultuserrole', "customer");
            wp_update_user(array(
                'ID' => $user_id,
                'role' => $defaultuserrole,
                'first_name' => $name,
                'display_name' => $name));


            if (class_exists('WooCommerce')) {
                // code that requires WooCommerce

                $userdaata = array(
                    'user_login' => $ulogin,
                    'user_pass' => $password,
                    'user_email' => $mail,
                    'role' => $defaultuserrole,
                );
                do_action('woocommerce_created_customer', $user_id, $userdaata, $password);

            } else {
                do_action('register_new_user', $user_id);
            }
            wp_set_current_user($userd->ID, $userd->user_login);


            update_digp_reg_fields($reg_custom_fields, $user_id);

            if (wp_validate_auth_cookie() == FALSE) {
                wp_set_auth_cookie($userd->ID, true, false);
            }

            $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            $current_url = dig_removeStringParameter($current_url, "login");
            $current_url = dig_removeStringParameter($current_url, "page");

            $t = get_option("digits_regred");
            if (!empty($t)) $current_url = $t;

            wp_safe_redirect($current_url);
            exit();
        } else {
            if (is_wp_error($user_id) && !$validation_error->get_error_code()) $validation_error = $user_id;
        }
    }

    // Don't index any of these forms
    add_action('login_head', 'wp_no_robots');

    add_action('login_head', 'wp_login_viewport_meta');

    $separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

    $color = get_option('digit_color');
    $bgcolor = "#4cc2fc";
    $fontcolor = 0;

    $loginboxcolor = "rgba(255,255,255,1)";
    $sx = 0;
    $sy = 2;
    $sspread = 0;
    $sblur = 4;
    $scolor = "rgba(0, 0, 0, 0.5)";

    $fontcolor2 = "rgba(255,255,255,1)";
    $fontcolor1 = "rgba(20,20,20,1)";

    $left_color = 'rgba(255,255,255,1)';
    $page_type = 1;
    $sradius = 4;
    $left_bg_position = 'Center Center';
    $left_bg_size = 'auto';
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];

        if (isset($color['fontcolor'])) {
            $fontcolor = $color['fontcolor'];
            $loginboxcolor = $bgcolor;
            $scolor = "rgba(0,0,0,0)";
            if ($fontcolor == 1) {
                $fontcolor1 = "rgba(20,20,20,1)";
                $fontcolor2 = "rgba(255,255,255,1)";
            }
        }
        if (isset($color['sx'])) {
            $sx = $color['sx'];
            $sy = $color['sy'];
            $sspread = $color['sspread'];
            $sblur = $color['sblur'];
            $scolor = $color['scolor'];
            $fontcolor1 = $color['fontcolor1'];
            $fontcolor2 = $color['fontcolor2'];
            $loginboxcolor = $color['loginboxcolor'];
            $sradius = $color['sradius'];
            $backcolor = $color['backcolor'];

        }
        if(isset($color['type'])){
            $page_type = $color['type'];
            if($page_type==2) {
                $left_color = $color['left_color'];
            }

            $input_bg_color = $color['input_bg_color'];
            $input_border_color = $color['input_border_color'];
            $input_text_color = $color['input_text_color'];
            $button_bg_color = $color['button_bg_color'];
            $signup_button_color = $color['signup_button_color'];
            $signup_button_border_color = $color['signup_button_border_color'];
            $button_text_color = $color['button_text_color'];
            $signup_button_text_color = $color['signup_button_text_color'];
            $left_bg_position = $color['left_bg_position'];
            $left_bg_size = $color['left_bg_size'];
        }
    }

    $digit_tapp = get_option("digit_tapp", 1);

    if ($digit_tapp == 1) {
        wp_register_script('account-kit', 'https://sdk.accountkit.com/' . dig_get_accountkit_locale() . '/sdk.js', array(), null, false);
    }
    wp_register_script('digits-login-script', plugins_url('/assests/js/login.min.js', __FILE__, dig_deps_scripts(), null, true));

    wp_register_style('digits-main-login-style', plugins_url('/assests/css/login_body.css', __FILE__), array(), null, 'all');
    wp_register_style('digits-login-style', plugins_url('/assests/css/login.min.css', __FILE__), array(), null, 'all');

    wp_register_script('scrollTo', plugins_url('/assests/js/scrollTo.js', __FILE__, array('jquery'), null, true));

    wp_enqueue_style('google-roboto-regular', dig_fonts());

    $userCountryCode = getUserCountryCode();

    ?>

    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <?php
        function wp_login_viewport_meta()
        {
            ?>
            <meta name="viewport" content="width=device-width"/>
        <?php } ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title><?php echo get_bloginfo('name', 'display') . $separator; ?><?php _e("Log In", "digits"); ?></title>
        <?php wp_enqueue_style('login');
        /**
         * Enqueue scripts and styles for the login page.
         *
         * @since 3.1.0
         */

        wp_enqueue_style('login');
        do_action('login_enqueue_scripts');
        do_action('login_head');

        nice_select_scr();
        /**
         * Fires in the login page header after scripts are enqueued.
         *
         * @since 2.1.0
         */
        do_action('login_head');

        wp_print_styles('digits-login-style');
        wp_print_styles('digits-main-login-style');

        wp_print_styles('google-roboto-regular');

        $current_url = "//" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $current_url = removeParam($current_url, "login");
        $current_url = removeParam($current_url, "page");

        $left = 9;

        $users_can_register = get_option('dig_enable_registration', 1);

        $theme = "dark";
        $themevar = "light";
        $themee = "lighte";
        $bgtype = "bgdark";
        $bgtransbordertype = "bgtransborderdark";

        $bg = get_option('digits_bg_image');
        $url = "";
        if (!empty($bg)) {
            if (is_numeric($bg)) {
                $bg = wp_get_attachment_url($bg);
            }
            $url = ", url(" . $bg . ")";
        }

        $custom_css = get_option('digit_custom_css');
        $custom_css = str_replace(array("\'",'/"'),array("'",'"'),$custom_css);
        ?>
        <style>
            <?php echo $custom_css;?>
            .dig-container {
                background-color: <?php echo $loginboxcolor; ?>;
                border-radius: <?php echo $sradius; ?>px;
                box-shadow: <?php echo $sx."px ".$sy."px ".$sblur."px ".$sspread."px ".$scolor; ?>
            }

            body {
                background: linear-gradient(<?php echo $bgcolor; ?>,<?php echo $bgcolor; ?>)<?php echo $url; ?>;
                background-size: cover;
                background-attachment: fixed;
            }

            .dig_ma-box .bglight {
                background-color: <?php echo $fontcolor1; ?>;
            }

            .dig-custom-field-type-radio .dig_opt_mult_con .selected:before,
            .dig-custom-field-type-radio .dig_opt_mult_con label:before,
            .dig-custom-field-type-tac .dig_opt_mult_con .selected:before,
            .dig-custom-field-type-checkbox .dig_opt_mult_con .selected:before,
            .dig-custom-field-type-tac .dig_opt_mult_con label:before,
            .dig-custom-field-type-checkbox .dig_opt_mult_con label:before{
                background-color: <?php echo $fontcolor1;?>;
            }

            <?php if($page_type==2){ ?>
                .dig_ul_left_side {
                    background: <?php echo $left_color;?>;
                }
            <?php
            $input_bg_color = $color['input_bg_color'];
            $input_border_color = $color['input_border_color'];
            $input_text_color = $color['input_text_color'];
            $button_bg_color = $color['button_bg_color'];
            $signup_button_color = $color['signup_button_color'];
            $signup_button_border_color = $color['signup_button_border_color'];
            $button_text_color = $color['button_text_color'];
            $signup_button_text_color = $color['signup_button_text_color'];
            $left_bg_position = $color['left_bg_position'];
            $left_bg_size = $color['left_bg_size'];

            ?>
            .dig_ul_left_side{
                background-repeat: no-repeat;
                background-size: <?php echo $left_bg_size;?>;
                background-position: <?php echo $left_bg_position;?>;
            }
            .dig_ma-box .bgtransborderdark {
                color: <?php echo $signup_button_text_color; ?>;
            }
            .dig_ma-box .dark input[type="submit"], .dig_ma-box .lighte {
                color: <?php echo $button_text_color; ?> !important;
            }
            .dig_ma-box .dark .nice-select span,.dig_ma-box .dark a, .dig_ma-box .dark .dig-cont-close, .dig_ma-box .dark, .dig_ma-box .dark label, .dig_ma-box .dark input, .dig_ma-box .darke ,
            .dig_pgmdl_2 label{
                color: <?php echo $fontcolor1;?> !important;
            }
            .dig_pgmdl_2 .dark .nice-select span{
                color: <?php echo $input_text_color;?> !important;
            }
            .dig-custom-field .nice-select{
                background: <?php echo $input_bg_color;?>;
                padding-left: 1em;
                border: 1px solid <?php echo $input_border_color; ?> !important;
            }
            .dig_pgmdl_2 .nice-select::after {
                border-bottom: 2px solid <?php echo $input_border_color; ?> !important;
                border-right: 2px solid <?php echo $input_border_color; ?> !important;
            }

            .dig_ma-box .bgdark {
                background-color: <?php echo $button_bg_color; ?>;
            }

            .dig_ma-box .bgtransborderdark {
                border: 1px solid <?php echo $signup_button_border_color; ?>;
                background: <?php echo $signup_button_color;?>;
            }

            .dig_pgmdl_2 .minput .countrycodecontainer input,
            .dig_pgmdl_2 .minput input[type='date'],
            .dig_pgmdl_2 .minput input[type='number'],
            .dig_pgmdl_2 .minput input[type='password'],
            .dig_pgmdl_2 .minput textarea,
            .dig_pgmdl_2 .minput input[type='text']{
                color: <?php echo $input_text_color;?> !important;
                background: <?php echo $input_bg_color;?>;
            }

            .dig_pgmdl_2 .minput .countrycodecontainer input,
            .dig_pgmdl_2 .minput input[type='date'],
            .dig_pgmdl_2 .minput input[type='number'],
            .dig_pgmdl_2 .minput textarea,
            .dig_pgmdl_2 .minput input[type='password'],
            .dig_pgmdl_2 .minput input[type='text'],
            .dig_pgmdl_2 input:focus:invalid:focus,
            .dig_pgmdl_2 textarea:focus:invalid:focus,
            .dig_pgmdl_2 select:focus:invalid:focus{
                border: 1px solid <?php echo $input_border_color;?> !important;
            }

            .dig_ma-box .countrycodecontainer .dark{
                border-right: 1px solid <?php echo $input_border_color; ?> !important;
            }

            .dig-bgleft-arrow-right {
                border-left-color: <?php echo $left_color;?>;
            }
            
            .dig_pgmdl_2 .minput .countrycodecontainer .dig_input_error,
            .dig_pgmdl_2 .minput .dig_input_error,
            .dig_pgmdl_2 .minput .dig_input_error[type='date'],
            .dig_pgmdl_2 .minput .dig_input_error[type='number'],
            .dig_pgmdl_2 .minput .dig_input_error[type='password'],
            .dig_pgmdl_2 .minput .dig_input_error[type='text'],
            .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
            .dig_pgmdl_2 .dig_input_error:focus:invalid:focus,
            .dig_pgmdl_2 .dig_input_error:focus:invalid:focus{
                border: 1px solid #E00000 !important;
            }
            <?php
                $footer_text_color = get_option('login_page_footer_text_color');
                if(!empty($footer_text_color)){
                    echo '.dig_lp_footer,.dig_lp_footer *{color: '.$footer_text_color.';}';
                }
            ?>
            <?php }else{
                ?>

            .dig_ma-box .dark input[type="submit"], .dig_ma-box .lighte {
                color: <?php echo $fontcolor2; ?>;
            }
            .dig_ma-box .bgdark {
                background-color: <?php echo $fontcolor1; ?>;
            }
            .dig_ma-box .dark .nice-select span, .dig_ma-box .dark a, .dig_ma-box .dark .dig-cont-close, .dig_ma-box .dark, .dig_ma-box .dark label, .dig_ma-box .dark input, .dig_ma-box .darke {
                color: <?php echo $fontcolor1; ?>;
            }
            .dig_ma-box .countrycodecontainer .dark {
                border-right: 1px solid <?php echo $fontcolor1; ?> !important;
            }
            .dig_ma-box .bgtransborderdark {
                border: 1px solid <?php echo $fontcolor1; ?>;
                background: transparent;
            }
            .dig-custom-field .nice-select {
                border-bottom: 1px solid <?php echo $fontcolor1; ?>;
            }

            <?php
            }
            if(is_rtl()){
               ?>

            .minput label {
                right: 0 !important;
                left: auto !important;
            }
            <?php
         }?>
        </style>
    </head>
    <body <?php if($page_type==2) echo 'class="dig_ul_divd"';?>>

    <?php if($page_type==2){
        $bg_left = get_option('digits_left_bg_image');

        if (!empty($bg_left)) {
            if (is_numeric($bg_left)) {
                $bg_left = wp_get_attachment_url($bg_left);
            }
        }
        ?>
    <div class="dig_ul_left_side" style="background-image: url('<?php echo $bg_left; ?>');">
    <?php
        $footer = trim(get_option('login_page_footer'));

             if(!empty($footer)){
                 echo '<div class="dig_lp_footer">'.base64_decode($footer).'</div>';
             }
    ?>
    </div>
        <div class="dig-bgleft-arrow-right"></div>

    <?php } ?>

    <div class="dig_ma-box <?php if($page_type==2) echo 'dig_pgmdl_2';?>">
    <div class="header <?php echo $theme; ?>">
        <?php if($page_type==1){?>
        <a href="<?php echo $current_url; ?>" <?php if (!empty($backcolor)) echo 'style="color:' . $backcolor . ';"'; ?>><span><?php _e("BACK", "digits"); ?></span></a>
        <?php }?>
    </div>
    <?php
    $logo = get_option('digits_logo_image');
    $top = 0;

    if (!empty($logo)) {
        $top = 0;
        ?>
        <div class="logocontainer"><a href="<?php echo get_home_url();?>"><img class="logo" src="<?php
            $imgid = $logo;
            if (is_numeric($imgid)) {
                echo wp_get_attachment_url($imgid);
            } else echo $imgid;
                ?>" alt="Logo" draggable="false"/></a>
        </div>
    <?php } ?>
    <div class="dig_clg_bx" style="opacity: 0;">
        <div class="dig-container dig_ma-box <?php echo $theme; ?> <?php if ($page == 2) echo 'dig-min-het'; ?>">

            <?php
            $dig_login_details = digit_get_login_fields();

            $emailaccep = $dig_login_details['dig_login_email'];
            $passaccep = $dig_login_details['dig_login_password'];
            $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
            $otpaccp = $dig_login_details['dig_login_otp'];

            $captcha = $dig_login_details['dig_login_captcha'];

            if ($emailaccep == 1 && $mobileaccp == 1) {
                $emailaccep = 2;
            }

            if ($emailaccep == 2) {
                $emailmob = __("Email/Mobile Number", "digits");
            } else if ($mobileaccp == 1) {
                $emailmob = __("Mobile Number", "digits");
            } else if ($emailaccep > 0) {
                $emailmob = __("Email", "digits");
            } else {
                $emailmob = __("Username", "digits");
            }

            if($page_type==2) dig_verify_otp_box();
            ?>

            <div class="digloginpage" <?php if ($page != 1) echo 'style="display: none;"'; ?>>
                <form method="post">
                    <div class="dig_rl_msg_div"><?php if (!empty($login_message)) echo "<br />" . $login_message; ?></div>
                    <div class="minput">
                        <input type="text" name="mobmail" id="dig-mobmail" value="<?php if (isset($username)) {
                            echo $username;
                        } ?>" required/>

                        <div class="countrycodecontainer logincountrycodecontainer">
                            <input type="text" name="countrycode"
                                   class="input-text countrycode logincountrycode <?php echo $theme; ?>"
                                   value="<?php if (isset($countrycode)) {
                                       echo $countrycode;
                                   } else echo $userCountryCode; ?>"
                                   maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"/>
                        </div>

                        <label><?php echo $emailmob; ?></label>
                        <span class="<?php echo $bgtype; ?>"></span></div>
                    <?php

                    if ($digit_tapp > 1 && $mobileaccp == 1) {
                        ?>
                        <div class="minput" id="dig_login_otp" style="display: none;">
                            <input type="text" name="dig_otp" id="dig-login-otp"/>
                            <label><?php _e("OTP", "digits"); ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                        <?php
                    }

                    if ($passaccep == 1) {
                        ?>
                        <div class="minput">
                            <input type="password" name="password" required/>
                            <label><?php _e("Password", "digits"); ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                        <?php
                    }

                    if($captcha==1) {
                        dig_show_login_captcha(1,$bgtype);
                    }
                    ?>

                    <input type="hidden" name="dig_nounce" class="dig_nounce"
                           value="<?php echo wp_create_nonce('dig_form') ?>">

                    <?php
                    if ($passaccep == 1) { ?>
                        <div class="logforb">
                            <input type="submit" class="<?php echo $themee; ?> <?php echo $bgtype; ?> button"
                                   value="<?php _e("Log In", "digits"); ?>"/>
                            <?php
                            $digforgotpass = get_option('digforgotpass', 1);
                            if ($digforgotpass == 1) {
                                ?>
                                <div class="forgotpasswordaContainer"><a
                                            class="forgotpassworda"><?php _e("Forgot your password?", "digits"); ?></a>
                                </div>
                            <?php } ?>
                        </div>
                        <?php
                    }

                    if ($mobileaccp == 1 && $otpaccp == 1) {
                        ?>

                        <div id="dig_login_va_otp"
                             class=" <?php echo $themee; ?> <?php echo $bgtype; ?> button loginviasms"><?php _e("Login With OTP", "digits"); ?></div>
                        <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_log_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . __("Resend OTP", "digits") . "<span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>

                        <?php
                    }

                    if ($users_can_register == 1) { ?>
                        <div class="signdesc"><?php _e("Don't have an account?", "digits"); ?></div>
                        <div class="signupbutton transupbutton <?php echo $bgtransbordertype; ?>"><?php _e("Sign Up", "digits"); ?></div>
                    <?php }

                    do_action('login_form');
                    ?>

                </form>
            </div>

            <?php

            if ($users_can_register == 1) {
                $dig_reg_details = digit_get_reg_fields();

                $nameaccep = $dig_reg_details['dig_reg_name'];
                $usernameaccep = $dig_reg_details['dig_reg_uname'];
                $emailaccep = $dig_reg_details['dig_reg_email'];
                $passaccep = $dig_reg_details['dig_reg_password'];
                $mobileaccp = $dig_reg_details['dig_reg_mobilenumber'];

                if ($emailaccep == 1 && $mobileaccp == 1) {
                    $emailmob = __("Email/Mobile Number", "digits");
                } else if ($mobileaccp > 0) {
                    $emailmob = __("Mobile Number", "digits");
                } else if ($emailaccep > 0) {
                    $emailmob = __("Email", "digits");
                } else if ($usernameaccep == 0) {
                    $usernameaccep = 1;
                    $emailmob = __("Username", "digits");
                }

                if ($emailaccep == 0) {
                    echo "<input type=\"hidden\" value=\"1\" id=\"disable_email_digit\" />";
                }
                if ($passaccep == 0) {
                    echo "<input type=\"hidden\" value=\"1\" id=\"disable_password_digit\" />";
                }

                ?>
                <div class="register" <?php if ($page == 2) echo 'style="display: block;"'; ?> >
                    <div class="dig_rl_msg_div"><span class="loginerrordg"><?php

                            if ($validation_error->get_error_code()) {
                                echo '<br /><ul>';
                                echo '<li>' . implode('</li><li>', $validation_error->get_error_messages()) . '</li>';
                                echo '</ul>';
                            }
                            ?></span></div>
                    <form method="post" class="digits_register">
                        <div class="dig_reg_inputs">
                        <?php
                        if ($nameaccep > 0) {
                            ?>
                            <div class="minput" id="dig_cs_name">
                                <input type="text" name="digits_reg_name" id="digits_reg_name"
                                       value="<?php if (isset($name)) {
                                           echo $name;
                                       } ?>" <?php if ($nameaccep == 2) echo "required"; ?>/>
                                <label><?php _e("Name", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        <?php }

                        if ($usernameaccep > 0) {
                            ?>
                            <div class="minput" id="dig_cs_username">
                                <input type="text" name="digits_reg_username" id="digits_reg_username"
                                       value="<?php if (isset($username)) {
                                           echo $username;
                                       } ?>" <?php if ($usernameaccep == 2) echo "required"; ?>/>
                                <label><?php _e("Username", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        <?php }

                        $reqoropt = "";

                        if ($emailaccep > 0 || $mobileaccp > 0) {
                            ?>
                            <div class="minput" id="dig_cs_email">
                                <input type="text" name="digits_reg_mail" id="digits_reg_email"
                                       value="<?php if ($emailaccep == 2 || $mobileaccp == 2) {
                                           if ($mobileaccp == 1) $reqoropt = "(" . __("Optional", 'digits') . ")";
                                           if (isset($mob)) echo $mob;
                                       } else if (isset($mail)) {
                                           echo $mail;
                                       } ?>" <?php if (empty($reqoropt)) echo 'required' ?>/>
                                <div class="countrycodecontainer registercountrycodecontainer">
                                    <input type="text" name="digregcode"
                                           class="input-text countrycode registercountrycode  <?php echo $theme; ?>"
                                           value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                           placeholder="<?php echo $userCountryCode; ?>" <?php if ($emailaccep == 2 || $mobileaccp == 2) echo 'required'; ?>/>
                                </div>
                                <label><?php if ($emailaccep == 2 && $mobileaccp == 2) echo __('Mobile Number', 'digits'); else echo $emailmob; ?><?php echo $reqoropt; ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                            <?php
                        }
                        if ($emailaccep > 0 && $mobileaccp > 0) {
                            $emailmob = __('Email/Mobile Number', 'digits');

                            $reqoropt = "";
                            if ($emailaccep == 1) {
                                $reqoropt = "(" . __("Optional", 'digits') . ")";
                            }
                            if ($emailaccep == 2 || $mobileaccp == 2) {
                                $emailmob = __('Email', 'digits');
                            }
                            ?>
                            <div class="minput dig-mailsecond" <?php if ($emailaccep != 2 && $mobileaccp != 2) {
                                echo 'style="display: none;"';
                            } ?> id="dig_cs_email">
                                <input type="text" name="mobmail2"
                                       id="dig-secondmailormobile" <?php if ($emailaccep == 2) echo "required"; ?>/>
                                <div class="countrycodecontainer secondregistercountrycodecontainer">
                                    <input type="text" name="digregscode"
                                           class="input-text countrycode registersecondcountrycode  <?php echo $theme; ?>"
                                           value="<?php echo $userCountryCode; ?>" maxlength="6" size="3"
                                           placeholder="<?php echo $userCountryCode; ?>"/>
                                </div>
                                <label><span id="dig_secHolder"><?php echo $emailmob; ?></span> <?php echo $reqoropt; ?>
                                </label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                            <?php
                        }

                        if ($passaccep > 0) {
                            ?>
                            <div class="minput" <?php if ($passaccep == 1) echo 'style="display: none;"'; ?> id="dig_cs_password">
                                <input type="password" name="digits_reg_password"
                                       id="digits_reg_password" <?php if ($passaccep == 2) echo "required"; ?>/>
                                <label><?php _e("Password", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        <?php }

                        show_digp_reg_fields(1, $bgtype);

                        echo '</div>';
                        $digit_tapp = get_option("digit_tapp", 1);
                        if ($digit_tapp > 1) {
                            ?>
                            <div class="minput" id="dig_register_otp" style="display: none;">
                                <input type="text" name="dig_otp" id="dig-register-otp"/>
                                <label><?php _e("OTP", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                            <?php
                        }
                        ?>

                        <input type="hidden" name="code" id="register_code"/>
                        <input type="hidden" name="csrf" id="register_csrf"/>
                        <input type="hidden" name="dig_reg_mail" id="dig_reg_mail">
                        <input type="hidden" name="dig_nounce" class="dig_nounce"
                               value="<?php echo wp_create_nonce('dig_form') ?>">
                        <div></div>

                        <?php
                        if ($mobileaccp > 0 || $passaccep == 0 || $passaccep == 2) {
                            if (($passaccep == 0 && $mobileaccp == 0) || $passaccep == 2 || ($passaccep==0 && $mobileaccp>0)) {
                                $subVal = __("Signup", "digits");
                            } else {
                                $subVal = __("Signup With OTP", "digits");
                            }
                            ?>

                            <button class="<?php echo $themee . ' ' . $bgtype; ?> button dig-signup-otp registerbutton"
                                    value="<?php echo $subVal; ?>" type="submit">
                                <?php echo $subVal; ?>
                            </button>
                            <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_reg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . __("Resend OTP", "digits") . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>
                        <?php } ?>

                        <?php if ($passaccep == 1) { ?>
                            <button class="<?php echo $themee . ' ' . $bgtype; ?> button registerbutton"
                                   id="dig_reg_btn_password" attr-dis="1"
                                   value="<?php _e("Signup With Password", "digits"); ?>" type="submit">
                                <?php _e("Signup With Password", "digits"); ?>
                            </button>
                        <?php } ?>
                        <div class="backtoLoginContainer"><a
                                    class="backtoLogin"><?php _e("Back to login", "digits"); ?></a>
                        </div>
                    </form>
                    <?php
                    do_action('register_form');
                    ?>
                </div>
                <?php
            }
            $top = $top;

            if ($digforgotpass == 1 && $dig_login_details['dig_login_password'] == 1) {
                $emailmob = __("Email/Mobile Number", "digits");
                ?>
                <div class="forgot" <?php if ($page == 3) echo 'style="display:block;"'; ?>>
                    <form method="post">
                        <div class="dig_rl_msg_div"><?php if (!empty($forgmessage)) echo "<br />" . $forgmessage; ?></div>

                        <input type="hidden" name="code" id="digits_code"/>
                        <input type="hidden" name="csrf" id="digits_csrf"/>

                        <?php if($show_changepass_fields) echo '<input type="hidden" id="digits_forgotPassChange" value="1"/>'; ?>

                        <input type="hidden" name="dig_nounce" class="dig_nounce"
                               value="<?php echo wp_create_nonce('dig_form') ?>" />

                        <?php if(!$show_changepass_fields){?>
                        <div class="minput forgotpasscontainer" >
                            <input type="text" name="forgotmail" id="forgotpass" required/>
                            <div class="countrycodecontainer forgotcountrycodecontainer">
                                <input type="text" name="countrycode"
                                       class="input-text countrycode forgotcountrycode  <?php echo $theme; ?>"
                                       value="<?php echo $userCountryCode; ?>"
                                       maxlength="6" size="3" placeholder="<?php echo $userCountryCode; ?>"/>
                            </div>
                            <label><?php echo $emailmob; ?></label>
                            <span class="<?php echo $bgtype; ?>"></span>
                        </div>
                        <?php } ?>

                        <?php
                        if ($digit_tapp > 1) {
                            ?>
                            <div class="minput" id="dig_forgot_otp" style="display: none;">
                                <input type="text" name="dig_otp" id="dig-forgot-otp"/>
                                <label><?php _e("OTP", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                            <?php
                        }
                        ?>
                        <input type="hidden" name="dig_token" class="dig_token"
                                   value="<?php if(isset($_GET['token'])) echo esc_attr($_GET['token']);?>" />
                            <input type="hidden" name="user" class="dig_user"
                                   value="<?php if(isset($_GET['user'])) echo esc_attr($_GET['user']);?>" />

                        <div class="changepassword"  <?php if($show_changepass_fields) echo 'style="display:block;"'?>>
                            <div class="minput">
                                <input type="password" id="digits_password" name="digits_password" required/>
                                <label><?php _e("Password", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>

                            <div class="minput">
                                <input type="password" id="digits_cpassword" name="digits_cpassword" required/>
                                <label><?php _e("Confirm Password", "digits"); ?></label>
                                <span class="<?php echo $bgtype; ?>"></span>
                            </div>
                        </div>
                        <button type="submit" class="<?php echo $themee; ?> <?php echo $bgtype; ?> button forgotpassword"
                                value="<?php _e("Reset Password", "digits"); ?>"><?php _e("Reset Password", "digits"); ?></button>
                        <?php if ($digit_tapp > 1) echo "<div  class=\"dig_resendotp dig_logof_forg_resend\" id=\"dig_lo_resend_otp_btn\" dis='1'>" . __("Resend OTP", "digits") . " <span>(00:<span>" . dig_getOtpTime() . "</span>)</span></div>"; ?>

                        <?php if(!$show_changepass_fields){?>
                        <div class="backtoLoginContainer"><a
                                    class="backtoLogin"><?php _e("Back to login", "digits"); ?></a></div>
                                    <?php } ?>
                    </form>
                </div>
                <?php
            }
            ?>
        </div>
<?php if($page_type==2){?>
<div class="dig_login_cancel">
<a href="<?php echo $current_url; ?>" <?php if (!empty($backcolor)) echo 'style="color:' . $backcolor . ';"'; ?>><span><?php _e("Cancel", "digits"); ?></span></a>
</div>
<?php } ?>
    </div>
    <?php $digpc = dig_get_option('dig_purchasecode');

    $style = "";
    if (!empty($backcolor)){ $style = 'fill:' . $fontcolor1 . '';}
    if (empty($digpc)) {
        ?>
        <div class='dig_powrd' style='opacity: 0;'>
            <a class='digmsg-pow' href='#'>                
            </a></div>
        <?php
    }
    ?>

    <div class="dig_load_overlay">
        <div class="dig_load_content">
            <div class="dig_spinner">
                <div class="dig_double-bounce1"></div>
                <div class="dig_double-bounce2"></div>
            </div>
            <?php
            if ($digit_tapp == 1) {
                echo '<div class="dig_overlay_text">' . __("Please check the Pop-up.", "digits") . '</div>';
            }

            ?>

        </div>
    </div>
    </div>
    </body>

    <?php
    do_action('login_footer');

    do_action('wp_print_scripts');
    wp_print_scripts('jquery');

    if (getCurrentGateway() == 1) {
        wp_add_inline_script('account-kit', iniAccInit());
        wp_print_scripts('account-kit');
    }
    wp_print_scripts('password-strength-meter');
    wp_print_scripts('scrollTo');

    digCountry();
    $app = get_option('digit_api');
    $appid = "";
    if ($app !== false) {
        $appid = $app['appid'];
    }

    $t = get_option("digits_loginred");
    if (!empty($t)) $current_url = $t;

    $firebase = 0;
    if (get_option('digit_tapp', 1) == 13) {
        $firebase = 1;
    }

    $dig_login_details = digit_get_login_fields();

    $jsData = array(
        'dig_sortorder' => get_option("dig_sortorder"),
        'dig_dsb' => get_option('dig_dsb',-1),
        'show_asterisk' => get_option('dig_show_asterisk',0),
        'login_mobile_accept' => $dig_login_details['dig_login_mobilenumber'],
        'login_mail_accept' => $dig_login_details['dig_login_email'],
        'login_otp_accept' => $dig_login_details['dig_login_otp'],
        'captcha_accept' => $dig_login_details['dig_login_captcha'],
        "Passwordsdonotmatch" => __("Passwords do not match!", "digits"),
        'fillAllDetails' => __('Please fill all the required details.', 'digits'),
        'accepttac' => __('Please accept terms & conditions.', 'digits'),
        'resendOtpTime' => dig_getOtpTime(),
        'useStrongPasswordString' => __('Please enter a stronger password.', 'digits'),
        'strong_pass' => dig_useStrongPass(),
        'firebase' => $firebase,
        'mail_accept' => $dig_reg_details['dig_reg_email'],
        'pass_accept' => $dig_reg_details['dig_reg_password'],
        'mobile_accept' => $dig_reg_details['dig_reg_mobilenumber'],
        'username_accept' => $dig_reg_details['dig_reg_uname'],
        'ajax_url' => admin_url('admin-ajax.php'),
        'appId' => $appid,
        'uri' => $current_url,
        'state' => wp_create_nonce('crsf-otp'),
        'left' => $left,
        'face' => plugins_url('digits/assests/images/face.png'),
        'cross' => plugins_url('digits/assests/images/cross.png'),
        'Registrationisdisabled' => __('Registration is disabled', 'digits'),
        'forgotPasswordisdisabled' => __('Forgot Password is disabled', 'digits'),
        'invalidlogindetails' => __("Invalid login credentials!", 'digits'),
        'invalidapicredentials' => __("Invalid API credentials!", 'digits'),
        'pleasesignupbeforelogginin' => __("Please signup before logging in.", 'digits'),
        'pleasetryagain' => __("Please try again!", 'digits'),
        'invalidcountrycode' => __("Invalid Country Code!", "digits"),
        "Mobilenumbernotfound" => __("Mobile number not found!", "digits"),
        "MobileNumberalreadyinuse" => __("Mobile Number already in use!", "digits"),
        "Error" => __("Error", "digits"),
        'Thisfeaturesonlyworkswithmobilenumber' => __('This features only works with mobile number', 'digits'),
        "InvalidOTP" => __("Invalid OTP!", "digits"),
        "ErrorPleasetryagainlater" => __("Error! Please try again later", "digits"),
        "Passworddoesnotmatchtheconfirmpassword" => __("Password does not match the confirm password!", "digits"),
        "Invaliddetails" => __("Invalid details!", "digits"),
        "InvalidEmail" => __("Invalid Email!", "digits"),
        "InvalidMobileNumber" => __("Invalid Mobile Number!", "digits"),
        "eitherenterpassormob" => __("Either enter your mobile number or click on sign up with password", "digits"),
        "login" => __("Log In", "digits"),
        "signup" => __("Sign Up", "digits"),
        "ForgotPassword" => __("Forgot Password", "digits"),
        "Email" => __("Email", "digits"),
        "Mobileno" => __("Mobile Number", "digits"),
        "ohsnap" => __("Oh Snap!", "digits"),
        "submit" => __("Submit", "digits"),
        'SubmitOTP' => __('Submit OTP', 'digits')
    );
    wp_localize_script('digits-login-script', 'dig_log_obj', $jsData);

    digits_in_script();
    wp_print_scripts('digits-login-script');

    ?>
    <script>
        jQuery(document).ready(function () {
            var reg;
            var ecd = jQuery(".dig_powrd");
            var b = jQuery(".dig_clg_bx");
            var c = jQuery(".logocontainer");
            var logp = jQuery(".digloginpage");
            var regp = jQuery(".register");
            var digc = jQuery(".dig-container");
            var digimgCon = jQuery(".dig_ul_left_side");
            var header = jQuery(".header");
            var dig_ma_box = jQuery(".dig_ma-box");

            jQuery(window).load(function () {
                updatePos();
            });
            jQuery(window).resize(function () {
                updatePos();
            });

            var updateLeftBx = function(){
                digimgCon.height(jQuery(document).height());
            };

            function updatePos() {
                if (regp.is(":visible")) {
                    reg = true;
                } else reg = false;
                updatebox(reg);
            }

            function updatebox(upRegHe) {
                var f, at;
                var minTo = 90;
                if (c.length > 0) {
                    f = c.height();
                    at = 25;
                }
                else {
                    f = 0;
                    at = 0;
                }

                var h = jQuery(window).height();

                var boxh = logp.outerHeight(true) + 44;

                if (upRegHe) {

                    var regh = regp.outerHeight(true) + 44;
                    if (regh > boxh) {
                        boxh = regh;
                    }
                }

                var ecdH = 0;
                if (ecd.length) {
                    ecdH = ecd.outerHeight(true);
                }
                var t = (h - f - boxh + at + ecdH + 28) / 2;

                var min_top = 70;

                if(!header.is(":visible")) {
                    min_top = 60;
                    minTo = min_top + 20;
                }

                if (c.length > 0) c.stop().animate({"top": Math.max(min_top, t - at), "opacity": 1}, 200);

                b.stop().animate({"top": Math.max(minTo, t), "opacity": 1}, 200);

                digc.height(boxh);

                if (ecd.length) {
                    ecd.animate({"opacity": "1"});
                }
            }

            jQuery(".signupbutton").click(function () {
                updatebox(true);
            })
            jQuery(".backtoLogin").click(function () {
                updatebox(false);
            })

        });
    </script>
    </html>
    <?php
    die();
}

function cust_dig_filter_string($string){
    $string = str_replace(array( '[t]', '[/t]','[p]', '[/p]' ), '', preg_replace('/\s+/', '', $string));
    return strtolower(dig_filter_string( $string));
}

function dig_filter_string($string)
{
    if(empty($string)) return $string;

    $string = preg_replace('/[^\p{L}\p{N}\s]/u', '', $string);

    return esc_attr(trim( $string));
}

function dig_update_wpwc_custom_fields($user_id,$meta_key,$value){
    if($meta_key=='display_name' || $meta_key=='last_name'){
        wp_update_user( array( 'ID' => $user_id, $meta_key => $value ) );
        return true;
    }else if($meta_key=='user_role'){
        $user = get_user_by('ID',$user_id);
        $user->set_role($value);
    }

    return false;
}

function update_digp_reg_fields($reg_custom_fields, $user_id)
{
    foreach ($reg_custom_fields as $label => $values) {
        $type = strtolower($values['type']);
        $meta_key = cust_dig_filter_string($values['meta_key']);

        if ($type == "captcha") continue;
        $label = cust_dig_filter_string($label);
        if (!isset($_POST['digits_reg_' . $meta_key])) continue;
        $e_value = $_POST['digits_reg_' . $meta_key];

        if(dig_update_wpwc_custom_fields($user_id,$values['meta_key'],$e_value))continue;

        if ($type == "textarea") $e_value = sanitize_textarea_field($e_value);
        else if($type=="checkbox"){
            $vals = array();

            foreach($e_value as $val){
                $vals[] = sanitize_text_field($val);
            }
            $e_value = $vals;

        }else $e_value = sanitize_text_field($e_value);

        dig_update_custom_field_data($user_id, sanitize_text_field($values['meta_key']), $e_value);
    }
}

function dig_update_custom_field_data($user_id,$meta_key,$value){
    update_user_meta($user_id, $meta_key, $value);
}

/*
 * todo: remove getting value from label after version 6
 * */
function dig_get_custom_field_data($user_id,$meta_key,$label = null,$single = true){    
    $value = get_user_meta($user_id, $meta_key, true);
    if($value==null && $label!=null){
        $value = get_user_meta($user_id, $label, true);
        update_user_meta($user_id,$meta_key,$value);
    }

    return $value;
}

function validate_digp_reg_fields($reg_custom_fields, $error, $captcha = true)
{
    if (session_id() == '')
        session_start();

    foreach ($reg_custom_fields as $label => $values) {

        $custom_class = null;
        $lb_class = null;
        $label = cust_dig_filter_string($label);
        $type = strtolower($values['type']);
        $required = $values['required'];
        $meta_key = cust_dig_filter_string($values['meta_key']);

        $post_index = 'digits_reg_' . $meta_key;

        if(dig_custom_hide_to_loggedin($type,$values['meta_key']))continue;

        $e_value = $_POST[$post_index];

        if(!is_array($e_value)) $e_value = trim($e_value);
        if ($required == 1 && empty($e_value)) {
            if ($type == "captcha" && !$captcha) continue;

            $error->add("incompletedetails", __('Please fill all the required details!', 'digits'));
            break;
        } else {
            if ($type == "captcha") {
                $ses = filter_var($_POST['dig_captcha_ses'], FILTER_SANITIZE_NUMBER_FLOAT);
                if ($e_value != $_SESSION['dig_captcha' . $ses] && $captcha) {
                    $error->add("captcha", __('Please enter a valid Captcha!', 'digits'));
                } else if (isset($_SESSION['dig_captcha' . $ses])) {
                    unset($_SESSION['dig_captcha' . $ses]);
                }
            } else if($type == "tac"){
                if($e_value!=1){
                    $error->add("tac", __('Please accept terms and condition!', 'digits'));
                }
            } else {
                if($type=='user_role'){
                    $type = 'dropdown';
                }
                if ( $type == "dropdown" || $type == "radio" ) {
                    if($required==0 && empty($e_value)){
                        continue;
                    }else if (!in_array($e_value, $values['options'])) {
                        $error->add("invalidValue", __('Please select a valid option!', 'digits'));
                    }
                }else if($type == "checkbox"){
                    if($required==0 && empty($e_value)){
                        continue;
                    }
                    if(!is_array($e_value)) $error->add("invalidValue", __('Please select a valid option!', 'digits'));

                    foreach ($e_value as $ev) {
                        if (!in_array($ev, $values['options'])) {
                            $error->add("invalidValue", __('Please select a valid option!', 'digits'));
                        }
                    }
                }
            }
        }
    }

    return $error;
}

function dig_custom_show_label($type){
    if($type=='tac') return false;

    return true;
}

function dig_custom_hide_to_loggedin($type,$meta_key){
    if(!is_user_logged_in())return false;

    $hidden_types = array('captcha','tac','user_role');
    if(in_array($type,$hidden_types)) return true;

    $show = apply_filters('dig_show_field_to_loggedin_user',$type,$meta_key);

    return $show;
}

function dig_show_login_captcha($login_page = 1, $bgtype = null, $user_id = 0){
    if(isset($_POST['digits_reg_logincaptcha'])) unset($_POST['digits_reg_logincaptcha']);
    dig_show_fields(array('Captcha'=>array(
            'label' => 'Captcha',
        'type' => 'captcha',
        'required' => '1',
        'meta_key' => 'login_captcha',
        'custom_class' => 'login_captcha'
    )),0,$login_page, $bgtype,$user_id);
}
/*
 * 1-> digits
 * 2-> WC
 * 3-> WP
 */
function show_digp_reg_fields($login_page = 1, $bgtype = null, $user_id = 0)
{
    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));
    $reg_custom_fields = json_decode($reg_custom_fields, true);
    $show_asterisk = get_option('dig_show_asterisk',0);
    dig_show_fields($reg_custom_fields,$show_asterisk,$login_page,$bgtype,$user_id);
}

function dig_show_fields($reg_custom_fields,$show_asterisk, $login_page = 1, $bgtype = null, $user_id = 0)
{
    foreach ($reg_custom_fields as $label => $values) {
        $asterisk = ( $show_asterisk == 1 && $values['required'] == 1)? ' *': '';

        $custom_class = null;
        $lb_class = null;
        $label = cust_dig_filter_string($label);

        $meta_key = cust_dig_filter_string($values['meta_key']);
        $type = strtolower($values['type']);

        if(is_user_logged_in()){
            if(dig_custom_hide_to_loggedin($type,$values['meta_key'])){
                continue;
            }
        }

        $wcClass = '';

        if ($login_page == 2) {
            $wcClass = 'woocommerce-Input woocommerce-Input--text input-text';
        }
        if (!empty($values['custom_class'])) {
            $custom_class = 'class="' . dig_filter_string($values['custom_class']) . ' ' . $wcClass . '"';
        }

        $e_value = false;

        if (isset($_POST['digits_reg_' . $meta_key])) {
            $e_value = cust_dig_filter_string($_POST['digits_reg_' . $meta_key]);
        }

        $extra_style = '';
        $user_role = 0;
        if($type=='user_role'){
            $type = 'dropdown';
            $user_role = 1;
        }
        if ($type == "dropdown") {
            $extra_style = 'style="min-height:32px;"';
        }

        if ($login_page == 1) {
            $dg = 'dg_min_capt';
            if ($type != "captcha") $dg = '';
            echo '<div id="dig_cs_'.cust_dig_filter_string($label).'" class="minput ' . $dg . ' dig-custom-field dig-custom-field-type-'.$type.'" '.$extra_style.'>';
        } else if ($login_page == 2) {
            echo '<div id="dig_cs_'.cust_dig_filter_string($label).'" class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide dig-custom-field dig-custom-field-type-'.$type.'" '.$extra_style.'>';
            echo '<p class="woocommerce-FormRow woocommerce-FormRow--wide form-row form-row-wide">';
            if(dig_custom_show_label($type)) {
                ?>
                <label for="digits_reg_<?php echo $meta_key; ?>"><?php _e($values['label'], "digits");
                    if ($values['required'] == 1) echo '<span class="required">*</span>'; ?></label>
                <?php
            }
        } else if ($login_page == 3) {
            echo '<tr id="dig_cs_'.cust_dig_filter_string($label).'">';
            ?>
            <th>
            <?php
            if(dig_custom_show_label($type)){
                ?>
                <label for="digits_reg_<?php echo $meta_key; ?>"><?php _e($values['label'], "digits"); ?></label>
                <?php }?>
                </th>
            <?php
            echo '<td>';
            $e_value = dig_get_custom_field_data($user_id,sanitize_text_field($values['meta_key']),sanitize_text_field($label), true);
        }        
        if ($type == "captcha") {
            if ($login_page == 3) continue;
            show_digcaptcha();
        }

        if ($type == "textarea") {
            ?>
            <textarea type="<?php echo $type; ?>" name="digits_reg_<?php echo $meta_key; ?>"
                      id="digits_reg_<?php echo $meta_key; ?>" <?php echo $custom_class; ?> <?php if ($values['required'] == 1) echo "required"; ?>
                      rows="2"><?php if ($e_value) echo $e_value; ?></textarea>
            <?php
        } else if ($type == "dropdown" || $type == "checkbox" || $type == "radio") {
            if($type == "dropdown" && $user_role==1){
                nice_select_scr();
            global $wp_roles;
            $roles = $wp_roles->roles;
                ?>
                <select name="digits_reg_<?php echo $meta_key; ?>" <?php echo $custom_class; ?> <?php if ($values['required'] == 1) echo "required"; ?>>
                    <?php

                    if(empty($e_value)){
                        $selected = "selected";
                    }

                    $drop_required = '';
                    if ($values['required'] === 1){
                        $drop_required = 'disabled';
                    }

                    echo '<option value '.$drop_required.' '.$selected.' data-display="'.__($values['label'],'digits') .'">'.__('Nothing','digits') .'</option>';

                    foreach ($values['options'] as $option) {
                        $selected = "";
                        $option = dig_filter_string($option);
                        if ($e_value == $option) $selected = "selected";
                        echo "<option " . $selected . " value='".$option."'>" . $roles[$option]['name'] . "</option>";
                    }
                    ?>
                </select>
                <?php

            }else if ($type == "dropdown") {
                nice_select_scr();

                $drop_required = '';
                    if ($values['required'] === 1){
                        $drop_required = 'disabled';
                    }

                ?>
                <select name="digits_reg_<?php echo $meta_key; ?>" <?php echo $custom_class; ?> <?php if ($values['required'] == 1) echo "required"; ?>>
                    <?php

                    if(empty($e_value)){
                        $selected = "selected";
                    }

                    echo '<option value '.$drop_required.' '.$selected.' data-display="'.__($values['label'],'digits') .'">'.__('Nothing','digits') .'</option>';

                    foreach ($values['options'] as $option) {
                        $selected = "";
                        $option = dig_filter_string($option);
                        if ($e_value == $option) $selected = "selected";
                        echo "<option " . $selected . ">" . $option . "</option>";
                    }
                    ?>
                </select>

                <?php
            } else {
                $re = '';
                if ($values['required'] == 1) $re = "data-req=1";

                echo "<div class='dig_opt_mult_con'>";

                $ar = "";
                if($type=='checkbox'){
                    $ar = "[]";

                }

                foreach ($values['options'] as $option) {
                    $lb_class = "dig_opt_mult_lab";
                    $option = dig_filter_string($option);

                    $selected = "";
                    if ($e_value == $option || ($type=='checkbox' && is_array($e_value) && in_array($option,$e_value))) $selected = "checked";

                    echo '<div class="dig_opt_mult" ><label for="digits_reg_for_'.$meta_key.'_' . $option . '"><input ' . $re . ' name="digits_reg_' . $meta_key . $ar . '" ' . $custom_class . ' id="digits_reg_for_'.$meta_key.'_' . $option . '" type="' . $type . '" value="' . $option . '" ' . $selected . '>' . $option . '</label></div><br />';

                }
                echo "</div>";
            }
        } else if($type == 'tac'){
            $re = '';
            if ($values['required'] == 1) $re = "data-req=1";
            echo "<div class='dig_opt_mult_con dig_opt_mult_con_tac'><div class=\"dig_opt_mult\" >";

            $option = $values['label'];
            $tac = $option;

            $defaultValues = array('[t]','[/t]','[p]','[/p]');

            $links = array('<a href="'.$values['tac_link'].'" target="_blank">','</a>','<a href="'.$values['tac_privacy_link'].'" target="_blank">','</a>');

            $tac = str_replace($defaultValues,$links,$tac);

            echo '<label for="digits_reg_for_' . $option . '"><input ' . $re . ' name="digits_reg_' . $meta_key . '" ' . $custom_class . ' id="digits_reg_for_' . $option . '" type="checkbox" value="1">' . $tac. '</label>';

            echo "</div></div>";
        }else {
            ?>

            <input type="<?php if ($type == "captcha") echo "text"; else echo $type; ?>"
                   name="digits_reg_<?php echo $meta_key; ?>"
                   id="digits_reg_<?php echo $meta_key; ?>" <?php echo $custom_class; ?> <?php if ($values['required'] == 1) echo "required"; ?>
                   value="<?php if ($e_value) echo $e_value; ?>"
            />
            <?php
        }

        ?>

        <?php
        if ($login_page == 1) {
            if(dig_custom_show_label($type)) {
                ?>
                <label <?php if (!empty($lb_class)) echo 'class="' . $lb_class . '"'; ?>><?php _e($values['label'], "digits");
                    echo $asterisk; ?></label>
                <?php
            }
            if ($type != "dropdown") echo '<span class="' . $bgtype . '"></span>';

            echo '</div>';
        } else if ($login_page == 2) echo '</p></div>';
        else if ($login_page == 3) echo '</td></tr>';
    }
}

function show_digcaptcha()
{
    $r = mt_rand();
    $cap = plugins_url('/captcha/captcha.php', __FILE__);
    ?>
    <input type="hidden" class="dig_captcha_ses" name="dig_captcha_ses" value="<?php echo $r; ?>"/>
    <img src="<?php echo $cap . '?r=' . $r; ?>" cap_src="<?php echo $cap; ?>" class="dig_captcha"
         draggable="false"/>
    <?php
}

function getCountryCode($country)
{
    if ($country == "") return '';
    $countryarray = getCountryList();

    $whiteListCountryCodes = get_option("whitelistcountrycodes");

    if (is_array($whiteListCountryCodes)) {
        $size = sizeof($whiteListCountryCodes);

        if ($size > 0) {
            if (!in_array($country, $whiteListCountryCodes)) {
                $defaultccode = get_option("dig_default_ccode");
                if (!in_array($defaultccode, $whiteListCountryCodes)) {
                    return $countryarray[$whiteListCountryCodes[0]];
                } else return $countryarray[$defaultccode];
            }
        }
    }
    if (array_key_exists($country, $countryarray)) {
        return $countryarray[$country];
    } else return '';
}

function getCountryList()
{
    return array(
        __("Afghanistan", "digits") => "93", __("Albania", "digits") => "355",__("Algeria", "digits") => "213",__("American Samoa", "digits") => "1",__("Andorra", "digits") => "376",__("Angola", "digits") => "244",__("Anguilla", "digits") => "1",__("Antigua", "digits") => "1",__("Argentina", "digits") => "54",__("Armenia", "digits") => "374",__("Aruba", "digits") => "297",__("Australia", "digits") => "61",__("Austria", "digits") => "43",__("Azerbaijan", "digits") => "994",__("Bahrain", "digits") => "973",__("Bangladesh", "digits") => "880",__("Barbados", "digits") => "1",__("Belarus", "digits") => "375",__("Belgium", "digits") => "32",__("Belize", "digits") => "501",__("Benin", "digits") => "229",__("Bermuda", "digits") => "1",__("Bhutan", "digits") => "975",__("Bolivia", "digits") => "591",__("Bonaire, Sint Eustatius and Saba", "digits") => "599",__("Bosnia and Herzegovina", "digits") => "387",__("Botswana", "digits") => "267",__("Brazil", "digits") => "55",__("British Indian Ocean Territory", "digits") => "246",__("British Virgin Islands", "digits") => "1",__("Brunei", "digits") => "673",__("Bulgaria", "digits") => "359",__("Burkina Faso", "digits") => "226",__("Burundi", "digits") => "257",__("Cambodia", "digits") => "855",__("Cameroon", "digits") => "237",__("Canada", "digits") => "1",__("Cape Verde", "digits") => "238",__("Cayman Islands", "digits") => "1",__("Central African Republic", "digits") => "236",__("Chad", "digits") => "235",__("Chile", "digits") => "56",__("China", "digits") => "86",__("Colombia", "digits") => "57",__("Comoros", "digits") => "269",__("Cook Islands", "digits") => "682",__("Costa Rica", "digits") => "506",__("Côte d'Ivoire", "digits") => "225",__("Croatia", "digits") => "385",__("Cuba", "digits") => "53",__("Curaçao", "digits") => "599",__("Cyprus", "digits") => "357",__("Czech Republic", "digits") => "420",__("Democratic Republic of the Congo", "digits") => "243",__("Denmark", "digits") => "45",__("Djibouti", "digits") => "253",__("Dominica", "digits") => "1",__("Dominican Republic", "digits") => "1",__("Ecuador", "digits") => "593",__("Egypt", "digits") => "20",__("El Salvador", "digits") => "503",__("Equatorial Guinea", "digits") => "240",__("Eritrea", "digits") => "291",__("Estonia", "digits") => "372",__("Ethiopia", "digits") => "251",__("Falkland Islands", "digits") => "500",__("Faroe Islands", "digits") => "298",__("Federated States of Micronesia", "digits") => "691",__("Fiji", "digits") => "679",__("Finland", "digits") => "358",__("France", "digits") => "33",__("French Guiana", "digits") => "594",__("French Polynesia", "digits") => "689",__("Gabon", "digits") => "241",__("Georgia", "digits") => "995",__("Germany", "digits") => "49",__("Ghana", "digits") => "233",__("Gibraltar", "digits") => "350",__("Greece", "digits") => "30",__("Greenland", "digits") => "299",__("Grenada", "digits") => "1",__("Guadeloupe", "digits") => "590",__("Guam", "digits") => "1",__("Guatemala", "digits") => "502",__("Guernsey", "digits") => "44",__("Guinea", "digits") => "224",__("Guinea-Bissau", "digits") => "245",__("Guyana", "digits") => "592",__("Haiti", "digits") => "509",__("Honduras", "digits") => "504",__("Hong Kong", "digits") => "852",__("Hungary", "digits") => "36",__("Iceland", "digits") => "354",__("India", "digits") => "91",__("Indonesia", "digits") => "62",__("Iran", "digits") => "98",__("Iraq", "digits") => "964",__("Ireland", "digits") => "353",__("Isle Of Man", "digits") => "44",__("Israel", "digits") => "972",__("Italy", "digits") => "39",__("Jamaica", "digits") => "1",__("Japan", "digits") => "81",__("Jersey", "digits") => "44",__("Jordan", "digits") => "962",__("Kazakhstan", "digits") => "7",__("Kenya", "digits") => "254",__("Kiribati", "digits") => "686",__("Kuwait", "digits") => "965",__("Kyrgyzstan", "digits") => "996",__("Laos", "digits") => "856",__("Latvia", "digits") => "371",__("Lebanon", "digits") => "961",__("Lesotho", "digits") => "266",__("Liberia", "digits") => "231",__("Libya", "digits") => "218",__("Liechtenstein", "digits") => "423",__("Lithuania", "digits") => "370",__("Luxembourg", "digits") => "352",__("Macau", "digits") => "853",__("Macedonia", "digits") => "389",__("Madagascar", "digits") => "261",__("Malawi", "digits") => "265",__("Malaysia", "digits") => "60",__("Maldives", "digits") => "960",__("Mali", "digits") => "223",__("Malta", "digits") => "356",__("Marshall Islands", "digits") => "692",__("Martinique", "digits") => "596",__("Mauritania", "digits") => "222",__("Mauritius", "digits") => "230",__("Mayotte", "digits") => "262",__("Mexico", "digits") => "52",__("Moldova", "digits") => "373",__("Monaco", "digits") => "377",__("Mongolia", "digits") => "976",__("Montenegro", "digits") => "382",__("Montserrat", "digits") => "1",__("Morocco", "digits") => "212",__("Mozambique", "digits") => "258",__("Myanmar", "digits") => "95",__("Namibia", "digits") => "264",__("Nauru", "digits") => "674",__("Nepal", "digits") => "977",__("Netherlands", "digits") => "31",__("New Caledonia", "digits") => "687",__("New Zealand", "digits") => "64",__("Nicaragua", "digits") => "505",__("Niger", "digits") => "227",__("Nigeria", "digits") => "234",__("Niue", "digits") => "683",__("Norfolk Island", "digits") => "672",__("North Korea", "digits") => "850",__("Northern Mariana Islands", "digits") => "1",__("Norway", "digits") => "47",__("Oman", "digits") => "968",__("Pakistan", "digits") => "92",__("Palau", "digits") => "680",__("Palestine", "digits") => "970",__("Panama", "digits") => "507",__("Papua New Guinea", "digits") => "675",__("Paraguay", "digits") => "595",__("Peru", "digits") => "51",__("Philippines", "digits") => "63",__("Poland", "digits") => "48",__("Portugal", "digits") => "351",__("Puerto Rico", "digits") => "1",__("Qatar", "digits") => "974",__("Republic of the Congo", "digits") => "242",__("Romania", "digits") => "40",__("Runion", "digits") => "262",__("Russia", "digits") => "7",__("Rwanda", "digits") => "250",__("Saint Helena", "digits") => "290",__("Saint Kitts and Nevis", "digits") => "1",__("Saint Pierre and Miquelon", "digits") => "508",__("Saint Vincent and the Grenadines", "digits") => "1",__("Samoa", "digits") => "685",__("San Marino", "digits") => "378",__("Sao Tome and Principe", "digits") => "239",__("Saudi Arabia", "digits") => "966",__("Senegal", "digits") => "221",__("Serbia", "digits") => "381",__("Seychelles", "digits") => "248",__("Sierra Leone", "digits") => "232",__("Singapore", "digits") => "65",__("Sint Maarten", "digits") => "1",__("Slovakia", "digits") => "421",__("Slovenia", "digits") => "386",__("Solomon Islands", "digits") => "677",__("Somalia", "digits") => "252",__("South Africa", "digits") => "27",__("South Korea", "digits") => "82",__("South Sudan", "digits") => "211",__("Spain", "digits") => "34",__("Sri Lanka", "digits") => "94",__("St. Lucia", "digits") => "1",__("Sudan", "digits") => "249",__("Suriname", "digits") => "597",__("Swaziland", "digits") => "268",__("Sweden", "digits") => "46",__("Switzerland", "digits") => "41",__("Syria", "digits") => "963",__("Taiwan", "digits") => "886",__("Tajikistan", "digits") => "992",__("Tanzania", "digits") => "255",__("Thailand", "digits") => "66",__("The Bahamas", "digits") => "1",__("The Gambia", "digits") => "220",__("Timor-Leste", "digits") => "670",__("Togo", "digits") => "228",__("Tokelau", "digits") => "690",__("Tonga", "digits") => "676",__("Trinidad and Tobago", "digits") => "1",__("Tunisia", "digits") => "216",__("Turkey", "digits") => "90",__("Turkmenistan", "digits") => "993",__("Turks and Caicos Islands", "digits") => "1",__("Tuvalu", "digits") => "688",__("U.S. Virgin Islands", "digits") => "1",__("Uganda", "digits") => "256",__("Ukraine", "digits") => "380",__("United Arab Emirates", "digits") => "971",__("United Kingdom", "digits") => "44",__("United States", "digits") => "1",__("Uruguay", "digits") => "598",__("Uzbekistan", "digits") => "998",__("Vanuatu", "digits") => "678",__("Venezuela", "digits") => "58",__("Vietnam", "digits") => "84",__("Wallis and Futuna", "digits") => "681",__("Western Sahara", "digits") => "212",__("Yemen", "digits") => "967",__("Zambia", "digits") => "260",__("Zimbabwe", "digits") => "263"
    );
}

add_action('init', 'digits_login', 100);

function dig_pcd_act() {
    if (! wp_next_scheduled ( 'dig_pcd_act_chk' )) {
        wp_schedule_event(time(), 'daily', 'dig_pcd_act_chk');
    }
}

add_action('init','dig_init_pcver');
function dig_init_pcver(){
    if (! wp_next_scheduled ( 'dig_pcd_act_chk' )) {
        wp_schedule_event(time(), 'daily', 'dig_pcd_act_chk');
    }
    digits_show_reg_check_disabled(false);
}

add_action('dig_pcd_act_chk', 'dig_pcd_act_chk_req');

function dig_pcd_act_chk_req(){
    dig_pcd_act_chk_req_cd(false);
}
function dig_pcd_act_chk_req_cd($dec = false) {

    $dpc = base64_decode('ZGlnX3B1cmNoYXNlY29kZQ==');
    $dicp = dig_get_option($dpc);

    $plugin_data = get_plugin_data( __FILE__ );
    $plugin_version = $plugin_data['Version'];

    $type = base64_decode('ZGlnX2xpY2Vuc2VfdHlwZQ==');
    $params = array(base64_decode('anNvbg==')=> 1,'code' => $dicp,
        base64_decode('cmVxdWVzdF9zaXRl') => network_home_url(),
        $type => dig_get_option(base64_decode('ZGlnX2xpY2Vuc2VfdHlwZQ=='), 1),
        base64_decode('dmVyc2lvbg==') => $plugin_version,'schedule'=>1);

    if($dec){
        $params[base64_decode('dW5yZWdpc3Rlcg==')] = 1;
    }
    $url = base64_decode("aHR0cHM6Ly9kaWdpdHMudW5pdGVkb3Zlci5jb20vdXBkYXRlcy92ZXJpZnkucGhw");
    $c = curl_init();
    curl_setopt( $c, CURLOPT_URL, $url );
    curl_setopt( $c, CURLOPT_POST, true );
    curl_setopt( $c, CURLOPT_POSTFIELDS, $params );
    curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($c);

    $http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $un = base64_decode('ZGlnX3Vucg==');
    $ds = base64_decode('ZGlnX2RzYg==');

    if(!curl_errno($c)){
        $pcf = base64_decode('ZGlnX3B1cmNoYXNlZmFpbA==');

        if($http_status==200){

            if($dec)return;

            $response = json_decode($result);
            $result = $response->code;

                if($result!=1){
                    $check = dig_get_option($pcf,2);
                    if($check==2){
                        delete_site_option($dpc);
                        delete_site_option($pcf);
                        delete_site_option($type);
                    }else{
                        update_site_option($pcf,2);
                    }

                    $t = dig_get_option($un,-1);

                    if($t==-1) update_site_option($un,time());

                }else if($result==1){
                    delete_site_option($pcf);
                    delete_site_option($un);
                    delete_site_option($ds);

                    if(isset($response->type)){

                        if($response->type!=-1) update_site_option($type,$response->type);
                    }
                }
        }
    }

    curl_close( $c );

    if(empty($digpc)){
        $time = get_option($un,-1);
        if($time==-1){
            $time = time();
            update_option($un,$time);
        }

        if(!empty($time)){
            $c = 360 * 3600;
            $time = $time + $c;
            $current_time = time();
            $t = $time - $current_time;
            if($t<0 || $t> $c){
                update_option($ds,1);
            }
        }
    }
}

register_deactivation_hook(__FILE__, 'dig_pcd_decact');

function dig_pcd_decact() {
    wp_clear_scheduled_hook('dig_pcd_act_chk');
    dig_pcd_act_chk_req_cd(true);
}

/**
 * Add admin menus/screens.
 */
function digits_admin_menus()
{
    add_dashboard_page('', '', 'manage_options', 'digits-setup', '');
}

function dig_getOtpTime()
{
    return min(max(get_option('dig_mob_otp_resend_time', 30), 20), 3600);
}

function dig_useStrongPass()
{
    return get_option('dig_use_strongpass', 1);
}

add_action('wp_ajax_digits_save_settings', 'digits_save_settings');
function digits_save_settings()
{
    digits_update_data(false);
    wp_die();
}

/**
 * update data.
 */
function digits_update_data($gs)
{
    if (!current_user_can('manage_options')) {

        die();
    }
    $digpc = dig_get_option('dig_purchasecode');

    if (isset($_POST['dig_custom_field_data'])) {
        $login_fields_array = array();
        foreach (digit_default_login_fields() as $login_field => $values) {
            $login_fields_array[$login_field] = sanitize_text_field($_POST[$login_field]);
        }
        update_option('dig_login_fields', $login_fields_array);

        $reg_default_fields_array = array();
        foreach (digit_get_reg_fields() as $reg_field => $values) {
            $reg_default_fields_array[$reg_field] = sanitize_text_field($_POST[$reg_field]);
        }
        update_option('dig_reg_fields', $reg_default_fields_array);

        $field_data = base64_encode($_POST['dig_reg_custom_field_data']);

        update_option('dig_reg_custom_field_data', $field_data);
    }

    if (isset($_POST['dig_sortorder'])) {
        $dig_sortorder = sanitize_text_field($_POST['dig_sortorder']);
        update_option('dig_sortorder', $dig_sortorder);
    }

    if (isset($_POST['dig_purchasecode'])) {
        $purchasecode = sanitize_text_field($_POST['dig_purchasecode']);

        $pcsave = true;
        if (isset($_REQUEST['pca'])) {
            if ($_REQUEST['pca'] == 1) $pcsave = true; else {
                $pcsave = false;

                delete_site_option('dig_purchasecode');
                delete_site_option('dig_license_type');

                $t = dig_get_option('dig_unr',-1);

                if($t==-1) update_site_option('dig_unr',time());
            }
        }

        if ($pcsave) {
            if (empty($purchasecode)) {
                delete_site_option('dig_purchasecode');
                delete_site_option('dig_license_type');

                $t = dig_get_option('dig_unr',-1);

                if($t==-1) update_site_option('dig_unr',time());
            } else {
                if (dig_get_option('dig_purchasecode') !== false) {
                    update_site_option('dig_purchasecode', $purchasecode);
                } else {
                    add_site_option('dig_purchasecode', $purchasecode);
                }
                delete_site_option('dig_purchasefail');
                delete_site_option('dig_unr');
                delete_site_option('dig_dsb');

                update_site_option('dig_license_type',sanitize_textarea_field($_POST['dig_license_type']));

                if ($gs == 1) {
                    wp_redirect(esc_url_raw(admin_url("index.php?page=digits-setup&step=documentation")));
                    exit();
                }
            }
        }
    }

    if (isset($_POST['digit_tapp'])) {
        $digit_tapp = sanitize_text_field($_POST['digit_tapp']);

        if (get_option('digit_tapp') !== false) {
            update_option('digit_tapp', $digit_tapp);
        } else {
            add_option('digit_tapp', $digit_tapp);
        }

        global $wpdb;
        $tb = $wpdb->prefix . 'digits_mobile_otp';
        $tb2 = $wpdb->prefix . 'digits_requests_log';
        $tb3 = $wpdb->prefix . 'digits_blocked_ip';
        if ($wpdb->get_var("SHOW TABLES LIKE '$tb'") != $tb) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $tb (
		          countrycode MEDIUMINT(8) NOT NULL,
		          mobileno VARCHAR(20) NOT NULL,
		          otp VARCHAR(32) NOT NULL,
		          time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		          UNIQUE ID(mobileno)
	            ) $charset_collate;";

            $sql2 = "CREATE TABLE $tb2 (
		          ip VARCHAR(32) NOT NULL,
		          requests VARCHAR(32) NOT NULL,
		          time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		          UNIQUE ID(ip)
	            ) $charset_collate;";

            $sql3 = "CREATE TABLE $tb3 (
		          ip VARCHAR(32) NOT NULL,
		          block VARCHAR(32) NOT NULL,
		          time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		          UNIQUE ID(ip)
	            ) $charset_collate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta(array($sql, $sql2, $sql3));
        }

        if ($digit_tapp == 1) {
            if (isset($_POST['appid']) && isset($_POST['appsecret'])) {
                $appid = sanitize_text_field($_POST['appid']);
                $appsecret = sanitize_text_field($_POST['appsecret']);
                $app = array(
                    'appid' => $appid,
                    'appsecret' => $appsecret
                );
                update_option('digit_api', $app);

                if (get_option('digit_api') !== false) {
                    update_option('digit_api', $app);
                } else {
                    add_option('digit_api', $app);
                }
            }
        } else if ($digit_tapp == 2) {
            $twiliosid = sanitize_text_field($_POST['twiliosid']);
            $twiliotoken = sanitize_text_field($_POST['twiliotoken']);
            $twiliosenderid = sanitize_text_field($_POST['twiliosenderid']);

            $tiwilioapicred = array(
                'twiliosid' => $twiliosid,
                'twiliotoken' => $twiliotoken,
                'twiliosenderid' => $twiliosenderid
            );

            if (get_option('digit_twilio_api') !== false) {
                update_option('digit_twilio_api', $tiwilioapicred);
            } else {
                add_option('digit_twilio_api', $tiwilioapicred);
            }
        } else if ($digit_tapp == 3) {
            $msg91authkey = sanitize_text_field($_POST['msg91authkey']);
            $msg91senderid = sanitize_text_field($_POST['msg91senderid']);
            $msg91route = sanitize_text_field($_POST['msg91route']);
            $msg91apicred = array(
                'msg91authkey' => $msg91authkey,
                'msg91senderid' => $msg91senderid,
                'msg91route' => $msg91route
            );
            if (get_option('digit_msg91_api') !== false) {
                update_option('digit_msg91_api', $msg91apicred);
            } else {
                add_option('digit_msg91_api', $msg91apicred);
            }
        } else if ($digit_tapp == 4) {
            $yunpianapikey = sanitize_text_field($_POST['yunpianapikey']);
            update_option('digit_yunpianapi', $yunpianapikey);
        }

        $smsgateways = getGateWayArray();

        foreach ($smsgateways as $name => $details) {
            $name = strtolower(str_replace([".", " "], "_", $name));
            $gatewaycred = array();
            foreach ($details['inputs'] as $inputlabel => $input) {
                $inputValue = sanitize_text_field($_POST[$name . "_" . $input['name']]);

                $gatewaycred[$input['name']] = $inputValue;

            }
            update_option('digit_' . strtolower($name), $gatewaycred);
        }

        if ($gs == 1) {
            wp_redirect(esc_url_raw(admin_url('index.php?page=digits-setup&step=shortcodes')));
            exit();
        }
    }

    if (isset($_POST['diglogintrans'])) {
        $diglogintrans = sanitize_text_field($_POST['diglogintrans']);
        $digregistertrans = sanitize_text_field($_POST['digregistertrans']);
        $digforgottrans = sanitize_text_field($_POST['digforgottrans']);
        $digmyaccounttrans = sanitize_text_field($_POST['digmyaccounttrans']);
        $diglogouttrans = sanitize_text_field($_POST['diglogouttrans']);

        $digonlylogintrans = sanitize_text_field($_POST['digonlylogintrans']);

        if (get_option('diglogintrans') !== false) {
            update_option('digonlylogintrans', $digonlylogintrans);

            update_option('diglogintrans', $diglogintrans);
            update_option('digregistertrans', $digregistertrans);
            update_option('digforgottrans', $digforgottrans);
            update_option('digmyaccounttrans', $digmyaccounttrans);
            update_option('diglogouttrans',$diglogouttrans);
        } else {
            add_option('digonlylogintrans', $digonlylogintrans);

            add_option('diglogintrans', $diglogintrans);
            add_option('digregistertrans', $digregistertrans);
            add_option('digforgottrans', $digforgottrans);
            add_option('digmyaccounttrans', $digmyaccounttrans);
            add_option('diglogouttrans',$diglogouttrans);
        }
    }

    if (isset($_POST['dig_otp_size']) && isset($_POST['dig_messagetemplate'])) {
        $dig_otp_size = sanitize_text_field($_POST['dig_otp_size']);
        $dig_messagetemplate = sanitize_text_field($_POST['dig_messagetemplate']);

        if ($dig_otp_size > 3 && $dig_otp_size < 11 && !empty($dig_messagetemplate)) {
            if (get_option('dig_otp_size') !== false) {
                update_option('dig_messagetemplate', $dig_messagetemplate);
                update_option('dig_otp_size', $dig_otp_size);
            } else {
                add_option('dig_messagetemplate', $dig_messagetemplate);
                add_option('dig_otp_size', $dig_otp_size);
            }
        }
    }

    if (!empty($digpc)) {
        if (isset($_POST['digit_custom_css'])) {
            $css = $_POST['digit_custom_css'];
            update_option("digit_custom_css", $css);
        }
    }
    if (isset($_POST['digpassaccep']) && isset($_POST['digemailaccep'])) {
        $passaccep = sanitize_text_field($_POST['digpassaccep']);
        $digemailaccep = sanitize_text_field($_POST['digemailaccep']);

        if (get_option('digpassaccep') !== false) {
            update_option('digpassaccep', $passaccep);
        } else {
            add_option('digpassaccep', $passaccep);
        }

        if (get_option('digemailaccep') !== false) {
            update_option('digemailaccep', $digemailaccep);
        } else {
            add_option('digemailaccep', $digemailaccep);
        }
    }

    if (isset($_POST['dig_mobilein_uname'])) {
        $dig_mobilein_uname = sanitize_text_field($_POST['dig_mobilein_uname']);
        update_option('dig_mobilein_uname', $dig_mobilein_uname);
    }
    if (isset($_POST['dig_enable_forgotpass'])) {
        $digforgotpass = sanitize_text_field($_POST['dig_enable_forgotpass']);
        if (get_option('digforgotpass') !== false) {
            update_option('digforgotpass', $digforgotpass);
        } else {
            add_option('digforgotpass', $digforgotpass);
        }
    }

    if (isset($_POST['dig_enable_registration'])) {
        $dig_enable_registration = sanitize_text_field($_POST['dig_enable_registration']);
        $show_asterisk = sanitize_text_field($_POST['dig_show_asterisk']);

        if (get_option('dig_enable_registration') !== false) {
            update_option('dig_enable_registration', $dig_enable_registration);
            update_option('dig_show_asterisk', $show_asterisk);
        } else {
            add_option('dig_enable_registration', $dig_enable_registration);
            add_option('dig_show_asterisk', $show_asterisk);
        }
    }

    if (isset($_POST['dig_mob_otp_resend_time'])) {
        $dig_mob_otp_resend_time = preg_replace("/[^0-9]/", "",$_POST['dig_mob_otp_resend_time']);
        if($dig_mob_otp_resend_time>19) {
            if (get_option('dig_mob_otp_resend_time') !== false) {
                update_option('dig_mob_otp_resend_time', $dig_mob_otp_resend_time);
            } else {
                add_option('dig_mob_otp_resend_time', $dig_mob_otp_resend_time);
            }
        }
    }

    if (isset($_POST['dig_enable_strongpass'])) {
        $dig_use_strongpass = sanitize_text_field($_POST['dig_enable_strongpass']);
        if (get_option('dig_use_strongpass') !== false) {
            update_option('dig_use_strongpass', $dig_use_strongpass);
        } else {
            add_option('dig_use_strongpass', $dig_use_strongpass);
        }
    }

    if (isset($_POST['dig_reqfieldbilling'])) {
        $dig_reqfieldbilling = sanitize_text_field($_POST['dig_reqfieldbilling']);

        if (get_option('dig_reqfieldbilling') !== false) {
            update_option('dig_reqfieldbilling', $dig_reqfieldbilling);
        } else {
            add_option('dig_reqfieldbilling', $dig_reqfieldbilling);
        }
    }
    if (isset($_POST['enable_createcustomeronorder']) && isset($_POST['defaultuserrole'])) {
        $enable_createcustomeronorder = sanitize_text_field($_POST['enable_createcustomeronorder']);
        $defaultuserrole = sanitize_text_field($_POST['defaultuserrole']);

        if (get_option('enable_createcustomeronorder') !== false) {
            update_option('enable_createcustomeronorder', $enable_createcustomeronorder);
            update_option('defaultuserrole', $defaultuserrole);
        } else {
            add_option('enable_createcustomeronorder', $enable_createcustomeronorder);
            add_option('defaultuserrole', $defaultuserrole);
        }

        if (get_option('defaultuserrole') !== false) {
            update_option('defaultuserrole', $defaultuserrole);
        } else {
            add_option('defaultuserrole', $defaultuserrole);
        }

        if (isset($_POST['dig_bill_ship_fields'])) {
            $dig_bill_ship_fields = sanitize_text_field($_POST['dig_bill_ship_fields']);
            update_option('dig_bill_ship_fields', $dig_bill_ship_fields);
        }

        if (isset($_POST['dig_mob_ver_chk_fields'])) {
            $dig_mob_ver_chk_fields = sanitize_text_field($_POST['dig_mob_ver_chk_fields']);
            update_option('dig_mob_ver_chk_fields', $dig_mob_ver_chk_fields);
        }

        if (isset($_POST['default_ccode'])) {
            $default_ccode = sanitize_text_field($_POST['default_ccode']);
            if (get_option('dig_default_ccode') !== false) {
                update_option('dig_default_ccode', $default_ccode);
            } else {
                add_option('dig_default_ccode', $default_ccode);
            }
        }
        if (isset($_POST['whitelistcountrycodes'])) {

            $whitelistCountryCodes = sanitize($_POST['whitelistcountrycodes']);
            if (sizeof($whitelistCountryCodes) > 0) {
                if (get_option('whitelistcountrycodes') !== false) {
                    update_option('whitelistcountrycodes', $whitelistCountryCodes);
                } else {
                    add_option('whitelistcountrycodes', $whitelistCountryCodes);
                }
            } else {
                delete_option("whitelistcountrycodes");
            }
        } else {
            delete_option("whitelistcountrycodes");
        }

        if ($gs == 1) {
            wp_redirect(esc_url_raw(admin_url("index.php?page=digits-setup&step=shortcodes")));

            exit();
        }
    }

    if (!empty($digpc)) {
        if (isset($_POST['lb_x']) && isset($_POST['bg_color'])) {
            $bgcolor = sanitize_text_field($_POST['bg_color']);
            $lbxbg_color = sanitize_text_field($_POST['lbxbg_color']);
            $lb_x = preg_replace("/[^0-9]/", "",$_POST['lb_x']);
            $lb_y = preg_replace("/[^0-9]/", "",$_POST['lb_y']);
            $lb_blur = preg_replace("/[^0-9]/", "",$_POST['lb_blur']);
            $lb_spread = preg_replace("/[^0-9]/", "",$_POST['lb_spread']);
            $lb_radius = preg_replace("/[^0-9]/", "",$_POST['lb_radius']);
            $lb_color = sanitize_text_field($_POST['lb_color']);
            $fontcolor1 = sanitize_text_field($_POST['fontcolor1']);
            $fontcolor2 = sanitize_text_field($_POST['fontcolor2']);
            $backcolor = sanitize_text_field($_POST['backcolor']);
            $left_color = sanitize_text_field($_POST['left_color']);

            $type = preg_replace("/[^0-9]/", "",$_POST['dig_page_type']);

            $input_bg_color = sanitize_text_field($_POST['input_bg_color']);
            $input_border_color = sanitize_text_field($_POST['input_border_color']);
            $input_text_color = sanitize_text_field($_POST['input_text_color']);
            $button_bg_color = sanitize_text_field($_POST['button_bg_color']);
            $signup_button_color = sanitize_text_field($_POST['signup_button_color']);
            $signup_button_bg_color = sanitize_text_field($_POST['signup_button_border_color']);
            $button_text_color = sanitize_text_field($_POST['button_text_color']);
            $signup_button_text_color = sanitize_text_field($_POST['signup_button_text_color']);

            $left_bg_size = sanitize_text_field($_POST['left_bg_size']);
            $left_bg_position = sanitize_text_field($_POST['left_bg_position']);

            $color = array(
                'bgcolor' => $bgcolor,
                'loginboxcolor' => $lbxbg_color,
                'sx' => $lb_x,
                'sy' => $lb_y,
                'sblur' => $lb_blur,
                'sspread' => $lb_spread,
                'sradius' => $lb_radius,
                'scolor' => $lb_color,
                'fontcolor1' => $fontcolor1,
                'fontcolor2' => $fontcolor2,
                'backcolor' => $backcolor,
                'type' => $type,
                'left_color' => $left_color,
                'input_bg_color' => $input_bg_color,
                'input_border_color' => $input_border_color,
                'input_text_color' => $input_text_color,
                'button_bg_color' => $button_bg_color,
                'signup_button_color' => $signup_button_color,
                'signup_button_border_color' => $signup_button_bg_color,
                'button_text_color' => $button_text_color,
                'signup_button_text_color' => $signup_button_text_color,
                'left_bg_size' => $left_bg_size,
                'left_bg_position' => $left_bg_position,
            );

            update_option('digit_color', $color);

            $bgcolor = sanitize_text_field($_POST['bg_color_modal']);
            $lbxbg_color = sanitize_text_field($_POST['lbxbg_color_modal']);
            $lb_x = preg_replace("/[^0-9]/", "",$_POST['lb_x_modal']);
            $lb_y = preg_replace("/[^0-9]/", "",$_POST['lb_y_modal']);
            $lb_blur = preg_replace("/[^0-9]/", "",$_POST['lb_blur_modal']);
            $lb_spread = preg_replace("/[^0-9]/", "",$_POST['lb_spread_modal']);
            $lb_radius = preg_replace("/[^0-9]/", "",$_POST['lb_radius_modal']);
            $lb_color = sanitize_text_field($_POST['lb_color_modal']);
            $fontcolor1 = sanitize_text_field($_POST['fontcolor1_modal']);
            $fontcolor2 = sanitize_text_field($_POST['fontcolor2_modal']);
            $type = preg_replace("/[^0-9]/", "",$_POST['dig_modal_type']);
            $left_color = sanitize_text_field($_POST['left_color_modal']);
            $button_text_color = sanitize_text_field($_POST['button_text_color_modal']);
            $signup_button_text_color = sanitize_text_field($_POST['signup_button_text_color_modal']);

            $input_bg_color = sanitize_text_field($_POST['input_bg_color_modal']);
            $input_border_color = sanitize_text_field($_POST['input_border_color_modal']);
            $input_text_color = sanitize_text_field($_POST['input_text_color_modal']);
            $button_bg_color = sanitize_text_field($_POST['button_bg_color_modal']);
            $signup_button_color = sanitize_text_field($_POST['signup_button_color_modal']);
            $signup_button_border_color = sanitize_text_field($_POST['signup_button_border_color_modal']);
            $left_bg_size = sanitize_text_field($_POST['left_bg_size_modal']);
            $left_bg_position = sanitize_text_field($_POST['left_bg_position_modal']);

            $color = array(
                'bgcolor' => $bgcolor,
                'loginboxcolor' => $lbxbg_color,
                'sx' => $lb_x,
                'sy' => $lb_y,
                'sblur' => $lb_blur,
                'sspread' => $lb_spread,
                'sradius' => $lb_radius,
                'scolor' => $lb_color,
                'fontcolor1' => $fontcolor1,
                'fontcolor2' => $fontcolor2,
                'type' => $type,
                'left_color' => $left_color,
                'input_bg_color' => $input_bg_color,
                'input_border_color' => $input_border_color,
                'input_text_color' => $input_text_color,
                'button_bg_color' => $button_bg_color,
                'signup_button_color' => $signup_button_color,
                'signup_button_border_color' => $signup_button_border_color,
                'button_text_color' => $button_text_color,
                'signup_button_text_color' => $signup_button_text_color,
                'left_bg_size' => $left_bg_size,
                'left_bg_position' => $left_bg_position,
            );

            update_option('digit_color_modal', $color);

            // Save attachment ID
            if (isset($_POST['image_attachment_id'])):
                update_option('digits_logo_image', sanitize_text_field($_POST['image_attachment_id']));
            endif;

            if (isset($_POST['bg_image_attachment_id_modal'])):
                update_option('digits_bg_image_modal', sanitize_text_field($_POST['bg_image_attachment_id_modal']));
            endif;

            if (isset($_POST['bg_image_attachment_id'])):
                update_option('digits_bg_image', sanitize_text_field($_POST['bg_image_attachment_id']));
            endif;

            if (isset($_POST['bg_image_attachment_id_left'])):
                update_option('digits_left_bg_image', sanitize_text_field($_POST['bg_image_attachment_id_left']));
            endif;

            if (isset($_POST['bg_image_attachment_id_left_modal'])):
                update_option('digits_left_bg_image_modal', sanitize_text_field($_POST['bg_image_attachment_id_left_modal']));
            endif;

            if (isset($_POST['dig_preset'])):
                update_option('dig_preset', absint($_POST['dig_preset']));
            endif;

            if(isset($_POST['login_page_footer'])){
                $login_page_footer = base64_encode(str_replace("\n","<br />",$_POST['login_page_footer']));
                update_option('login_page_footer', $login_page_footer);
                update_option('login_page_footer_text_color',sanitize_text_field($_POST['login_page_footer_text_color']));
            }
            if ($gs == 1) {
                wp_redirect(esc_url_raw(admin_url("index.php?page=digits-setup&step=shortcodes")));

                exit();
            }
        }
    }
    if (isset($_POST['digits_loginred'])) {
        $digits_loginred = sanitize_text_field($_POST['digits_loginred']);

        $digits_regred = sanitize_text_field($_POST['digits_regred']);
        $digits_forgotred = sanitize_text_field($_POST['digits_forgotred']);
        $digits_logoutred = sanitize_text_field($_POST['digits_logoutred']);

        update_option('digits_loginred', $digits_loginred);
        update_option('digits_regred', $digits_regred);
        update_option('digits_forgotred', $digits_forgotred);
        update_option('digits_logoutred', $digits_logoutred);
    }
}

/**
 * Show the setup wizard.
 */
function digits_setup_wizard()
{
    if (empty($_GET['page']) || 'digits-setup' !== $_GET['page']) {
        return;
    }

    digits_update_data(1);

    wp_enqueue_style(array('wp-admin', 'dashicons', 'install'));

    //enqueue style for admin notices
    wp_enqueue_style('wp-admin');
    wp_enqueue_media();
    wp_enqueue_script('media');

    ob_start();
    setup_wizard_header();

    exit();
}

/**
 * Setup Wizard Header.
 */
function setup_wizard_header()
{
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta name="viewport" content="width=device-width"/>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <title>DIGITS &rsaquo; <?php _e("Setup", "digits"); ?></title>
        <?php do_action('admin_print_styles');
        do_action('admin_print_scripts');

        wp_enqueue_style('google-roboto-regular', dig_fonts());

        nice_select_scr();

        wp_register_style('digits-gs-style', plugins_url('/assests/css/gs.css', __FILE__), array('google-roboto-regular', 'nice-select'), null, 'all');
        wp_print_styles('digits-gs-style');
        select2js();
        ?>

        <style>
            body {
                margin: 40px auto 24px;
                box-shadow: none;
                background: #f1f1f1;
                padding: 0;
            }
        </style>
    </head>
    
    <?php wp_print_styles('wp-color-picker');
    wp_print_scripts('wp-color-picker');

    wp_enqueue_script('rubaxa-sortable', plugins_url('/assests/js/sortable.min.js', __FILE__), null);

    wp_print_scripts('rubaxa-sortable');

    wp_enqueue_script('slick', plugins_url('/assests/js/slick.min.js', __FILE__), null);

    wp_print_scripts('slick');

    wp_register_script('digits-script', plugins_url('/assests/js/settings.js', __FILE__), array('jquery', 'wp-color-picker', 'rubaxa-sortable', 'slick','nice-select'), false, true);

    $settings_array = array(
        'plsActMessage' => __('Please activate your plugin to change the look and feel of your Login page and Popup', 'digits'),
        'cannotUseEmailWithoutPass' => __('Oops! You cannot enable email without password for login', 'digits'),
        'bothPassAndOTPCannotBeDisabled' => __('Both Password and OTP cannot be disabled', 'digits'),
        'selectatype' => __('Select a type', 'digits'),
        "Invalidmsg91senderid" => __("Invalid msg91 sender id!", 'digits'),
        "invalidpurchasecode" => __("Invalid Purchase Code", 'digits'),
        "Error" => __("Error! Please try again later", "digits"),
        "PleasecompleteyourSettings" => __("Please complete your Settings", 'digits'),
        "PleasecompleteyourAPISettings" => __("Please complete your API Settings", 'digits'),
        "PleasecompleteyourCustomFieldSettings" => __("Please complete your Custom Field Settings", 'digits'),
        "Copiedtoclipboard" => __("Copied to clipboard", "digits"),
        'ajax_url' => admin_url('admin-ajax.php'),
        'face' => plugins_url('digits/assests/images/face.png'),
        'fieldAlreadyExist' => __('Field Already exist', 'digits'),
        'duplicateValue' => __('Duplicate Value', 'digits'),
        'cross' => plugins_url('digits/assests/images/cross.png'),
        "ohsnap" => __("Oh Snap!", "digits"),
        "string_no" => __("No", "digits"),
        "string_optional" => __("Optional", "digits"),
        "string_required" => __("Required", "digits"),
        "validnumber" => __("Please enter a valid mobile number", "digits"),
    );
    wp_localize_script('digits-script', 'digsetobj', $settings_array);

    wp_enqueue_script('digits-script');

    wp_enqueue_script('jquery-mask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.11/jquery.mask.min.js', array('jquery'), null, false);

    wp_print_scripts('digits-script');
    wp_print_scripts('jquery-mask');

    digits_add_style();

    wp_print_styles('digits-login-style');
    ?>
    </html>
    <?php
}

function digit_shortcodes($showbuttons = true)
{
    if ($showbuttons) echo "<h1>" . __("Shortcodes", "digits") . "</h1>";
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="digit_loginreg_short"><?php _e("Login/Signup Page", "digits"); ?> </label></th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_loginreg_short" value="[dm-page]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="digit_loginreg_modal_short"><?php _e("Login/Signup Modal", "digits"); ?> </label></th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_loginreg_modal_short" value="[dm-modal]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
    <tr>
        <th scope="row"><label for="digit_login_short"><?php _e("Login Page", "digits"); ?> </label></th>
        <td>
            <div class="digits_shortcode_tbs">
                <input type="text" id="digit_login_short" value="[dm-login-page]" readonly/>
                <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                     src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
            </div>
        </td>
    </tr>
        <tr>
            <th scope="row"><label for="digit_login_modal_short"><?php _e("Login Modal", "digits"); ?>
                </label></th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_login_modal_short" value="[dm-login-modal]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
    <tr>
        <th scope="row"><label for="digit_reg_page_short"><?php _e("Sign Up Page", "digits"); ?>
            </label></th>
        <td>
            <div class="digits_shortcode_tbs">
                <input type="text" id="digit_reg_page_short" value="[dm-signup-page]" readonly/>
                <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                     src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
            </div>
        </td>
    </tr>
        <tr>
            <th scope="row"><label for="digit_reg_short"><?php _e("Sign Up Modal", "digits"); ?>
                </label></th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_reg_short" value="[dm-signup-modal]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
    <tr>
        <th scope="row"><label
                    for="digit_forg_page_short"><?php _e("Forgot Password Page", "digits"); ?>
            </label></th>
        <td>
            <div class="digits_shortcode_tbs">
                <input type="text" id="digit_forg_page_short" value="[dm-forgot-password-page]" readonly/>
                <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                     src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
            </div>
        </td>
    </tr>
        <tr>
            <th scope="row"><label
                        for="digit_forg_short"><?php _e("Forgot Password Modal", "digits"); ?>
                    </label></th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_forg_short" value="[dm-forgot-password-modal]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="digit_logout_short"><?php _e("Logout Shortcode", "digits"); ?> </label>
            </th>
            <td>
                <div class="digits_shortcode_tbs">
                    <input type="text" id="digit_logout_short" value="[dm-logout]" readonly/>
                    <img class="dig_copy_shortcode" alt="<?php _e('Copy', "digits"); ?>"
                         src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%2016%2019%22%20width%3D%2232%22%20height%3D%2232%22%3E%3Cdefs%3E%3Cstyle%3E.c%7Bstroke%3A%237a889a%3Bstroke-linecap%3Around%3Bstroke-linejoin%3Around%3Bstroke-miterlimit%3A10%3Bfill%3Anone%7D%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20opacity%3D%22.4%22%3E%3Cpath%20d%3D%22M4.25%204.101H3v12h10v-12h-1.25%22%20stroke%3D%22%237a889a%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-miterlimit%3D%2210%22%20fill%3D%22%237a889a%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M11.469%202.301h3.406a.613.613%200%200%201%20.625.6v15a.613.613%200%200%201-.625.6H1.125a.613.613%200%200%201-.625-.6v-15a.613.613%200%200%201%20.625-.6h3.406%22%2F%3E%3Cpath%20class%3D%22c%22%20d%3D%22M10.187%201.705h-.656a1.577%201.577%200%200%200-3.062%200h-.656a1.5%201.5%200%200%200-1.136.41%201.379%201.379%200%200%200-.427%201.09v.9h7.5v-.9a1.409%201.409%200%200%200-.438-1.079%201.535%201.535%200%200%200-1.125-.421z%22%2F%3E%3C%2Fg%3E%3C%2Fsvg%3E">
                </div>
            </td>
        </tr>
    </table>

    <?php
    if ($showbuttons) {
        ?>
        <p class="digits-setup-action step">
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=ready'); ?>"
               class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></a>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=apisettings'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
        <?php
    }
}

function digit_shortcodes_translations()
{
    ?>
    <div class="dig_ad_head"><span><?php _e('Menu Items', 'digits'); ?></span></div>
    <?php

    $diglogintrans = get_option("diglogintrans", __("Login / Register", "digits"));
    $digregistertrans = get_option("digregistertrans", __("Register", "digits"));
    $digforgottrans = get_option("digforgottrans", __("Forgot your Password?", "digits"));
    $digmyaccounttrans = get_option("digmyaccounttrans", __("My Account", "digits"));
    $diglogouttrans = get_option("diglogouttrans",__("Logout","digits"));

    $digonlylogintrans = get_option("digonlylogintrans",__("Login","digits"));
    ?>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="diglogintrans"><?php _e("Login / Register", "digits"); ?> </label></th>
            <td>
                <input type="text" id="diglogintrans" name="diglogintrans" class="regular-text"
                       value="<?php echo $diglogintrans; ?>" required/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="digonlylogintrans"><?php _e("Login", "digits"); ?> </label></th>
            <td>
                <input type="text" id="digonlylogintrans" name="digonlylogintrans" class="regular-text"
                       value="<?php echo $digonlylogintrans; ?>" required/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="digregistertrans"><?php _e("Register", "digits"); ?> </label></th>
            <td>
                <input type="text" id="digregistertrans" name="digregistertrans" class="regular-text"
                       value="<?php echo $digregistertrans; ?>" required/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="digforgottrans"><?php _e("Forgot", "digits"); ?> </label></th>
            <td>
                <input type="text" id="digforgottrans" name="digforgottrans" class="regular-text"
                       value="<?php echo $digforgottrans; ?>" required/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="digmyaccounttrans"><?php _e("My Account", "digits"); ?> </label></th>
            <td>
                <input type="text" id="digmyaccounttrans" name="digmyaccounttrans" class="regular-text"
                       value="<?php echo $digmyaccounttrans; ?>" required/>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="diglogouttrans"><?php _e("Logout", "digits"); ?> </label></th>
            <td>
                <input type="text" id="diglogouttrans" name="diglogouttrans" class="regular-text"
                       value="<?php echo $diglogouttrans; ?>" required/>
            </td>
        </tr>
    </table>

    <div class="dig_desc_sep_pc"></div>
    <p class="dig_ecr_desc dig_cntr_algn dig_ltr_trnsdc"><?php _e('Transation of whole plugin can be done through POT file present in the plugin languages folder. You\'ll need to upload .MO and .PO files to the languages folder of this plugin. The easiest way to translate is to use Loco Translate WordPress plugin.','digits'); ?>
    </p>
    <?php
}

function digits_presets_custom_fields(){
    return array(
        array('type'=>'text','values'=> array('label'=>'Last Name','required'=> 1,'custom_class'=>'','meta_key' => 'last_name')),
        array('type'=>'user_role','values'=> array('label'=>'User Role','required'=> 1,'custom_class'=>'','meta_key' => 'user_role')),
        array('type'=>'text','values'=> array('label'=>'Display Name','required'=> 1,'custom_class'=>'','meta_key' => 'display_name')),
        array('type'=>'text','values'=> array('label'=>'Company','required'=> 1,'custom_class'=>'','meta_key' => 'billing_company')),
        array('type'=>'text','values'=> array('label'=>'Address Line 1','required'=> 1,'custom_class'=>'','meta_key' => 'billing_address_1')),
        array('type'=>'text','values'=> array('label'=>'Address Line 2','required'=> 1,'custom_class'=>'','meta_key' => 'billing_address_2')),
        array('type'=>'text','values'=> array('label'=>'City', 'required'=> 1,'custom_class'=>'','meta_key' => 'billing_city')),
        array('type'=>'text','values'=> array('label'=>'State','required'=> 1,'custom_class'=>'','meta_key' => 'billing_state')),
        array('type'=>'text','values'=> array('label'=>'Country','required'=> 1,'custom_class'=>'','meta_key' => 'billing_country')),
        array('type'=>'text','values'=> array('label'=>'Postcode / ZIP','required'=> 1,'custom_class'=>'','meta_key' => 'billing_postcode')),
    );
}

function digits_customfieldsTypeList()
{
    return array('text' => array('name' => 'Text', 'force_required' => 0, 'meta_key' => 1, 'options' => 0, 'slug' => 'text'),
        'textarea' => array('name' => 'TextArea', 'force_required' => 0, 'meta_key' => 1, 'options' => 0, 'slug' => 'textarea'),
        'date' => array('name' => 'Date', 'force_required' => 0, 'meta_key' => 1, 'options' => 0, 'slug' => 'date'),
        'number' => array('name' => 'Number', 'force_required' => 0, 'meta_key' => 1, 'options' => 0, 'slug' => 'number'),
        'dropdown' => array('name' => 'DropDown', 'force_required' => 0, 'meta_key' => 1, 'options' => 1, 'slug' => 'dropdown'),
        'checkbox' => array('name' => 'CheckBox', 'force_required' => 0, 'meta_key' => 1, 'options' => 1, 'slug' => 'checkbox'),
        'radio' => array('name' => 'Radio', 'force_required' => 0, 'meta_key' => 1, 'options' => 1, 'slug' => 'radio'),
        'tac' => array('name' => 'Terms & Conditions', 'force_required' => 1, 'meta_key' => 1, 'options' => 0,'slug' => 'tac'),
        'captcha' => array('name' => 'Captcha', 'force_required' => 1, 'meta_key' => 0, 'options' => 0, 'slug' => 'captcha'),
        'user_role' => array('name' => 'User Role', 'force_required' => 1, 'meta_key' => 1, 'options' => 0, 'slug' => 'user_role','hidden'=>1,'user_role' => 1),
    );
}

/*
 * 0-> Disabled
 * 1-> Optional
 * 2-> Required
 */
function digit_default_login_fields()
{
    return array('dig_login_email' => array('name' => __('Email', 'digits')),
        'dig_login_mobilenumber' => array('name' => __('Mobile Number', 'digits')),
        'dig_login_otp' => array('name' => __('OTP', 'digits'), 'opt' => 1),
        'dig_login_password' => array('name' => __('Password', 'digits'),
            'ondis_disable' => 'dig_login_email', 'opt' => 1),
        'dig_login_captcha' => array('name' => __('Captcha', 'digits'), 'opt' => 1),
    );
}

function digit_get_login_fields()
{
    $dig_login_fields = get_option('dig_login_fields', false);
    if ($dig_login_fields) {
        if(!isset($dig_login_fields['dig_login_captcha'])){
            $dig_login_fields['dig_login_captcha'] = 0;
        }
        return $dig_login_fields;
    } else {
        return array('dig_login_email' => get_option("digemailaccep", 1),
            'dig_login_mobilenumber' => 1,
            'dig_login_otp' => 1,
            'dig_login_password' => get_option("digpassaccep", 1),
            'dig_login_captcha' => 0
        );
    }
}

function digit_default_reg_fields()
{
    return array(
        'dig_reg_name' => array('name' => __('Name', 'digits')),
        'dig_reg_uname' => array('name' => __('Username', 'digits')),
        'dig_reg_email' => array('name' => __('Email', 'digits')),
        'dig_reg_mobilenumber' => array('name' => __('Mobile Number', 'digits')),
        'dig_reg_password' => array('name' => __('Password', 'digits')),
    );
}

function digit_get_reg_fields()
{
    $dig_reg_fields = get_option('dig_reg_fields', false);
    if ($dig_reg_fields) {
        return $dig_reg_fields;
    } else {
        return array('dig_reg_name' => 1,
            'dig_reg_uname' => 0,
            'dig_reg_email' => get_option("digemailaccep", 1),
            'dig_reg_mobilenumber' => 1,
            'dig_reg_password' => get_option("digpassaccep", 1)
        );
    }
}

function digit_customfields()
{
    $user_can_register = get_option('dig_enable_registration', 1);

    $show_asterisk = get_option('dig_show_asterisk', 0);
    ?>

    <table class="form-table">
        <tr id="enableregistrationrow">
            <th scope="row"><label><?php _e('Enable Registration', 'digits'); ?> </label></th>
            <td>
                <select name="dig_enable_registration">
                    <option value="1" <?php if ($user_can_register == 1) echo 'selected="selected"'; ?>><?php _e('Yes', 'digits'); ?></option>
                    <option value="0" <?php if ($user_can_register == 0) echo 'selected="selected"'; ?>><?php _e('No', 'digits'); ?></option>
                </select>
<!--                <p class="dig_ecr_desc"><?php /*_e('This function only works on Digits Login/Signup Modal and Page', 'digits'); */?></p>-->
            </td>
        </tr>

        <tr id="showasteriskrow">
            <th scope="row"><label><?php _e('Show asterisk (*) on required fields', 'digits'); ?> </label></th>
            <td>
                <select name="dig_show_asterisk">
                    <option value="1" <?php if ($show_asterisk == 1) echo 'selected="selected"'; ?>><?php _e('Yes', 'digits'); ?></option>
                    <option value="0" <?php if ($show_asterisk == 0) echo 'selected="selected"'; ?>><?php _e('No', 'digits'); ?></option>
                </select>
            </td>
        </tr>
    </table>

    <input type="hidden" name="dig_custom_field_data"/>
    <div class="dig_ad_head"><span><?php _e('LOGIN FIELDS', 'digits'); ?></span></div>

    <table class="form-table">
        <?php
        $dig_login_field_details = digit_get_login_fields();
        foreach (digit_default_login_fields() as $login_field => $values) {
            $field_value = $dig_login_field_details[$login_field];
            ?>
            <tr>
                <th scope="row"><label><?php _e($values['name'], "digits"); ?> </label></th>
                <td>
                    <select name="<?php echo $login_field; ?>"
                            class="dig_custom_field_sel dig_custom_field_login_j" <?php if (isset($values['ondis_disable'])) echo 'data-disable="' . $values['ondis_disable'] . '"';
                    if (isset($values['opt'])) echo 'data-opt="' . $values['opt'] . '"'; ?> >
                        <option value="1" <?php if ($field_value == 1) echo 'selected'; ?>><?php _e('Yes', 'digits'); ?></option>
                        <option value="0" <?php if ($field_value == 0) echo 'selected'; ?>><?php _e('No', 'digits'); ?></option>
                    </select>
                </td>
            </tr>
            <?php
        }
        ?>
    </table>

    <div class="dig_ad_head"><span><?php _e('REGISTRATION FIELDS', 'digits'); ?></span></div>

    <?php
    $reg_custom_fields = stripslashes(base64_decode(get_option("dig_reg_custom_field_data", "e30=")));

    $dig_sortorder = get_option("dig_sortorder");
    ?>

    <input type="hidden" id="dig_sortorder" name="dig_sortorder"
           value='<?php echo $dig_sortorder; ?>'/>

    <input type="hidden" id="dig_reg_custom_field_data" name="dig_reg_custom_field_data"
           value='<?php echo $reg_custom_fields; ?>'/>
    <table class="form-table dig-reg-fields <?php if (is_rtl()) echo 'dig_rtl'; ?>" id="dig_custom_field_table">

        <tbody>
        <?php
        $dig_reg_field_details = digit_get_reg_fields();
        foreach (digit_default_reg_fields() as $reg_field => $values) {
            $field_value = $dig_reg_field_details[$reg_field];
            ?>
            <tr id="dig_cs_<?php echo cust_dig_filter_string($values['name']); ?>">
                <th scope="row"><label><?php _e($values['name'], "digits"); ?> </label></th>
                <td class="dg_cs_td">
                    <div class="icon-drag icon-drag-dims dig_cust_field_drag dig_cust_default_fields_drag"></div>
                    <select name="<?php echo $reg_field; ?>"
                            class="dig_custom_field_sel" <?php if (isset($values['ondis_disable'])) echo 'data-disable="' . $values['ondis_disable'] . '"'; ?>>
                        <option value="2" <?php if ($field_value == 2) echo 'selected'; ?>><?php _e('Required', 'digits'); ?></option>
                        <option value="1" <?php if ($field_value == 1) echo 'selected'; ?>><?php _e('Optional', 'digits'); ?></option>
                        <option value="0" <?php if ($field_value == 0) echo 'selected'; ?>><?php _e('No', 'digits'); ?></option>
                    </select>
                </td>
            </tr>
            <?php
        }
        ?>

        <?php
        $reg_custom_fields = json_decode($reg_custom_fields, true);

        foreach ($reg_custom_fields as $label => $values) {
            ?>
            <tr id="dig_cs_<?php echo cust_dig_filter_string($label); ?>" dig-lab="<?php echo $label; ?>">
                <th scope="row"><label><?php echo $label; ?> </label></th>
                <td>
                    <div class="dig_custom_field_list">
                        <?php echo dig_requireCustomToString($values['required']); ?>
                        <div class="dig_icon_customfield">
                            <div class="icon-shape icon-shape-dims dig_cust_field_delete"></div>
                            <div class="icon-gear icon-gear-dims dig_cust_field_setting"></div>
                            <div class="icon-drag icon-drag-dims dig_cust_field_drag"></div>
                        </div>
                    </div>
                </td>
            </tr>
            <?php
        }
        ?>
        </tbody>

        <tfoot>
        <th></th>
        <td>
            <div id="dig_add_new_reg_field"><?php _e('ADD FIELD', 'digits'); ?></div>
        </td>
        </tfoot>
    </table>

    <div class="dig_side_bar">
        <div class="dig_sb_head"><?php _e('Select a type', 'digits'); ?></div>
        <div class="dig_sb_content">

            <div class="dig_sb_select_field">
                <?php
                $dig_custom_fields = digits_customfieldsTypeList();
                foreach ($dig_custom_fields as $fieldname => $type) {
                    if(isset($type['hidden']) && $type['hidden']==1) continue;
                    ?>

                    <div class="dig_sb_field_types dig_sb_field_list"
                         id="dig_cust_list_type_<?php echo $fieldname; ?>" data-val='<?php echo $fieldname; ?>'
                         data-configure_fields='<?php echo json_encode($type); ?>'>
                        <?php _e($type['name'], 'digits'); ?>
                    </div>
                    <?php
                }
                do_action('dig_custom_fields_list');

                echo '<div class="dig_dsc_cusfield">'.__('WordPress / WooCommerce Fields','digits') .'</div>';
                foreach(digits_presets_custom_fields() as $custom_field){
                    ?>
                    <div class="dig_sb_field_wp_wc_types dig_sb_field_list"
                         id="dig_cust_list_type_<?php echo $custom_field['type']; ?>" data-val='<?php echo $custom_field['type']; ?>'
                         data-values='<?php echo json_encode($custom_field['values']);?>'
                         data-configure_fields='<?php echo json_encode($dig_custom_fields[$custom_field['type']]); ?>'>
                        <?php _e($custom_field['values']['label'], 'digits'); ?>
                    </div>
                    <?php
                    do_action('dig_custom_preset_fields_list');
                }
                ?><br /><br /><br /></div>
            <div class="dig_fields_options">
                <div class="dig_fields_options_main">
                    <input type="hidden" data-type="" id="dig_custom_field_data_type"/>
                    <div class="dig_sb_field" data-req="1" id="dig_field_label">
                        <div class="dig_sb_field_label">
                            <label for="custom_field_label"><?php _e('Label', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <input type="text" id="custom_field_label" name="label"/>
                        </div>

                        <div class="dig_sb_field_tac dig_sb_extr_fields dig_sb_field_tac_desc">
                            <?php _e('Enclose the word(s) between [t] and [/t] for terms and condition and [p] and [/t] for privacy policy.','digits');?>
                            <br /><br />
                            <?php _e('For example "Agree [t]Terms and Conditions[/t] & [p]Privacy Policy[/t]"','digits'); ?>
                        </div>
                        <?php do_action('dig_custom_fields_label_desc'); ?>
                    </div>

                    <div class="dig_sb_field" id="dig_field_required" data-req="1">
                        <div class="dig_sb_field_label">
                            <label><?php _e('Required Field', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <select name="required">
                                <option value="1"><?php _e('Yes', 'digits'); ?></option>
                                <option value="0"><?php _e('No', 'digits'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="dig_sb_field" id="dig_field_meta_key" data-req="1">
                        <div class="dig_sb_field_label">
                            <label for="custom_field_meta_key"><?php _e('Meta Key', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <input type="text" id="custom_field_meta_key" name="meta_key"/>
                        </div>
                    </div>
                    <div class="dig_sb_field" id="dig_field_custom_class" data-req="0">
                        <div class="dig_sb_field_label">
                            <label for="custom_field_class"><?php _e('Custom Class', 'digits'); ?></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <input type="text" id="custom_field_class" name="custom_class"/>
                        </div>
                    </div>

                    <div class="dig_sb_field" id="dig_field_options" data-req="1" data-list="1">
                        <div class="dig_sb_field_label">
                            <label><?php _e('Options', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <ul id="dig_field_val_list"></ul>

                        <div  class="dig_sb_field_list dig_sb_field_add_opt">
                        <input type="text" class="dig_sb_field_list_input"
                               placeholder="<?php _e('Add a Option', 'digits'); ?>" />
                             </div>
                    </div>

                    <div class="dig_sb_field dig_sb_field_tac dig_sb_extr_fields" data-req="1">
                        <div class="dig_sb_field_label">
                            <label for="dig_csf_tac_link"><?php _e('Terms & Conditions Link', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <input type="text" id="dig_csf_tac_link" name="tac_link"/>
                        </div>
                    </div>

                    <div class="dig_sb_field dig_sb_field_tac dig_sb_extr_fields"  data-req="0">
                        <div class="dig_sb_field_label">
                            <label for="dig_csf_tac_privacy_link"><?php _e('Privacy Link', 'digits'); ?></label>
                        </div>
                        <div class="dig_sb_field_input">
                            <input type="text" id="dig_csf_tac_privacy_link" name="tac_privacy_link"/>
                        </div>
                    </div>

                    <div class="dig_sb_field dig_sb_extr_fields dig_sb_field_user_role" id="dig_field_roles" data-req="1" data-list="2">
                        <div class="dig_sb_field_label">
                            <label><?php _e('User Roles', 'digits'); ?><span class="dig_sb_required">*</span></label>
                        </div>
                        <ul>
                             <?php
                             global $wp_roles;
                             foreach ( $wp_roles->roles as $key=>$value ):
                             ?>
                             <label><input class="dig_chckbx_usrle" type="checkbox" value="<?php echo  $key; ?>" /><?php echo $value['name']; ?></label>
                             <?php endforeach; ?>
                         </ul>
                    </div>

                    <?php do_action('dig_custom_fields_options'); ?>
                </div>

                <div id="dig_cus_field_footer">
                    <div class="dig_ad_blue dig_cus_field_done"><?php _e('Add', 'digits'); ?></div>
                    <div class="dig_ad_cancel"><?php _e('Back', 'digits'); ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function dig_requireCustomToString($value)
{
    switch ($value) {
        case 0:
            return __("Optional", "digits");
        case 1:
            return __("Required", "digits");
        default:
            return null;
    }
}

function digit_activation($form = true)
{
    if ($form) {
        echo '<form class="dig_activation_form" method="post">';
        ?>
        <h1><?php _e("Activate Digits", "digits"); ?></h1>
<?php
    }
        $code = dig_get_option('dig_purchasecode');
        $license_type = dig_get_option('dig_license_type', 1);;

    $plugin_data = get_plugin_data( __FILE__ );
                $plugin_version = $plugin_data['Version'];
        ?>
        <input type="hidden" name="dig_license_type" value="<?php echo dig_get_option('dig_license_type', 1);?>" />

        <input type="hidden" name="dig_domain" value="<?php echo network_home_url();?>" />

        <input type="hidden" name="dig_version" value="<?php echo $plugin_version;?>" />

        <table class="form-table">
            <tr class="dig_domain_type" <?php if (!empty($code)) echo 'style="display:none;"'; ?>>
                <th scope="row"><label for="dig_purchasecode"><?php _e("Is this domain your", "digits"); ?> </label></th>
                <td>
                <button class="button" type="button" val="1"><?php _e('Live Server','digits');?></button>
                <button class="button" type="button" val="2"><?php _e('Testing Server','digits');?></button>
                </td>
            </tr>
            <tr class="dig_prchcde" <?php if (!empty($code)) echo 'style="display:table-row;"'; ?>>
                <th scope="row"><label for="dig_purchasecode"><?php _e("Purchase code", "digits"); ?> </label></th>
                <td>
                    <div class="digits_shortcode_tbs digits_shortcode_stb">
                        <input class="dig_inp_wid31" nocop="1" type="text" name="dig_purchasecode"
                               id="dig_purchasecode"
                               placeholder="<?php _e("Purchase Code", "digits"); ?>" autocomplete="off"
                               value="<?php echo $code ?>" readonly>
                               <button class="button dig_btn_unregister" type="button"><?php _e('UNREGISTER','digits');?></button>
                        <img class="dig_prc_ver"
                             src="<?php echo plugin_dir_url( __FILE__ ) . 'assests/images/check_animated.svg' ;?>"
                             draggable="false" <?php if (!empty($code)) echo 'style="display:block;"'; ?>>
                        <img class="dig_prc_nover"
                             src="<?php echo plugin_dir_url( __FILE__ ) . 'assests/images/cross_animated.svg' ;?>"
                             draggable="false">
                    </div>
                </td>
            </tr>
        </table>

        <div class="dig_desc_sep_pc dig_prchcde" <?php if (!empty($code)) echo 'style="display:block;"'; ?>></div>
        <p class="dig_ecr_desc dig_cntr_algn_clr dig_prchcde" <?php if (!empty($code)) echo 'style="display:block;"'; ?>>
            <?php _e('Please activate your plugin by entering purchase code to remove "Powered By','digits'); ?>
            <b></b>" <?php _e('and receive updates','digits');?>.
        </p>

    <table class="form-table dig_prchcde" <?php if (!empty($code)) echo 'style="display:table-row;"'; ?>>
    <tr >
    <td>
        <p class="dig_ecr_desc dig_cntr_algn dig_sme_lft_algn request_live_server_addition" <?php if($license_type==1) echo'style="display:none;"';?>>
            <?php _e('If you want to use same purchase code on your live server then please click the below button to request for it. Our team will take less than 12 hours to respond to your request, and will notify via email.','digits'); ?>
        </p>
        <p class="dig_ecr_desc dig_cntr_algn dig_sme_lft_algn request_testing_server_addition" <?php if($license_type==2) echo'style="display:none;"';?>>
            <?php _e('If you want to use same purchase code on your testing server then please click the below button to request for it. Our team will take less than 12 hours to respond to your request, and will notify via email.','digits'); ?>
        </p>
        <button href="https://help.unitedover.com/digits/request-production/" class="button dig_request_server_addition request_live_server_addition" type="button" <?php if($license_type==1) echo'style="display:none;"';?>><?php _e('Request Live Server Addition','digits');?></button>
        <button href="https://help.unitedover.com/digits/request-staging/" class="button dig_request_server_addition request_testing_server_addition" type="button" <?php if($license_type==2) echo'style="display:none;"';?>><?php _e('Request Testing Server Addition','digits');?></button>
        </td>
    </tr>
    </table>
        <?php
        if(!$form)
        return;
    ?>
                <br/>

        <p class="digits-setup-action step">
            <Button type="submit" href="<?php echo admin_url('index.php?page=digits-setup&step=documentation'); ?>"
                   class="button-primary button button-large button-next regular-text"
                    ><?php _e("Activate", "digits"); ?></Button>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=documentation'); ?>"
               class="button"><?php _e("Skip", "digits"); ?></a>
        </p>
    </form>

    <?php
}

/**
 * Output the content for Documentation
 */
function digit_documentation()
{
    ?>
    <h1><?php _e("Have a look at our documentation", "digits"); ?></h1>
    <p class="lead"
       style="border-bottom:none;padding-bottom:0;"><?php _e("Do you feel like you need some help with the setup, go through our detailed documentation it will guide you through.", "digits"); ?></p>
    <br/><br/>

    <center><a href="https://help.unitedover.com/" class="button"
               target="_blank"><?php _e("Open Documentation", "digits"); ?></a></center>
    <br/><br/>

    <p class="lead"><?php _e("Having the documentation opened in other tab can help you if you get stuck somewhere in the middle.", "digits"); ?></p>
    <p><?php _e("Don't worry, we'll not tell anyone that you went through our documentation to setup this simple thing.", "digits"); ?></p>
    <p class="digits-setup-action step">
        <a href="<?php echo admin_url('index.php?page=digits-setup&step=apisettings'); ?>"
           class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></a>
        <a href="<?php echo admin_url('index.php?page=digits-setup&step=activation'); ?>"
           class="button"><?php _e("Back", "digits"); ?></a>
    </p>
    <?php
}

/**
 * Output the content for Ready
 */
function digit_ready()
{
    ?>

    <h1><?php _e("Digits is ready!", "digits"); ?></h1>
    <p class="lead"><?php _e("Congratulations! Digits has been activated and your website is ready. Login to your WordPress
        dashboard to make changes and modify any of the content to suit your needs.", "digits"); ?>
    </p>

    <p class="digits-setup-action step">
        <a href="<?php echo esc_url(admin_url('options-general.php?page=digits_settings&tab=customize')); ?>"
           class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></a>
        <a href="<?php echo admin_url('index.php?page=digits-setup&step=shortcodes'); ?>"
           class="button"><?php _e("Back", "digits"); ?></a>
    </p>

    <?php
}

/**
 * Output the content for Configure
 */

function digit_configure()
{
    $color = get_option('digit_color');
    $bgcolor = "#4cc2fc";
    $fontcolor = 0;
    if ($color !== false) {
        $bgcolor = $color['bgcolor'];
    }
    ?>

    <h1><?php _e("Login Page Configuration", "digits"); ?></h1>
    <p class="lead"></p>

    <form method="post" enctype="multipart/form-data">
        <?php
        digits_configure_settings();
        ?>

        <p class="digits-setup-action step">
            <Button type="submit" class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></Button>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=apisettings'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
    </form>
    <?php

    dig_config_scripts();
}

function dig_config_scripts()
{
    wp_register_script('digits-upload-script', plugins_url('/assests/js/upload.js', __FILE__, array('jquery'), null, true));

    $jsData = array(
        'logo' => get_option('digits_logo_image'),
        'selectalogo' => __('Select a Image', 'digits'),
        'usethislogo' => __('Use this Image', 'digits'),
        'changeimage' => __('Change Image', 'digits'),
        'selectimage' => __('Select Image', 'digits'),
        'removeimage' => __('Remove Image', 'digits'),
    );
    wp_localize_script('digits-upload-script', 'dig', $jsData);

    wp_enqueue_script('wp-color-picker-alpha', plugins_url('/assests/js/wp-color-picker-alpha.min.js', __FILE__, array('jquery'), null, true)
        , array('wp-color-picker'), '1.2.2', false);

    wp_enqueue_script('digits-upload-script');

    @do_action('admin_footer');
    do_action('admin_print_footer_scripts');
}

/**
 * Output the content for API SETTINGS
 */
function digit_apisettings()
{
    $app = get_option('digit_api');
    $appid = "";
    $appsecret = "";
    if ($app !== false) {
        $appid = $app['appid'];
        $appsecret = $app['appsecret'];
    }
    ?>

    <h1><?php _e("API Settings", "digits"); ?></h1>
    <p class="lead"></p>

    <form method="post">
        <?php
        digits_api_settings();
        ?>
        <p class="digits-setup-action step">
            <Button type="submit" class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></Button>
            <a href="<?php echo admin_url('index.php?page=digits-setup&step=documentation'); ?>"
               class="button"><?php _e("Back", "digits"); ?></a>
        </p>
    </form>

    <?php
}

function digit_addons(){
    $plugins = doCurl("digits.unitedover.com/addons/data.json");

    if(empty($plugins)) {

    ?>

    <div class="dig_addons_coming_soon"><?php _e('Coming Soon','digits') ;?></div>

    <?php
    return;
}

foreach ($plugins as $plugin) {
    ?>
    <div class="digits-addons-container">
        <a href="<?php echo $plugin['location'];?>" target="_blank">
        <div class="dig-addon-item">
            <div class="dig-addon-par">
                <div class="dig_addon_img">
                    <img src="<?php echo $plugin['image']; ?>" draggable="false"/>
                </div>
                <div class="dig_addon_details">
                    <div class="dig_addon_name"><?php echo $plugin['name']; ?></div>
                    <div class="dig_addon_sep"></div>
                    <div class="dig_addon_btm_pnl">
                        <table>
                            <tr>
                                <td class="dig_addon_dsc">
                                    <?php echo $plugin['desc']; ?>
                                </td>
                                <td>
                                    <div class="dig_addon_btn_con">
                                        <div class="dig_addon_btn">
                                            <?php
                                            if(is_plugin_active($plugin['path'])){
                                                echo __('Installed','digits');
                                            }else
                                                echo $plugin['price'];
                                            ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>

                </div>
            </div>
        </div>
        </a>
    </div>
    <?php
}
}

/**
 * Output the content for introduction
 */
function digit_introduction()
{
    ?>
    <h1><?php _e("Welcome to the configuration wizard for DIGITS!", "digits"); ?></h1>
    <p class="lead">
        <?php _e("Thank you for choosing Digits. This quick setup wizard will help you to configure this plugin in a few simple steps.", "digits"); ?>
        <br/><br/>
        <?php _e("It should only take 4-5 minutes.", "digits"); ?>
    </p>
    <p><?php _e("Busy right now! If you don't want to go through the wizard, you can skip and return to the WordPress dashboard and come back anytime.", "digits"); ?></p>

    <p class="digits-setup-action step">
        <a href="<?php echo admin_url('index.php?page=digits-setup&step=activation') ?>"
           class="button-primary button button-large button-next"><?php _e("Continue", "digits"); ?></a>
    </p>
    <?php
}

/**
 * Output the content for the current step.
 */
function setup_wizard_content($steps, $step)
{
    echo '<div class="digits-setup-content">';
    call_user_func($steps[$step]['view']);
    echo '<a class="return-to-dashboard" href="' . esc_url(admin_url()) . '">' . __("Return to the WordPress Dashboard", "digits") . '</a>';

    echo '</div>';
}


/**
 * Output the steps.
 */
function setup_wizard_steps($steps, $currentStep)
{
    $ouput_steps = $steps;

    ?>
    <ol class="digits-setup-steps">
        <?php foreach ($ouput_steps as $step_key => $step): ?>
            <li class="<?php
            if ($step_key === $currentStep) {
                echo 'active';
            } elseif (array_search($currentStep, array_keys($steps)) > array_search($step_key, array_keys($steps))) {
                echo 'done';
            }
            ?>"><?php echo esc_html($step['name']); ?></li>
        <?php endforeach; ?>
    </ol>
    <?php
}

function sanitize($input)
{
    // Initialize the new array that will hold the sanitize values
    $new_input = array();

    // Loop through the input and sanitize each of the values
    foreach ($input as $key => $val) {
        $new_input[$key] = sanitize_text_field($val);
    }

    return $new_input;
}
?>