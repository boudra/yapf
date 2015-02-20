<?php

$config['db_host']	= 'localhost';
$config['db_user']	= 'root';
$config['db_pass']	= 'root';
$config['db_db']	= 'gestio';
$config['encoding']	= 'UTF-8';
$config['language']	= 'en-US';

define('ROOT_DIR', dirname(__FILE__));
define('ROOT_URL', 'http://localhost/gestio/api');

define('DATA_URL', ROOT_URL . '/data');
define('DATA_DIR', ROOT_DIR . '/data');

?>
