<?php
$api = "bot";
$api .="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"; //Replace with your bot's token.
global $api;

//Connection to Database.
$hostdb = "localhost";
$userdb = "user";
$passworddb = "password";
$databasedb = "my_database";
$dbuser = new mysqli($hostdb, $userdb, $passworddb, $databasedb);

//Other.
$langdir = "lang/";
?>
