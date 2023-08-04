<?php

namespace Packetery\Vendor;

use Packetery\Engine\Form;

class VendorForm extends Form {

    /** @var VendorService */
    private $vendorService;

    /** @var VendorFactory */
    private $vendorFactory;

    /** @var Page */
    private $vendorPage;


    /**
     * @param VendorService $vendorService
     * @param VendorFactory $vendorFactory
     * @param Page $vendorPage
     * @return void
     */
    public function injectServices(
        VendorService $vendorService,
        VendorFactory $vendorFactory,
        Page $vendorPage
    ) {
        $this->vendorService = $vendorService;
        $this->vendorFactory = $vendorFactory;
        $this->vendorPage = $vendorPage;
    }

    /**
     * @param callable $callback
     * @return void
     */
    public function process(callable $callback) {
        $values = $this->getValues();
        $vendorData = $this->vendorService->prepareFormData($values);
        $vendor = $this->vendorFactory->create($vendorData, $values['weight_rules']);
        $this->vendorService->save($vendor);

        $callback($vendor);
    }

    /**
     * @return bool
     */
    public function isValid() {
        $values = $this->getValues();
        if (isset($values['weight_rules'])) {
            $values['weight_rules'] = array_values(
                $this->vendorPage->removeEmptyWeightRules($values['weight_rules'])
            );
        }
        $errors = $this->vendorPage->validate($values);
        $this->setErrors($errors);

        return ! $this->hasErrors();
    }
}
