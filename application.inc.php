<?php

require_once 'config.inc.php';
require_once 'config.class.php';
require_once 'controller.inc.php';
require_once 'router.inc.php';
require_once 'request.class.php';
require_once 'response.class.php';
require_once 'template_engine.class.php';
require_once 'image.class.php';
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
        return $service;
    }

    public static function inject($class) {

        if(is_string($class)) {
            $class = new ReflectionClass($class);
        }
        
        $constructor = $class->getConstructor();

        $arguments = [];

        if($constructor !== null) {

            $parameters = $constructor->getParameters();

            foreach($parameters as $param) {
                $arguments[] = self::get($param->getClass()->name);
            }

        }

        return $class->newInstanceArgs($arguments);
    }

    public static function inject_set($class) {
        $class = self::inject($class);
        self::set($class);
        return $class;
    }

};

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
	$this->controller_dirs[] = $directory;
    }

    public function add_views_dir($directory) {
	Services::get('TemplateEngine')->add_dir($directory);
    }

    public function get_config() {
	return $this->config;
    }

    public function __construct($url)
    {

        global $config;

        spl_autoload_register(array($this, 'load_class'));

	$this->config = new Config();

	$this->config->db_host = $config['db_host'];
	$this->config->language = $config['language'];
	$this->config->encoding = $config['encoding'];

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

        $this->params = array_values(array_filter(explode('/', explode('?', $url)[0])));

        $request_body = json_decode(file_get_contents('php://input'), true);
        if($request_body === null) $request_body = [];
        $request_data = array_merge($request_body, $_GET, $_POST);

        $function_name = $this->method . '_' . $this->params[0];

        $this->request = new Request(explode('?', $url)[0], $this->method, $request_data);

        $this->db = new Database($config);

        Services::set($this->db);
        Services::set($this->request);
	Services::set(new TemplateEngine());

        $this->router = Services::inject_set('Router');

    }

    public function start() {

	$response = $this->router->route($this->request);

        if($response === null)
        {
            $this->response = response('error')->json('invalid action');
        }
        else
        {
            $this->response = $response;
        }

    }

    public function finish() {
        $this->response->respond($this->views_dirs);
    }

};

?>
