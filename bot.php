<?php
require('config.php');
require('class-http-request.php');
require('functions.php');

$content = file_get_contents('php://input');
$update = json_decode($content, true);

$chatID = $update["message"]["chat"]["id"];
$userID = $update["message"]["from"]["id"];
$msg = $update["message"]["text"];
$username = $update["message"]["chat"]["username"];
$name = $update["message"]["chat"]["first_name"];

global $msg;

$inline = $update["inline_query"]["id"];

$callback = $update["callback_query"]["data"];

if($callback){
    $chatID = $update["callback_query"]["chat"]["id"];
    $userID = $update["callback_query"]["from"]["id"];
} else if($inline){
    $msg = $update["inline_query"]["query"];
    $userID = $update["inline_query"]["from"]["id"];
    $username = $update["inline_query"]["from"]["username"];
    $name = $update["inline_query"]["from"]["first_name"];
}

$query = "SELECT * FROM BNoteBot_user WHERE userID = '" . $userID . "'";
$result = $dbuser->query($query) or die("0");
$numrows = mysqli_num_rows($result);
if($numrows == 0 && $inline == false){
    $query = "INSERT INTO BNoteBot_user (userID, username, name) VALUES ('$userID', '$username', '" . $dbuser->real_escape_string($name) . "')";
    $result = $dbuser->query($query) or die("0");
} else {
    $row = $result->fetch_array(MYSQLI_ASSOC);
    $status = $row['status'];
    $language = $lang = $row['lang'];
    $notes = $row['notes'];
    $invertmemodata = $row['invertmemodata'];
    $timezone = $row['timezone'];
}

if($lang == "it"){
    include($langdir . 'message.it.php');
    $dateformat = "d-m-Y H:i:s";
    $dateformatnosec = "d-m-Y H:i";
    if($timezone == FALSE){
        $timezone = "Europe/Rome";
    }
    date_default_timezone_set($timezone);
} else if($lang == "en"){
    include($langdir . 'message.en.php');
    $dateformat = "Y-m-d H:i:s";
    $dateformatnosec = "Y-m-d H:i";
    if($timezone == FALSE){
        $timezone = "Europe/London";
    }
    date_default_timezone_set($timezone);
} else if($lang == "pt"){
    include($langdir . 'message.en.php');
    include($langdir . 'message.pt.php');
    $dateformat = "d-m-Y H:i:s";
    $dateformatnosec = "d-m-Y H:i";
    if($timezone == FALSE){
        $timezone = "America/Brasilia";
    }
    date_default_timezone_set($timezone);
}

if($inline){
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
                        'title' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " \xF0\x9F\x93\x85",
                        'description' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                } else {
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'description' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " \xF0\x9F\x93\x85",
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
                        'title' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " \xF0\x9F\x93\x85",
                        'description' => $row["memo"],
                        'message_text' => $row["memo"],
                        'parse_mode' => 'HTML'
                    );
                } else {
                    $json[] = array(
                        'type' => 'article',
                        'id' => $row["id"],
                        'description' => $lang['datememo'] . date($dateformat, $row['timestamp']) . " \xF0\x9F\x93\x85",
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
        'inline_query_id' => $inline,
        'results' => $json,
        'cache_time' => 5,
        'is_personal' => true,
        'switch_pm_text' => $lang['settingsinline'],
        'switch_pm_parameter' => "settingsinline"
    );
    $r = new HttpRequest("post", "https://api.telegram.org/$api/answerInlineQuery", $args);
} else if($callback){
    $textalert = "";
    $alert = false;
    $data = explode("-", $callback);
    if($data[0] == "deleterem"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE id = '" . $data[1] . "'");
        $dbuser->query("UPDATE BNoteBot_user SET notes='" . ($notes - 1) . "' WHERE userID='$userID'");
        sm($userID, $lang["deleted"]);
        acq($update["callback_query"]["id"], $textalert, $alert);
        em($userID, $update["callback_query"]["message"]["message_id"], $update["callback_query"]["message"]["text"]);
        exit();
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
                    $counter = $counter + 1;
                }
                $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
            }
        }
        if($set[$i]['memo'] == null){
            $text = $lang['end'];
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-" . $data[1] . "-" . $i));
        } else {
            $text = $set[$i]['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set[$i]['timestamp']) . "\xF0\x9F\x93\x85" . $reminders;
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-" . $data[1] . "-" . $i), array(
                "text" => $lang['next'],
                "callback_data" => "next-" . $data[1] . "-" . $i));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-" . $data[1] . "-" . $i), array(
                "text" => $lang['edit'],
                "callback_data" => "edit-" . $json['result']['message_id'] . "-0"));
            $menu[] = array(array(
                "text" => $lang['remindme'],
                "callback_data" => "remindme-" . $data[1] . "-" . $i), array(
                "text" => $lang['date'],
                "callback_data" => "retrodate-" . $data[1] . "-" . $i));
        }
    } else if($data[0] == "back"){
        $i = $data[2] - 1;
        $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set[$i]['id'] . "' ORDER by timestamp DESC";
        if($result = $dbuser->query($query)){
        	if($result->num_rows > 0){
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                    $counter = $counter + 1;
                }
                $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
            }
        }
        $text = $set[$i]['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set[$i]['timestamp']) . "\xF0\x9F\x93\x85" . $reminders;
        if($i == 0){
            $menu[] = array(array(
                "text" => $lang['next'],
                "callback_data" => "next-" . $data[1] . "-" . $i));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-" . $data[1] . "-" . $i), array(
                "text" => $lang['edit'],
                "callback_data" => "edit-" . $json['result']['message_id'] . "-0"));
            $menu[] = array(array(
                "text" => $lang['remindme'],
                "callback_data" => "remindme-" . $data[1] . "-" . $i), array(
                "text" => $lang['date'],
                "callback_data" => "retrodate-" . $data[1] . "-" . $i));
        } else {
            $menu[] = array(array(
                "text" => $lang['back'],
                "callback_data" => "back-" . $data[1] . "-" . $i), array(
                "text" => $lang['next'],
                "callback_data" => "next-" . $data[1] . "-" . $i));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-" . $data[1] . "-" . $i), array(
                "text" => $lang['edit'],
                "callback_data" => "edit-" . $json['result']['message_id'] . "-0"));
            $menu[] = array(array(
                "text" => $lang['remindme'],
                "callback_data" => "remindme-" . $data[1] . "-" . $i), array(
                "text" => $lang['date'],
                "callback_data" => "retrodate-" . $data[1] . "-" . $i));
        }
    } else if($data[0] == "delete"){
        $text = $set[$data[2]]['memo'] . "\n\n" . $lang['confdelete'];
        $menu[] = array(array(
            "text" => $lang['delete'],
            "callback_data" => "confdelete-" . $data[1] . "-" . $data[2] . "-" .  $set[$data[2]]['id']), array(
            "text" => $lang['no'],
            "callback_data" => "next-" . $data[1] . "-" . ($data[2]-1)));
    } else if($data[0] == "confdelete"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE id = '" . $data[3] . "'");
        $dbuser->query("UPDATE BNoteBot_user SET notes='" . ($notes - 1) . "' WHERE userID='$userID'");
        $text = $lang['deleted'];
    } else if($data[0] == "toggle"){
        if($data[2] == "invertmemodata"){
            if($invertmemodata == 0){ $toset = 1; $textalert = $lang['enabled']; } else { $toset = 0; $textalert = $lang['disabled']; }
            $dbuser->query("UPDATE BNoteBot_user SET invertmemodata = '" . $toset . "' WHERE userID = '" . $userID . "'");
            $menu[] = array(array(
                "text" => $lang['invertmemodata'] . $textalert,
                "callback_data" => "toggle-" . $data[1] . "-invertmemodata"));
            $text = $lang['settingstextinline'];
        }
    } else if($data[0] == "confdeleteall"){
        $dbuser->query("DELETE FROM BNoteBot_memo WHERE userID = '" . $userID ."'");
        $dbuser->query("UPDATE BNoteBot_user SET notes='0' WHERE userID='$userID'");
        $text = $lang['deleted'];
    } else if($data[0] == "confdeleteallno"){
        $text = $lang['cancelled'];
    } else if($data[0] == "remindme"){
        $text = $set[$data[2]]['memo'];
        $dbuser->query("UPDATE BNoteBot_user SET status='addremind-" . $data[2] . "' WHERE userID='$userID'");
        $menur[] = array($lang['remindmetut']);
        $menur[] = array($lang['cancel']);
        $query = "SELECT * FROM BNoteBot_reminder WHERE memoid = '" . $set[$data[2]]['id'] . "' ORDER by timestamp DESC";
        if($result = $dbuser->query($query)){
        	if($result->num_rows > 0){
                $counter = 1;
                while ($row = $result->fetch_assoc()) {
                    $reminders = $reminders . $counter . ". " . date($dateformatnosec, $row["timestamp"]) . "\n";
                    $counter = $counter + 1;
                }
                $reminders = "\n\n" . $lang['uhareminders'] . "\n" . $reminders . "\n";
            }
        }
        sm($userID, $reminders . $lang['remindmetxt'], $menur);
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
    }
    em($userID, $data[1], $text, $menu, true);
    acq($update["callback_query"]["id"], $textalert, $alert);
}

$sexploded = explode("-", $status);

if($status == "select"){
    if($msg == "English \xF0\x9F\x87\xAC\xF0\x9F\x87\xA7"){
        include($langdir . 'message.en.php');
        menu($chatID, $lang['welcome'], $lang);
        $dbuser->query("UPDATE BNoteBot_user SET lang='en' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Italiano \xF0\x9F\x87\xAE\xF0\x9F\x87\xB9"){
        include($langdir . 'message.it.php');
        menu($chatID, $lang['welcome'], $lang);
        $dbuser->query("UPDATE BNoteBot_user SET lang='it' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Português \xF0\x9F\x87\xA7\xF0\x9F\x87\xB7"){
        include($langdir . 'message.pt.php');
        menu($chatID, $lang['welcome'], $lang);
        $dbuser->query("UPDATE BNoteBot_user SET lang='pt' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else if($msg == "Russian \xF0\x9F\x87\xB7\xF0\x9F\x87\xBA"){
        include('message.ru.php');
        menu($chatID, $lang['welcome'], $lang);
        $dbuser->query("UPDATE BNoteBot_user SET lang='ru' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    } else {
        $msg == "/start";
    }
} else if($status == "addmemo"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else {
        if($msg == ""){
            menu($chatID, $lang['onlytxt'], $lang);
        } else {
            $dbuser->query("INSERT INTO BNoteBot_memo (userID, memo, timestamp) VALUES ('$userID', '" . $dbuser->real_escape_string($msg) . "', '" . time() . "')");
            $dbuser->query("UPDATE BNoteBot_user SET notes='" . ++$notes . "' WHERE userID='$userID'");
            menu($chatID, $lang['saved'], $lang);
        }
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    }
} else if($status == "settings"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else {
        if($msg == $lang['inlinemode']){
            inlinemodeset($chatID, $lang, $invertmemodata);
        } else if($msg == $lang['deleteallnote']){
            $json = sm($chatID, $lang['askdeleteallnote']);
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "confdeleteall-" .  $json['result']['message_id']), array(
                "text" => $lang['no'],
                "callback_data" => "confdeleteallno-" . $json['result']['message_id']));
            em($chatID, $json['result']['message_id'], $lang['askdeleteallnote'], $menu, true);
        } else if($msg == $lang['settimezone']){
            $dbuser->query("UPDATE BNoteBot_user SET status='timezone' WHERE userID='$userID'");
            $menu[] = array($lang['defaulttimezone']);
            $menu[] = array($lang['cancel']);
            sm($chatID, $lang['settimezonetxt'] . "\n\n" . $lang['currenttimezone'] . $timezone, $menu);
        }
    }
} else if($status == "timezone"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else if($msg == $lang['defaulttimezone']) {
        $dbuser->query("UPDATE BNoteBot_user SET timezone='' WHERE userID='$userID'");
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['savedt'], $lang);
    } else {
        $timezone = date_default_timezone_set($msg);
        if($timezone == TRUE){
            $dbuser->query("UPDATE BNoteBot_user SET timezone='$msg' WHERE userID='$userID'");
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
            menu($chatID, $lang['savedt'], $lang);
        } else {
            sm($chatID, $lang['invalidtimezone']);
        }
    }
} else if($status == "feedback"){
    if($msg == $lang['cancel']){
        menu($chatID, $lang['cancelled'], $lang);
    } else {
        $feedback = "Messaggio: $msg\nNome: $name\nUsername: @$username\nUserID: $userID\nLingua: ".$language."\nData: " . date($dateformat, time());
        sm("31507896", $feedback);
        $var=fopen("feedback.txt","a+");
        fwrite($var, "\n\n" . $feedback);
        fclose($var);
        menu($chatID, $lang['thanksfeedback'], $lang);
    }
    $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
} else if($sexploded[0] == "addremind"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else if($msg == $lang['remindmetut']){
        sm($chatID, $lang['remindmeformat']);
    } else {
        $timemsg = strtotime(str_replace(".", "-", toendate($msg)));
        if($timemsg == true){
            $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
            for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
            $dbuser->query("INSERT INTO BNoteBot_reminder (userID, memoid, timestamp) VALUES ('$userID', '".$set[$sexploded[1]]['id']."', '$timemsg')");
            menu($chatID, $lang['remindersaved'] . "\n" . date($dateformatnosec, $timemsg), $lang);
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        } else {
            sm($chatID, $lang['invaliddate']);
        }
    }
} else if($sexploded[0] == "retrodate") {
    if ($msg == $lang['cancel']) {
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else if ($msg == $lang['remindmetut']) {
        sm($chatID, $lang['remindmeformat']);
    } else {
        $timemsg = strtotime(str_replace(".", "-", toendate($msg)));
        if ($timemsg == true) {
            $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
            for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row) ;
            $dbuser->query("UPDATE BNoteBot_memo SET timestamp='" . $timemsg . "' WHERE id='" . $set[$sexploded[1]]['id'] . "'");
            menu($chatID, $lang['datesaved'] . "\n" . date($dateformatnosec, $timemsg), $lang);
            $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        } else {
            sm($chatID, $lang['invaliddate']);
        }
    }
} else if($sexploded[0] == "edit"){
    if($msg == $lang['cancel']){
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
        menu($chatID, $lang['cancelled'], $lang);
    } else {
        $result = $dbuser->query("SELECT * FROM BNoteBot_memo WHERE userID = '" . $userID . "' ORDER BY timestamp DESC") or die("0");
        for ($set = array(); $row = $result->fetch_assoc(); $set[] = $row);
        $dbuser->query("UPDATE BNoteBot_memo SET memo='$msg' WHERE id='" . $set[$sexploded[1]]['id'] . "'");
        //$dbuser->query("INSERT INTO BNoteBot_reminder (userID, memoid, timestamp) VALUES ('$userID', '".$set[$sexploded[1]]['id']."', '$timemsg')");
        menu($chatID, $lang['saved'], $lang);
        $dbuser->query("UPDATE BNoteBot_user SET status='' WHERE userID='$userID'");
    }
} else {
    if($msg == $lang['addmemo']){
        $dbuser->query("UPDATE BNoteBot_user SET status='addmemo' WHERE userID='$userID'");
        $menu[] = array($lang['cancel']);
        sm($chatID, $lang['addmemotext'], $menu, 'HTML', false, false, true);
    } else if($msg == $lang['settings']){
        $dbuser->query("UPDATE BNoteBot_user SET status='settings' WHERE userID='$userID'");
        setmenu($chatID, $lang['settings'], $lang);
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
                        $counter = $counter + 1;
                    }
                    $reminders = "\n\n" . $lang['reminders'] . "\n" . $reminders;
                }
            }
            $text = $set['0']['memo'] . "\n\n" . $lang['datememo'] . date($dateformat, $set['0']['timestamp']) . "\xF0\x9F\x93\x85" . $reminders;
            $json = sm($chatID, $text);
            $menu[] = array(array(
                "text" => $lang['next'],
                "callback_data" => "next-" . $json['result']['message_id'] . "-0"));
            $menu[] = array(array(
                "text" => $lang['delete'],
                "callback_data" => "delete-" . $json['result']['message_id'] . "-0"), array(
                "text" => $lang['edit'],
                "callback_data" => "edit-" . $json['result']['message_id'] . "-0"));
            $menu[] = array(array(
                "text" => $lang['remindme'],
                "callback_data" => "remindme-" . $json['result']['message_id'] . "-0"), array(
                "text" => $lang['date'],
                "callback_data" => "retrodate-" . $json['result']['message_id'] . "-0"));
            em($chatID, $json['result']['message_id'], $text, $menu, true);
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
            "text" => "PayPal \xF0\x9F\x92\xB3",
            "url" => "https://paypal.me/franci22"), array(
            "text" => "Bitcoin \xF0\x9F\x92\xB0",
            "url" => "https://paste.ubuntu.com/24299810/"
            ));
        sm($chatID, $lang['supportmetext'], $menu, 'HTML', false, false, false, true);
    } else {
        switch ($msg){
            case '/start':
                $text = "\xF0\x9F\x87\xAC\xF0\x9F\x87\xA7 - Welcome! Select a language:
\xF0\x9F\x87\xAE\xF0\x9F\x87\xB9 - Benvenuto! Seleziona una lingua:
\xF0\x9F\x87\xA7\xF0\x9F\x87\xB7 - Bem-vindo! Escolha um idioma:
\xF0\x9F\x87\xB7\xF0\x9F\x87\xBA - Добро пожаловать! Выберите язык:";
                langmenu($chatID, $text);
                $dbuser->query("UPDATE BNoteBot_user SET status='select' WHERE userID='$userID'");
                break;
            case '/start settingsinline':
                inlinemodeset($chatID, $lang, $invertmemodata);
                break;
            default:
                sm($chatID, $lang['messagenovalid']);
                break;
        }
    }
}

function langmenu($chatID, $text){
    $menu[] = array("English \xF0\x9F\x87\xAC\xF0\x9F\x87\xA7");
    $menu[] = array("Italiano \xF0\x9F\x87\xAE\xF0\x9F\x87\xB9");
    $menu[] = array("Português \xF0\x9F\x87\xA7\xF0\x9F\x87\xB7");
    $menu[] = array("Russian \xF0\x9F\x87\xB7\xF0\x9F\x87\xBA");
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function menu($chatID, $text, $lang){
    include($langdir . 'message.' . $lang . '.php');
    $menu[] = array($lang['addmemo']);
    $menu[] = array($lang['savedmemo']);
    $menu[] = array($lang['info'], $lang['supportme']);
    $menu[] = array($lang['feedback']);
    $menu[] = array($lang['settings'], $lang['github']);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function setmenu($chatID, $text, $lang){
    include($langdir . 'message.' . $lang . '.php');
    $menu[] = array($lang['inlinemode']);
    $menu[] = array($lang['deleteallnote']);
    $menu[] = array($lang['settimezone']);
    $menu[] = array($lang['cancel']);
    sm($chatID, $text, $menu, 'HTML', false, false, true);
}

function inlinemodeset($chatID, $lang, $invertmemodata){
    include($langdir . 'message.' . $lang . '.php');
    if($invertmemodata == 1){ $invertmemodatatxt = $lang['enabled']; } else { $invertmemodatatxt = $lang['disabled']; }
    $json = sm($chatID, $lang['settingstextinline']);
    $menu[] = array(array(
        "text" => $lang['invertmemodata'] . $invertmemodatatxt,
        "callback_data" => "toggle-" . $json['result']['message_id'] . "-invertmemodata"));
    em($chatID, $json['result']['message_id'], $lang['settingstextinline'], $menu, true);
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
