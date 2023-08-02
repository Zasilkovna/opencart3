<?php

namespace Packetery\Facade\Admin;

use Packetery\Engine\FlashMessage;
use Packetery\Engine\Link;

abstract class Facade {

    const TEXT_TITLE_MAIN = 'heading_title';

    /** @var \Request */
    protected $request;

    /** @var \Response */
    protected $response;

    /** @var \Language */
    protected $language;

    /** @var \Session */
    protected $session;

    /** @var Template */
    protected $template;

    /** @var Link */
    protected $link;

    /** @var array */
    private $errors = [];

    /**
     * @param \Request $request
     * @param \Response $response
     * @param \Language $language
     * @param \Session $session
     * @param Link $link
     * @param Template $template
     * @return void
     */
    public function injectServices(
        \Request $request,
        \Response $response,
        \Language $language,
        \Session $session,
        Link $link,
        Template $template
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->language = $language;
        $this->session = $session;
        $this->link = $link;
        $this->template = $template;
    }

    /**
     * @param string $parameter
     * @return string|null
     */
    protected function getParameter($parameter) {
        return isset($this->request->get[$parameter]) ? $this->request->get[$parameter] : null;
    }

    /**
     * @param string $text
     * @param string $type
     * @param string|null $icon
     * @return void
     */
    protected function flashMessage($text, $type = 'info', $icon = null) {
        $flashMessage['text'] = $text ;

        if (key_exists($type, FlashMessage::TYPES)) {
            $flashMessage['class'] = FlashMessage::TYPES[$type]['class'];
            $flashMessage['icon'] = FlashMessage::TYPES[$type]['icon'];
        } else {
            $flashMessage['class'] = 'alert-' . $type;
            $flashMessage['icon'] = $icon;
        }

        $this->session->data['flashMessages'][] = $flashMessage;
    }

    /**
     * @param string $key
     * @return string
     */
    protected function translate($key) {
        return $this->language->get($key);
    }

    /**
     * @return bool
     */
    protected function isFormSubmitted() {
        return $this->request->server['REQUEST_METHOD'] === 'POST';
    }

    /**
     * @param array $errors
     * @return void
     */
    protected function setErrors(array $errors) {
        $this->errors = $errors;
    }

    /**
     * @return bool
     */
    protected function hasErrors() {
        return (bool)$this->errors;
    }

    /**
     * @return array
     */
    protected function getErrors() {
        return $this->errors;
    }

    /**
     * @return array
     */
    protected function getFormValues() {
        return $this->request->post;
    }

    /**
     * @param array $defaultFormValues
     * @return void
     */
    protected function setDefaultFormValues(array $defaultFormValues) {
        $this->template->addParameter('form', $defaultFormValues);
    }
}
