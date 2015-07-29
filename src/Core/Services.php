<?php

namespace App\Core;

class Services {

    public static $services = [];

    public static function exists($type) {
        return isset(self::$services[$type]);
    }

    public static function get($type) {
        if(self::exists($type)) {
            return self::$services[$type];
        }

        try {
            $class = (new \ReflectionClass($type))->getShortName();
            return self::$services[$class];
        } catch (Exception $e) {
            return null;
        }

    }

    public static function set(&$service) {
        $class = new \ReflectionClass(get_class($service));
        self::$services[$class->getShortName()] = &$service;
        return $service;
    }

    public static function inject($class) {

        if(is_callable($class))
            return self::inject_fn($class);
        else
            return self::inject_class($class);
    }

    private static function inject_fn($fn) {

        $rfn = new \ReflectionFunction($fn);

        $arguments = [];

        if($fn !== null) {
            $parameters = $rfn->getParameters();
            foreach($parameters as $param) {
                $arguments[] = self::get($param->getClass()->name);
            }
        }

        return $rfn->invokeArgs($arguments);
    }

    private static function inject_class($class) {
        $class = new \ReflectionClass($class);
        $fn = $class->getConstructor();
        $arguments = [];

        if($fn !== null) {
            $parameters = $fn->getParameters();
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
