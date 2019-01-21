<?php



if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists('Clockwork') ) {
    require_once plugin_dir_path(__DIR__) . 'gateways/clockwork/wordpress/class-clockwork-plugin.php';
}



require_once plugin_dir_path(__DIR__) . 'gateways/melipayamak/autoload.php';
use \Melipayamak\MelipayamakApi;


require_once plugin_dir_path( __DIR__ ).'Twilio/autoload.php';
use Twilio\Rest\Client;


add_action("wp_ajax_nopriv_digits_resendotp", "digits_resendotp");

add_action("wp_ajax_digits_resendotp", "digits_resendotp");

function digits_resendotp(){
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp==1) die();
    $countrycode = sanitize_text_field($_REQUEST['countrycode']);
    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $login = $_REQUEST['login'];

    if(!checkwhitelistcode($countrycode)) {
        echo "-99";
        die();
    }

    if (!wp_verify_nonce($csrf,'dig_form')){
        echo '0';
        die();
    }

    $users_can_register = get_option('dig_enable_registration',1);
    $digforgotpass = get_option('digforgotpass',1);
    if($users_can_register==0 && $login == 2){
        echo "0";
        die();
    }
    if($digforgotpass==1 && $login == 3){
        echo "0";
        die();
    }

    if(OTPexists($countrycode,$mobileno,true)) {
        digits_check_mob();
    }
    echo "0";die();

}


add_action("wp_ajax_nopriv_digits_verifyotp_login", "digits_verifyotp_login");

add_action("wp_ajax_digits_verifyotp_login", "digits_verifyotp_login");


function checkwhitelistcode($code){


    $whiteListCountryCodes = get_option("whitelistcountrycodes");

    $size = sizeof($whiteListCountryCodes);
    if($size>0 && is_array($whiteListCountryCodes)){

        $countryarray = getCountryList();
        $code = str_replace("+", "", $code);

        foreach($countryarray as $key => $value){
            if($value==$code){
                if(in_array($key,$whiteListCountryCodes)){
                    return true;
                }
            }

        }

        return false;
    }
    return true;

}
function digits_verifyotp_login()
{
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp==1) die();
    $countrycode = sanitize_text_field($_REQUEST['countrycode']);


    if(!checkwhitelistcode($countrycode)) {
        echo "-99";
        die();
    }


    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $otp = sanitize_text_field($_REQUEST['otp']);
    $del = false;


    $users_can_register = get_option('dig_enable_registration',1);
    $digforgotpass = get_option('digforgotpass',1);
    if($users_can_register==0 && $_REQUEST['dtype'] == 2){
        echo "1013";
        die();
    }
    if($digforgotpass==0 && $_REQUEST['dtype'] == 3){
        echo "0";
        die();
    }

    if (!wp_verify_nonce($csrf, 'dig_form')) {
        echo '1011';
        die();
    }


    if ($_REQUEST['dtype'] == 1) $del = true;

    $rememberMe = false;
    if(isset($_GET['rememberMe']) && $_GET['rememberMe']===true){
        $rememberMe = true;
    }

    if(verifyOTP($countrycode,$mobileno,$otp,$del)){

        $user1 = getUserFromPhone($countrycode.$mobileno);
        if ($user1) {

            if($_REQUEST['dtype']==1) {
                wp_set_current_user($user1->ID, $user1->user_login);
                wp_set_auth_cookie($user1->ID,$rememberMe);
                echo '11';
            }else{
                echo '1';
            }

            die();
        }else{
            echo '-1';
            die();
        }


    }else{
        echo '0';
        die();
    }

}
add_action("wp_ajax_nopriv_digits_check_mob", "digits_check_mob");
add_action("wp_ajax_digits_check_mob", "digits_check_mob");


function sanitize_mobile_field_dig($mobile){
    return ltrim(sanitize_text_field($mobile), '0');
}
function digits_check_mob(){


    $dig_login_details = digit_get_login_fields();
    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
    $otpaccp = $dig_login_details['dig_login_otp'];

    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp==1) die();
    $countrycode = sanitize_text_field($_REQUEST['countrycode']);
    $mobileno = sanitize_mobile_field_dig($_REQUEST['mobileNo']);
    $csrf = $_REQUEST['csrf'];
    $login = $_REQUEST['login'];


    if(($otpaccp==0 && $login==1) || ($mobileaccp==0 && $login==1)){echo "-99";die();}

    if(!checkwhitelistcode($countrycode)) {
        echo "-99";
        die();
    }

    if (!wp_verify_nonce($csrf,'dig_form')){
        echo '0';
        die();
    }


    if(isset($_POST['captcha']) && isset($_POST['captcha_ses'])){
        $ses = filter_var($_POST['captcha_ses'], FILTER_SANITIZE_NUMBER_FLOAT);
        if ($_POST['captcha'] != $_SESSION['dig_captcha' . $ses]) {
            echo '9194';
            die();
        }
    }
    $users_can_register = get_option('dig_enable_registration',1);
    $digforgotpass = get_option('digforgotpass',1);
    if($users_can_register==0 && $login == 2){
        echo "0";
        die();
    }
    if($digforgotpass==0 && $login == 3){
        echo "0";
        die();
    }

    if($login == 2 || $login==11){
        if(isset($_POST['username'])  && !empty($_POST['username'])){
            $username = sanitize_text_field($_POST['username']);
            if(username_exists($username)){
                die('9192');
            }
        }
        if(isset($_POST['email']) && !empty($_POST['email'])){
            $email = sanitize_text_field($_POST['email']);
            if(email_exists($email)){
                if($login==11){
                    $user = get_user_by( 'email', $email );
                    if($user->ID!=get_current_user_id()){
                        die('9193');
                    }

                }else{
                    die('9193');
                }
            }
        }

    }




    $user1 = getUserFromPhone($countrycode.$mobileno);
    if(($user1!=null && $login==11) || ($user1!=null && $login==2)){

        echo "-1";
        die();
    }
    if($user1!=null || $login==2 || $login==11){

        $digit_tapp = get_option('digit_tapp',1);




        if($digit_tapp!=13) {

            if (OTPexists($countrycode, $mobileno)) {
                echo "1";
                die();
            }

            $code = dig_get_otp();


            
            if (!digit_send_otp($digit_tapp, $countrycode, $mobileno, $code)) {
                echo "0";
                die();
            }


            $mobileVerificationCode = md5($code);

            global $wpdb;
            $table_name = $wpdb->prefix . "digits_mobile_otp";

            $db = $wpdb->replace($table_name, array(
                'countrycode' => $countrycode,
                'mobileno' => $mobileno,
                'otp' => $mobileVerificationCode,
                'time' => date("Y-m-d H:i:s",strtotime("now"))
            ), array(
                    '%d',
                    '%s',
                    '%s',
                    '%s')
            );

            if(!$db){
                echo "0";
                die();
            }

        }

        echo "1";
        die();

    }else{
        echo '-11';
        die();
    }

    echo "0";
    die();

}


function digit_send_otp($digit_tapp,$countrycode,$mobile,$otp,$testCall = false){


    $dig_messagetemplate = get_option("dig_messagetemplate","Your OTP for %NAME% is %OTP%");
    $dig_messagetemplate = str_replace("%NAME%", get_option('blogname'), $dig_messagetemplate);
    $dig_messagetemplate = str_replace("%OTP%", $otp, $dig_messagetemplate);

    return digit_send_message($digit_tapp,$countrycode,$mobile,$otp,$dig_messagetemplate,$testCall);

}

function digit_send_message($digit_tapp,$countrycode,$mobile,$otp,$dig_messagetemplate,$testCall = false){



    switch($digit_tapp){
        case 2:


            $tiwilioapicred = get_option('digit_twilio_api');


            $twiliosenderid = $tiwilioapicred['twiliosenderid'];


            $sid = $tiwilioapicred['twiliosid'];
            $token = $tiwilioapicred['twiliotoken'];


            $client = new Client($sid, $token);

            try {
                $result = $client->messages->create(
                    $countrycode.$mobile,
                    array(
                        'From' => $twiliosenderid,
                        'Body' => $dig_messagetemplate
                    )
                );
            }catch(Exception $e){
                if($testCall) return $e->getMessage();
                return false;
            }

            if($testCall) return $result;
            return true;
        case 3:

            $msg91apicred = get_option('digit_msg91_api');


            $authKey = $msg91apicred['msg91authkey'];
            $senderId = $msg91apicred['msg91senderid'];
            $msg91route = $msg91apicred['msg91route'];

            if(empty($msg91route)){
                $msg91route = 2;
            }
            $message = urlencode($dig_messagetemplate);

            if($msg91route==1){


                $postData = array(
                    'authkey' => $authKey,
                    'mobile' => str_replace("+", "", $countrycode) . $mobile,
                    'message' => $message,
                    'sender' => $senderId,
                    'otp' => $otp,
                    'otp_expiry' => 10
                );


                $url = "https://control.msg91.com/api/sendotp.php?" . http_build_query($postData);
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CUSTOMREQUEST => 'GET'

                ));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $result = curl_exec($ch);

                curl_close($ch);

                if($testCall) return $result;

                if (curl_errno($ch)) {

                    if($testCall) return "curl error:". curl_errno($ch);

                    return false;
                }

            }else{


                $postData = array(
                    'authkey' => $authKey,
                    'mobiles' => $mobile,
                    'message' => $message,
                    'sender' => $senderId,
                    'route' => 4,
                    '&country' => $countrycode
                );


                $url="https://control.msg91.com/api/sendhttp.php";
                $ch = curl_init();
                curl_setopt_array($ch, array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData

                ));
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                $result = curl_exec($ch);

                curl_close($ch);

                if(curl_errno($ch))
                {
                    if($testCall) return "curl error:". curl_errno($ch);
                    return false;
                }

                if($testCall) return $result;
                return true;
            }
            return true;

        case 4:
            $apikey = get_option('digit_yunpianapi');

            $data=array('text'=>$dig_messagetemplate,'apikey'=>$apikey,'mobile'=>$mobile);


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept:text/plain;charset=utf-8', 'Content-Type:application/x-www-form-urlencoded','charset=utf-8'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $result = curl_exec($ch);
            curl_close($ch);

            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;

            return true;
        case 5:

            $clickatell = get_option('digit_clickatell');

            $apikey = $clickatell['api_key'];
            $from = $clickatell['from'];



            $toarray = array();
            $toarray[] = $countrycode.$mobile;

            $cs_array = array();
            $cs_array['content'] = $dig_messagetemplate;
            if(!empty($from)) $cs_array['from'] = $from;
            $data= $cs_array;
            $data['to'] = $toarray;
            $data_string = json_encode($data);


            $ch = curl_init();


            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: '.$apikey,

            ));


            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            curl_setopt ($ch, CURLOPT_URL, 'https://platform.clickatell.com/messages');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            $result = curl_exec($ch);
            curl_close($ch);

            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;


            return true;
        case 6:
            $clicksend = get_option('digit_clicksend');
            $username = $clicksend['apiusername'];
            $apikey = $clicksend['apikey'];
            $from = $clicksend['from'];


            $data = array();
            $message = array();
            $message[0]=array('body'=>$dig_messagetemplate,'from'=>$from,'to'=>$countrycode.$mobile);
            $data['messages'] = $message;

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Authorization: Basic ' . base64_encode("$username:$apikey")));

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'https://rest.clicksend.com/v3/sms/send');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);
            curl_close($ch);


            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($result === false) return false;

            if($testCall) return $result;

            return true;
        case 7:

            try
            {


                $clockwork = get_option('digit_clockwork');


                $clockworkapi = $clockwork['clockworkapi'];
                $from = $clockwork['from'];



                
                $clockwork = new WordPressClockwork( $clockworkapi );

                // Setup and send a message
                $message = array( 'from' => $from, 'to' => str_replace("+","",$countrycode).$mobile, 'message' => $dig_messagetemplate );
                $result = $clockwork->send( $message );

                // Check if the send was successful
                if($result['success']) {

                    if($testCall) return $result;

                    return true;

                } else {
                    return false;
                }
            }
            catch (ClockworkException $e)
            {
                if($testCall) return $e->getMessage();
                return false;

            }
        case 8:

            $messagebird = get_option('digit_messagebird');
            $accesskey = $messagebird['accesskey'];
            $originator = $messagebird['originator'];
            $data=array('body'=>$dig_messagetemplate,'originator'=>$originator,'recipients'=>str_replace("+","",$countrycode).$mobile);


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json','Content-Type: application/json','Authorization: AccessKey '.$accesskey));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'https://rest.messagebird.com/messages');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);
            curl_close($ch);

            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;

            return true;

        case 9:
            $mobily = get_option('digit_mobily_ws');

            $mobily_mobile = $mobily['mobile'];
            $password = $mobily['password'];
            $sender = $mobily['sender'];

            $data=array('msg'=>convertToUnicode($dig_messagetemplate),
                'mobile'=>$mobily_mobile,
                'password'=>$password,
                'sender'=>$sender,
                'applicationType' => '68',
                'numbers'=>str_replace("+","",$countrycode).$mobile);



            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'http://mobily.ws/api/msgSend.php?'.http_build_query($data));
            $result = curl_exec($ch);

            curl_close($ch);

            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;

            return true;
        case 10:
            $nexmo = get_option('digit_nexmo');
            $from = $nexmo['from'];
            $apikey = $nexmo['api_key'];
            $apisecret = $nexmo['api_secret'];

            $data=array('text'=>$dig_messagetemplate,
                'to'=>$countrycode.$mobile,
                'from'=>$from,
                'api_key'=>$apikey,
                'api_secret'=>$apisecret);


            $ch = curl_init();


            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'https://rest.nexmo.com/sms/json');
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $result = curl_exec($ch);
            curl_close($ch);


            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;

            return true;
        case 11:
            $pilvo = get_option('digit_pilvo');
            $authid = $pilvo['auth_id'];
            $authtoken = $pilvo['auth_token'];
            $sender_id = $pilvo['sender_id'];

            $data=array('text'=>$dig_messagetemplate,
                'src'=>$sender_id,
                'dst'=>$countrycode.$mobile,);


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_USERPWD, $authid . ":" . $authtoken);

            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt ($ch, CURLOPT_URL, 'https://api.plivo.com/v1/Account/'.$authid.'/Message/');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $result = curl_exec($ch);
            curl_close($ch);


            if(curl_errno($ch))
            {
                if($testCall) return "curl error:". curl_errno($ch);
                return false;
            }

            if($testCall) return $result;

            if($result === false) return false;

            return true;
        case 12:

            $smsapi = get_option('digit_smsapi');
            $token = $smsapi['token'];
            $from = $smsapi['from'];
            $params = array(
                'to' => str_replace("+","",$countrycode).$mobile,
                'from' => $from,
                'message' => $dig_messagetemplate,
            );

            $url = 'https://api.smsapi.com/sms.do';
            $c = curl_init();
            curl_setopt( $c, CURLOPT_URL, $url );
            curl_setopt( $c, CURLOPT_POST, true );
            curl_setopt( $c, CURLOPT_POSTFIELDS, $params );
            curl_setopt( $c, CURLOPT_RETURNTRANSFER, true );
            curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt( $c, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer $token"
            ));

            $content = curl_exec( $c );
            $http_status = curl_getinfo($c, CURLINFO_HTTP_CODE);



            curl_close( $c );

            if($testCall) return $content;

            if(curl_errno($c))
            {
                if($testCall) return "curl error:". curl_errno($c);
                return false;
            }

            if($http_status != 200){
                return false;
            }
            return true;
        case 13:
            return true;
        case 14:
            $unifonic = get_option('digit_unifonic');
            $app_sid = $unifonic['appsid'];
            $sender_id = $unifonic['senderid'];

            $params = 'AppSid='.$app_sid.'&Recipient='.str_replace("+","",$countrycode).$mobile . '&Body='.$dig_messagetemplate;
            if(!empty($sender_id)){
                $params = $params."&SenderID=".$sender_id;
            }



            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, "http://api.unifonic.com/rest/Messages/Send");
            curl_setopt($c, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($c, CURLOPT_HEADER, FALSE);
            curl_setopt($c, CURLOPT_POST, TRUE);
            curl_setopt($c, CURLOPT_POSTFIELDS,$params);



            curl_setopt($c, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
            $result = curl_exec($c);
            curl_close($c);


            


            if($testCall) return $result;
            if(curl_errno($c))
            {
                if($testCall) return "curl error:". curl_errno($c);
                return false;
            }

            if($result === false) return false;

            return true;
        case 15:

            $kaleyra = get_option('digit_kaleyra');
            $api_key = $kaleyra['api_key'];
            $sender_id = $kaleyra['sender_id'];
            $curl = curl_init();


            $url = "http://api-alerts.solutionsinfini.com/v4/?method=sms&sender=".$sender_id."&to=".str_replace("+","",$countrycode).$mobile ."&message=".urlencode($dig_messagetemplate)."&api_key=".$api_key;
            
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
            ));
            $result = curl_exec($curl);

            if(curl_errno($curl)) {
                $result = curl_error($curl);
                if(!$testCall) return false;
            }
            curl_close($curl);


            if($testCall) return $result;

            return true;
        case 16:
            $melipayamak = get_option('digit_melipayamak');

            $username = $melipayamak['username'];
            $password = $melipayamak['password'];
            $from =  $melipayamak['from'];
            $api = new MelipayamakApi($username,$password);
            $sms = $api->sms();
            $to = '0'.$mobile;
            $result = $sms->send($to,$from,$dig_messagetemplate);
            if($testCall) return $result;

            return true;
        case 999:
            $LimeCellular = get_option('digit_lime_cellular');

            $user = $LimeCellular['user'];
            $api_id = $LimeCellular['api_id'];
            $short_code = $LimeCellular['short_code'];

            $data=array('message'=> $dig_messagetemplate,
                'user'=>$user,
                'api_id'=>$api_id,
                'shortcode'=>$short_code,
                'mobile'=>str_replace("+","",$countrycode).$mobile);


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt ($ch, CURLOPT_URL, 'https://mcpn.us/sendsmsapi?'.http_build_query($data));
            $result = curl_exec($ch);



            curl_close($ch);

            if($result === false) return false;

            return true;
        default:
            return false;
    }


}

add_action("wp_ajax_nopriv_digits_login_user", "digits_login_user");


function digits_login_user() {


    $code = sanitize_text_field($_REQUEST['code']);
    $csrf = sanitize_text_field($_REQUEST['csrf']);



    $dig_login_details = digit_get_login_fields();
    $mobileaccp = $dig_login_details['dig_login_mobilenumber'];
    $otpaccp = $dig_login_details['dig_login_otp'];


    if (!wp_verify_nonce($csrf,'crsf-otp') || $mobileaccp==0 || $otpaccp==0){
        echo '0';
        die();
    }


    $json = getUserPhoneFromAccountkit($code);

    $phoneJson = json_decode($json,true);


    $phone = $phoneJson['phone'];



    $rememberMe = false;
    if(isset($_GET['rememberMe']) && $_GET['rememberMe']===true){
        $rememberMe = true;
    }


    if($json!=null) {
        $user1 = getUserFromPhone($phone);
        if ($user1) {
            wp_set_current_user($user1->ID, $user1->user_login);
            wp_set_auth_cookie($user1->ID,$rememberMe);

            do_action( 'wp_login', $user1->user_login, $user1 );

            echo '1';
            die();
        }else{
            echo '-1';
            die();
        }
    }else{
        echo '-9';
        die();
    }




    echo '0';
    die();
}



function dig_get_otp($isPlaceHolder = false){
    $dig_otp_size = get_option("dig_otp_size", 5);
    $digit_tapp = get_option('digit_tapp',1);
    if($digit_tapp==1 || $digit_tapp==13){
        $dig_otp_size = 6;
    }
    $code = "";
    for ($i = 0; $i < $dig_otp_size; $i++) {
        if(!$isPlaceHolder) {
            $code .= rand(0, 9);
        }else{
            $code .= '-';
        }

    }

    return $code;
}

function digits_test_api(){

    if (!current_user_can('manage_options')) {
        echo '0';
        die();
    }

    $mobile = sanitize_text_field($_POST['digt_mobile']);
    $countrycode = sanitize_text_field($_POST['digt_countrycode']);
    if(empty($mobile) || !is_numeric($mobile) || empty($countrycode) || !is_numeric($countrycode) ){
         _e('Invalid Mobile Number','digits');
        die();
    }

    $digit_tapp = get_option('digit_tapp',1);

    $code = dig_get_otp();

    $result = digit_send_otp($digit_tapp, $countrycode, $mobile, $code,true);
    if (!$result) {
        _e('Error','digits');
        die();
    }
    print_r($result);
    die();

}

add_action( 'wp_ajax_digits_test_api', 'digits_test_api' );


function dig_validate_login_captcha(){
    $ses = filter_var($_POST['dig_captcha_ses'], FILTER_SANITIZE_NUMBER_FLOAT);
    if ($_POST['digits_reg_logincaptcha'] != $_SESSION['dig_captcha' . $ses]) {
        return false;
    } else if (isset($_SESSION['dig_captcha' . $ses])) {
        unset($_SESSION['dig_captcha' . $ses]);
        return true;
    }

}






?>
