<?php declare(strict_types=1);

namespace JuanchoSL\SocketClient\Traits;

trait SecureTrait
{

    protected $crt;
    protected $pk;

    public function setCertificates(string $private_key, ?string $public_key = null): static
    {
        $this->pk = $private_key;
        $this->crt = $public_key ?? $private_key;
        return $this;
    }

    public function setCrypto(bool $enable = true, $parent = null): bool
    {
        //$this->encoded = 13;
        if ($this->isConnected()) {
            return @stream_socket_enable_crypto($this->channel, $enable, $enable ? $this->encrypt : null, $parent) === true;
        }
        return false;
    }

    public function getContext()
    {
        $contextOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'disable_compression' => true,
                'security_level' => 0,
                //'local_cert' => realpath(__DIR__ . DIRECTORY_SEPARATOR . StreamSslSocketClient::CERTIFICATE),
                //'local_cert' => realpath(__DIR__ . DIRECTORY_SEPARATOR . StreamSslSocketServer::CERTIFICATE),
                //'local_pk' => realpath(__DIR__ . DIRECTORY_SEPARATOR . StreamSslSocketServer::CERTIFICATE),
                'allow_self_signed' => true,
                'ssltransport' => $this->uri->getScheme()
            )
        );
        $context = stream_context_create($contextOptions);
        stream_context_set_option($context, 'ssl', 'capture_peer_cert', true);
        return $context;
    }
}