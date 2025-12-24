<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Traits;

trait EncodingTrait
{
    protected ?int $encoded = null;
    protected $cert = '';
    protected function cert()
    {
        $data = stream_context_get_params($this->channel)["options"]["ssl"];
        $this->cert = $data["peer_certificate"];
    }
    public function read(int $buffer_size = 2048): string
    {
        $buffer = parent::read($buffer_size);
        if ($this->encoded) {
            //openssl_public_decrypt($buffer, $buffer, $this->cert);
        }
        $buffer = ($this->connected && !empty($buffer)) ? $this->decode($buffer) : $buffer;
        $this->logger?->debug($buffer);
        return $buffer;
    }

    public function write(string $data): bool
    {
        $this->logger?->debug($data);
        $data = ($this->connected && !empty($data)) ? $this->encode($data) : $data;
        if ($this->encoded) {
            //openssl_public_encrypt($data, $data, $this->cert);
        }
        return parent::write($data);
    }
    protected function encode(string $frame)
    {
        if (!$this->encoded) {
            return $frame;
        } elseif ($this->encoded == 7) {
            return $this->encode7($frame);
        } elseif ($this->encoded == 8) {
            return $this->encode8($frame);
        } else {
            return $this->encode13($frame);
        }
    }

    protected function decode(string $frame)
    {
        if (!$this->encoded) {
            return $frame;
        } elseif ($this->encoded == 7) {
            return $this->decode7($frame);
        } elseif ($this->encoded == 8) {
            return $this->decode8($frame);
        } else {
            return $this->decode13($frame);
        }
    }
    protected function encode13(string $text)
    {
        $masked = false;//$this->masked;
        /*
        $secondByte = sprintf('%08b', ord($text[1]));
        $masked = ($secondByte[0] == '1') ? true : false;
        */
        $type = 'text';
        $payload = $text;
        $frameHead = array();
        $frame = '';
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                // first byte indicates FIN, Text-Frame (10000001):
                $frameHead[0] = 129;
                break;

            case 'close':
                // first byte indicates FIN, Close Frame(10001000):
                $frameHead[0] = 136;
                break;

            case 'ping':
                // first byte indicates FIN, Ping frame (10001001):
                $frameHead[0] = 137;
                break;

            case 'pong':
                // first byte indicates FIN, Pong frame (10001010):
                $frameHead[0] = 138;
                break;
        }

        // set mask and payload length (using 1, 3 or 9 bytes)
        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }

            // most significant bit MUST be 0 (close connection if frame too big)
            if ($frameHead[2] > 127) {
                $this->disconnect();
                return false;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        // convert frame-head to string:
        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }

        if ($masked === true) {
            // generate a random mask:
            $mask = array();
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }

            $frameHead = array_merge($frameHead, $mask);
        }
        $frame = implode('', $frameHead);
        // append payload to frame:
        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    protected function decode13(string $frame)
    {
        $bytes = $frame;
        $dataLength = '';
        $mask = '';
        $coded_data = '';
        $decodedData = '';
        $secondByte = sprintf('%08b', ord($bytes[1]));
        $masked = ($secondByte[0] == '1') ? true : false;
        $dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);

        if ($masked === true) {
            if ($dataLength === 126) {
                $mask = substr($bytes, 4, 4);
                $coded_data = substr($bytes, 8);
            } elseif ($dataLength === 127) {
                $mask = substr($bytes, 10, 4);
                $coded_data = substr($bytes, 14);
            } else {
                $mask = substr($bytes, 2, 4);
                $coded_data = substr($bytes, 6);
            }
            for ($i = 0; $i < strlen($coded_data); $i++) {
                $decodedData .= $coded_data[$i] ^ $mask[$i % 4];
            }
        } else {
            if ($dataLength === 126) {
                $decodedData = substr($bytes, 4);
            } elseif ($dataLength === 127) {
                $decodedData = substr($bytes, 10);
            } else {
                $decodedData = substr($bytes, 2);
            }
        }
        return $decodedData;
    }

    protected function decode8(string $data)
    {
        $bytes = $data;
        $data_length = "";
        $mask = "";
        $coded_data = "";
        $decoded_data = "";
        $data_length = $bytes[1] & 127;
        if ($data_length === 126) {
            $mask = substr($bytes, 4, 8);
            $coded_data = substr($bytes, 8);
        } else if ($data_length === 127) {
            $mask = substr($bytes, 10, 14);
            $coded_data = substr($bytes, 14);
        } else {
            $mask = substr($bytes, 2, 6);
            $coded_data = substr($bytes, 6);
        }
        for ($i = 0; $i < strlen($coded_data); $i++) {
            $decoded_data .= $coded_data[$i] ^ $mask[$i % 4];
        }
        return $decoded_data;
    }

    protected function encode8(string $text)
    {
        $frame = array();
        $encoded = "";
        $frame[0] = 0x81;
        $data_length = strlen($text);

        if ($data_length <= 125) {
            $frame[1] = $data_length;
        } else {
            $frame[1] = 126;
            $frame[2] = $data_length >> 8;
            $frame[3] = $data_length & 0xFF;
        }

        for ($i = 0; $i < sizeof($frame); $i++) {
            $encoded .= chr($frame[$i]);
        }

        $encoded .= $text;
        return $encoded;
    }

    protected function decode7(string $frame)
    {
        $len = ord($frame[1]) & 127;
        if ($len === 126) {
            $ofs = 8;
        } elseif ($len === 127) {
            $ofs = 14;
        } else {
            $ofs = 6;
        }
        $text = '';
        for ($i = $ofs; $i < strlen($frame); $i++) {
            $text .= $frame[$i] ^ $frame[$ofs - 4 + ($i - $ofs) % 4];
        }
        return $text;
    }

    protected function encode7(string $text)
    {
        $b = 129; // FIN + text frame
        $len = strlen($text);
        if ($len < 126) {
            return pack('CC', $b, $len) . $text;
        } elseif ($len < 65536) {
            return pack('CCn', $b, 126, $len) . $text;
        } else {
            return pack('CCNN', $b, 127, 0, $len) . $text;
        }
    }

}