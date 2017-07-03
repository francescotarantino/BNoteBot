<?php
require("class-http-request.php");
require("config.php");
$url = "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$url = str_ireplace("setWebhook.php", "bot.php", $url);
$args = array(
  'url' => $url
);
$r = new HttpRequest("post", "https://api.telegram.org/$api/setWebhook", $args);
echo $r->getResponse();
?>
