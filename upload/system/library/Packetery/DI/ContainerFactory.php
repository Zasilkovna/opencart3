<?php

namespace Packetery\DI;

use Packetery\API\CarriersDownloader;
use Packetery\DAL\TablePrefixer;

class ContainerFactory {
    /** @var Container|null */
    private static $instance;

    /**
     * @return Container
     * @throws \ReflectionException
     */
    public static function create(\Registry $registry) {
        if (self::$instance === null) {
            self::$instance = new Container($registry);
        }

        self::$instance->register(
            CarriersDownloader::class,
            function ()  {
                $apiKey = self::$instance->get(\Config::class)->get('shipping_zasilkovna_api_key');
                return new CarriersDownloader($apiKey);
            }
        );

        self::$instance->register(
            TablePrefixer::class,
            function ()  {
                return new TablePrefixer(DB_PREFIX);
            }
        );

        return self::$instance;
    }
}
