<?php

require_once 'config.inc.php';
require_once 'application.inc.php';
require_once 'request.class.php';

$url = str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $_SERVER['REQUEST_URI']);
$api = new Application($url);

?>
