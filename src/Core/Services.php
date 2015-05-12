<?php

namespace App\Core;

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

?>
