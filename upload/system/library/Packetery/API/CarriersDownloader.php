<?php

namespace Packetery\API;

class CarriersDownloader
{
	const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

	/** @var string */
	private $apiKey;

	/** @var \GuzzleHttp\Client */
	private $client;

	/**
	 * @param string $apiKey
	 * @param \GuzzleHttp\Client $client
	 */
	public function __construct($apiKey, \GuzzleHttp\Client $client)
	{
		$this->apiKey = $apiKey;
		$this->client = $client;
	}

	/**
	 * @return array|null
	 * @throws DownloadException
	 */
	public function fetchAsArray()
	{
		$url = sprintf(self::API_URL, $this->apiKey);
		try {
			$result = $this->client->get($url);
		} catch (\GuzzleHttp\Exception\TransferException $exception) {
			throw new DownloadException($exception->getMessage());
		}
		$json = $result->getBody();

		return $this->getFromJson($json);
	}

	/**
	 * @param \GuzzleHttp\Stream\Stream $json
	 * @return array|null
	 */
	private function getFromJson($json)
	{
		$carriersData = json_decode($json, true);
		if (!isset($carriersData['carriers'])) {
			return null;
		}

		return $carriersData['carriers'];
	}
}
