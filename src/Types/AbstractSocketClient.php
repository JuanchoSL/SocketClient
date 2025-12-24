<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types;

use JuanchoSL\SocketClient\Contracts\CommunicationInterface;
use JuanchoSL\SocketClient\Contracts\ConnectionInterface;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractSocketClient implements LoggerAwareInterface, ConnectionInterface, CommunicationInterface
{

    use LoggerAwareTrait;

    protected UriInterface $uri;

    protected bool $connected = false;
    protected $channel;
    protected string $scheme;
    protected string $host;
    protected int $port;

    public function __construct(UriInterface $uri)
    {
        $this->uri = $uri;
    }
    public function __destruct()
    {
        $this->disconnect();
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }
}