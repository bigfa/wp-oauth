<?php

define('WB_APPID','');//appkey
define('WB_APPSECRET','');//appsecret

function wb_ouath_redirect(){
    $url = home_url();
    wp_redirect( $url );
    exit;
}

function wb_get_access_token($code){
    $url = "https://api.weibo.com/oauth2/access_token";

    $data = array('client_id' => WB_APPID,
        'client_secret' => WB_APPSECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => home_url(),
        'code' => $code);

    $response = wp_remote_post( $url, array(
            'method' => 'POST',
            'body' => $data,
        )
    );

    $output = json_decode($response['body'],true);
    return $output;
}

function weibo_oauth(){

    if(!isset($_GET['code'])) wp_die('code empty.');

    $code = $_GET['code'];

    $output = wb_get_access_token($code);

    $sina_access_token = $output['access_token'];
    $sina_uid = $output['uid'];

    if(empty($sina_uid)){
        wp_die('服务器响应错误。');
    }

    $get_user_info_url = "https://api.weibo.com/2/users/show.json?uid=".$sina_uid."&access_token=".$sina_access_token;
    $usersina = wp_remote_get( $get_user_info_url );
    $userinfo  = json_decode($usersina['body'] , true);
    $username = $userinfo['screen_name'];
    $avatar = $userinfo['profile_image_url'];
    if(is_user_logged_in()){
        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"sina_uid",$sina_uid);
        update_user_meta($this_user->ID ,"sina_access_token",$sina_access_token);
        update_user_meta($this_user->ID ,"sina_avatar",$avatar);
        wb_ouath_redirect();
    }else{
        $user_weibo = get_users(array("meta_key "=>"sina_uid","meta_value"=>$sina_uid));
        if(is_wp_error($user_weibo) || !count($user_weibo)){

            $login_name = wp_create_nonce($sina_uid);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $username,
                'user_pass' => $random_password,
                'nick_name' => $username
            );
            $user_id = wp_insert_user( $userdata );
            wp_signon(array("user_login"=>$login_name,"user_password"=>$random_password),false);
            update_user_meta($user_id ,"sina_uid",$sina_uid);
            update_user_meta($user_id ,"sina_access_token",$sina_access_token);
            update_user_meta($user_id ,"sina_avatar",$avatar);
            wb_ouath_redirect();

        }else{
            update_user_meta($user_weibo[0]->ID ,"sina_access_token",$sina_access_token);
            update_user_meta($user_weibo[0]->ID ,"sina_avatar",$str['profile_image_url']);
            wp_set_auth_cookie($user_weibo[0]->ID);
            wb_ouath_redirect();
        }
    }
}

function social_oauth_weibo(){
    if (isset($_GET['code']) && isset($_GET['type']) && $_GET['type'] == 'sina'){
        weibo_oauth();
    }
}
add_action('init','social_oauth_weibo');


function weibo_oauth_url(){
    $url = 'https://api.weibo.com/oauth2/authorize?client_id=' . WB_APPID . '&response_type=code&redirect_uri=' . urlencode (home_url('/?type=sina'));
    return $url;
}