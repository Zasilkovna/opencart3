<?php

namespace Packetery\API;

class KeyValidator
{
    /**
     * @param string $apiKey
     * @return bool
     */
    public function validateFormat($apiKey)
    {
        return (bool)preg_match('/^[a-z\d]{16}$/', $apiKey);
    }
}
