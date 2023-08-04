<?php

namespace Packetery\Vendor;

use Packetery\Engine\Grid\DataGrid;
use Packetery\Engine\Grid\Exception\DataGridException;
use Packetery\Engine\Grid\IGrid;

class VendorGrid extends BaseGridFactory implements IGrid {

    //TODO: extends BaseGridFactory

    /**
     * @param array $data
     * @return DataGrid
     * @throws DataGridException
     */
    public function create(array $data) {
        $grid = parent::create($data);
        //$grid = new DataGrid($this->template);
        //$grid->setData($data);
        $grid->addColumnText('name', 'carrier_settings_country_column_name');
        $grid->addColumnText('status', 'carrier_settings_country_column_status');

        return $grid;
    }
}
