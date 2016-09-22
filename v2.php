<?php

// parameters
$authorization_endpoint = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
$token_endpoint = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
$client_id = '<client_id>';
$client_secret = '<client_secret>';
$redirect_uri = 'https://example.com/v2.php';
$response_type = 'code';
$state =  'foo'; // 手抜き
$nonce = 'bar'; // 手抜き

// codeの取得(codeがパラメータについてなければ初回アクセスとしてみなしています。手抜きです)
$req_code = $_GET['code'];
if(!$req_code){
	// 初回アクセスなのでログインプロセス開始
	// GETパラメータ関係
	$query = http_build_query(array(
		'client_id'=>$client_id,
		'response_type'=>$response_type,
		'redirect_uri'=> $redirect_uri,
		'scope'=>'openid profile',
		'state'=>$state,
		'nonce'=>$nonce
	));
	// リクエスト
	header('Location: ' . $authorization_endpoint . '&' . $query );
	exit();
}

// POSTデータの作成
$postdata = array(
	'grant_type'=>'authorization_code',
	'client_id'=>$client_id,
	'code'=>$req_code,
	'client_secret'=>$client_secret,
	'redirect_uri'=>$redirect_uri
);

// TokenエンドポイントへPOST
$ch = curl_init($token_endpoint);
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query($postdata));
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
$response = json_decode(curl_exec($ch));
curl_close($ch);

// id_tokenの取り出しとdecode
$id_token = explode('.', $response->id_token);
$payload = base64_decode(str_pad(strtr($id_token[1], '-_', '+/'), strlen($id_token[1]) % 4, '=', STR_PAD_RIGHT));
$payload_json = json_decode($payload, true);

// 整形と表示
print<<<EOF
	<html>
	<head>
	<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
	<title>Obtained claims</title>
	</head>
	<body>
	<!--
	id_token : $response->id_token<br>
	-->
	<table border=1>
	<tr><th>Claim</th><th>Value</th></tr>
EOF;
	foreach($payload_json as $key => $value){
		print('<tr><td>'.$key.'</td><td>'.$value.'</td></tr>');
	}
print<<<EOF
	</table>
	</body>
	</html>
EOF;

?>
