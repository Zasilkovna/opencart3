<?php

namespace Packetery\API\Request;

class PacketsLabelsPdf
{
    /** @var array */
    private $packetIds;

    /** @var string */
    private $format;

    /** @var int */
    private $offset;

    /**
     * @param array $packetIds
     * @return \Packetery\API\Request\PacketsLabelsPdf
     */
    public function setPacketIds(array $packetIds)
    {
        $this->packetIds = $packetIds;
        return $this;
    }

    /**
     * @param string $format
     * @return \Packetery\API\Request\PacketsLabelsPdf
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }

    /**
     * @param string $offset
     * @return \Packetery\API\Request\PacketsLabelsPdf
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

}
