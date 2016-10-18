<?php

define('QQ_APPID','');//appkey
define('QQ_APPSECRET','');//appsecret

require( dirname(__FILE__) . '/wp-load.php' );

// edit this function if you wanna redirect to other url.
function fa_qq_oauth_redirect(){
    echo '<script>if (window.opener) {
    window.opener.location.reload();
    window.close()
} else {
    window.location.href = "'.home_url().'"
}</script>';
}


function qq_oauth(){

    $code = $_GET['code'];
    $token_url = "https://graph.qq.com/oauth2.0/token?client_id=" . QQ_APPID . "&client_secret=" . QQ_APPSECRET . "&grant_type=authorization_code&redirect_uri=".urlencode (home_url())."&code=".$code;
    $response = wp_remote_get( $token_url );
    if (is_wp_error($response)) {
        die($response->get_error_message());
    }
    $response = $response['body'];
    if (strpos ( $response, "callback" ) !== false) {
        wp_redirect(home_url());
    }
    $params = array ();
    parse_str ( $response, $params );
    $qq_access_token = $params ["access_token"];
    $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $qq_access_token;
    $str = wp_remote_get( $graph_url );
    $str = $str['body'];
    if (strpos ( $str, "callback" ) !== false) {
        $lpos = strpos ( $str, "(" );
        $rpos = strrpos ( $str, ")" );
        $str = substr ( $str, $lpos + 1, $rpos - $lpos - 1 );
    }
    $user = json_decode ( $str,true );
    if (isset ( $user->error )) {
        echo "<h3>错误代码:</h3>" . $user->error;
        echo "<h3>信息  :</h3>" . $user->error_description;
        exit ();
    }
    $qq_openid = $user['openid'];
    if(!$qq_openid){
        wp_redirect(home_url());
        exit;
    }
    $get_user_info = "https://graph.qq.com/user/get_user_info?" . "access_token=" . $qq_access_token . "&oauth_consumer_key=" . QQ_APPID . "&openid=" . $qq_openid . "&format=json";
    $data = wp_remote_get( $get_user_info );
    $data = $data['body'];
    $data  = json_decode($data , true);
    $username = $data['nickname'];
    $avatar = $data['figureurl_2'];

    if(is_user_logged_in()){

        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"qq_openid",$qq_openid);
        update_user_meta($this_user->ID ,"qq_avatar",$avatar);
        fa_qq_oauth_redirect();

    } else {
        $user_qq = get_users(array('meta_key'=>'qq_openid',
            'meta_value'=>$qq_openid,
            ));

        if( is_wp_error($user_qq) || !count($user_qq ) ) {

            $login_name = wp_create_nonce($qq_openid);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );         
            $userdata=array(
                'user_login' => 'qq' . $login_name,
                'display_name' => $username,
                'user_pass' => $random_password,
                'nickname' => $username
            );

            $user_id = wp_insert_user( $userdata );

            wp_signon(array("user_login"=>$login_name,"user_password"=>$random_password),false);

            update_user_meta($user_id ,"qq_openid",$qq_openid);
            update_user_meta($user_id ,"qq_avatar",$avatar);
            fa_qq_oauth_redirect();

        } else {

            wp_set_auth_cookie($user_qq[0]->ID);
            update_user_meta($user_qq[0]->ID ,"qq_avatar",$avatar);
            fa_qq_oauth_redirect();

        }
    }
}

function qq_oauth_url(){

    $url = "https://graph.qq.com/oauth2.0/authorize?client_id=" . QQ_APPID . "&state=" . md5 ( uniqid ( rand (), true ) ) . "&response_type=code&redirect_uri=" . urlencode (home_url('/auth-qq.php'));
    return $url;
}

if (isset($_GET ['state']) && isset($_GET ['code'])) qq_oauth();

if (isset($_GET ['showurl']) ) echo qq_oauth_url();
