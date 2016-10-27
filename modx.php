<?php

$url = 'https://uc-sync-rogerhyam.c9users.io/index.php?' . str_replace('&amp;', '&', base64_decode($_GET['qs64']));
//$url = 'https://uc-sync-rogerhyam.c9users.io/index.php?q=calendar-test&flags=&month=5&garden_id=all';
readfile($url);
?>