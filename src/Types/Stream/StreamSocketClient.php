<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Stream;

use Exception;
use JuanchoSL\SocketClient\Contracts\CommunicationInterface;
use JuanchoSL\SocketClient\Contracts\ConnectionInterface;
use JuanchoSL\SocketClient\Traits\TransmitterTrait;
use JuanchoSL\SocketClient\Types\AbstractSocketClient;
use Psr\Log\LoggerAwareInterface;

class StreamSocketClient extends AbstractSocketClient implements LoggerAwareInterface, ConnectionInterface, CommunicationInterface
{

    use TransmitterTrait;
    protected int $encrypt = STREAM_CRYPTO_METHOD_TLS_CLIENT;
    protected int $timeout = 90;

    public function connect(): bool
    {
        $time = microtime(true);
        $this->channel = stream_socket_client("{$this->uri->getScheme()}://{$this->uri->getHost()}:{$this->uri->getPort()}", $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT, $this->getContext());
        $this->logger?->debug("Connecting: {scheme}://{host}:{port}", [
            'scheme' => $this->uri->getScheme(),
            'host' => $this->uri->getHost(),
            'port' => $this->uri->getPort(),
            'time' => number_format(microtime(true) - $time, 4, '.', '')
        ]);
        if (empty($this->channel)) {
            $this->logger?->debug("Error: [{code}] -> {message}", ['code' => $errno, 'message' => $errstr]);
            throw new Exception($errstr, $errno);
        }
        stream_set_timeout($this->channel, 5);
        return $this->connected = true;
    }

    public function disconnect(): void
    {
        if (!empty($this->channel)) {
            if (stream_socket_shutdown($this->channel, STREAM_SHUT_RDWR)) {
                unset($this->channel);
                $this->connected = false;
            }
        }
    }

    public function setBlockingMode(bool $status = true): bool
    {
        return (!empty($this->channel)) ? stream_set_blocking($this->channel, $status) : false;
    }

    public function getMetadata(): array
    {
        return stream_get_meta_data($this->channel);
    }

    public function getError()
    {
        if ($this->channel) {
            $code = socket_last_error($this->channel);
            $error = socket_strerror($code);
            $exception = new Exception($error, $code);
            $this->logger?->error("", ['exception' => $exception]);
            socket_clear_error($this->channel);
            throw $exception;
        }
    }

    protected function getContext()
    {
        return null;
    }
}