<?php

namespace Packetery\API;

use Packetery\API\Exceptions\CreatePacketAttributesFault;
use Packetery\API\Exceptions\CreatePacketFault;
use Packetery\API\Exceptions\IncorrectApiPasswordFault;

class Client
{
    const WSDL_URL = 'http://www.zasilkovna.cz/api/soap.wsdl';

    /**
     * @var string $apiPassword
     */
    private $apiPassword;

    /**
     * @param string $apiPassword
     * @return void
     */
    public function setApiPassword($apiPassword)
    {
        $this->apiPassword = $apiPassword;
    }

    /**
     * Submits packet data to Packeta API.
     *
     * @param Request\CreatePacket $request Packet attributes.
     *
     * @return Response\CreatePacket
     * @throws \Packetery\API\Exceptions\IncorrectApiPasswordFault
     * @throws \Exception
     */
    public function createPacket(Request\CreatePacket $request)
    {
        try {
            $soapClient = new \SoapClient(self::WSDL_URL, ['cache_wsdl' => WSDL_CACHE_NONE]);
            $result = $soapClient->__soapCall('createPacket', [$this->apiPassword, $request]);

            return new Response\CreatePacket($result->id, $result->barcode, $result->barcodeText);

        } catch ( \SoapFault $exception ) {

            if(isset($exception->detail->PacketAttributesFault)) {
                throw new CreatePacketAttributesFault($exception->getMessage(), 0, null, $exception->detail->PacketAttributesFault->attributes->fault);
            }

            if (isset($exception->detail->IncorrectApiPasswordFault)) {
                throw new IncorrectApiPasswordFault($exception->detail->IncorrectApiPasswordFault);
            }

            throw new CreatePacketFault($exception->getMessage());
        }
    }
}
