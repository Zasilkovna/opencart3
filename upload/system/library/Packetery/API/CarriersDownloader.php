<?php

namespace Packetery\API;

use Packetery\API\Exceptions\DownloadException;

class CarriersDownloader
{
    const API_URL = 'https://www.zasilkovna.cz/api/v4/%s/branch.json?address-delivery';

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
        $json = $this->downloadJson();

        return $this->getFromJson($json);
    }

    /**
     * @throws DownloadException
     */
    private function downloadJson()
    {
        $url = sprintf(self::API_URL, $this->apiKey);

        set_error_handler(
            function ($severity, $message) {
                throw new DownloadException($message);
            }
        );

        $result = file_get_contents($url);

        restore_error_handler();

        return $result;
    }

    /**
     * @param string $json
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
