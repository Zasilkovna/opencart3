<?php

namespace Packetery\DI;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;

class Container {

    const EXCEPTION_MESSAGE = 'Param is not a class, extend this method if you need to support other types.';

    /** @var array */
    private $factories;

    /** @var array */
    private $services;

    /** @var \Registry */
    private $ocRegistry;

    /**
     * @param \Registry $ocRegistry
     */
    public function __construct(\Registry $ocRegistry) {
        $this->ocRegistry = $ocRegistry;
        $this->services[self::class] = $this;
    }

    /**
     * @param string $class
     * @return mixed|object
     * @throws ReflectionException
     */
    public function get($class) {
        if (!isset($this->services[$class])) {
            $serviceName = strtolower($class);
            if ($this->ocRegistry->has($serviceName)) {
                return $this->ocRegistry->get($serviceName);
            }

            $service = $this->create($class);
            $this->injectDependencies($service);
            $this->services[$class] = $service;
        }

        return $this->services[$class];
    }

    /**
     * @param string $class
     * @param Closure $factory
     * @return void
     */
    public function register($class, Closure $factory) {
        $this->factories[$class] = $factory;
    }

    /**
     * @param string $class
     * @return object
     * @throws ReflectionException
     * @throws \Exception
     */
    private function create($class) {
        if (isset($this->factories[$class])) {
            return $this->factories[$class]();
        }

        $reflection = new ReflectionClass($class);
        $paramInstances = $this->getConstructorInstances($reflection);

        return $reflection->newInstanceArgs($paramInstances);
    }

    /**
     * @param ReflectionClass $reflection
     * @return array
     * @throws Exception
     */
    private function getConstructorInstances(ReflectionClass $reflection) {
        $constructorReflection = $reflection->getConstructor();
        if ($constructorReflection === null) {
            return [];
        }

        return $this->getMethodParamInstances($constructorReflection);
    }

    /**
     * @param object $service
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    private function injectDependencies($service) {
        $injectMethods = $this->getInjectMethods($service);
        /** @var \ReflectionMethod $injectMethod */
        foreach ($injectMethods as $method) {
            $params = $this->getMethodParamInstances($method);
            $method->invokeArgs($service, $params);
        }
    }

    /**
     * @param object $service
     * @return \ReflectionMethod[]
     */
    private function getInjectMethods($service) {
        $reflection = new ReflectionClass($service);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $injectMethods = [];
        foreach ($methods as $method) {
            $startsWithInject = substr(strtolower($method->getName()), 0, strlen('inject')) === 'inject';
            if ($startsWithInject) {
                $injectMethods[] = $method;
            }
        }

        return $injectMethods;
    }

    /**
     * @param \ReflectionMethod $method
     * @return \stdClass[]
     * @throws Exception
     */
    private function getMethodParamInstances(\ReflectionMethod $method) {
        $instances = [];
        $params = $method->getParameters();
        foreach ($params as $param) {
            if (PHP_VERSION_ID >= 70100) {
                $paramType = $param->getType();
                if (!$paramType instanceof ReflectionNamedType || $paramType->isBuiltin()) {
                    throw new Exception(self::EXCEPTION_MESSAGE);
                }
                $className = $paramType->getName();
            } else {
                $paramClass = $param->getClass();
                if ($paramClass === null) {
                    throw new Exception(self::EXCEPTION_MESSAGE);
                }
                $className = $paramClass->name;
            }
            $instances[] = $this->get($className);
        }

        return $instances;
    }
}
