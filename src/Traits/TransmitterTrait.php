<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Traits;

trait TransmitterTrait
{
    public function read(int $buffer_size = 2048): string
    {
        $buffer = '';
        do {
            $tmp = fread($this->channel, $buffer_size);
            if (!empty($tmp)) {
                //$this->logCall($tmp, ['buffer' => $buffer, 'tmp' => $tmp, 'size' => strlen($tmp)]);
                $buffer .= $tmp;
            } else {
                break;
            }
            //} while (!is_numeric(substr($tmp, 0, 3)) || substr($tmp, 3, 1) != ' ');
        } while (strlen($tmp) >= $buffer_size);
        //$this->logCall('after', ['metadata' => stream_get_meta_data($this->link), 'transports' => stream_get_transports(), 'wrappers' => stream_get_wrappers()]);
        $this->logger?->debug($buffer);
        return trim($buffer, "\r\n");
    }

    public function write(string $data): bool
    {
        $this->logger?->debug($data);
        return fwrite($this->channel, $data) !== false;
    }
}