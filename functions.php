<?php
//Answer CallBack Query
function acq($id, $text, $alert = false){
  global $api;
  global $chatID;
  $args = array(
    'callback_query_id' => $id,
    'text' => $text,
    'show_alert' => $alert
  );
  $r = new HttpRequest("post", "https://api.telegram.org/$api/answerCallbackQuery", $args);
  $rr = $r->getResponse();
  return $rr;
}

//Send Message
function sm($chatID, $text, $rmf = false, $pm = false, $dis = false, $replyto = false, $preview = false, $inline = false){
  global $api;
  global $update;

  if($inline){
    $rm = array('inline_keyboard' => $rmf);
  } else {
    $rm = array('keyboard' => $rmf,
      'resize_keyboard' => true
    );
  }

  $rm = json_encode($rm);

  $args = array(
    'chat_id' => $chatID,
    'text' => $text,
    'disable_notification' => $dis
  );
  if($replyto) $args['reply_to_message_id'] = $update["message"]["message_id"];
  if($rmf) $args['reply_markup'] = $rm;
  if($preview) $args['disable_web_page_preview'] = $preview;
  if($pm) $args['parse_mode'] = $pm;
  if($text)
  {
    $r = new HttpRequest("post", "https://api.telegram.org/$api/sendmessage", $args);
    $rr = $r->getResponse();
    $ar = json_decode($rr, true);
  }
  return $ar;
}

//Edit Message
function em($chatID, $messageID, $text, $rmf = false, $inline = false, $pm = 'Markdown'){
  global $api;

  if($inline){
    $rm = array('inline_keyboard' => $rmf);
    } else {
    $rm = array(
      'keyboard' => $rmf,
      'resize_keyboard' => true
    );
  }

  $rm = json_encode($rm);

  $args = array(
    'chat_id' => $chatID,
    'message_id' => $messageID,
    'text' => $text
  );
  if($rmf) $args['reply_markup'] = $rm;
  if($text){
    $r = new HttpRequest("post", "https://api.telegram.org/$api/editMessageText", $args);
    $rr = $r->getResponse();
  }
  return $rr;
}

//Edit Message keyboard
function emk($chatID, $messageID, $rmf, $inline_msgid = false){
  global $api;

  $rm = array('inline_keyboard' => $rmf);
  $rm = json_encode($rm);

  $args["reply_markup"] = $rm;
  if ($inline_msgid) {
    $args["inline_message_id"] = $inline_msgid;
  } else {
    $args["chat_id"] = $chatID;
    $args["message_id"] = $messageID;
  }

  new HttpRequest("post", "https://api.telegram.org/$api/editMessageReplyMarkup", $args);
}
