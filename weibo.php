<?php

define('WB_APPID','');//appkey
define('WB_APPSECRET','');//appsecret
function weibo_oauth(){
    $code = $_GET['code'];
    $url = "https://api.weibo.com/oauth2/access_token";
    $data = "client_id=" . WB_APPID . "&client_secret=" . WB_APPSECRET . "&grant_type=authorization_code&redirect_uri=".urlencode (home_url())."&code=".$code;
    $output = json_decode(oauth_http('post',array(),$url,$data));
    $sina_access_token = $output->access_token;
    $sina_uid = $output->uid;
    if(empty($sina_uid)){
        wp_redirect(home_url('/?3'.$sina_uid));
        exit;
    }
    if(is_user_logged_in()){
        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"sina_uid",$sina_uid);
        update_user_meta($this_user->ID ,"sina_access_token",$sina_access_token);
        echo '<script>if( window.opener ) {window.opener.location.reload();
						window.close();
						}else{
						window.location.reload()";
						}</script>';
    }else{
        $user_weibo = get_users(array("meta_key "=>"sina_uid","meta_value"=>$sina_uid));
        if(is_wp_error($user_weibo) || !count($user_weibo)){
            $get_user_info = "https://api.weibo.com/2/users/show.json?uid=".$sina_uid."&access_token=".$sina_access_token;
            $data = get_url_contents ( $get_user_info );
            $str  = json_decode($data , true);
            $username = $str['screen_name'];
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
            echo '<script>if( window.opener ) {window.opener.location.reload();
						window.close();
						}else{
						window.location.href = "'.home_url().'";
						}</script>';

        }else{
            update_user_meta($user_weibo[0]->ID ,"sina_access_token",$sina_access_token);
            wp_set_auth_cookie($user_weibo[0]->ID);

            echo '<script>if( window.opener ) {window.opener.location.reload();
						window.close();
						}else{
						window.location.href = "'.home_url().'";
						}</script>';
        }
    }
}

function social_oauth_weibo(){
    if (isset($_GET['code']) && isset($_GET['type']) && $_GET['type'] == 'sina'){

        weibo_oauth();

    }

}
add_action('init','social_oauth_weibo')


function weibo_oauth_url(){

    $url = 'https://api.weibo.com/oauth2/authorize?client_id=' . WB_APPID . '&response_type=code&redirect_uri=' . urlencode (home_url('/?type=sina'));
    return $url;
}