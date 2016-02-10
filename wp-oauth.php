<?php

define('WB_APPID','');//appkey
define('WB_APPSECRET','');//appsecret

function oauth_js_redirect(){
    echo '<script>
if (window.opener) {
    window.opener.location.reload();
    window.close();
} else {
    window.location.href = "'.home_url().'";
} </script>';
}

function oauth_redirect( $url = null ) {
    wp_redirect( $url );
    exit;
}


function oauth_url() {
    $oauth_type = $_GET['type'];
    switch ($oauth_type) {
        case 'sina':
            echo oauth_sina_url();
            break;
        
        default:
            # code...
            break;
    }
}

function oauth_sina_url(){
    $url = 'https://api.weibo.com/oauth2/authorize?client_id=' . WB_APPID . '&response_type=code&redirect_uri=' . urlencode (home_url('/?type=sina'));
    return $url;
}