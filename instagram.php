<?php
define('INS_APPID','');//appkey
define('INS_APPSECRET','');//appsecret

function ins_ouath_redirect(){
    echo '<script>if( window.opener ) {window.opener.location.reload();
                        window.close();
                        }else{
                        window.location.href = "'.home_url().'";
                        }</script>';
}

function ins_oauth(){
    $code = $_GET['code'];
    $url = "https://api.instagram.com/oauth/access_token";
    $data = array(
        'client_id' => INS_APPID,
        'client_secret' => INS_APPSECRET,
        'grant_type' => 'authorization_code',
        'redirect_uri' => home_url('/?type=instagram'),
        'code' => $code
    );
    $response = wp_remote_post( $url, array(
            'method' => 'POST',
            'body' => $data,
        )
    );
    $output = json_decode($response['body'],true);
    $token = $output['access_token'];
    $user = $output['user'];
    $ins_id = $user['id'];
    $name = $user['username'];
    if(!$ins_id){
        wp_redirect(home_url('/?3'.$douban_id));
        exit;
    }
    if(is_user_logged_in()){
        $this_user = wp_get_current_user();
        update_user_meta($this_user->ID ,"instagram_id",$ins_id);
        ins_ouath_redirect();
    }else{
        $user_ins = get_users(array("meta_key "=>"instagram_id","meta_value"=>$ins_id));
        if(is_wp_error($user_ins) || !count($user_ins)){
            $login_name = wp_create_nonce($ins_id);
            $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
            $userdata=array(
                'user_login' => $login_name,
                'display_name' => $name,
                'user_email' => '',
                'user_pass' => $random_password,
                'nickname' => $name
            );
            $user_id = wp_insert_user( $userdata );
            wp_signon(array("user_login"=>$login_name,"user_password"=>$random_password),false);
            update_user_meta($user_id ,"instagram_id",$ins_id);
            ins_ouath_redirect();

        }else{
            wp_set_auth_cookie($user_ins[0]->ID);
            ins_ouath_redirect();
        }
    }
}

function social_oauth_ins(){
    if (isset($_GET['code']) && isset($_GET['type']) && $_GET['type'] == 'instagram'){
        ins_oauth();
    }
}
add_action('init','social_oauth_ins');


function ins_oauth_url(){
    $url = 'https://api.instagram.com/oauth/authorize/?client_id=' . INS_APPID . '&response_type=code&redirect_uri=' . urlencode (home_url('/?type=instagram') );
    return $url;
}
