<?php

namespace Packetery\API;

class CarriersFetcher
{
	const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

	/** @var string */
	private $apiKey;

	/** @var \GuzzleHttp\Client */
	private $client;

	/**
	 * @param string $apiKey
	 */
	public function __construct($apiKey, \GuzzleHttp\Client $client)
	{
		$this->apiKey = $apiKey;
		$this->client = $client;
	}

	/**
	 * @return array|null
	 */
	public function fetch()
	{
		$url = sprintf(self::API_URL, $this->apiKey);
		$result = $this->client->get($url);
		$json = $result->getBody();

		return $this->getFromJson($json);
	}

	/**
	 * @param \GuzzleHttp\Stream\Stream $json
	 * @return array|null
	 */
	private function getFromJson($json)
	{
		if (!$json) {
			return null;
		}

		$carriersData = json_decode($json, true);
		if (!isset($carriersData['carriers'])) {
			return null;
		}

		return $carriersData['carriers'];
	}
}
