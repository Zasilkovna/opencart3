<?php

namespace Packetery\Engine;

class Link {

    const ROUTING_BASE_PATH = 'extension/shipping/zasilkovna';

    /** @var Registry */
    private $registry;

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry) {
        $this->registry = $registry;
    }

    /**
     * Creates link to given action in administration including user token.
     *
     * @param string $actionName    internal name of module action
     * @param array $urlParameters additional parameters to url
     * @return string
     */
    public function createAdminLink($actionName, array $urlParameters = []) {
        // empty action name => main page of module
        if ($actionName === '') {
            $actionName = self::ROUTING_BASE_PATH;
        }

        // action name without slash (/) => action of module
        if (strpos($actionName, '/') === false) {
            $actionName = self::ROUTING_BASE_PATH . '/' . $actionName;
        }

        // otherwise action name is absolute routing path => no change in action name
        // user token must be part of any administration link
        $urlParameters['user_token'] = $this->registry->session->data['user_token'];

        return $this->registry->url->link($actionName, $urlParameters, true);
    }
}
