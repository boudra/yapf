<?php

function fix_types(&$arr) {
    $arr = array_map(function($v) {
        return is_numeric($v) ?  0 + $v : $v;
    }, $arr);
}

function rename_keys($arr, $keys) {
    foreach($arr as $k => $v) {
        if(!isset($keys[$k])) continue;
        $arr[$keys[$k]] = $v;
        unset($arr[$k]);
    }
    return $arr;
}

function response($status = 200, $data = null) {
    return new Response($status, $data);
}

function query($table, $alias = null) {
    return Service::get('Database')->query()->table($table, $alias);
}

function get_current_path() {
    static $url = null;

    if($url === null) {
	if(empty($_GET['url'])) {
	    $url = str_replace(dirname($_SERVER['SCRIPT_NAME']) . '/', '', $_SERVER['REQUEST_URI']);
	}
	else
	{
	    $url = $_GET['url'];
	}
    }

    return $url;
}

function path_to_url($path) {
    $protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? 'http' : 'https';
    $domain = $_SERVER['HTTP_HOST'];
    $full_url = "$protocol://{$domain}{$path}";
    $full_url = rtrim($full_url, '/');
    return $full_url;
}

function guess_lib_url() {
    $relative = quotemeta(get_current_path());
    $base_url = preg_replace("!" . $relative . "(.*)$!", '', str_replace("\\", '/', $_SERVER['REQUEST_URI']));
    return path_to_url($base_url);
}

function guess_app_url() {
    $base_dir = str_replace("\\", '/', dirname($_SERVER['SCRIPT_NAME']));
    $script_relative = str_replace("\\", '/', $_SERVER['SCRIPT_NAME']);
    $script_absolute = str_replace("\\", '/', $_SERVER['SCRIPT_FILENAME']);
    $doc_root = preg_replace("!{$script_relative}$!", '', $script_absolute);
    $base_url = preg_replace("!^{$doc_root}!", '', $base_dir);
    return path_to_url($base_url);
}

?>
