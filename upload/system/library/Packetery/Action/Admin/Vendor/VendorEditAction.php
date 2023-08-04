<?php

namespace Packetery\Action\Admin\Vendor;

use Packetery\DAL\Entity\Vendor;

class VendorEditAction extends BaseVendorAction {

    /** @var Vendor */
    private $vendor;

    /**
     * @param string $id
     * @return void
     */
    public function action($id) {
        $this->vendor = $this->getVendor((int)$id);

        $formDefaults = [
            'cart_name' => $this->vendor->getCartName(),
            'free_shipping_limit' => $this->vendor->getFreeShippingLimit(),
            'is_enabled' => $this->vendor->isEnabled(),
        ];

        foreach ($this->vendor->getPrices() as $key => $vendorPrice) {
            $formDefaults['weight_rules'][$key]['max_weight'] = $vendorPrice->getMaxWeight();
            $formDefaults['weight_rules'][$key]['price'] = $vendorPrice->getPrice();
        }

        $this->vendorForm->setDefaults($formDefaults);
    }

    /**
     * @return void
     */
    public function handleForm() {
        parent::handleForm();
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function render() {
        $this->template->setTitle(sprintf($this->translate('vendor_edit_title'), $this->vendor->getTitle()));
        $this->template->addParameter('id', $this->vendor->getId());
        $this->template->addParameter('panel_title', $this->vendor->getTitle());
        $this->template->addParameter('link_form_action', $this->link->createAdminLink(
            'edit_vendor',
            ['id' => $this->vendor->getId()]
        ));
        $countryCode = $this->vendor->getTransport()->getCountry();
        $country = $this->countryService->getByCountryCode($countryCode);
        $this->template->addParameter('country', $country);

        $this->initBreadcrumbs($country);
        $this->template->addBreadcrumb($this->vendor->getTitle());

        $this->response->setOutput($this->template->render('Vendor/vendor'));
    }
}
