<?php

define('WXMP_APPID','');
define('WXMP_APPSECRET','');

require( dirname(__FILE__) . '/../../../wp-load.php' );

function mpwechat_oauth(){
    $code = $_GET['code'];
    $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=" . WXMP_APPID . "&secret=" . WXMP_APPSECRET . "&code=" . $code . "&grant_type=authorization_code";
    $content = file_get_contents($url);
    $ss = json_decode($content,true);
    $info_url = 'https://api.weixin.qq.com/sns/userinfo?access_token=' . $ss['access_token'] . '&openid=' . $ss['openid'];
    $user_info = json_decode(file_get_contents($info_url),true);
    $weixin_id = $user_info["unionid"];
    if(is_user_logged_in()){
        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"weixin_uid",$weixin_id);
        update_user_meta($this_user->ID ,"weixin_avatar",$user_info['headimgurl']);
        wp_redirect( home_url() );
    }else{
        $oauth_user = get_users(array("meta_key "=>"weixin_uid","meta_value"=>$weixin_id));
        if(is_wp_error($oauth_user) || !count($oauth_user)){
            $username = $user_info["nickname"];
            $login_name = wp_create_nonce($weixin_id);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $username,
                'user_pass' => $random_password,
                'nick_name' => $username
            );
            $user_id = wp_insert_user( $userdata );
            wp_signon(array("user_login"=>$login_name,"user_password"=>$random_password),false);
            wp_set_auth_cookie($user_id,true);
            update_user_meta($user_id ,"weixin_uid",$weixin_id);
            update_user_meta($user_id  ,"weixin_avatar",$user_info['headimgurl']);
            wp_redirect( home_url() );

        }else{
            wp_set_auth_cookie($oauth_user[0]->ID,true);
            $reddd = isset($_GET['state']) ? $_GET['state'] : home_url();
            wp_redirect( $reddd );
        }
    }
}


if (isset($_GET['code'])){
    mpwechat_oauth();
}


function mpwechat_oauth_url(){

    $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='. WXMP_APPID .'&redirect_uri='. urlencode ( get_template_directory_uri() ) .'/wechat-inner.php&response_type=code&scope=snsapi_userinfo&state=' . urlencode ( $_SERVER["HTTP_REFERER"] ) . '#wechat_redirect';
    return $url;
}

echo mpwechat_oauth_url();