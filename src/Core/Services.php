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

    public static function inject($class, $values = []) {

        if(is_callable($class))
            return self::injectFunction($class, $values);
        if(is_array($class) && is_callable($class, true))
            return self::injectMethod($class, $values);
        else
            return self::injectClass($class, $values);
    }

    public static function injectMethod($fn, $values = []) {
        $rfn = new \ReflectionMethod($fn[0], $fn[1]);
        $arguments = self::getArguments($rfn, $values);
        return $rfn->invokeArgs($fn[0], $arguments);
    }

    private static function getArguments($fn, $values = []) {
        $arguments = [];
        if(!$fn) return $arguments;
        $parameters = $fn->getParameters();
        foreach($parameters as $param) {
            if(isset($values[$param->getName()])) {
                $arguments[] = $values[$param->getName()];
            } else {
                $name = self::getClassName($param);
                $arguments[] = self::get($name);
            }
        }
        return $arguments;
    }

    /* we just need the name, using $param->getClass()
     * will try to find de class */
    private static function getClassName(\ReflectionParameter $param) {
        preg_match('/(\w+)\s\$\w+\s\]$/s', $param->__toString(), $matches);
        return isset($matches[1]) ? $matches[1] : null;
    }

    public static function injectFunction($fn, $values = []) {
        $rfn = new \ReflectionFunction($fn);
        $arguments = self::getArguments($rfn, $values);
        return $rfn->invokeArgs($arguments);
    }

    public static function injectClass($class, $values = []) {
        $rclass = new \ReflectionClass($class);
        $fn = $rclass->getConstructor();
        $arguments = self::getArguments($fn, $values);
        return $rclass->newInstanceArgs($arguments);
    }

    public static function inject_set($class) {
        $class = self::inject($class);
        self::set($class);
        return $class;
    }

};

?>
