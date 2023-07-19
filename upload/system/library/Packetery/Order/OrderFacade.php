<?php

namespace Packetery\Order;

use Session;

class OrderFacade
{
    /** @var Session */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function sessionCleanup() {
        $sessionKeys = [
            'zasilkovna_branch_id',
            'zasilkovna_branch_name',
            'zasilkovna_branch_description',
            'zasilkovna_carrier_id',
            'zasilkovna_carrier_pickup_point',
        ];

        foreach ($sessionKeys as $sessionKey) {
            unset($this->session->data[$sessionKey]);
        }
    }

}
