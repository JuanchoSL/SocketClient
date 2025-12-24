<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Web;

use JuanchoSL\SocketClient\Contracts\CommunicationInterface;
use JuanchoSL\SocketClient\Contracts\ConnectionInterface;
use JuanchoSL\SocketClient\Contracts\SecureConnectionInterface;
use JuanchoSL\SocketClient\Traits\SecureTrait;
use Psr\Log\LoggerAwareInterface;

class SecureWebSocketClient extends WebSocketClient implements LoggerAwareInterface, ConnectionInterface, CommunicationInterface, SecureConnectionInterface
{

    use SecureTrait;
    public function connect(): bool
    {
        parent::connect();
        //$this->setCrypto(true);
        $this->cert();
        return $this->isConnected();
    }
}