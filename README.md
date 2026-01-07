# SocketClient

Little methods collection in order to connect and use some socket connections from distinct types of origins

## Raw 

### Sockets

It's the most simple sockets use, using the sockets php library, can be used for tcp and udp connections.
No need factory, using full domain on construct, with protocol as schema, it parse and config the socket_client

```php
$client = new RawSocketClient('tcp://host.docker.internal:8001');
```

## Stream

Powerfull socket type, using streams, can be instantiated or use the Factory in order to auto-select the rigth Client type

```php
$stream = (new StreamSocketClientFactory)->createFromUrl('ftp://ftp.ftpserver.com:21');
```

Compatible (and really tested) Protocols

| Selected | Used | Class |
|:---------|:-----|:------|
| tcp      | tcp  | StreamSocketClient |
| udp      | udp  | StreamSocketClient |
| ftp      | tcp  | StreamSocketClient |
| ftps     | tls  | SecureStreamSocketClient |
| ssl      | ssl  | SecureStreamSocketClient |
| tls      | tls  | SecureStreamSocketClient |
| ws       | tcp  | WebSocketClient |
| wss      | ssl  | SecureWebSocketClient |

Available methods

| Method | Description |
|:-------|:------------|
| construct | Set the url and prepare the instance for future connection|
| connect | try to establish a real connection with the prepared server|
| isConnected | return true or false in order to know the connection status|
| disconnect | close the connection|
| setBlockingMode | set true or false for enable or disable the blocking mode with the connected server|
| read |try to read n bytes from buffer, if block mode is enabled, wait for receive some data |
| write | send a command or data to the socket server|
| getMetadata | returns an array with the headers or server metadata|
| getError | when an error exists, retrieve the code and the message and throw an Exception|
| setCrypto | Only for secure sockets (Streams and Web)|

### Stream Socket

For use with own servers, Email, FTP, etc...

```php
$socket = (new StreamSocketClientFactory)->createFromUrl('ftps://ftp.ftpserver.com:21');
$socket->connect();
echo $socket->read(); //220-Juancho's home SSH server
$socket->write("AUTH TLS\r\n");
echo $response = $socket->read(); //234 Security mechanism set to TLS
if (str_starts_with($response, 234)) {
    $socket->setCrypto(true);
    $socket->write("USER username\r\n");
    echo $response = $socket->read(); //331 Need password
    $socket->write("PASS password\r\n");
    echo $response = $socket->read(); //230 User logged in (elevated)
}
$socket->write("QUIT\r\n");
echo $response = $socket->read(); //221 Service closing control connection
```

### Web Sockets

This is an extension of StreamSocket (normal and secure) but perform the needed handshake with the server on connect.
Actually, it do a handshake v1, but we are in process to implements v2 and v3

![WSS local connection.](/assets/websocket.png "This is a real example of a wss connection.")