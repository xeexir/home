<?php
header("Access-Control-Allow-Origin: *");

//--------------Admin credentials----------------------------------------------------
$adminName="admin";
$adminPass="admin";

//SQLite sample
$dbAlias[0]="db";
$dbNames[0]="main.db";

//MySQL sample
//$dbAlias[1]="db2";
//$dbNames[1]="realDataBaseName";
//$dbServerNames[1]="localhost";
//$dbUserNames[1]="MySQLUserName";
//dbPasswords[1]="MySQLUserPassword";

//-------------SQL queries section------------------------------------------------------------------

$sqlAlias[0]="insert";
$sqlQuerys[0]="INSERT INTO notes (title,content) VALUES (?,?)";
$sqlMaxUserLevel[0]=-1;

?>