<?php

namespace Packetery\API;

use Packetery\API\Exceptions\CreatePacketAttributesFault;
use Packetery\API\Exceptions\CreatePacketFault;
use Packetery\API\Exceptions\IncorrectApiPasswordFault;
use Packetery\API\Request\PacketsLabelsPdf;

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
                $isObject = is_object($exception->detail->PacketAttributesFault->attributes->fault);
                if ($isObject) {
                    $error[] = $exception->detail->PacketAttributesFault->attributes->fault;
                } else {
                    $error = $exception->detail->PacketAttributesFault->attributes->fault;
                }
                //var_dump($exception->detail->PacketAttributesFault->attributes->fault);
                //die('STOP');
                throw new CreatePacketAttributesFault($exception->getMessage(), 0, null, $error);
            }

            if (isset($exception->detail->IncorrectApiPasswordFault)) {
                throw new IncorrectApiPasswordFault($exception->detail->IncorrectApiPasswordFault);
            }

            throw new CreatePacketFault($exception->getMessage());
        }
    }

    public function packetsLabelsPdf(PacketsLabelsPdf $request)
    {
        try {
            $soapClient = new \SoapClient(self::WSDL_URL, ['cache_wsdl' => WSDL_CACHE_NONE]);
            $result = $soapClient->__soapCall('packetLabelsPdf', [$this->apiPassword, $request]);
        } catch ( \SoapFault $exception ) {
            die('CHYBA');
        }

        return 1;
        
    }
}
