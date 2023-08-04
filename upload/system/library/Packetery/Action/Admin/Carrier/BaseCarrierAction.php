<?php

namespace Packetery\Action\Admin\Carrier;

use Packetery\Engine\Action\Action;
use Packetery\Engine\Action\IAction;

abstract class BaseCarrierAction extends Action implements IAction {
    const ACTION_CARRIER_SETTINGS = 'carrier_settings';
    const ACTION_CARRIER_SETTINGS_COUNTRY = 'carrier_settings_country';
}
