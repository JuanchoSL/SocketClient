<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Contracts;

interface SecureConnectionInterface
{

    /**
     * Enable or disable the encrypted data comunication into stream
     * @param bool $status The new crypto status
     * @return bool tru if change has been applyed
     */
    public function setCrypto(bool $status): bool;
}