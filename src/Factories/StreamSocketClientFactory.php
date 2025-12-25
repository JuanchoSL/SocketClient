<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Factories;

use JuanchoSL\HttpData\Factories\UriFactory;
use JuanchoSL\SocketClient\Types\Stream\SecureStreamSocketClient;
use JuanchoSL\SocketClient\Types\Stream\StreamSocketClient;
use JuanchoSL\SocketClient\Types\Web\SecureWebSocketClient;
use JuanchoSL\SocketClient\Types\Web\WebSocketClient;
use Psr\Http\Message\UriInterface;

class StreamSocketClientFactory
{
    public function createFromUri(UriInterface $uri)
    {
        if (in_array($uri->getScheme(), ['wss'])) {
            return new SecureWebSocketClient($uri->withScheme('ssl'));
        } elseif (in_array($uri->getScheme(), ['ws'])) {
            return new WebSocketClient($uri->withScheme('tcp'));
        } elseif (in_array($uri->getScheme(), ['sftp', 'ftps', 'ssl', 'tls'])) {
            return new SecureStreamSocketClient($uri);
        } elseif (in_array($uri->getScheme(), ['ftp', 'tcp', 'upd'])) {
            return new StreamSocketClient($uri);
        }
    }

    public function createFromUrl(string $uri)
    {
        return $this->createFromUri((new UriFactory)->createUri($uri));
    }

}