<?php
require('config.php');
require('class-http-request.php');
require('functions.php');

$content = file_get_contents('php://input');
$update = json_decode($content, true);

if ($update["message"]) {
    $chatID = $update["message"]["chat"]["id"];
    $userID = $update["message"]["from"]["id"];
    $msg = $update["message"]["text"];
    $username = $update["message"]["chat"]["username"];
    $name = $update["message"]["chat"]["first_name"];
} else if($update["callback_query"]["data"]){
    $chatID = $update["callback_query"]["message"]["chat"]["id"];
    $userID = $update["callback_query"]["from"]["id"];
    $msgid = $update["callback_query"]["message"]["message_id"];
} else if($update["inline_query"]["id"]){
    $msg = $update["inline_query"]["query"];
    $userID = $update["inline_query"]["from"]["id"];
    $username = $update["inline_query"]["from"]["username"];
    $name = $update["inline_query"]["from"]["first_name"];
}

$result = $dbuser->query("SELECT * FROM BNoteBot_user WHERE userID = '" . $userID . "'") or die("0");
$numrows = mysqli_num_rows($result);
if($numrows == 0 && $update["inline_query"]["id"] == false){
    $query = "INSERT INTO BNoteBot_user (userID, username, name) VALUES ('$userID', '$username', '" . $dbuser->real_escape_string($name) . "')";
    $result = $dbuser->query($query) or die("0");
} else {
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $status = $row['status'];
    $language = $lang = $row['lang'];
    $invertmemodata = $row['invertmemodata'];
    $justwritemode = $row['justwritemode'];
    $timezone = $row['timezone'];
}

switch ($lang) {
    case 'it':
        include($langdir . 'message.it.php');
        $dateformat = "d-m-Y H:i:s";
        $dateformatnosec = "d-m-Y H:i";
        if($timezone == FALSE){
            $timezone = "Europe/Rome";
        }
        date_default_timezone_set($timezone);
        break;
    case 'en':
        include($langdir . 'message.en.php');
        $dateformat = "Y-m-d H:i:s";
        $dateformatnosec = "Y-m-d H:i";
        if($timezone == FALSE){
            $timezone = "Europe/London";
        }
        date_default_timezone_set($timezone);
        break;
    case 'pt':
        include($langdir . 'message.en.php');
        include($langdir . 'message.pt.php');
        $dateformat = "d-m-Y H:i:s";
        $dateformatnosec = "d-m-Y H:i";
        if($timezone == FALSE){
            $timezone = "America/Brasilia";
        }
        date_default_timezone_set($timezone);
        break;
    case 'ru':
        include($langdir . 'message.en.php');
        include($langdir . 'message.ru.php');
        $dateformat = "d-m-Y H:i:s";
        $dateformatnosec = "d-m-Y H:i";
        if($timezone == FALSE){
            $timezone = "Europe/Moscow";
        }
        date_default_timezone_set($timezone);
        break;
}

if($update["inline_query"]["id"]){
    $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC");
    if($result->num_rows == 0){
        $json[] = array(
            'type' => 'article',
            'id' => "0",
            'title' => "Inline Memo",
            'description' => $lang["nomemo"],
            'message_text' => $lang["nomemo"],
            'parse_mode' => 'HTML'
        );
    } else {
        while($row = $result->fetch_assoc()) {
            if($msg == true && strpos($row["memo"], $msg) !== false){
                if($invertmemodata == 1){
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'title' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " 📅",
                        'description' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                } else {
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'description' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " 📅",
                        'title' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                }
            } else if($msg == false){
                if($invertmemodata == 1){
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'title' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " 📅",
                        'description' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                } else {
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'description' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " 📅",
                        'title' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                }
            }
        }
    }
    $json = json_encode($json);
    $args = array(
        'inline_query_id' => $update["inline_query"]["id"],
        'results' => $json,
        'cache_time' => 5,
        'is_personal' => true,
        'switch_pm_text' => $lang['settingsinline'],
        'switch_pm_parameter' => "settingsinline"
    );
    $r = new HttpRequest("post", "https://api.telegram.org/$api/answerInlineQuery", $args);
} else if($update["callback_query"]["data"]){
    $textalert = "";
    $alert = false;
    $data = explode("-", $update["callback_query"]["data"]);
    if($data[0] == "deleterem"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE id = '" . $data[1] . "'");
        sm($userID, $lang["deleted"]);
        acq($update["callback_query"]["id"], $textalert, $alert);
        em($userID, $update["callback_query"]["message"]["message_id"], $update["callback_query"]["message"]["text"]);
        exit();
    } elseif ($data[0] == "reply" AND $status == NULL AND $userID == $owner) {
        $dbuser->query("UPDATE BNoteBot_user SET status='reply-" . $data[1] . "' WHERE userID='$userID'");
        sm($userID, "*Send the response message:*", false, "Markdown");
    }
    $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
    for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
    if($data[0] == "next"){
        $i = $data[2] + 1;
        $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set[$i]['id'] . "' ORDER by timestamp DESC";
        if($result = $dbuser->query($query)){
        	if($result->num_rows > 0){
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                    $counter++;
                }
                $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
            }
        }
        if($set[$i]['memo'] == null){
            $text = $lang['end'];
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-0-" . $i));
        } else {
            $text = $set[$i]['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set[$i]['timestamp']) . "📅" . $reminders;
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-0-" . $i), array(
                "text" => $lang['next'],
                "callback_data" => "next-0-" . $i));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-0-" . $i), array(
                "text" => $lang['remindme'],
                "callback_data" => "reminder-0-" . $i));
            $menu[] = array(array(
                "text" => $lang['showmore'],
                "callback_data" => "showmore-0-" . $i));
        }
    } else if($data[0] == "back"){
        $i = $data[2] - 1;
        $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set[$i]['id'] . "' ORDER by timestamp DESC";
        if($result = $dbuser->query($query)){
        	if($result->num_rows > 0){
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                    $counter++;
                }
                $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
            }
        }
        $text = $set[$i]['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set[$i]['timestamp']) . "📅" . $reminders;
        if($i == 0){
            $menu[] = array(array(
                "text" => $lang['next'],
                "callback_data" => "next-0-" . $i));
        } else {
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-0-" . $i), array(
                "text" => $lang['next'],
                "callback_data" => "next-0-" . $i));
        }
        $menu[] = array(array(
            "text" => $lang['delete'],
            "callback_data" => "delete-0-" . $i), array(
            "text" => $lang['remindme'],
            "callback_data" => "reminder-0-" . $i));
        $menu[] = array(array(
            "text" => $lang['showmore'],
            "callback_data" => "showmore-0-" . $i));
    } else if($data[0] == "delete"){
        $text = $set[$data[2]]['memo'] . "\n\n" . $lang['confdelete'];
        $menu[] = array(array(
            "text" => $lang['delete'],
            "callback_data" => "confdelete-0-" . $data[2] . "-" .  $set[$data[2]]['id']), array(
            "text" => $lang['no'],
            "callback_data" => "back-0-" . ($data[2]+1)));
    } else if($data[0] == "confdelete"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE id = '" . $data[3] . "'");
        $text = $lang['deleted'];
    } else if($data[0] == "toggle"){
        if($data[2] == "invertmemodata"){
            if($invertmemodata == 0){ $toset = 1; $textalert = $lang['enabled']; } else { $toset = 0; $textalert = $lang['disabled']; }
            $dbuser->query("UPDATE BNoteBot_user SET invertmemodata = '" . $toset . "' WHERE userID = '" . $userID . "'");
            $menu[] = array(array(
                "text" => $lang['invertmemodata'] . $textalert,
                "callback_data" => "toggle-0-invertmemodata"));
            $text = $lang['settingstextinline'];
        } else if ($data[2] == "justwritemode") {
            if($justwritemode == 0){ $toset = 1; $textalert = $lang['enabled']; } else { $toset = 0; $textalert = $lang['disabled']; }
            $dbuser->query("UPDATE BNoteBot_user SET justwritemode = '" . $toset . "' WHERE userID = '" . $userID . "'");
            $menu[] = array(array(
                "text" => $textalert,
                "callback_data" => "toggle-0-justwritemode"));
            $text = $lang['justwritemodesettings'];
        }
    } else if($data[0] == "confdeleteall"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE userID = '" . $userID ."'");
        $dbuser->query("UPDATE BNoteBot_user SET notes='0' WHERE userID='$userID'");
        $text = $lang['deleted'];
    } else if($data[0] == "confdeleteallno"){
        $text = $lang['cancelled'];
    } else if ($data[0] == "reminder") {
      $menu[] = array(array(
        "text" => $lang['add'],
        "callback_data" => "remindme-0-" . $data[2]
      ));
      $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set[$data[2]]['id'] . "' ORDER by timestamp DESC";
      if($result = $dbuser->query($query)){
        if($result->num_rows > 0){
              $counter = 1;
              while ($row = $result->fetch_assoc()) {
                  $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                  $counter++;
              }
              $reminders = $lang['uhareminders'] . "\n" . $reminders . "\n";
              $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "deletereminder-0-" . $set[$data[2]]['id'] . "-" . $data[2]
              ));
          }
      }
      $menu[] = array(array(
        "text" => $lang['back'],
        "callback_data" => "back-0-" . ($data[2]+1)
      ));
      $text = $set[$data[2]]['memo'] . "\n\n" . $lang['reminderman'] . "\n\n" . $reminders;
    } else if($data[0] == "remindme"){
        $text = $set[$data[2]]['memo'];
        $dbuser->query("UPDATE BNoteBot_user SET status='addremind-" . $data[2] . "' WHERE userID='$userID'");
        $menur[] = array($lang['remindmetut']);
        $menur[] = array($lang['cancel']);
        sm($userID, $lang['remindmetxt'], $menur);
    } else if($data[0] == "deletereminder"){
      $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $data[2] . "' ORDER by timestamp DESC";
      if($result = $dbuser->query($query)){
        if($result->num_rows > 0){
          $counter = 1;
          while ($row = $result->fetch_assoc()) {
            $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
            $menur[] = array(array(
              "text" => "$counter",
              "callback_data" => "deletenreminder-0-" . $row["id"] . "-" . $data[3] . "-" . $data[2]
            ));
            $counter++;
          }
        }
      }
      $menur[] = array(array(
        "text" => $lang['deleteall'],
        "callback_data" => "deleteallreminders-0-" . $data[2]
      ));
      $menur[] = array(array(
        "text" => $lang['back'],
        "callback_data" => "reminder-0-" . $data[3]
      ));
      em($userID, $msgid, $lang['deletereminder'] . "\n\n" . $reminders, $menur, true);
    } elseif ($data[0] == "deleteallreminders") {
      $dbuser->query("DELETE FROM BNoteBot_reminder WHERE memoid = " . $data[2]);
      $menur[] = array(array(
        "text" => $lang['back'],
        "callback_data" => "reminder-0-" . $data[2]
      ));
      em($userID, $msgid, $lang['noreminder'], $menur, true);
    } elseif ($data[0] == "deletenreminder") {
      $dbuser->query("DELETE FROM BNoteBot_reminder WHERE id = " . $data[2]);
      $textalert = $lang['deletedreminder'];
      $alert = true;
      $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $data[4] . "' ORDER by timestamp DESC";
      if($result = $dbuser->query($query)){
        if($result->num_rows > 0){
          $counter = 1;
          while ($row = $result->fetch_assoc()) {
            $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
            $menur[] = array(array(
              "text" => "$counter",
              "callback_data" => "deletenreminder-0-" . $row["id"]
            ));
            $counter++;
          }
          $menur[] = array(array(
            "text" => $lang['back'],
            "callback_data" => "reminder-0-" . $data[3]
          ));
          em($userID, $msgid, $lang['deletereminder'] . "\n\n" . $reminders, $menur, true);
        } else {
          $menur[] = array(array(
            "text" => $lang['back'],
            "callback_data" => "reminder-0-" . $data[3]
          ));
          em($userID, $msgid, $lang['noreminder'], $menur, true);
        }
      }
    } else if($data[0] == "retrodate"){
        $text = $set[$data[2]]['memo'];
        $dbuser->query("UPDATE BNoteBot_user SET status='retrodate-" . $data[2] . "' WHERE userID='$userID'");
        $menur[] = array($lang['remindmetut']);
        $menur[] = array($lang['cancel']);
        sm($userID, $lang['retrodatetxt'], $menur);
    } else if($data[0] == "edit"){
        $text = $set[$data[2]]['memo'];
        $dbuser->query("UPDATE BNoteBot_user SET status='edit-" . $data[2] . "' WHERE userID='$userID'");
        $menur[] = array($lang['cancel']);
        sm($userID, $lang['edittxt'], $menur);
    } else if ($data[0] == "showmore") {
        if($data[2] == 0){
            $menu[] = array(array(
                "text" => $lang['next'],
                "callback_data" => "next-0-" . $data[2]));
        } else {
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-0-" . $data[2]), array(
                "text" => $lang['next'],
                "callback_data" => "next-0-" . $data[2]));
        }
        $menu[] = array(array(
            "text" => $lang['delete'],
            "callback_data" => "delete-0-" . $data[2]), array(
            "text" => $lang['remindme'],
            "callback_data" => "reminder-0-" . $data[2]));
        $menu[] = array(array(
            "text" => $lang['edit'],
            "callback_data" => "edit-0-" . $data[2]), array(
            "text" => $lang['date'],
            "callback_data" => "retrodate-0-" . $data[2]));
        emk($chatID, $msgid, $menu);
    }
    em($userID, $msgid, $text, $menu, true);
    acq($update["callback_query"]["id"], $textalert, $alert);
}

$sexploded = explode("-", $status);

if($status == "select"){
    if($msg == "English 🇬🇧"){
        include($langdir . 'message.en.php');
        menu($lang['welcome']);
        $dbuser->query("UPDATE BNoteBot_user SET lang='en' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Italiano 🇮🇹"){
        include($langdir . 'message.it.php');
        menu($lang['welcome']);
        $dbuser->query("UPDATE BNoteBot_user SET lang='it' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Português 🇧🇷"){
        include($langdir . 'message.pt.php');
        menu($lang['welcome']);
        $dbuser->query("UPDATE BNoteBot_user SET lang='pt' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Russian 🇷🇺"){
        include($langdir . 'message.ru.php');
        menu($lang['welcome']);
        $dbuser->query("UPDATE BNoteBot_user SET lang='ru' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else {
        langmenu($chatID);
    }
} else if($status == "addmemo"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['cancelled']);
    } else {
        if($msg == ""){
            menu($lang['onlytxt']);
        } else {
            $dbuser->query("INSERT INTO BNoteBot_memo (userID, memo, timestamp) VALUES ('$userID', '" . $dbuser->real_escape_string($msg) . "', '" . time() . "')");
            menu($lang['saved']);
        }
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    }
} else if($status == "timezone"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['cancelled']);
    } else if($msg == $lang['defaulttimezone']) {
        $dbuser->query("UPDATE BNoteBot_user SET timezone='' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['savedt']);
    } else {
        $timezone = date_default_timezone_set($msg);
        if($timezone == TRUE){
            $dbuser->query("UPDATE BNoteBot_user SET timezone='$msg' WHERE userID='$userID'");
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
            menu($lang['savedt']);
        } else {
            sm($chatID, $lang['invalidtimezone']);
        }
    }
} else if($status == "feedback"){
    if($msg == $lang['cancel']){
        menu($lang['cancelled']);
    } else {
        $menu[] = array(array(
            "text" => "Reply",
            "callback_data" => "reply-" . $userID));
        $feedback = "New feedback received!\n\nMessage: $msg\nName: $name\nUsername: @$username\nUserID: $userID\nLanguage: ".$language."\nDate: " . date($dateformat, time());
        sm($owner, $feedback, $menu, false, false, false, false, true);
        $var=fopen("feedback.txt","a+");
        fwrite($var, "\n\n" . $feedback);
        fclose($var);
        menu($lang['thanksfeedback']);
    }
    $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
} else if($sexploded[0] == "addremind"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['cancelled']);
    } else if($msg == $lang['remindmetut']){
        sm($chatID, $lang['remindmeformat']);
    } else {
        $timemsg = strtotime(str_replace(".", "-", toendate($msg)));
        if($timemsg == true){
            $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
            for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
            $dbuser->query("INSERT INTO BNoteBot_reminder (userID, memoid, timestamp) VALUES ('$userID', '".$set[$sexploded[1]]['id']."', '$timemsg')");
            menu($lang['remindersaved'] . "\n" . date($dateformatnosec, $timemsg));
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        } else {
            sm($chatID, $lang['invaliddate']);
        }
    }
} else if($sexploded[0] == "retrodate") {
    if ($msg == $lang['cancel']) {
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['cancelled']);
    } else if ($msg == $lang['remindmetut']) {
        sm($chatID, $lang['remindmeformat']);
    } else {
        $timemsg = strtotime(str_replace(".", "-", toendate($msg)));
        if ($timemsg == true) {
            $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
            for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row) ;
            $dbuser->query("UPDATE BNoteBot_memo SET timestamp='" . $timemsg . "' WHERE id='" . $set[$sexploded[1]]['id'] . "'");
            menu($lang['datesaved'] . "\n" . date($dateformatnosec, $timemsg));
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        } else {
            sm($chatID, $lang['invaliddate']);
        }
    }
} else if($sexploded[0] == "edit"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($lang['cancelled']);
    } else {
        $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
        for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
        $dbuser->query("UPDATE BNoteBot_memo SET memo='$msg' WHERE id='" . $set[$sexploded[1]]['id'] . "'");
        //$dbuser->query("INSERT INTO BNoteBot_reminder (userID, memoid, timestamp) VALUES ('$userID', '".$set[$sexploded[1]]['id']."', '$timemsg')");
        menu($lang['saved']);
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    }
} else if($sexploded[0] == "reply" AND $userID == $owner){
    $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    sm($userID, "*Sent.*", false, "Markdown");
    sm($sexploded[1], $msg);
} else {
    if($msg == $lang['addmemo']){
        $dbuser->query("UPDATE BNoteBot_user SET status='addmemo' WHERE userID='$userID'");
        $menu[] = array($lang['cancel']);
        sm($chatID, $lang['addmemotext'], $menu, 'HTML', false, false, true);
    } else if($msg == $lang['settings']){
        setmenu($lang['settings']);
    } else if($msg == $lang['feedback']){
        $dbuser->query("UPDATE BNoteBot_user SET status='feedback' WHERE userID='$userID'");
        $menu[] = array($lang['cancel']);
        sm($chatID, $lang['feedbacktext'], $menu, 'HTML', false, false, true);
    } else if($msg == $lang['savedmemo']){
        $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
        if($result->num_rows > 0){
            for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
            $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set['0']['id'] . "' ORDER by timestamp DESC";
            if($result = $dbuser->query($query)){
                if($result->num_rows > 0){
                    $counter = 1;
                    while ($row = $result->fetch_assoc()) {
                        $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                        $counter++;
                    }
                    $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
                }
            }
            $text = $set['0']['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set['0']['timestamp']) . "📅" . $reminders;
            $menu[] = array(array(
                "text" => $lang['next'],
                "callback_data" => "next-0-0"));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-0-0"), array(
                "text" => $lang['remindme'],
                "callback_data" => "reminder-0-0"));
            $menu[] = array(array(
                "text" => $lang['showmore'],
                "callback_data" => "showmore-0-0"));
            sm($chatID, $text, $menu, false, false, false, false, true);
        } else {
            sm($chatID, $lang['nomemo']);
        }
    } else if($msg == $lang['info']){
        $menu[] = array(array(
            "text" => $lang['subchannel'],
            "url" => "https://telegram.me/joinchat/AeDFuD2cuxFaLAyV6aly5g"));
        sm($chatID, $lang['infomsg'], $menu, 'HTML', false, false, false, true);
    } else if($msg == $lang['github']){
      $menu[] = array(array(
          "text" => $lang['github'],
          "url" => "https://github.com/franci22/BNoteBot"));
      sm($chatID, $lang['opensource'], $menu, 'HTML', false, false, false, true);
    } else if($msg == $lang['supportme']){
        $menu[] = array(array(
            "text" => $lang['vote'],
            "url" => "https://telegram.me/storebot?start=bnotebot"), array(
            "text" => "PayPal 💳",
            "url" => "https://paypal.me/franci22"), array(
            "text" => "Bitcoin 💰",
            "url" => "https://paste.ubuntu.com/24299810/"
            ));
        sm($chatID, $lang['supportmetext'], $menu, 'HTML', false, false, false, true);
    } else if($msg == $lang['inlinemode']){
        inlinemodeset($invertmemodata);
    } else if($msg == $lang['deleteallnote']){
        $menu[] = array(array(
            "text" => $lang['delete'],
            "callback_data" => "confdeleteall"), array(
            "text" => $lang['no'],
            "callback_data" => "confdeleteallno"));
        sm($chatID, $lang['askdeleteallnote'], $menu, 'HTML', false, false, false, true);
    } else if($msg == $lang['settimezone']){
        $dbuser->query("UPDATE BNoteBot_user SET status='timezone' WHERE userID='$userID'");
        $menu[] = array($lang['defaulttimezone']);
        $menu[] = array($lang['cancel']);
        sm($chatID, $lang['settimezonetxt'] . "\n\n" . $lang['currenttimezone'] . $timezone, $menu);
    } else if($msg == $lang['justwritemode']){
        if($justwritemode){ $justwritemodetxt = $lang['enabled']; } else { $justwritemodetxt = $lang['disabled']; }
        $menu[] = array(array(
            "text" => $justwritemodetxt,
            "callback_data" => "toggle-0-justwritemode"));
        sm($chatID, $lang['justwritemodesettings'], $menu, 'HTML', false, false, false, true);
    } else if($msg == $lang['cancel']){
        menu($lang['cancelled']);
    } else {
        switch ($msg){
            case '/start':
                langmenu($chatID);
                $dbuser->query("UPDATE BNoteBot_user SET status='select' WHERE userID='$userID'");
                break;
            case '/start settingsinline':
                inlinemodeset($invertmemodata);
                break;
            default:
                if ($update["message"]["text"]) {
                  if ($justwritemode) {
                    $dbuser->query("INSERT INTO BNoteBot_memo (userID, memo, timestamp) VALUES ('$userID', '" . $dbuser->real_escape_string($msg) . "', '" . time() . "')");
                    $menu[] = array(array(
                        "text" => $lang['delete'],
                        "callback_data" => "confdelete-0-0-" .$dbuser->insert_id));
                    sm($chatID, $lang['saved'] . "\n\n" . $msg, $menu, 'HTML', false, false, false, true);
                  } else {
                    sm($chatID, $lang['messagenovalid']);
                  }
                }
                break;
        }
    }
}

function langmenu($chatID){
    $text = "🇬🇧 - Welcome! Select a language:
🇮🇹 - Benvenuto! Seleziona una lingua:
🇧🇷 - Bem-vindo! Escolha um idioma:
🇷🇺 - Добро пожаловать! Выберите язык:";
    $menu[] = array("English 🇬🇧");
    $menu[] = array("Italiano 🇮🇹");
    $menu[] = array("Português 🇧🇷");
    $menu[] = array("Russian 🇷🇺");
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function menu($text){
    global $lang;
    global $chatID;
    $menu[] = array($lang['addmemo']);
    $menu[] = array($lang['savedmemo']);
    $menu[] = array($lang['info'], $lang['supportme']);
    $menu[] = array($lang['feedback']);
    $menu[] = array($lang['settings'], $lang['github']);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function setmenu($text){
    global $lang;
    global $chatID;
    $menu[] = array($lang['inlinemode']);
    $menu[] = array($lang['justwritemode']);
    $menu[] = array($lang['deleteallnote']);
    $menu[] = array($lang['settimezone']);
    $menu[] = array($lang['cancel']);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function inlinemodeset($invertmemodata){
    global $lang;
    global $chatID;
    if($invertmemodata == 1){ $invertmemodatatxt = $lang['enabled']; } else { $invertmemodatatxt = $lang['disabled']; }
    $menu[] = array(array(
        "text" => $lang['invertmemodata'] . $invertmemodatatxt,
        "callback_data" => "toggle-0-invertmemodata"));
    sm($chatID, $lang['settingstextinline'], $menu, 'HTML', false, false, false, true);
}

function toendate($date){
    $date = str_ireplace("oggi","today", $date);
    $date = str_ireplace("ieri","yesterday", $date);
    $date = str_ireplace("domani","tomorrow", $date);
    $date = str_ireplace("alle","", $date);
    $date = str_ireplace("at","", $date);
    return $date;
}


?>
