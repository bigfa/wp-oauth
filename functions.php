<?php

function oauth_http($method,$header,$url,$data){
	$method = $method ? $method : 'get';
	if( $method == 'get') {
		$ch = curl_init ();
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$result = curl_exec ( $ch );
		curl_close ( $ch );
		return $result;
	
	} elseif ( $method == 'post' ) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, TRUE );
    curl_setopt ( $ch, CURLOPT_POST, TRUE );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $ret = curl_exec ( $ch );
    curl_close ( $ch );
    return $ret;
	
	}

}	