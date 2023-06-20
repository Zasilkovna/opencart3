<?php
/**
 * A filter to only include zasilkovna*.php files from OC3 model directory.
 */
namespace PHP_CodeSniffer\Filters;

use PHP_CodeSniffer\Util;

class ZasilkovnaModels extends Filter {
	const OC3_MODEL_EXTENSION_SHIPPING_DIR = 'upload/admin/model/extension/shipping';

	/**
	 * Doesn't accept files that are not zasilkovna*.php files and are located in OC3 model directory.
	 * @return bool
	 */
	public function accept() {
		if (parent::accept() === false) {
			return false;
		}

		$filePath = Util\Common::realpath($this->current());

		return !((strpos($filePath, self::OC3_MODEL_EXTENSION_SHIPPING_DIR) !== false) && strpos($filePath, 'zasilkovna') === false);
	}
}
