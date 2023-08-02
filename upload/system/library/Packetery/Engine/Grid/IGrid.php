<?php

namespace Packetery\Engine\Grid;

interface IGrid {

    /**
     * @param array $data
     * @return DataGrid
     */
    public function create(array $data);
}
