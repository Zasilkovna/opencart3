<?php

use Packetery\Exceptions\UpgradeException;

require_once DIR_SYSTEM . 'library/Packetery/deps/autoload.php';

/**
 * Model for admin part of extension for zasilkovna.
 *
 * @property DB $db
 * @property Loader $load
 * @property ModelSettingEvent $model_setting_event
 * @property ModelSettingExtension $model_setting_extension
 * @property ModelSettingSetting $model_setting_setting
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
        /** TODO: ošetřit stav, kdy DB tabulky při instalaci už existují. Pokud v installeru smažu instalaci,
         * odstraní se zdrojové soubory, ale záznamy v DB ne. Odstraněním souborů modul zmizí z 'Extensions'.
         * Pak nezbyde nic jiného, než tabulky odstranit ručně, jinak modul nejde instalovat.
         */
        // new table for additional data of orders
        $sqlOrderTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_orders` (
			`order_id` int(11) NOT NULL COMMENT "ID of order in e-shop",
			`branch_id` int(11) NOT NULL COMMENT "ID of selected zasilkovna branch (pickup point)",
			`branch_name` varchar(255) NOT NULL COMMENT "name of selected zasilkovna branch",
			`carrier_pickup_point` VARCHAR(40) NULL COMMENT "Code of selected carrier pickup point related to branch_id",
			`is_carrier` TINYINT(1) NOT NULL DEFAULT "0" COMMENT "Tells if branch_id is carrier",
			`exported` datetime COMMENT "date and time of export order do CSV file",
			`total_weight` double NOT NULL COMMENT "total weight of order",
			PRIMARY KEY (`order_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
        $this->db->query($sqlOrderTable);

        // new table for weight rules for countries
        $sqlWeightRulesTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_weight_rules` (
			`rule_id` int(11) NOT NULL AUTO_INCREMENT,
			`target_country` varchar(5) NOT NULL COMMENT "iso code of target country",
			`max_weight` decimal(10,2) NOT NULL DEFAULT 0,
			`price` float(12,2) NOT NULL COMMENT "price for given weight and shipping type",
			PRIMARY KEY (`rule_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
        $this->db->query($sqlWeightRulesTable);

        // new table for list of shipping types in countries
        $sqlShippingRulesTable = 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_shipping_rules` (
			`rule_id` int(11) NOT NULL AUTO_INCREMENT,
			`target_country` varchar(5) NOT NULL COMMENT "iso code of target country",
			`default_price` float(12,2) NOT NULL COMMENT "default shipping price for given country",
			`free_over_limit` float(12,2) COMMENT "limit for free of charge shipping",
			`is_enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT "flag if shipping type is enabled",
			PRIMARY KEY (`rule_id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
        $this->db->query($sqlShippingRulesTable);

        $this->db->query($this->getCreateCarriersTableSQL());
        $this->db->query($this->getCreateVendorTableSQL());
        $this->db->query($this->getCreateVendorPriceTableSQL());

        $this->installEvents();
    }

    /**
     * @return string
     */
    private function getCreateCarriersTableSQL()
    {
        return 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_carrier` (
			`id` int NOT NULL,
			`name` varchar(255) NOT NULL,
			`is_pickup_points` boolean NOT NULL,
			`has_carrier_direct_label` boolean NOT NULL,
			`separate_house_number` boolean NOT NULL,
			`customs_declarations` boolean NOT NULL,
			`requires_email` boolean NOT NULL,
			`requires_phone` boolean NOT NULL,
			`requires_size` boolean NOT NULL,
			`disallows_cod` boolean NOT NULL,
			`country` varchar(255) NOT NULL,
			`currency` varchar(255) NOT NULL,
			`max_weight` float NOT NULL,
			`deleted` boolean NOT NULL,
			UNIQUE (`id`)
		) ENGINE=MyISAM;';
    }

    /**
     * @return string
     */
    private function getCreateVendorTableSQL() {
        return 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_vendor` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`carrier_id` int(11) NULL,
		`packeta_id` varchar(10) NULL,
		`cart_name` varchar(255) NULL,
		`free_shipping_limit` float NULL,
		`is_enabled` tinyint(1) NOT NULL DEFAULT 0,
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    }

    /**
     * @return string
     */
    private function getCreateVendorPriceTableSQL() {
        return 'CREATE TABLE `' . DB_PREFIX . 'zasilkovna_vendor_price` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`vendor_id` int(11) NOT NULL,
		`max_weight` decimal(10,3) NOT NULL,
		`price` float NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    }

    /**
     * Alters database schema
     * @param string $oldVersion version before upgrade
     * @throws UpgradeException
     */
    public function upgradeSchema($oldVersion)
    {
        $queries = [];

        if ($oldVersion && version_compare($oldVersion, '2.0.4') < 0) {
            $queries[] = "ALTER TABLE `" . DB_PREFIX . "zasilkovna_orders`
				ADD COLUMN `carrier_pickup_point` VARCHAR(40) NULL
				COMMENT 'Code of selected carrier pickup point related to branch_id' AFTER `branch_name`;";
            $queries[] = "ALTER TABLE `" . DB_PREFIX . "zasilkovna_orders`
				ADD COLUMN `is_carrier` TINYINT(1) NOT NULL DEFAULT 0
				COMMENT 'Tells if branch_id is carrier' AFTER `carrier_pickup_point`;";
            $queries[] = "ALTER TABLE `" . DB_PREFIX . "zasilkovna_weight_rules`
				CHANGE `min_weight` `min_weight` decimal(10,2) NOT NULL DEFAULT 0;";
            $queries[] = "ALTER TABLE `" . DB_PREFIX . "zasilkovna_weight_rules`
				CHANGE `max_weight` `max_weight` decimal(10,2) NOT NULL DEFAULT 0;";
        }
        if ($oldVersion && version_compare($oldVersion, '2.1.0') < 0) {
            $queries[] = $this->getCreateCarriersTableSQL();
            $queries[] = "ALTER TABLE `" . DB_PREFIX . "zasilkovna_weight_rules` DROP `min_weight`;";
        }

        //TODO: před vydáním upravit číslo verze na 3.0
        if ($oldVersion && version_compare($oldVersion, '2.2.0') < 0) {
            $queries[] = sprintf("
				DELETE `zo` FROM `%s` `zo`
				LEFT JOIN `%s` `o` ON `o`.`order_id` = `zo`.`order_id` 
				WHERE `zo`.`exported` IS NULL 
					AND `o`.`shipping_code` NOT LIKE 'zasilkovna%%'
				",
                DB_PREFIX . 'zasilkovna_orders',
                DB_PREFIX . 'order');
            $queries[] = $this->getCreateVendorTableSQL();
            $queries[] = $this->getCreateVendorPriceTableSQL();
        }

        foreach ($queries as $query) {
            try {
                $this->db->query($query);
            } catch (Exception $exception) {
                $this->log->write('Exception "' . $exception->getMessage() . '" was thrown during execution of SQL query: ' . $query);
                throw new UpgradeException($exception->getMessage());
            }
        }
    }

    public function installEvents()
    {
        // new events for processing additional data
        // source and target must be in the same part of e-shop (catalog or admin)
        $this->load->model('setting/event');

        // add new cart here 1/3
        $events = [
            'catalog/controller/checkout/confirm/after' => 'extension/module/zasilkovna/saveOrderData',
            'catalog/controller/checkout/success/before' => 'extension/module/zasilkovna/sessionCleanup',
            'catalog/controller/checkout/checkout/before' => 'extension/module/zasilkovna/addStyleAndScript',
            'catalog/controller/checkout/shipping_address/save/before' => 'extension/module/zasilkovna/sessionCheckOnShippingChange',
            'catalog/controller/checkout/guest_shipping/save/before' => 'extension/module/zasilkovna/sessionCheckOnShippingChangeGuest',
            'catalog/controller/checkout/guest/save/before' => 'extension/module/zasilkovna/sessionCheckOnShippingChangeGuest',
            'catalog/controller/journal3/checkout/save/before' => 'extension/module/zasilkovna/journal3CheckoutSave',
            'catalog/controller/journal3/checkout/save/after' => 'extension/module/zasilkovna/journal3SaveOrderData',
            'catalog/controller/api/order/edit/after' => 'extension/module/zasilkovna/handleApiOrderEditAfter',
            'admin/view/common/column_left/before' => 'extension/shipping/zasilkovna/adminMenuExtension'
        ];

        $this->model_setting_event->deleteEventByCode(self::EVENT_CODE);
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
        $tableNames = [
            'zasilkovna_weight_rules',
            'zasilkovna_shipping_rules',
            'zasilkovna_orders',
            'zasilkovna_carrier',
            'zasilkovna_vendor',
            'zasilkovna_vendor_price'
        ];
        foreach ($tableNames as $shortTableName) {
            $sql = 'DROP TABLE IF EXISTS `' . DB_PREFIX . $shortTableName . '`;';
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
