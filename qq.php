<?php

define('QQ_APPID','');//appkey
define('QQ_APPSECRET','');//appsecret

function fa_qq_oauth_redirect(){
    echo '<script>if( window.opener ) {window.opener.location.reload();
						window.close();
						}else{
						window.location.href = "'.home_url().'";
						}</script>';
}

function qq_oauth(){
    if (!empty($_GET ['state'])) {$code = $_GET['code'];
        $token_url = "https://graph.qq.com/oauth2.0/token?client_id=" . QQ_APPID . "&client_secret=" . QQ_APPSECRET . "&grant_type=authorization_code&redirect_uri=".urlencode (home_url())."&code=".$code;
        $response = file_get_contents( $token_url );
        if (strpos ( $response, "callback" ) !== false) {
            wp_redirect(home_url());
        }
        $params = array ();
        parse_str ( $response, $params );
        $qq_access_token = $params ["access_token"];
    } else {
        echo ("The state does not match. You may be a victim of CSRF.");
        exit;
    }
    $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $qq_access_token;

    $str = file_get_contents( $graph_url );
    if (strpos ( $str, "callback" ) !== false) {
        $lpos = strpos ( $str, "(" );
        $rpos = strrpos ( $str, ")" );
        $str = substr ( $str, $lpos + 1, $rpos - $lpos - 1 );
    }

    $user = json_decode ( $str );
    if (isset ( $user->error )) {
        echo "<h3>错误代码:</h3>" . $user->error;
        echo "<h3>信息  :</h3>" . $user->error_description;
        exit ();
    }
    $qq_openid = $user->openid;
    if(empty($qq_openid)){
        wp_redirect(home_url());
        exit;
    }
    $get_user_info = "https://graph.qq.com/user/get_user_info?" . "access_token=" . $qq_access_token . "&oauth_consumer_key=" . QQ_APPID . "&openid=" . $qq_openid . "&format=json";
    $data = file_get_contents( $get_user_info );
    $str  = json_decode($data , true);
    $username = $str['nickname'];
    $avatar = $str['figureurl_2'];
    if(is_user_logged_in()){

        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"qq_openid",$qq_openid);
        update_user_meta($this_user->ID ,"qq_avatar",$avatar);
        fa_qq_oauth_redirect();
    } else {
        $user_qq = get_users(array("meta_key "=>"qq_openid","meta_value"=>$qq_openid));
        if(is_wp_error($user_qq) || !count($user_qq)){
            $login_name = wp_create_nonce($qq_openid);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $username,
                'user_pass' => $random_password,
                'nick_name' => $username
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

function social_oauth_qq(){
    if (isset($_GET['code']) && isset($_GET['type']) && $_GET['type'] == 'qq'){
        qq_oauth();
    }
}
add_action('init','social_oauth_qq');


function qq_oauth_url(){
    session_start;
    $_SESSION ['state'] = md5 ( uniqid ( rand (), true ) );
    $url = "https://graph.qq.com/oauth2.0/authorize?client_id=" . QQ_APPID . "&state=" . $_SESSION ['state'] . "&response_type=code&redirect_uri=" . urlencode (home_url('/?type=qq'));
    return $url;
}