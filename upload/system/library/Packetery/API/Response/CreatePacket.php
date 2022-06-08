<?php

namespace Packetery\API\Response;

class CreatePacket
{
    /** @var int */
    private $id;

    /** @var string $barcode */
    private $barcode;

    /** @var string $barcodeText */
    private $barcodeText;

    public function __construct($id, $barcode, $barcodeText)
    {
        $this->id = $id;
        $this->barcode = $barcode;
        $this->barcodeText = $barcodeText;
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getBarcode() {
        return $this->barcode;
    }

    /**
     * @return string
     */
    public function getBarcodeText() {
        return $this->barcodeText;
    }

}