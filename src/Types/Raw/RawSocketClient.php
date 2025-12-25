<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Types\Raw;

use Exception;
use JuanchoSL\SocketClient\Contracts\CommunicationInterface;
use JuanchoSL\SocketClient\Contracts\ConnectionInterface;
use JuanchoSL\SocketClient\Types\AbstractSocketClient;
use JuanchoSL\Validators\Types\Strings\StringValidation;
use Psr\Log\LoggerAwareInterface;

class RawSocketClient extends AbstractSocketClient implements LoggerAwareInterface, ConnectionInterface, CommunicationInterface
{

    protected $channel;
    protected string $schema;
    protected string $host;
    protected int $port;

    public function __construct(string $full_address)
    {
        $config = parse_url($full_address);
        $this->schema = $config['scheme'];
        $this->host = $config['host'];
        $this->port = $config['port'];

        if (StringValidation::isDomain($this->host)) {
            $this->host = gethostbyname($this->host);
        }

        if (empty($this->port) && !empty($this->schema)) {
            $type = ($this->schema == 'udp') ? 'udp' : 'tcp';
            $this->port = getservbyname($this->schema, $type);
        } elseif (empty($this->schema) && !empty($this->port)) {
            $this->schema = getservbyport($this->port, 'tcp');
        }

        if (StringValidation::isIpV4($this->host)) {
            $domain = AF_INET;
        } elseif (StringValidation::isIpV6($this->host)) {
            $domain = AF_INET6;
        } else {
            $domain = AF_UNIX;
        }
        $type = ($this->schema == 'udp') ? SOCK_DGRAM : SOCK_STREAM;
        //$protocol = ($this->schema == 'udp') ? SOL_UDP : SOL_TCP;
        $protocol = getprotobyname($this->schema);
        $this->channel = socket_create($domain, $type, $protocol);
    }

    public function connect(): bool
    {
        if (empty($this->channel) OR (!$this->connected = socket_connect($this->channel, $this->host, $this->port))) {
            $this->getError();
        }
        return $this->connected;
    }

    public function disconnect(): void
    {
        if (!empty($this->channel)) {
            @socket_close($this->channel);
            unset($this->channel);
            $this->connected = false;
        }
    }

    public function setBlockingMode(bool $status = true): bool
    {
        return ($status) ? socket_set_block($this->channel) : socket_set_nonblock($this->channel);
    }

    public function read(int $buffer_size = 2048): string
    {
        $buffer = '';
        do {
            if ($this->connected) {
                $tmp = socket_read($this->channel, $buffer_size, PHP_BINARY_READ);
                if ($tmp !== false) {
                    $buffer .= $tmp;
                    $tmp = strlen($tmp);
                } else {
                    break;
                }
            } else {
                $tmp = socket_recvfrom($this->channel, $buffer, $buffer_size, MSG_WAITALL, $this->host, $this->port); //UDP or SERVER???
            }
            //$tmp = socket_recvmsg($this->channel, $buffer_size); //UDP or SERVER???
            //$tmp = socket_recv($this->channel, $buffer_size); //UDP or SERVER???
        } while ($tmp >= $buffer_size);
        $this->logger?->debug($buffer);
        return $buffer;
    }

    public function write(string $data): bool
    {
        $data = rtrim($data);
        $this->logger?->debug($data);
        return socket_write($this->channel, $data) !== false;

        /// //UDP or SERVER???
        return socket_sendmsg($this->channel, $data) !== false;
        return socket_send($this->channel, $data, strlen($data), MSG_OOB) !== false;
    }

    public function getMetadata(): array
    {
        return socket_get_status($this->channel);
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

}