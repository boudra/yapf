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
            if(self::exists($class)) {
                return self::$services[$class];
            }
            return self::inject_set($class);
        } catch (\ReflectionException $e) {
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
            return self::injectFunction($class);
        if(is_array($class) && is_callable($class, true))
            return self::injectMethod($class);
        else
            return self::injectClass($class);
    }

    public static function injectMethod($fn) {
        $rfn = new \ReflectionMethod($fn[0], $fn[1]);
        $arguments = self::getArguments($rfn);
        return $rfn->invokeArgs($fn[0], $arguments);
    }

    private static function getArguments($fn) {
        $arguments = [];
        if(!$fn) return $arguments;
        $parameters = $fn->getParameters();
        foreach($parameters as $param) {
            $name = self::getClassName($param);
            if(!$name) continue;
            $arguments[] = self::get($name);
        }
        return $arguments;
    }

    /* we just need the name, using $param->getClass()
     * will try to find de class */
    private static function getClassName(\ReflectionParameter $param) {
        preg_match('/(\w+)\s\$\w+\s\]$/s', $param->__toString(), $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    public static function injectFunction($fn) {

        $rfn = new \ReflectionFunction($fn);

        $arguments = self::getArguments($rfn);

        return $rfn->invokeArgs($arguments);
    }

    public static function injectClass($class) {
        $class = new \ReflectionClass($class);
        $fn = $class->getConstructor();

        $arguments = self::getArguments($fn);

        return $class->newInstanceArgs($arguments);
    }

    public static function inject_set($class) {
        $class = self::inject($class);
        self::set($class);
        return $class;
    }

};

?>
