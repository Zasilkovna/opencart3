<?php

namespace Packetery\API\Exceptions;

class CreatePacketAttributesFault extends \Exception
{
    /**
     * @var array
     */
    private $params;

    public function __construct($message = "", $code = 0, $previous = NULL, \stdClass $params)
    {
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

}