<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Traits;

trait TransmitterTrait
{
    public function read(int $buffer_size = 2048): string
    {
        $buffer = '';
        do {
            $tmp = fread($this->channel, $buffer_size);
            if ($tmp !== false) {
                $this->logger?->debug($tmp, ['buffer' => $buffer, 'tmp' => $tmp, 'size' => strlen($tmp)]);
                $buffer .= $tmp;
            } else {
                break;
            }
            //} while (!is_numeric(substr($tmp, 0, 3)) || substr($tmp, 3, 1) != ' ');
        } while (mb_strlen($tmp) >= $buffer_size);
        $this->logger?->debug($buffer);
        return trim($buffer, "\r\n");
    }

    public function write(string $data): bool
    {
        $this->logger?->debug($data);
        return fwrite($this->channel, $data, mb_strlen($data)) !== false;
    }
}