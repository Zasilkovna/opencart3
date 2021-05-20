<?php

namespace Packetery\API;

class CarriersManager
{
	const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';
	private $apiKey;

	/**
	 * @param string $apiKey
	 */
	public function __construct($apiKey)
	{
		$this->apiKey = $apiKey;
	}

	/**
	 * @return array|null
	 */
	public function fetch()
	{
		$url = sprintf(self::API_URL, $this->apiKey);
		$client = new GuzzleHttp\Client();
		$res = $client->get($url);
		$json = $res->getBody();

		if ($json) {
			$carriersData = json_decode($json, true);
			if (isset($carriersData['carriers'])) {
				return $carriersData['carriers'];
			}
		}
		return null;
	}

}
