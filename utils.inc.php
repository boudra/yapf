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

?>
