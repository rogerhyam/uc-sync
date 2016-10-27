<?php

date_default_timezone_set('Europe/London');

// comment these out in deployment
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../db_config.php');

// create and initialise the database connection
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_database);    

// connect to the database
if ($mysqli->connect_error) {
  echo $mysqli->connect_error;
}

if (!$mysqli->set_charset("utf8")) {
  echo printf("Error loading character set utf8: %s\n", $mysqli->error);
}

?>