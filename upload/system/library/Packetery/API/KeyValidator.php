<?php

namespace Packetery\API;

use Packetery\API\Exceptions\DownloadException;

class KeyValidator
{
	const API_URL = 'https://www.zasilkovna.cz/api/%s/test';

	/** @var \GuzzleHttp\Client */
	private $client;

	/**
	 * @param \GuzzleHttp\Client $client
	 */
	public function __construct(\GuzzleHttp\Client $client)
	{
		$this->client = $client;
	}

	/**
	 * @param string $apiKey
	 * @return bool
	 */
	public function validateFormat($apiKey)
	{
		return (bool)preg_match('/^[a-f\d]{16}$/', $apiKey);
	}

	/**
	 * @return bool
	 * @throws DownloadException
	 */
	public function validate($apiKey)
	{
		$result = $this->downloadResult($apiKey);
		return ($result == '1');
	}

	/**
	 * @return \GuzzleHttp\Stream\Stream
	 * @throws DownloadException
	 */
	private function downloadResult($apiKey)
	{
		$url = sprintf(self::API_URL, $apiKey);
		try {
			$result = $this->client->get($url);
		} catch (\GuzzleHttp\Exception\TransferException $exception) {
			throw new DownloadException($exception->getMessage());
		}

		return $result->getBody();
	}
}