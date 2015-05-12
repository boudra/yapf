<?php

namespace App\Core;

/**
 * Request
 */
class Request
{

    private $params = [];
    private $url;
    private $method;
    
    public function __construct($path, $method, $values)
    {
        $this->path = $path;
        $this->method = strtolower($method);
        $this->params = $values;
        fix_types($this->params);
    }

    public static function url() {
        $ssl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
        $sp = strtolower($_SERVER['SERVER_PROTOCOL']);
        $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
        $port = $_SERVER['SERVER_PORT'];
        $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
        $host = $_SERVER['SERVER_NAME'] . $port;
        return $protocol . '://' . $host . $_SERVER['REQUEST_URI'];
    }

    public function path() {
        return $this->path;
    }

    public function is($path) {
        return substr($this->path, 0, strlen($path)) === $path;
    }

    public function is_method($method) {
        return $method === $this->method;
    }

    public function method() {
        return $this->method;
    }

    public function has($key) {
        return isset($this->params[$key]);
    }

    public function get($key, $default = null) {
        return $this->has($key) ? $this->params[$key] : $default;
    }

    public function only() {
        $args = func_get_args();
        $params = [];
        foreach($args as $arg) {
            if($this->has($arg)) {
                $params[$arg] = $this->get($arg);
            }
        }
        return $params;
    }

    public function get_all() {
        return $this->params;
    }

}

?>
