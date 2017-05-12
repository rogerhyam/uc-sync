<?php
$flag_set = @$_GET['flag_set'];

if($flag_set){
    $flag_param = '?flag_set=' . $flag_set;
}else{
    $flag_param = '?flag_set=default';
}

$qs64 = @$_GET['qs64'];
if($qs64){
    $qs_params = '&' . str_replace('&amp;', '&', base64_decode($_GET['qs64']));
}else{
    $qs_params = '';
}

$url = "http".(!empty($_SERVER['HTTPS'])?"s":"") . "://".$_SERVER['SERVER_NAME'] . '/index.php' . $flag_param . $qs_params;

//$url = 'https://uc-sync-rogerhyam.c9users.io/index.php?q=calendar-test&flags=&month=5&garden_id=all';

readfile($url);
?>