<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Stream;

use JuanchoSL\SocketClient\Contracts\CommunicationInterface;
use JuanchoSL\SocketClient\Contracts\ConnectionInterface;
use JuanchoSL\SocketClient\Contracts\SecureConnectionInterface;
use JuanchoSL\SocketClient\Traits\EncodingTrait;
use JuanchoSL\SocketClient\Traits\SecureTrait;
use Psr\Log\LoggerAwareInterface;

class SecureStreamSocketClient extends StreamSocketClient implements LoggerAwareInterface, ConnectionInterface, CommunicationInterface, SecureConnectionInterface
{

    use EncodingTrait, SecureTrait;

}