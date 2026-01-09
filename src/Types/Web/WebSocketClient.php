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
        $time = microtime(true);
        $result = $this->handshake();
        if ($result->hasHeader('Sec-Websocket-Version')) {
            $this->encoded = +$result->getHeaderLine('Sec-Websocket-Version');
        }
        $this->logger?->debug("Handshake", [
            'encoded' => intval($this->encoded),
            'result' => (string) $result,
            'time' => number_format(microtime(true) - $time, 4, '.', '')
        ]);
        return $this->isConnected();
    }

}