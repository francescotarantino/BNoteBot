<?php
require('config.php');
require('class-http-request.php');
require('functions.php');

$result = $dbuser->query("SELECT * FROM BNoteBot_reminder WHERE timestamp < '" . time() . "'");
while($row = $result->fetch_assoc()) {
  $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE id = '" . $row["memoid"] . "'");
  $row2 = $result->fetch_array(MYSQLI_ASSOC);
  $result = $dbuser->query("SELECT * FROM BNoteBot_user WHERE userID = '" . $row["userID"] . "'");
  $row3 = $result->fetch_array(MYSQLI_ASSOC);
  $timezone = $row3['timezone'];
  if($row3["lang"] == "en"){
    if($timezone == FALSE){
      $timezone = "Europe/London";
    }
    date_default_timezone_set($timezone);
    $timetxt = "It's " . date("H:i") . ".";
    include($langdir . "message.en.php");
  } else if($row3["lang"] == "it"){
    if($timezone == FALSE){
      $timezone = "Europe/Rome";
    }
    date_default_timezone_set($timezone);
    $timetxt = "Sono le " . date("H:i") . ".";
    include($langdir . "message.it.php");
  } else if($row3["lang"] == "pt"){
    if($timezone == FALSE){
      $timezone = "America/Brasilia";
    }
    date_default_timezone_set($timezone);
    $timetxt = "São las " . date("H:i") . ".";
    include($langdir . "message.en.php");
    include($langdir . "message.pt.php");
  } else if($row3["lang"] == "ru"){
    if($timezone == FALSE){
      $timezone = "Europe/Moscow";
    }
    date_default_timezone_set($timezone);
    $timetxt = "Установлено на " . date("H:i") . ".";
    include($langdir . "message.en.php");
    include($langdir . "message.ru.php");
  }

  $menu[] = array(array(
    "text" => $lang['delete'],
    "callback_data" => "deleterem-" . $row["memoid"]));
    sm($row["userID"], "Reminder\xF0\x9F\x95\x92\n" . $timetxt . "\n\n" . $row2["memo"], $menu, 'HTML', false, false, false, true);
    $dbuser->query("DELETE FROM BNoteBot_reminder WHERE id = '" . $row["id"] . "'");
}
?>
