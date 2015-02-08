<?php

require_once 'controller.inc.php';
require_once 'router.inc.php';
require_once 'response.class.php';
require_once 'utils.inc.php';
require_once 'db.inc.php';


class Services {

    public static $services = [];

    public static function exists($type) {
        return isset(self::$services[$type]);
    }

    public static function get($type) {
        return self::exists($type) ? self::$services[$type] : null;
    }

    public static function set(&$service) {
        self::$services[get_class($service)] = &$service;
    }

};

class Application {

    protected $url = '';
    protected $method = '';
    protected $args = array();

    private $config = [];
    private $request = null;
    private $response = [
        'status' => 'error',
        'result' => null 
    ];

    public function __construct($url)
    {

        global $config;

        $this->config = $config;

        $this->method = strtolower($_SERVER['REQUEST_METHOD']);

        if($this->method == 'post' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
        {
            if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE' ||
               $_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
            {
                $this->method = strtolower($_SERVER['HTTP_X_HTTP_METHOD']);
            }
        }

        $this->params = array_values(array_filter(explode('/', explode('?', $url)[0])));

        $request_body = json_decode(file_get_contents('php://input'), true);
        if($request_body === null) $request_body = [];
        $request_data = array_merge($request_body, $_GET, $_POST);

        $files = glob('controllers/*.php', GLOB_BRACE);
        $function_name = $this->method . '_' . $this->params[0];

        $this->request = new Request(explode('?', $url)[0], $this->method, $request_data);

        Services::set($this->request);

        $this->db = new Database($this->config);

        Services::set($this->db);

        foreach($this->params as $key => $param) {
            if(strrpos($param, '?') !== false || strrpos($param, '&') !== false)
                unset($this->params[$key]);
        }

        $this->params = array_map($to_number, $this->params);

        require_once 'controllers/taxo.php';

        $router = new Router();
        $router->resource('taxo', 'Taxo');

        $response = $router->route($this->request);

        if($response === null)
        {
            $this->response = response('Invalid action.', 'error')->type('json');
        }
        else
        {
            $this->response = $response;
        }

        $this->finish();

    }

    public function respond($status, $msg)
    {
        $this->response = [
            'status' => $status,
            'result' => $msg
        ];
    }

    public function finish() {
        $this->response->respond();
    }

    private function encode_json($data)
    {
        $string =  json_encode($data, JSON_PRETTY_PRINT);
        $replaced_string = preg_replace("/\\\\u(\w{4})/", "&#x$1;", $string);
        $unicode_string = mb_convert_encoding($replaced_string, 'UTF-8', 'HTML-ENTITIES');
        return $unicode_string;
    }

};

?>
