<?php

namespace Packetery\DAL\Mapper;

use Packetery\DAL\Entity\Packeta;

class PacketaMapper {
    /**
     * @param array $packetaData
     * @return Packeta
     */
    public function createFromData(array $packetaData) {
        return new Packeta(
            $packetaData['id'],
            $packetaData['name'],
            $packetaData['country'],
            $packetaData['group']
        );
    }
}
