<?php

class Router
{

    private $routes = [];

    protected static $types = [
	'int' => '\d+',
	'str' => '[A-z0-9-_]+'
    ];

    public function __construct() {}

    public function resource($name, $controller) {

        $names = explode('.', $name);

        // [0] => "taxo", [1] => "sinonims"

        $last = array_pop($names);

        $base = "";

        foreach($names as $name) {
            $base .= "{$name}/{{$name}_id}/";
        }

        $this->add_resource($base, $last, $controller);

    }

    private function add_resource($base, $name, $controller) {

        // index  => GET /resource/
        // get    => GET /resource/{id:int}
        // update => PUT /resource/{id:int} && $params
        // create => POST /resource/ && $params
        // delete => DELETE /resource/{id:int}

        $this->get("{$base}{$name}", "{$controller}.index");
        $this->get("{$base}{$name}/{{$name}_id}", "{$controller}.get");
        $this->put("{$base}{$name}/{{$name}_id}", "{$controller}.update");
        $this->post("{$base}{$name}", "{$controller}.create");
        $this->delete("{$base}{$name}/{{$name}_id}", "{$controller}.delete");

    }
    
    private function parse_route($route) {
	$params = [];
	preg_match_all("/(?<outer>{(?<names>[^}]+)})/", $route, $params);
	$replaces = [];
	$find = [];
	$param_names = [];

	$num_params = count($params['names']);

	for($i = 0; $i < $num_params; ++$i) {
	    $options = explode(':', $params['names'][$i]);
	    $capture = isset(self::$types[$options[1]]) ?
		       self::$types[$options[1]] :
		       self::$types['str'];
	    array_push($replaces, "(?<{$options[0]}>$capture)?");
	    array_push($find, '/' .$params[0][$i] . '/');
	    array_push($param_names, $options[0]);
	}

	$regexp = preg_replace($find, $replaces, $route, $num_params);
        $regexp = trim($regexp, '/');
	$regexp = "/^\/*" . addcslashes($regexp, '/') . "\/*$/";

        return ['regexp' => $regexp, 'params' => $param_names];
    }

    private function parse_action($action) {
	if(is_string($action) &&
	    strpos($action, '.') !== false) {
	    $action = explode('.', $action);
	}
        return [
	    'action' => $action
	];
    }
    

    public function add($method, $name, $action) {
        $route = $this->parse_route($name);
        $action = $this->parse_action($action);
	$this->routes[$method][] = array_merge($route, $action);
        return true;
    }
    
    public function get($name, $action) {
        $this->add('get', $name, $action);
        return $this;
    }

    public function post($name, $action) {
        $this->add('post', $name, $action);
        return $this;
    }

    public function put($name, $action) {
        $this->add('put', $name, $action);
        return $this;
    }

    public function delete($name, $action) {
        $this->add('delete', $name, $action);
        return $this;
    }

    public function route(Request $request) {

	$routes = &$this->routes[$request->method()];
	$values = null;
	$response = null;

	if(isset($routes)) {
	    foreach($routes as $route) {

		$values = [];
		$matches = null;
		$nmatches = preg_match($route['regexp'], $request->path(), $matches);

		if($nmatches > 0) {
                    foreach($route['params'] as $key) {
			if(!isset($matches[$key])) break;
			$values[$key] = $matches[$key];
                    }

                    if(count($values) == count($route['params'])) {
			$response = $this->call($route, $values, $request);
			break;
                    }
		}

	    }
	}

        return $response;

    }

    public function call($route, $values, Request $request) {

	try {
	    
	    if(is_array($route['action'])) {

		$controller_class = new ReflectionClass($route['action'][0]);
		$action_methods = $controller_class->getMethods(ReflectionMethod::IS_PUBLIC);

		array_walk($action_methods, function(&$v) {
		    $v = $v->getName();
		});

		if(($index = array_search($route['action'][1], $action_methods)) === false){
		    return null;
		}

		$action_method = $controller_class->getMethod($action_methods[$index]);

		$controller = Services::inject($controller_class);

		$parameters = $action_method->getParameters();

		$arguments = [];

		foreach($parameters as $param) {
		    $arguments[] = isset($values[$param->getName()]) ?
				   $values[$param->getName()] :
				   Services::get($param->getClass()->name);
		}

		fix_types($arguments);

		$result = call_user_func_array(
		    array($controller, $action_method->name),
		    $arguments
		);

	    } else if(is_callable($route['action'])) {

		$action_method = new ReflectionFunction($route['action']);
		$parameters = $action_method->getParameters();
		$arguments = [];

		foreach($parameters as $param) {
		    $arguments[] = isset($values[$param->getName()]) ?
				   $values[$param->getName()] :
				   Services::get($param->getClass()->name);
		}

		fix_types($arguments);

		$result = call_user_func_array(
		    $route['action'],
		    $arguments
		);

	    }



	} catch (Exception $e) {
	    echo $e->getMessage();
            return null;
	}

	if(!($result instanceof Response)) {
	    $result = response();
	}

        return $result;

    }

}

?>
