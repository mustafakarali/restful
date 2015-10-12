<?php
require_once("request.class.php");
require_once("bsAPI.class.php");
require_once("db_mysql.php");
require_once("db_settings.php");
$db = new SQL();
$db->DBHost = "localhost";
$db->DBUser = $dbUser;
$db->DBPassword = $dbPassword;
$db->DBDatabase = $dbDatabase;

define("CONTROLLER_DIR", $_SERVER["DOCUMENT_ROOT"]."/controllers/");

$r = new Request();

$a = new bsAPI($r, $db);

$a->APIprocess();





















