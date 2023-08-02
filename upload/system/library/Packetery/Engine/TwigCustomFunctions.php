<?php

namespace Packetery\Engine;

use Packetery\Engine\Grid\DataGrid;

class TwigCustomFunctions {

    /** @var Link */
    private $link;

    /**
     * @param Link $link
     */
    public function __construct(Link $link) {
        $this->link = $link;
    }

    /**
     * @param string $action
     * @param array $params
     * @return string
     */
    public function urlAdmin($action, array $params) {
        return $this->link->createAdminLink($action, $params);
    }

    /**
     * @param DataGrid|null $grid
     * @return string
     * @throws \Exception
     */
    public function grid(DataGrid $grid) {
        return $grid->render();
    }
}
