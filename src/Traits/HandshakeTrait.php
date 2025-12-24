<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Traits;

use Fig\Http\Message\RequestMethodInterface;
use JuanchoSL\HttpData\Bodies\Parsers\ResponseReader;
use JuanchoSL\HttpData\Factories\RequestFactory;
use JuanchoSL\HttpData\Factories\StreamFactory;
use JuanchoSL\HttpData\Factories\UriFactory;

trait HandshakeTrait
{
    protected function handshake()
    {
        return $this->handshakeHttp1();
    }
    protected function handshakeHttp1()
    {
        $message = (new RequestFactory())
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->uri)
            ->withProtocolVersion('1.1')
            ->withHeader('Upgrade', 'websocket')
            ->withHeader('Connection', 'Upgrade')
            ->withHeader('Sec-WebSocket-Key', base64_encode(uniqid()))
            ->withHeader('Sec-WebSocket-Version', '13')
            ->withHeader('Origin', gethostname())
        ;

        $this->write((string) $message);
        $response = $this->read();
        $response = new ResponseReader((new StreamFactory())->createStream($response));
        //echo "<pre>" . print_r($response, true);
        return $response = $response();
    }
}