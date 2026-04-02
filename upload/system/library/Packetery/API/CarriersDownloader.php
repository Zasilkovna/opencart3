<?php

namespace Packetery\API;

use Packetery\API\Exceptions\DownloadException;

class CarriersDownloader
{
	const API_URL = 'https://pickup-point.api.packeta.com/v5/%s/carrier/json';
	const HTTP_TIMEOUT = 5;
	const ERROR_DOWNLOAD_FAILED = 'carrier_download_failed';
	const ERROR_INVALID_JSON = 'carrier_invalid_json';

	/** @var string */
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
	 * @throws DownloadException
	 */
	public function fetchAsArray()
	{
		list($json, $httpCode) = $this->downloadJson();
		$decoded = json_decode($json, true);

		$errorMessage = $this->extractFeedErrorMessage($decoded);
		if ($errorMessage !== '') {
			$errorCode = ($httpCode !== null ? (int)$httpCode : 0);
			throw new DownloadException($errorMessage, $errorCode);
		}

		if ($httpCode !== null && $httpCode >= 400) {
			throw new DownloadException(self::ERROR_DOWNLOAD_FAILED, (int)$httpCode);
		}

		if (isset($decoded[0]) && is_array($decoded[0])) {
			return $decoded;
		}

		if ($decoded === []) {
			return null;
		}

		throw new DownloadException(self::ERROR_INVALID_JSON);
	}

	/**
	 * @throws DownloadException
	 */
	private function downloadJson()
	{
		$url = sprintf(self::API_URL, $this->apiKey);
		$context = stream_context_create([
			'http' => [
				'method' => 'GET',
				'timeout' => self::HTTP_TIMEOUT,
				'protocol_version' => 1.1,
				'ignore_errors' => true, // to capture error responses
			],
			'ssl' => [
				'verify_peer' => true,
				'verify_peer_name' => true,
			],
		]);

		set_error_handler(
			function ($severity, $message) {
				throw new DownloadException($message);
			}
		);

		try {
			$result = file_get_contents($url, false, $context);
		} finally {
			restore_error_handler();
		}

		if ($result === false) {
			throw new DownloadException(self::ERROR_DOWNLOAD_FAILED);
		}

		$headers = isset($http_response_header) ? $http_response_header : [];
		$httpCode = $this->getHttpStatusCodeFromHeaders($headers);

		return [$result, $httpCode];
	}

	/**
	 * @param mixed $decoded
	 * @return string
	 */
	private function extractFeedErrorMessage($decoded)
	{
		if (!is_array($decoded)) {
			return '';
		}

		if (isset($decoded['error']) && is_string($decoded['error']) && $decoded['error'] !== '') {
			return $decoded['error'];
		}

		return '';
	}

	/**
	 * @return int|null
	 */
	private function getHttpStatusCodeFromHeaders(array $headers)
	{
		if (!isset($headers[0]) || !is_string($headers[0])) {
			return null;
		}

		if (preg_match('/^HTTP\/\d+(?:\.\d+)?\s+(\d{3})(?:\s|$)/i', $headers[0], $matches) !== 1) {
			return null;
		}

		return (int)$matches[1];
	}

}
