<?php

define('DB_APPID','');//appkey
define('DB_APPSECRET','');//appsecret

function db_oauth_redirect(){
    wp_redirect( home_url() );
    exit;
}

function douban_oauth(){
    $code = $_GET['code'];
    $url = "https://www.douban.com/service/auth2/token";
    $data = array(
        'client_id' => WB_APPID,
        'client_secret' => WB_APPSECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => home_url(),
        'code' => $code
    );
    $response = wp_remote_post( $url, array(
            'method' => 'POST',
            'body' => $data,
        )
    );
    $output = json_decode($response['body'],true);
    $token = $output['access_token'];
    $douban_id = $output['douban_user_id'];
    if(empty($douban_id)){
        wp_die('服务器响应错误。');
    }
    if(is_user_logged_in()){
        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"douban_id",$douban_id);
        db_ouath_redirect();
    }else{
        $user_douban = get_users(array(
            "meta_key "=>"douban_id",
            "meta_value"=>$douban_uid
        ));
        if(is_wp_error($user_douban) || !count($user_douban)){
            $get_user_info = "http://api.douban.com/labs/bubbler/user/".$douban_id;
            $datas = wp_remote_get( $get_user_info );
            $str = json_decode($datas['body'] , true);
            $username = $str['title'];
            $login_name = wp_create_nonce($douban_id);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $username,
                'user_email' => $str['uid'] .'@fatesinger.com',
                'user_pass' => $random_password,
                'nickname' => $username
            );
            $user_id = wp_insert_user( $userdata );
            wp_signon(array(
                "user_login"=>$login_name,
                "user_password"=>$random_password
            ),false);
            update_user_meta($user_id ,"douban_id",$douban_id);
            db_oauth_redirect();

        }else{
            wp_set_auth_cookie($user_douban[0]->ID);
            db_oauth_redirect();
        }
    }
}

function social_oauth_douban(){
    if (isset($_GET['code']) && isset($_GET['type']) && $_GET['type'] == 'douban'){
        douban_oauth();
    }
}
add_action('init','social_oauth_douban');


function douban_oauth_url(){
    $url = 'https://www.douban.com/service/auth2/auth?client_id=' . DB_APPID . '&scope=shuo_basic_r,shuo_basic_w,douban_basic_common&response_type=code&redirect_uri=' . urlencode (home_url('/?type=douban');
    return $url;
}