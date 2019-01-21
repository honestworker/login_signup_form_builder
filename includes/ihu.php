<?php

add_action('ump_before_printing_errors','dig_iump_verify_error');
function dig_iump_verify_error($errors){

if(!isset($_POST['phone'])){
    return $errors;
}


    $phone = sanitize_mobile_field_dig($_POST['phone']);
    $code = sanitize_text_field($_POST['code']);
    $csrf = sanitize_text_field($_POST['csrf']);

    $otp = sanitize_text_field($_POST['digit_otp']);

    $countrycode = sanitize_text_field($_POST['digt_countrycode']);

    $digit_tapp = get_option('digit_tapp', 1);
    if (empty($phone) || !is_numeric($phone)) {
        $errors['phone'] = __('Please enter a valid Mobile Number','digits');
        return $errors;
    }


    $mobVerificationFailed = __('Mobile Number verification failed','digits');
    if ($digit_tapp == 1) {
        if (empty($code) || !wp_verify_nonce($csrf, 'crsf-otp')) {
            $errors['phone'] = $mobVerificationFailed;
            return $errors;
        }
        $json = getUserPhoneFromAccountkit($code);
        $phoneJson = json_decode($json, true);
        if ($json == null) {
            $errors['phone'] = $mobVerificationFailed;
            return $errors;

        }

        $mob = $countrycode . $phone;

        if($phoneJson['phone']!=$mob){
            $errors['phone'] = $mobVerificationFailed;
            return $errors;

        }

        $mob = $phoneJson['phone'];
        $phone = $phoneJson['nationalNumber'];
        $countrycode = $phoneJson['countrycode'];



    } else {
        if (empty($otp)) {
            $errors['phone'] = __('Please enter a valid OTP','digits');
            return $errors;
        }
        if (verifyOTP($countrycode, $phone, $otp, true)) {

            $mob = $countrycode . $phone;
        } else {
            $errors['phone'] = $mobVerificationFailed;
            return $errors;
        }
    }

    $user = getUserFromPhone($mob);
    if ($phone != 0 && $user == null) {
        $_POST['save_dig'] = 1;
    }

    return $errors;
}

add_action('ump_on_register_action','dig_iump_add_mobile');
function dig_iump_add_mobile($user_id){
    if(isset($_POST['save_dig']) && $_POST['save_dig']==1){
        $phone = sanitize_mobile_field_dig($_POST['phone']);
        $countrycode = sanitize_text_field($_POST['digt_countrycode']);

        update_user_meta($user_id, 'digt_countrycode', $countrycode);
        update_user_meta($user_id, 'digits_phone_no', $phone);
        update_user_meta($user_id, 'digits_phone', $countrycode . $phone);
    }
}
?>