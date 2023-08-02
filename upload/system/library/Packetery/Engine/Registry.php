<?php

namespace Packetery\Engine;

class Registry {
    /** @var \Request */
    private $request;

    /** @var \Response */
    private $response;

    /** @var \Language */
    public $language;

    /** @var \Session */
    public $session;

    /** @var \Url */
    public $url;

    /**
     * @param \Request $request
     * @param \Response $response
     * @param \Language $language
     * @param \Session $session
     * @param \Url $url
     */
    public function __construct(
        \Request $request,
        \Response $response,
        \Language $language,
        \Session $session,
        \Url $url
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->language = $language;
        $this->session = $session;
        $this->url = $url;
    }
}
