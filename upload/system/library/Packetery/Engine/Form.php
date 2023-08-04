<?php

namespace Packetery\Engine;

use Request;

class Form {

    /** @var array */
    private $values = [];

    /** @var array */
    private $errors;

    /** @var Request */
    private $request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * @return array
     */
    public function getValues() {
        return empty(!$this->getPost()) ? $this->getPost() : $this->values;
    }

    /**
     * @param array $values
     * @return void
     */
    public function setDefaults(array $values) {
        $this->values = $values;
    }

    /**
     * @return bool
     */
    public function isSuccess() {
        return $this->isSubmitted() && !$this->hasErrors();
    }

    /**
     * @return bool
     */
    public function isSubmitted() {
        return $this->getPost() !== [];
    }

    /**
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * @param array $errors
     * @return void
     */
    public function setErrors(array $errors) {
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    public function hasErrors() {
        return $this->errors !== [];
    }

    /**
     * @return array
     */
    public function getPost() {
        return $this->request->post;
    }
}
