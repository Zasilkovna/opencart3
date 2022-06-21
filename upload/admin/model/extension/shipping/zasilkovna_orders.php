<?php
require_once(__DIR__ . '/zasilkovna_common.php');

/**
 * Model for additional order data of extension for zasilkovna.
 *
 * @property Config $config
 * @property \Cart\Currency $currency
 * @property DB $db
 * @property Loader $load
 * @property ModelSettingStore $model_setting_store
 * @property Request $request
 */
class ModelExtensionShippingZasilkovnaOrders extends ZasilkovnaCommon {

	/** @var string full name of DB table with additional order data (including prefix) */
	const TABLE_NAME = DB_PREFIX . 'zasilkovna_orders';
	/** @var string full name of DB table with base order data */
	const BASE_ORDER_TABLE_NAME = DB_PREFIX . 'order';

	/** @var string name of filter parameter - order ID */
	const FILTER_ORDER_ID = 'filter_order_id';
	/** @var string name of filter parameter - name of customer (first name, surname) */
	const FILTER_CUSTOMER = 'filter_customer';
	/** @var string name of filter parameter - start order date */
	const FILTER_ORDER_DATE_FROM = 'filter_order_date_from';
	/** @var string name of filter parameter - end order date */
	const FILTER_ORDER_DATE_TO = 'filter_order_date_to';
	/** @var string name of filter parameter - description name of Zasilkovna branch */
	const FILTER_BRANCH_NAME_OR_ID = 'filter_branch_name';
	/** @var string name of filter parameter - start date of export date to CSV */
	const FILTER_EXPORT_DATE_FROM = 'filter_export_date_from';
	/** @var string name of filter parameter - end date of export date to CSV */
	const FILTER_EXPORT_DATE_TO = 'filter_export_date_to';
	/** @var string name of filter parameter - status of export (exported, not exported, all) */
	const FILTER_EXPORTED = 'filter_exported';

	/** @var string name of list parameter - column for sorting */
	const PARAM_SORT_COLUMN = 'sort';
	/** @var string name of list parameter - order of sorting */
	const PARAM_SORT_DIRECTION = 'order';
	/** @var string name of list parameter - page number for list */
	const PARAM_PAGE_NUMBER = 'page';

	/**
	 * Get parameters from url (filter, sorting, paging).
	 * Returns parametrs as associative array.
	 *
	 * @return array
	 */
	public function getUrlParameters() {
		$paramData = [];
		$filterData = [];

		// get values of filter parameters
		$filterParamList = [
			self::FILTER_ORDER_ID,
			self::FILTER_CUSTOMER,
			self::FILTER_ORDER_DATE_FROM,
			self::FILTER_ORDER_DATE_TO,
			self::FILTER_BRANCH_NAME_OR_ID,
			self::FILTER_EXPORT_DATE_FROM,
			self::FILTER_EXPORT_DATE_TO,
			self::FILTER_EXPORTED
		];

		foreach ($filterParamList as $filterParamName) {
			if (!empty($this->request->get[$filterParamName])) {
				$filterData[$filterParamName] = $this->request->get[$filterParamName];
			}
			else {
				$filterData[$filterParamName] = '';
			}
		}
		// overwrite default value of "exported" parameter to "not exported"
		if (empty($filterData[self::FILTER_EXPORTED])) {
			$filterData[self::FILTER_EXPORTED] = 'not_exported';
		}

		$allowedSortColumns = ['o.order_id', 'customer', 'order_status_id', 'o.total', 'date_added', 'oz.branch_name', 'exported'];
		if (!empty($this->request->get[self::PARAM_SORT_COLUMN]) && in_array($this->request->get[self::PARAM_SORT_COLUMN], $allowedSortColumns)) {
			$sortColumn = $this->request->get[self::PARAM_SORT_COLUMN];
		}
		else {
			$sortColumn = 'o.order_id';
		}

		$allowedSortDirections = ['ASC', 'DESC'];
		if (!empty($this->request->get[self::PARAM_SORT_DIRECTION]) && in_array($this->request->get[self::PARAM_SORT_DIRECTION], $allowedSortDirections)) {
			$sortDirection = $this->request->get[self::PARAM_SORT_DIRECTION];
		}
		else {
			$sortDirection = 'DESC';
		}

		if (!empty($this->request->get[self::PARAM_PAGE_NUMBER])) {
			$pageNumber = (int) $this->request->get[self::PARAM_PAGE_NUMBER];
			if ($pageNumber <= 0) {
				$pageNumber = 1;
			}
		}
		else {
			$pageNumber = 1;
		}

		$paramData = [
			'filterData' => $filterData,
			self::PARAM_SORT_COLUMN => $sortColumn,
			self::PARAM_SORT_DIRECTION => $sortDirection,
			self::PARAM_PAGE_NUMBER => $pageNumber
		];

		return $paramData;
	}

	/**
	 * Creates additional sql conditions for list of orders.
	 *
	 * @param array $filterData filter parameters
	 * @return string
	 */
	private function createFilterConditions(array $filterData) {
		$sqlConditions = [];

		// filter by selected order statuses selected in global configuration
		$orderStatuses = $this->config->get('shipping_zasilkovna_order_statuses');
		if (!empty($orderStatuses)) {
			$orderStatusesString = '';
			foreach ($orderStatuses as $status) {
				$orderStatusesString .= (int) $status . ', ';
			}
			$orderStatusesString = substr($orderStatusesString, 0, -2);
			$sqlConditions[] = ' `o`.`order_status_id` IN (' . $orderStatusesString . ') ';
		} else {
			$sqlConditions[] = ' `o`.`order_status_id` > 0 ';
		}

		if (!empty($filterData[self::FILTER_ORDER_ID])) {
			$sqlConditions[] = ' `o`.`order_id`=' . (int) $filterData[self::FILTER_ORDER_ID] . ' ';
		}

		if (!empty($filterData[self::FILTER_CUSTOMER])) {
			$sqlConditions[] = ' CONCAT(`o`.`firstname`, " ", `o`.`lastname`) LIKE "%' . $this->db->escape($filterData[self::FILTER_CUSTOMER]) . '%" ';
		}

		if (!empty($filterData[self::FILTER_ORDER_DATE_FROM])) {
			$sqlConditions[] = ' `o`.`date_added` >= "' . $this->db->escape($filterData[self::FILTER_ORDER_DATE_FROM]) . '" ';
		}
		if (!empty($filterData[self::FILTER_ORDER_DATE_TO])) {
			$sqlConditions[] = ' `o`.`date_added` <= "' . $this->db->escape($filterData[self::FILTER_ORDER_DATE_TO]) . ' 23:59:59" ';
		}

		if (!empty($filterData[self::FILTER_BRANCH_NAME_OR_ID])) {
			$sqlConditions[] = ' CONCAT(`oz`.`branch_name`, `oz`.`branch_id`) like "%' . $filterData[self::FILTER_BRANCH_NAME_OR_ID] . '%" ';
		}

		if (!empty($filterData[self::FILTER_EXPORT_DATE_FROM])) {
			$sqlConditions[] = ' `oz`.`exported` >= "' . $this->db->escape($filterData[self::FILTER_EXPORT_DATE_FROM]) . '" ';
		}
		if (!empty($filterData[self::FILTER_EXPORT_DATE_TO])) {
			$sqlConditions[] = ' `oz`.`exported` <= "' . $this->db->escape($filterData[self::FILTER_EXPORT_DATE_TO]) . ' 23:59:59" ';
		}

		if (!empty($filterData[self::FILTER_EXPORTED])) {
			switch ($filterData[self::FILTER_EXPORTED]) {
				case 'exported':
					$sqlConditions[] = ' `oz`.`exported` IS NOT NULL ';
					break;
				case 'not_exported':
					$sqlConditions[] = ' `oz`.`exported` IS NULL ';
					break;
				default: // value "all" and other values
					break;
			}
		}

		return implode(' AND ', $sqlConditions);
	}

	/**
	 * Returns counts of "Zasilkovna" orders according to current filters.
	 *
	 * @param array $filterData filter parameters
	 * @return int count of orders
	 */
	public function getOrdersCount(array $filterData) {
		$sqlConditions = $this->createFilterConditions($filterData);
		$whereClause = (($sqlConditions ? ' WHERE ' . $sqlConditions : ''));
		$sqlQueryTemplate = 'SELECT COUNT(*) AS `total` FROM `%s` `o` JOIN `%s` `oz` ON (`oz`.`order_id` = `o`.`order_id`) %s';
		$sqlQuery = sprintf($sqlQueryTemplate, self::BASE_ORDER_TABLE_NAME, self::TABLE_NAME, $whereClause);

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);
		return (int) $queryResult->row['total'];
	}

	/**
	 * Returns list of "Zasilkovna" orders including additional data according to current filters.
	 *
	 * @param array $paramData url parameters
	 * @return array list of orders
	 */
	public function getOrders(array $paramData) {
		$sqlConditions = $this->createFilterConditions($paramData['filterData']);
		$pageSize = $this->config->get('config_limit_admin');
		$queryOffset = ($paramData[self::PARAM_PAGE_NUMBER] - 1) * $pageSize;
		$whereClause = (($sqlConditions ? ' WHERE ' . $sqlConditions : ''));

		$sqlQueryTemplate = 'SELECT `o`.`order_id`, CONCAT(o.firstname, " ", o.lastname) AS customer, o.order_status_id, '
			. ' `o`.`date_added`, `o`.`payment_code`, `o`.`total`, `oz`.`total_weight`, `o`.`currency_code`, `o`.`currency_value`, `oz`.`branch_id`, `oz`.`branch_name`, `oz`.`exported`'
			. ' FROM `%s` `o` JOIN `%s` `oz` ON (`oz`.`order_id` = `o`.`order_id`) %s'
			// add sorting and paging parts (variables with column name and direction is already sanitized in getUrlParameters())
			. ' ORDER BY %s %s LIMIT %s, %s';
		$sqlQuery = sprintf(
			$sqlQueryTemplate,
			self::BASE_ORDER_TABLE_NAME,
			self::TABLE_NAME,
			$whereClause,
			$paramData[self::PARAM_SORT_COLUMN],
			$paramData[self::PARAM_SORT_DIRECTION],
			(int)$queryOffset,
			(int)$pageSize
		);

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);
		return $queryResult->rows;
	}

    /**
     * @param array $orderIds
     * @return array
     */
    public function getPacketsByOrderIds($orderIds)
    {
        if (empty($orderIds)) {
            return [];
        }

        $ids = implode(',', $orderIds);

        $sql = <<<SQL
            SELECT `o`.`packet_id` FROM `%s` `o` WHERE `o`.`packet_id` IS NOT NULL AND `o`.`order_id` IN (%s)
SQL;

        $ids = $this->db->query(sprintf($sql, self::TABLE_NAME, $ids))->rows;
        var_dump($ids);
        die('STOP');
        return  $ids;
    }



    /**
     * @param array $ids
     * @return \Packetery\API\Request\CreatePacket[]
     */
    public function getApiExportReuqest(array $ids)
    {
        // load list of payment method considered as "cash on delivery"
        $codPaymentMethod = $this->config->get('shipping_zasilkovna_cash_on_delivery_methods');

        // load list of e-shop identifiers from module settings
        $eshopIdentifierList = $this->getEshopIdentifiers();

        $sqlQueryTemplate = 'SELECT `o`.`order_id`, `o`.`store_id`, `o`.`shipping_firstname`, `o`.`shipping_lastname`, `o`.`shipping_company`,'
            . ' `o`.`email`, `o`.`telephone`, `o`.`currency_code`, `o`.`currency_value`, `o`.`total`, `oz`.`total_weight`, `oz`.`branch_id`,'
            . ' `oz`.`carrier_pickup_point`,'
            . ' `o`.`shipping_address_1`, `o`.`shipping_city`, `o`.`shipping_postcode`, `o`.`payment_code`, `o`.`invoice_no`, `o`.`invoice_prefix` '
            . ' FROM `%s` `o` JOIN `%s` `oz` ON (`oz`.`order_id` = `o`.`order_id`) WHERE `o`.`order_id` IN (' . implode(',', $ids) .') AND `oz`.`packet_id` IS NULL';

        $sqlQuery = sprintf(
            $sqlQueryTemplate,
            self::BASE_ORDER_TABLE_NAME,
            self::TABLE_NAME
        );

        /** @var StdClass $queryResult */
        $queryResult = $this->db->query($sqlQuery);
        $requestPackets = [];
        foreach ($queryResult->rows as $dbRow) {
            $priceInTargetCurrency = $this->currency->format($dbRow['total'], $dbRow['currency_code'], $dbRow['currency_value'], false);

            // set value of "cash on delivery" according to payment method
            if (in_array($dbRow['payment_code'], $codPaymentMethod)) {
                $cod = $priceInTargetCurrency;
            }
            else {
                $cod = '';
            }

            $eshopIdentifier = (isset($eshopIdentifierList[$dbRow['store_id']])) ? $eshopIdentifierList[$dbRow['store_id']] : '';

            $packetNumberSource = $this->config->get('shipping_zasilkovna_packet_number_source');
            $orderNumber = $dbRow['order_id'];
            if ($packetNumberSource === 'invoice_number') {
                if (!$dbRow['invoice_no']) {
                    continue;
                }

                $orderNumber = $dbRow['invoice_prefix'] . $dbRow['invoice_no'];
            }

            $requestPacket = new \Packetery\API\Request\CreatePacket();
            // street number contains also house number, e-shop doesn't have separate items for street and house number
            $requestPacket
                ->setOrderNumber($orderNumber)
                ->setName($dbRow['shipping_firstname'])
                ->setSurname($dbRow['shipping_lastname'])
                ->setCompany($dbRow['shipping_company'])
                ->setEmail($dbRow['email'])
                ->setPhone($dbRow['telephone'])
                ->setCod($cod)
                ->setCurrency($dbRow['currency_code'])
                ->setValue((double) $priceInTargetCurrency)
                ->setWeight($dbRow['total_weight'])
                ->setPickupPointOrCarrierId($dbRow['branch_id'])
                ->setEshop($eshopIdentifier)
                ->setCarrierPickupPoint((string) $dbRow['carrier_pickup_point'])
                ->setStreet($dbRow['shipping_address_1'])
                ->setCity($dbRow['shipping_city'])
                ->setZip($dbRow['shipping_postcode']);

            $requestPackets[$dbRow['order_id']] = $requestPacket;
        }

        return $requestPackets;

    }

	/**
	 * Returns raw data for CSV export of orders.
	 *
	 * @param array $paramData url parameters for order list (filter parameters)
	 * @param string $scope of export (all or selected record)
	 * @param array $orderIdList array of selected order IDs
	 * @return array raw data for CSV export
	 */
	public function getCsvExportData(array $paramData, $scope, $orderIdList = []) {
		// load list of payment method considered as "cash on delivery"
		$codPaymentMethod = $this->config->get('shipping_zasilkovna_cash_on_delivery_methods');

		// load list of e-shop identifiers from module settings
		$eshopIdentifierList = $this->getEshopIdentifiers();

		// load list of orders including additional order data including filters used in order grid
		$filterConditions = $this->createFilterConditions($paramData['filterData']);
		if ('selected' === $scope && !empty($orderIdList)) {
			// function implode cannot be used because of possible sql injection
			$orderIdListString = '';
			foreach ($orderIdList as $orderId) {
				$orderIdListString .= (int) $orderId . ',';
			}
			$orderIdListString = substr($orderIdListString, 0 , -1);

			$filterConditions = ' `o`.`order_id` IN (' . $orderIdListString . ') ' . ($filterConditions ? ' AND ' . $filterConditions : '');
		}
		$whereClause = (($filterConditions ? ' WHERE ' . $filterConditions : ''));

		$sqlQueryTemplate = 'SELECT `o`.`order_id`, `o`.`store_id`, `o`.`shipping_firstname`, `o`.`shipping_lastname`, `o`.`shipping_company`,'
			. ' `o`.`email`, `o`.`telephone`, `o`.`currency_code`, `o`.`currency_value`, `o`.`total`, `oz`.`total_weight`, `oz`.`branch_id`,'
			. ' `oz`.`carrier_pickup_point`,'
			. ' `o`.`shipping_address_1`, `o`.`shipping_city`, `o`.`shipping_postcode`, `o`.`payment_code`, `o`.`invoice_no`, `o`.`invoice_prefix` '
			. ' FROM `%s` `o` JOIN `%s` `oz` ON (`oz`.`order_id` = `o`.`order_id`) %s '
			// add sorting parts (variables with column name and direction is already sanitized in getUrlParameters())
			. ' ORDER BY %s %s';

		$sqlQuery = sprintf(
			$sqlQueryTemplate,
			self::BASE_ORDER_TABLE_NAME,
			self::TABLE_NAME,
			$whereClause,
			$paramData[self::PARAM_SORT_COLUMN],
			$paramData[self::PARAM_SORT_DIRECTION]
		);

		/** @var StdClass $queryResult */
		$queryResult = $this->db->query($sqlQuery);

		// format data for CSV export
		$csvRawData = [];
		$exportedOrders = [];
		foreach ($queryResult->rows as $dbRow) {
			// Parts of order price:
			// order.total - total amount of order in main store currency
			// order.currency_code - iso code of target currency
			// order.currency_value - ratio between main store currency and target currency
			$priceInTargetCurrency = $this->currency->format($dbRow['total'], $dbRow['currency_code'], $dbRow['currency_value'], false);

			// set value of "cash on delivery" according to payment method
			if (in_array($dbRow['payment_code'], $codPaymentMethod)) {
				$cod = $priceInTargetCurrency;
			}
			else {
				$cod = '';
			}

			$eshopIdentifier = (isset($eshopIdentifierList[$dbRow['store_id']])) ? $eshopIdentifierList[$dbRow['store_id']] : '';

			$packetNumberSource = $this->config->get('shipping_zasilkovna_packet_number_source');
			$orderNumber = $dbRow['order_id'];
			if ($packetNumberSource === 'invoice_number') {
				if (!$dbRow['invoice_no']) {
					continue;
				}

				$orderNumber = $dbRow['invoice_prefix'] . $dbRow['invoice_no'];
			}

			$csvRawData[] = [
				'Reserved'          => '',
				'OrderNumber'       => $orderNumber,
				'Name'              => $dbRow['shipping_firstname'],
				'Surname'           => $dbRow['shipping_lastname'],
				'Company'           => $dbRow['shipping_company'],
				'E-mail'            => $dbRow['email'],
				'Phone'             => $dbRow['telephone'],
				'COD'               => $cod,
				'Currency'          => $dbRow['currency_code'],
				'Value'             => (double) $priceInTargetCurrency,
				'Weight'            => $dbRow['total_weight'],
				'Pickupoint'        => $dbRow['branch_id'],
				'SenderLabel'       => $eshopIdentifier,
				'AdultContent'      => '',
				'DelayedDelivery'   => '',
				// street number contains also house number, e-shop doesn't have separate items for street and house number
				'Street'            => $dbRow['shipping_address_1'],
				'House Number'      => '',
				'City'              => $dbRow['shipping_city'],
				'ZIP'               => $dbRow['shipping_postcode'],
				'CarrierPickup'     => (string) $dbRow['carrier_pickup_point'],
				'Width'             => '',
				'Height'            => '',
				'Depth'             => '',
			];

			$exportedOrders[] = $dbRow['order_id'];
		}

		// mark all exported records as exported (set current date and time)
		if (!empty($exportedOrders)) {
			$sqlQueryTemplate = 'UPDATE `%s` SET `exported` = NOW() WHERE `order_id` IN (%s);';
			// direct use of implode method is possible because order ID is received from DB record
			$sqlQuery = sprintf($sqlQueryTemplate, self::TABLE_NAME, implode(',', $exportedOrders));
			$this->db->query($sqlQuery);
		}

		return $csvRawData;
	}

	/**
	 * Returns list of e-shop identifiers for defined stores.
	 *
	 * @return array list of e-shop identifiers from settings
	 */
	private function getEshopIdentifiers() {
		$result = [
			0 => $this->config->get('shipping_zasilkovna_eshop_identifier_0')
		];

		$storeList = $this->model_setting_store->getStores();
		foreach ($storeList as $storeItem) {
			$configItemName = 'shipping_zasilkovna_eshop_identifier_' . $storeItem['store_id'];
			$result[$storeItem['store_id']] = $this->config->get($configItemName);
		}

		return $result;
	}

	/**
	 * @param $id
	 * @param array $data
	 */
	public function updateOrder($id, array $data) {
		$table = self::TABLE_NAME;
		$weight = (float)$data['weight'];
		$id = (int)$id;
		$this->db->query("UPDATE `{$table}` SET total_weight = {$weight} WHERE order_id = {$id}");
	}
}
