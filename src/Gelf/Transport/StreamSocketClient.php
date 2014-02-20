<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gelf\Transport;

use RuntimeException;

/**
 * StreamSocketClient is a very simple OO-Wrapper around PHP stream_socket-library
 * and some specific stream-functions like fwrite
 *
 * @author Benjamin Zikarsky <benjamin@zikarsky.de>
 */
class StreamSocketClient
{
    const SOCKET_TIMEOUT = 30;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var integer
     */
    protected $port;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var array
     */
    protected $context;

    public function __construct($scheme, $host, $port, array $context = [])
    {
        $scheme = strtolower($scheme);
        if (!in_array($scheme, stream_get_transports())) {
            throw new RuntimeException("Unsupported stream-transport $scheme");
        }

        $this->scheme = $scheme;
        $this->host = $host;
        $this->port = $port;
        $this->context = $context;
    }

    /**
     * Destructor, closes socket if possible
     */
    public function __destruct()
    {
        if (!is_resource($this->socket)) {
            return;
        }

        fclose($this->socket);
        $this->socket = null;
    }

    /**
     * Initializes socket-client
     *
     * @param string $scheme like "udp" or "tcp"
     * @param string $host
     * @param integer $port
     *
     * @return resource
     *
     * @throws RuntimeException on connection-failure
     */
    protected static function initSocket($scheme, $host, $port, array $contextOptions)
    {
        $socketDescriptor = sprintf("%s://%s:%d", $scheme, $host, $port);
        $context = stream_context_create($contextOptions);

        $socket = stream_socket_client(
            $socketDescriptor,
            $errNo,
            $errStr,
            static::SOCKET_TIMEOUT,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($socket === false) {
            throw new RuntimeException("Failed to create socket-client for $socketDescriptor");
        }

        return $socket;
    }

    /**
     * Returns raw-socket-resource
     *
     * @return resource
     */
    public function getSocket()
    {
        // lazy initializing of socket-descriptor
        if (!$this->socket) {
            $this->socket = self::initSocket($this->scheme, $this->host, $this->port, $this->context);
        }

        return $this->socket;
    }

    /**
     * Writes a given string to the socket and returns the
     * number of written bytes
     *
     * @param string $buffer
     *
     * @return int
     *
     * @throws RuntimeException on write-failure
     */
    public function write($buffer)
    {
        $socket = $this->getSocket();
        $byteCount = fwrite($socket, $buffer);

        if ($byteCount === false || $byteCount != strlen($buffer)) {
            throw new RuntimeException("Failed to write to socket");
        }

        return $byteCount;
    }
}
