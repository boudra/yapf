<?php

function fix_types(&$arr) {
    $arr = array_map(function($v) {
        return is_numeric($v) ?  0 + $v : $v;
    }, $arr);
}

function response($status = 200, $data = null) {
    return new Response($status, $data);
}

?>
