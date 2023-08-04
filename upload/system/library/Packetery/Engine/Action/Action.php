<?php

namespace Packetery\Engine\Action;

use Packetery\Engine\FlashMessage;
use Packetery\Engine\Link;
use Packetery\Engine\Template;
use Response;
use Session;

class Action {

    /** @var Response */
    protected $response;

    /** @var Session */
    protected $session;

    /** @var Link */
    protected $link;

    /** @var Template */
    protected $template;

    /** @var \Language */
    protected $language;

    /**
     * @param Response $response
     * @param Session $session
     * @param \Language $language
     * @param Link $link
     * @param Template $template
     * @return void
     */
    public function injectServices(
        Response $response,
        Session $session,
        \Language $language,
        Link $link,
        Template $template
    ) {
        $this->response = $response;
        $this->link = $link;
        $this->language = $language;
        $this->template = $template;
        $this->session = $session;
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
}
