<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Web;

use JuanchoSL\SocketClient\Traits\EncodingTrait;
use JuanchoSL\SocketClient\Traits\HandshakeTrait;
use JuanchoSL\SocketClient\Types\Stream\StreamSocketClient;

class WebSocketClient extends StreamSocketClient
{

    use HandshakeTrait, EncodingTrait;

    public function connect(): bool
    {
        parent::connect();
        $result = $this->handshake();
        if ($result->hasHeader('Sec-websocket-version')) {
            $this->encoded = +$result->getHeaderLine('Sec-websocket-version');
        }
        return $this->isConnected();
    }

}