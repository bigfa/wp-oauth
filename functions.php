<?php
/*

公共跳转函数

*/
function fa_auth_redirect(){
    echo '<script>if( window.opener ) {
            window.opener.location.reload();
            window.close();
          } else {
             window.location.href = "'.home_url().'";
          }</script>';
}

/*

Gravatar 头像 Hook， 包含微信、微博、QQ头像。

*/
function fa_avatar_hook( $avatar, $id_or_email, $size, $default, $alt ) {
    $user = false;

    if ( is_numeric( $id_or_email ) ) {

        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );

    } elseif ( is_object( $id_or_email ) ) {

        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }

    } else {
        $user = get_user_by( 'email', $id_or_email );   
    }

    if ( $user && is_object( $user ) ) {
            if( get_user_meta($user->data->ID,'weixin_avatar',true) ){
            $avatar = get_user_meta($user->data->ID,'weixin_avatar',true);
            $avatar =  str_replace('http','https',$avatar);
            $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        } else if( get_user_meta($user->data->ID,'sina_avatar',true) ){
            $avatar = get_user_meta($user->data->ID,'sina_avatar',true);
            $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }//根据你的存储头像的key来写
    }

    return $avatar;
}
add_filter('get_avatar', 'fa_avatar_hook' , 1 , 5);

