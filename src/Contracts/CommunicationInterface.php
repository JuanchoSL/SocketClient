<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Contracts;

interface CommunicationInterface
{

    /**
     * Enable or disable the blocking mode on the connected socket
     * @param bool $blocking_status The new blocking status
     * @return void
     */
    public function setBlockingMode(bool $blocking_status = true): bool;

    /**
     * Read the boffer form the connected socket
     * @param int $buffer The buffer size
     * @return string The data readed
     */
    public function read(int $buffer = 2048): string;

    /**
     * Write into the connected socket
     * @param string $data The data to write
     * @return bool True if the writed size is grether than 0
     */
    public function write(string $data): bool;
}