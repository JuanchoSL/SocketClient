<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Listeners;

use Fig\Http\Message\StatusCodeInterface;
use JuanchoSL\DataManipulation\Manipulators\Strings\StringsManipulators;
use JuanchoSL\HttpData\Factories\ResponseFactory;
use JuanchoSL\HttpData\Factories\StreamFactory;
use JuanchoSL\SocketClient\Traits\EncodingTrait;
use JuanchoSL\SocketClient\Traits\SecureTrait;
use JuanchoSL\SocketClient\Types\Stream\StreamSocketClient;

class WebSocketServer extends StreamSocketClient
{
    use EncodingTrait, SecureTrait;
    protected array $clients = [];
    protected array $clientsconfig = [];
    protected int $timeout = 15;
    protected $wait = true;
    protected $masked = false;
    protected \Closure $on_receive;
    protected \Closure $on_idle;
    protected int $encrypt = STREAM_CRYPTO_METHOD_TLS_SERVER;

    public function connect(): bool
    {
        $context = $this->getContext();
        if (isset($this->pk, $this->crt)) {
            stream_context_set_option($context, 'ssl', 'local_cert', $this->crt);
            stream_context_set_option($context, 'ssl', 'local_pk', $this->pk);
        }
        $this->channel = stream_socket_server("{$this->uri->getScheme()}://{$this->uri->getHost()}:{$this->uri->getPort()}", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);
        if (false === $this->channel) {
            $this->getError();
        }
        //stream_set_timeout($this->channel, 5);
        return $this->connected = true;
    }

    public function send(string $data, string $destiny)
    {
        $this->logger?->debug($data, ['size' => mb_strlen($data),'metadata'=>stream_get_meta_data($this->clients[$destiny])]);
        return fwrite($this->clients[$destiny], $data, mb_strlen($data)) !== false;
    }

    public function receive($client = null)
    {
        $buffer_size = 1024;
        $buffer = '';
        do {
            $tmp = fread($client, $buffer_size);
            if ($tmp !== false) {
                $this->logger?->debug($tmp, ['buffer' => $buffer, 'tmp' => $tmp, 'size' => strlen($tmp)]);
                $buffer .= $tmp;
            } else {
                break;
            }
        } while (mb_strlen($tmp) >= $buffer_size);
        $this->logger?->debug($buffer);
        return trim($buffer, "\r\n");
    }

    public function listen()
    {
        if (($client = @stream_socket_accept($this->channel, empty($this->clients) ? -1 : 0, $peer1)) !== false) {
            $peerIndex = md5($peer1);
            if (!array_key_exists($peerIndex, $this->clients)) {
                $headers = $this->receive($client);
                $this->handshake($client, $headers);
                return null;
            }
        }
        // wait for any stream data
        if (!empty($this->clients)) {
            $write = null;
            $except = null;
            $read = $this->clients;
            if (stream_select($read, $write, $except, $this->timeout)) {
                foreach ($read as $peerIndex => $c) {
                    echo "Procesing {$peerIndex}" . PHP_EOL;
                    if (feof($c)) {
                        echo 'Connection closed ' . $peerIndex . PHP_EOL;
                        fclose($c);
                        unset($this->clients[$peerIndex]);
                        unset($this->clientsconfig[$peerIndex]);
                    } else {
                        $this->encoded = (array_key_exists($peerIndex, $this->clientsconfig)) ? +$this->clientsconfig[$peerIndex]['encoded'] : false;
                        $this->masked = $this->clientsconfig[$peerIndex]['masked'];
                        $contents = $this->receive($c);
                        if (!empty($contents)) {
                            echo "Raw: " . $contents . PHP_EOL;
                            $buf = $this->decode($contents);
                            echo "Decoded: " . $buf . PHP_EOL;
                            echo $peerIndex . ': ' . trim($buf) . PHP_EOL;
                        }
                        if (isset($this->on_receive, $buf) && $this->on_receive instanceof \Closure) {
                            $response = call_user_func($this->on_receive, $buf);
                            echo $response . PHP_EOL;
                            if ($this->wait && isset($response)) {
                                if (!$this->send($this->encode($response), $peerIndex)) {
                                    unset($this->clients[$peerIndex]);
                                    unset($this->clientsconfig[$peerIndex]);
                                }
                            }
                        }
                    }
                }
            } else {
                if (isset($this->on_idle) && $this->on_idle instanceof \Closure) {
                    $responseIdle = call_user_func($this->on_idle);
                    if (count($this->clients) > 0 && isset($responseIdle) && is_string($responseIdle)) {
                        foreach ($this->clients as $client => $socket) {
                            $this->send($this->encode($responseIdle), $client);
                        }
                    }
                    unset($responseIdle);
                }
            }
        }
    }

    protected function handshake(
        $client,
        $rcvd
    ) {
        $headers = array();
        $lines = (new StringsManipulators($rcvd))->eol("\r\n");
        $lines = preg_split("/\r\n/", $rcvd);
        foreach ($lines as $line) {
            $line = rtrim($line);
            if (preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
                $headers[$matches[1]] = $matches[2];
            }
        }
        $origin = $headers['Origin'] ?? stream_socket_get_name($client, true);
        $secKey = $headers['Sec-WebSocket-Key'];
        $secVersion = $headers['Sec-WebSocket-Version'] ?? 13;
        $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        $masked = (in_array($this->uri->getScheme(), ['tls', 'ssl','wss']));
        $ws_protocol = (in_array($this->uri->getScheme(), ['tls', 'ssl'])) ? 'wss' : 'ws';
        //hand shaking header
        $upgrade = (new ResponseFactory())->createResponse(StatusCodeInterface::STATUS_SWITCHING_PROTOCOLS, 'Web Socket Protocol Handshake')
        ->withHeader('Upgrade', 'websocket')
        ->withHeader('Connection', 'Upgrade')
        ->withHeader('Sec-WebSocket-Origin', $origin)
        ->withHeader('Sec-WebSocket-Location', "{$ws_protocol}://{$this->uri->getHost()}:{$this->uri->getPort()}")
        ->withHeader('Sec-WebSocket-Version', $secVersion)
        ->withHeader('Sec-WebSocket-Accept', $secAccept);
        /*
        ->withBody((new StreamFactory())->createStream('.'));
        $upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "Sec-WebSocket-Origin: $origin\r\n" .
        "Sec-WebSocket-Location: {$ws_protocol}://{$this->uri->getHost()}:{$this->uri->getPort()}\r\n" .
        "Sec-WebSocket-Version: {$secVersion}\r\n" .
        "Sec-WebSocket-Accept: $secAccept\r\n\r\n";
        */
        $peer = stream_socket_get_name($client, true);
        $this->logger->debug("handshake done...{peer} connected", ['peer' => $peer]);
        $peerIndex = md5($peer);
        $this->clients[$peerIndex] = $client;
        $this->clientsconfig[$peerIndex] = ['encoded' => $secVersion, 'peer' => $peer, 'masked' => $masked];
        $this->send((string) (new StringsManipulators((string)$upgrade))->eol("\r\n"), $peerIndex);
    }

    public function onReceive(\Closure $callback)
    {
        $this->on_receive = $callback;
        return $this;
    }

    public function onIdle(\Closure $callback)
    {
        $this->on_idle = $callback;
        return $this;
    }

    public function persistentListen()
    {
        $this->connect();
        if ($this->isConnected()) {
            while (true) {
                $this->listen();
            }
        }
    }
}