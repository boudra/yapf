<?php

namespace App;

use App\Core\Config;
use App\Core\Services;
use App\Core\Request;
use App\Core\Router;

use App\Utils\TemplateEngine;

require 'Utils/Utils.php';

class Application {

    protected $url = '';
    protected $method = '';
    protected $args = array();

    protected $controller_dirs = [];
    protected $views_dirs = [];

    private $config = [];
    private $request = null;
    private $router = null;
    private $response = [
        'status' => 'error',
        'result' => null
    ];

    public function load_class($name) {
        $name = strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($name)));
        foreach($this->controller_dirs as $dir) {
            if(is_dir($dir) && file_exists("{$dir}/{$name}.php")) {
                require "{$dir}/{$name}.php";
                break;
            }
        }
    }

    public function add_controllers_dir($directory) {
        $this->controller_dirs[] = realpath($directory);
    }

    public function add_views_dir($directory) {
        Services::get('TemplateEngine')->add_dir(realpath($directory));
    }

    public function get_config() {
        return $this->config;
    }

    public function __construct(Config $config = null, $url = null)
    {

        if(!$url) $url = get_current_path();

        spl_autoload_register(array($this, 'load_class'));

        $this->config = $config ? $config : new Config();

        if(empty($this->config->encoding))
            $this->config->encoding = 'UTF-8';

        $this->config->lib_url = guess_lib_url();
        $this->config->lib_dir = dirname(__FILE__);

        $this->config->app_dir = dirname($_SERVER['SCRIPT_FILENAME']);
        $this->config->app_url = guess_app_url();

        $this->config->data_dir = $this->config->app_dir . '/data';
        $this->config->data_url = $this->config->app_url . '/data';

        Services::set($this->config);

        $this->method = strtolower($_SERVER['REQUEST_METHOD']);

        if($this->method == 'post' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
        {
            if($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE' ||
                $_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
            {
                $this->method = strtolower($_SERVER['HTTP_X_HTTP_METHOD']);
            }
        }

        $request_body = json_decode(file_get_contents('php://input'), true);
        if($request_body === null) $request_body = [];
        $request_data = array_merge($request_body, $_GET, $_POST);

        $this->request = new Request(explode('?', $url)[0], $this->method, $request_data);

        Services::set($this->request);
        Services::set(new TemplateEngine());

        $this->router = Services::inject_set(Router::class);

    }

    public function run($callback) {

        if(is_callable($callback)) {
            Services::inject($callback);
        }

        $response = $this->router->route($this->request);

        if($response === null)
        {
            $this->response = response('not_found')->set('Route not found');
        }
        else
        {
            $this->response = $response;
        }

        echo $this->response;

    }

};

?>
