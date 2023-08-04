<?php

namespace Packetery\Action\Admin\Vendor;

use Packetery\Action\Admin\Carrier\BaseCarrierAction;
use Packetery\Engine\FlashMessage;

class VendorDeleteAction extends BaseVendorAction {

    /**
     * @param string $id
     * @return void
     */
    public function action($id) {
        $vendor = $this->getVendor((int)$id);
        $this->vendorService->delete($vendor);

        $this->flashMessage($this->language->get('vendor_delete_success'), FlashMessage::SUCCESS);

        $this->response->redirect(
            $this->link->createAdminLink(
                BaseCarrierAction::ACTION_CARRIER_SETTINGS_COUNTRY,
                ['country' => $vendor->getTransport()->getCountry()]
            )
        );
    }
}
