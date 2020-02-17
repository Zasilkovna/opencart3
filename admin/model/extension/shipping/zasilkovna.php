<?php
/**
 * Model for admin part of extension for zasilkovna.
 *
 * @property DB $db
 * @property Loader $load
 * @property ModelSettingEvent $model_setting_event
 * @property ModelSettingExtension $model_setting_extension
 */
class ModelExtensionShippingZasilkovna extends Model {
	/** @var string identifier for e-shop events (trigger before/after action) */
	const EVENT_CODE = 'shipping_zasilkovna';

	/**
	 * Creation of new DB tables and registering required e-shop events.
	 * Used during plugin installation.
	 *
	 * @throws Exception
	 */
	public function createTablesAndEvents() {
		// new table for additional data of orders
		$sqlOrderTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_orders` (
			`order_id` int(11) NOT NULL COMMENT "ID of order in e-shop",
			`branch_id` int(11) NOT NULL COMMENT "ID of selected zasilkovna branch (pickup point)",
			`branch_name` varchar(255) NOT NULL COMMENT "name of selected zasilkovna branch",
			`exported` datetime COMMENT "date and time of export order do CSV file",
			`total_weight` double NOT NULL COMMENT "total weight of order",
			PRIMARY KEY (`order_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		$this->db->query($sqlOrderTable);

		// new table for weight rules for countries
		$sqlWeightRulesTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_weight_rules` (
			`rule_id` int(11) NOT NULL AUTO_INCREMENT,
			`target_country` varchar(5) NOT NULL COMMENT "iso code of target country",
			`min_weight` int(11) NOT NULL,
			`max_weight` int(11) NOT NULL,
			`price` int(11) NOT NULL COMMENT "price for given weight and shipping type",
			PRIMARY KEY (`rule_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		$this->db->query($sqlWeightRulesTable);

		// new table for list of shipping types in countries
		$sqlShippingRulesTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_shipping_rules` (
			`rule_id` int(11) NOT NULL AUTO_INCREMENT,
			`target_country` varchar(5) NOT NULL COMMENT "iso code of target country",
			`default_price` int(11) NOT NULL COMMENT "default shipping price for given country",
			`free_over_limit` int(11) COMMENT "limit for free of charge shipping",
			`is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT "flag if shipping type is enabled",
			PRIMARY KEY (`rule_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
		$this->db->query($sqlShippingRulesTable);

		// new events for processing additional data
		// source and target must be in the same part of e-shop (catalog or admin)
		$this->load->model('setting/event');

		$events = [
			'catalog/controller/checkout/confirm/after' => 'extension/module/zasilkovna/saveOrderData',
			'catalog/controller/checkout/success/before' => 'extension/module/zasilkovna/sessionCleanup',
			'admin/view/common/column_left/before' => 'extension/shipping/zasilkovna/adminMenuExtension'
		];

		foreach ($events as $trigger => $action) {
			$this->model_setting_event->addEvent(self::EVENT_CODE, $trigger, $action, 1, 0);
		}
	}

	/**
	 * Cleanup during plugin uninstall. Deletes additional DB tables and removes registered events.
	 *
	 * @throws Exception
	 */
	public function deleteTablesAndEvents() {
		// drop additional tables for extension module
		$tableNames = ['zasilkovna_weight_rules', 'zasilkovna_shipping_rules', 'zasilkovna_orders'];
		foreach ($tableNames as $shortTableName) {
			$sql = 'DROP TABLE `' . DB_PREFIX . $shortTableName . '`;';
			$this->db->query($sql);
		}
		// remove events registered for "zasilkovna" plugin
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode(self::EVENT_CODE);
	}

	/**
	 * Load list of payment methods including description name of method.
	 * If description name is not found, internal method name is returned.
	 *
	 * @return array list of payment methods
	 * @throws Exception
	 */
	public function getInstalledPaymentMethods() {
		// load internal names of installed payment methods
		$this->load->model('setting/extension');
		$paymentCodeList = $this->model_setting_extension->getInstalled('payment');

		// Get description name of payment methods.
		// It must implemented inline because there is no model method for it.
		// Based on implementation in method getList in class ControllerExtensionExtensionPayment
		$paymentMethods = [];
		foreach ($paymentCodeList as $paymentCode) {
			// check if main file of extension exists
			$mainFilePath = DIR_APPLICATION . 'controller/extension/payment/' . $paymentCode . '.php';
			if (!file_exists($mainFilePath)) {
				continue; // extension is registered as installed, but file is missing
			}

			// load description name of payment method from language file of extension
			$this->load->language('extension/payment/' . $paymentCode, 'extension');
			$extensionName = $this->language->get('extension')->get('heading_title');

			$paymentMethods[] = [
				'code' => $paymentCode,
				'name' => $extensionName
			];
		}

		return $paymentMethods;
	}
}