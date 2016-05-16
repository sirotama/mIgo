<?php
// memo: /^@srtm\s+mecab\s+(.+)$/
require_once 'lib/Igo.php';
require_once 'lib/Requests/library/Requests.php';

Requests::register_autoloader();

$igo = new Igo("./ipadic", "UTF-8");
$text = "すもももももももものうち";
$result = $igo->parse($text);


$getCookie = Requests::get('http://misskey.tk/');

$hmskToken = $getCookie->cookies['hmsk']->value;

preg_match('/<meta name="csrf-token" content="([A-Za-z0-9\\-_]+)">/',$getCookie->body,$getCsrf);

$csrf = $getCsrf[1];

$authdata = json_decode(file_get_contents('ids.json'),1);

$header = ['Cookie'=>'hmsk='.$hmskToken,'csrf-token'=>$csrf];

$login = Requests::post('http://login.misskey.tk/',$header,$authdata);

$latestCursor = file_get_contents('cursor.txt') ?: 0;
$sleepSec = 3;
while (1){
	$body = ['since-cursor'=>$latestCursor];
	$getMention = Requests::post('http://himasaku.misskey.tk/posts/mentions/show',$header,$body);

	$mentions = json_decode($getMention->body);
	if($mentions != []){
		$latestCursor = $mentions[0]->cursor;
		file_put_contents('cursor.txt',$latestCursor);
	} else {
		$sleepSec + 1.5;
	};
	$mentions = array_reverse($mentions);
	$mentionText = "";
	foreach($mentions as $mention){
		$mentionText = $mention->text;
		$mentionID = $mention->id;
		$replybody = '@'.$mention->user->screenName.' ';
		//mecab
		if(preg_match('/^@srtm mecab (.+)$/',$mentionText,$match)){
			$igopost = $igo->parse($match[1]);
			foreach($igopost as $var){
				$replybody .= $var->feature;
				$replybody .= $var->surface;
				$replybody .= "\n";
			}
			$header = ['Cookie'=>'hmsk='.$hmskToken,'csrf-token'=>$csrf];
			$postbody = ['text'=>$replybody,'in-reply-to-post-id' => $mentionID];
			$createPost = Requests::post('http://himasaku.misskey.tk/posts/reply',$header,$postbody);

			sleep($sleepSec);
		}
		//update_name
		if(preg_match('/^@srtm update_name (.+)$/',$mentionText,$match)){
			$header = ['Cookie'=>'hmsk='.$hmskToken,'csrf-token'=>$csrf];
			$renamebody = ['name'=>$match[1]];
			$postbody = ['text'=>$replybody.$match[1].'に改名させられました。','in-reply-to-post-id' => $mentionID];
			$updateName = Requests::post('http://himasaku.misskey.tk/account/name/update',$header,$renamebody);
			$createPost = Requests::post('http://himasaku.misskey.tk/posts/reply',$header,$postbody);

			sleep($sleepSec);
		}
	}
	var_dump($mentionText);


	if($mentions != []){
		$latestCursor = $mentions[0]->cursor;
		file_put_contents('cursor.txt',$latestCursor);
	} else {
		$sleepSec + 1.5;
	};
	sleep($sleepSec);
}
