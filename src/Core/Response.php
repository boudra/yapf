<?php

namespace App\Core;

class Response {

    private $content_type = "text/plain";
    private $charset = "utf-8";
    private $data = null;
    private $plain_response = null;
    private $status_code = 200;

    private static $status_codes = [
        'ok' => 200,
        'not_found' => 404,
        'error' => 500,
        'fail' => 400
    ];

    private static $content_types_short = [
        'plain' => 'text/plain',
        'json' => 'application/json',
        'html' => 'text/html'
    ];

    public function __construct($status = 200, $data = null) {
        $this->data = $data;
        $this->status($status);
    }

    public function respond() {

        header("Content-type: {$this->content_type}; charset={$this->charset}");
        http_response_code($this->status_code);

        if($this->content_type === 'application/json') {
            return $this->encode_json($this->data);
        } else if(substr($this->content_type, 0, 5) === 'image') {
            return file_get_contents($this->data);
        } else if($this->content_type === 'text/html') {
            if(isset($this->view)) {
                $te = Services::get('TemplateEngine');
                return $te->display($this->view, $this->data);
            } else {
                return $this->data;
            }
        } else {
            return $this->data;
        }

    }

    public function __toString() {
        return $this->respond();
    }

    public function type($type) {
        $this->content_type = isset(self::$content_types_short[$type]) ?
            self::$content_types_short[$type] :
            $type;
        return $this;
    }

    public function status($status) {
        $this->status_code = is_numeric($status) ?
            $status :
            self::$status_codes[$status];
        return $this;
    }

    public function json($data) {
        $this->type('json');
        $this->data = $data;
        return $this;
    }

    public function image($data) {
        $mime = getimagesize($data)['mime'];
        $this->type($mime);
        $this->data = $data;
        return $this;
    }

    public function view($name, $data = null) {
        $this->type('html');
        $this->data = $data;
        $this->view = $name;
        return $this;
    }

    public function output($key, $value) {
        $this->data[$key] = $value;
        return $this;
    }

    public function set($data) {
        $this->data = $data;
        return $this;
    }

    public function html($data) {
        $this->type('html');
        $this->data = $data;
        return $this;
    }

    /* encode json as UTF-8 */
    private function encode_json($data)
    {
        $string =  json_encode($data, JSON_PRETTY_PRINT);
        $replaced_string = preg_replace("/\\\\u(\w{4})/", "&#x$1;", $string);
        $unicode_string = mb_convert_encoding($replaced_string, 'UTF-8', 'HTML-ENTITIES');
        return $unicode_string;
    }

}

?>
