<?php

namespace Packetery\Engine\Action;

use Packetery\DI\Container;
use Packetery\Exceptions\InvalidStateException;
use Request;

class ActionRunner {

    /** @var Container */
    private $diContainer;

    /** @var Request */
    private $request;

    /**
     * @param Container $diContainer
     * @param Request $request
     */
    public function __construct(Container $diContainer, Request $request) {
        $this->diContainer = $diContainer;
        $this->request = $request;
    }

    /**
     * @param string $actionClassName
     * @return void
     * @throws \Exception
     */
    public function run($actionClassName) {
        $action = $this->diContainer->get($actionClassName);
        $this->tryCall($action, 'action');
        $this->tryCall($action, 'handleForm');
        $this->tryCall($action, 'render');
    }

    /**
     * @param IAction $action
     * @param string $methodName
     * @throws \ReflectionException
     * @throws \Exception
     * @return void
     */
    private function tryCall(IAction $action, $methodName) {
        $rc = new \ReflectionClass($action);
        if (! $rc->hasMethod($methodName)) {
            return;
        }

        $rm = $rc->getMethod($methodName);
        if (! $rm->isPublic() || $rm->isAbstract() || $rm->isStatic()) {
            return;
        }

        $params = $rm->getParameters();
        $args = [];
        foreach ($params as $param) {
            $args[] = $this->getRequestParameter($param->getName());
        }
        $rm->invokeArgs($action, $args);
    }

    /**
     * @param string $parameter
     * @return mixed|null
     */
    protected function getRequestParameter($parameter) {
        return isset($this->request->get[$parameter]) ? $this->request->get[$parameter] : null;
    }
}
