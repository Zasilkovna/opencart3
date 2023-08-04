<?php

namespace Packetery\Vendor;

use Packetery\Engine\Grid\DataGrid;
use Packetery\Engine\Template;

class BaseGridFactory extends DataGrid {

    /**
     * @param Template $template
     */
    public function __construct(Template $template) {
        parent::__construct($template);
    }

    /**
     * @param array $data
     * @return DataGrid
     */
    public function create(array $data) {
        return new DataGrid($this->template);
    }
}
