<?php
// memo: /^@srtm\s+mecab\s+(.+)$/
require_once 'lib/Igo.php';
require_once 'lib/Requests/library/Requests.php';

Requests::register_autoloader();

$igo = new Igo("./ipadic", "UTF-8");
$text = "すもももももももものうち";
$result = $igo->parse($text);

foreach ($result as $var) {
	echo $var->feature;
	echo $var->surface . "<br>";
}

$getCookie = Requests::get('http://misskey.tk/');

$hmskToken = $getCookie->cookies['hmsk']->value;

preg_match('/<meta name="csrf-token" content="([A-Za-z0-9\\-_]+)">/',$getCookie->body,$getCsrf);

$csrf = $getCsrf[1];

$authdata = json_decode(file_get_contents('ids.json'),1);

$header = ['Cookie'=>'hmsk='.$hmskToken,'csrf-token'=>$csrf];

$login = Requests::post('http://login.misskey.tk/',$header,$authdata);

var_dump($login->body);
