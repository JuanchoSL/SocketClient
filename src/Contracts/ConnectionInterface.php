<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Contracts;

interface ConnectionInterface
{

    /**
     * Connect to the preconfigured socket
     * @return bool The result of the connection
     */
    public function connect(): bool;

    /**
     * Return the connection status of the configured socket
     * @return bool True if has been connected, false otherwise
     */
    public function isConnected(): bool;

    /**
     * Disconnect the socket
     * @return void
     */
    public function disconnect(): void;

    /**
     * Retrieve the headers/metadata of the stream
     * @return array The socket mewtadata
     */
    public function getMetadata(): array;
}