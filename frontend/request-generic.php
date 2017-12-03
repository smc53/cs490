
<?php
header('content-type: application/json; charset=utf-8');
header("access-control-allow-origin: *");
$post = [
	'request' => $_REQUEST["request"],
	'username' => $_REQUEST["username"],	
	'payload' => $_REQUEST["payload"],
];
$string = http_build_query($post);

$ch = curl_init("http://afsaccess4.njit.edu/~mss86/CS490/middleend.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $string);

$response = curl_exec($ch);

curl_close($ch);

echo $response;
?>
