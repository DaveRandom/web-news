<?php

  namespace NNTP;

  class Client {

    /**
     * @var string $host
     */
    private $host;

    /**
     * @var int $port
     */
    private $port;

    /**
     * @var bool $secure
     */
    private $secure = FALSE;

    /**
     * @var int $connectTimeout
     */
    private $connectTimeout = 15;

    /**
     * @var resource $socket
     */
    private $socket;

    /**
     * Construct a socket connection string
     *
     * @return string
     */
    private function buildConnectURI() {
      $transport = $this->secure ? 'tls' : 'tcp';
      if (isset($this->port)) {
        $port = $this->port;
      } else {
        $port = $this->secure ? 563 : 119;
      }

      return "{$transport}://{$this->host}:{$port}";
    }

    /**
     * Creates and connects the socket resource
     *
     * @return resource
     * @throws \NNTP\Exception
     */
    private function initializeSocket($uri) {
      $socket = stream_socket_client($uri, $errNo, $errStr, $this->connectTimeout, STREAM_CLIENT_CONNECT);

      if (!$socket) {
        throw new Exception('Unable to connect to server: Connect failed: ['.$errNo.'] '.$errStr);
      }

      return $socket;
    }

    /**
     * Verifies that the next line of data pending on a socket conforms to RFC3977 greeting message format
     *
     * @return resource
     * @throws \NNTP\Exception
     */
    private function verifyServerGreeting() {
      $hello = trim($this->readLine());

      if (!preg_match('/^20\d\s/', $hello)) {
        throw new Exception('Unable to connect to server: Unexpected greeting message from server: '.$hello);
      }
    }

    /**
     * @param string $command
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     */
    private function sendCommand($command) {
      $command = trim($command)."\r\n";
      $length = strlen($command);
      if ($length > 512) {
        throw new \InvalidArgumentException('Maximum command length of 510 bytes exceeded');
      }

      $this->clearDataBuffer();

      $sent = (int) @fwrite($this->socket, $command);
      if ($sent !== $length) {
        throw new Exception('Unable to send command: Send failed: '.$sent.' of '.$length.' bytes written');
      }
    }

    private function disconnect() {
      @fclose($this->socket);
      $this->socket = NULL;
    }

    private function readLine() {
      if (!isset($this->socket)) {
        throw new Exception('Unable to read data: Socket not connected');
      }

      if (feof($this->socket)) {
        $this->disconnect();
        throw new Exception('Unable to read data: Remote host closed socket');
      }

//      $line = fgets($this->socket);
//      echo $line;
//      return $line;
      return fgets($this->socket);
    }

    /**
     * @return array
     * @throws \NNTP\Exception
     */
    private function readResponse() {
      $response = trim($this->readLine());

      if ($response === FALSE) {
        throw new Exception('Unable to send command: Read response failed');
      }

      return preg_split('/\s+/', rtrim($response), 2);
    }

    private function clearDataBuffer() {
      while ($this->hasPendingData()) {
        $this->readLine();
      }
    }

    /**
     * @return bool
     */
    private function hasPendingData() {
      $r = array($this->socket);
      $w = $e = null;
      return (bool) stream_select($r, $w, $e, 0);
    }

    /**
     * @param int|string $expected
     * @param int|string $actual
     * @throws \NNTP\Exception
     */
    private function verifyExpectedResponseCode($expected, $actual) {
      $expr = "/^{$expected}$/";
      if (!preg_match($expr, $actual)) {
        throw new Exception('Unable to send command: Response code '.$actual.' does not match expected pattern '.$expected);
      }
    }

    /**
     * @param string $address
     * @param int $port
     */
    public function __construct($host = NULL, $secure = NULL, $port = NULL, $connectTimeout = NULL) {
      if (isset($host)) {
        $this->setHost($host);
      }
      if (isset($secure)) {
        $this->enableSecureTransport($secure);
      }
      if (isset($port)) {
        $this->setPort($port);
      }
      if (isset($connectTimeout)) {
        $this->setConnectTimeout($connectTimeout);
      }
    }

    /**
     * @param string $host
     */
    public function setHost($host) {
      $this->host = (string) $host;
    }

    /**
     * @return string
     */
    public function getHost() {
      return $this->host;
    }

    /**
     * @param bool secure
     */
    public function enableSecureTransport($secure) {
      $this->secure = (bool) $secure;
    }

    /**
     * @return bool
     */
    public function isSecure() {
      return $this->secure;
    }

    /**
     * @param int $port
     * @throws \InvalidArgumentException
     */
    public function setPort($port) {
      if (isset($port)) {
        $port = (int) $port;
        if ($port < 1 || $port > 65535) {
          throw new \InvalidArgumentException('Specified port outside valid range 1 - 65535');
        }

        $this->port = $port;
      } else {
        $this->port = NULL;
      }
    }

    /**
     * @return int
     */
    public function getPort() {
      if (isset($this->port)) {
        return $this->port;
      }
      return $this->secure ? 563 : 119;
    }

    /**
     * @param int $timeout
     */
    public function setConnectTimeout($timeout) {
      $this->connectTimeout = (int) $timeout;
    }

    /**
     * @return int
     */
    public function getConnectTimeout() {
      return $this->connectTimeout;
    }

    /**
     * @throws \NNTP\Exception
     */
    public function connect() {
      $uri = $this->buildConnectURI();
      $this->socket = $this->initializeSocket($uri);
      $this->verifyServerGreeting();
    }

    public function close() {
      if (isset($this->socket)) {
        @fclose($this->socket);
        $this->socket = NULL;
      }
    }

    /**
     * @return bool
     */
    public function isConnected() {
      if (isset($this->socket)) {
        if (feof($this->socket)) {
          $this->disconnect();
        }
      }

      return isset($this->socket);
    }

    /**
     * @param string $command
     * @param int|string $expectedResponseCode
     * @return string
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     */
    public function command($command, $expectedResponseCode = NULL) {
      if (!$this->isConnected()) {
        $this->connect();
      }

      $this->sendCommand($command);
      $response = $this->readResponse();

      if (isset($expectedResponseCode)) {
        $this->verifyExpectedResponseCode($expectedResponseCode, $response[0]);
      }

      return $response[1];
    }

    /**
     * @param int $length
     * @return string
     * @throws \NNTP\Exception
     * @throws \LogicException
     */
    public function readLineRaw($length = 16384) {
      if (!$this->isConnected()) {
        throw new \LogicException('Unable to read data: Socket not connected');
      }

      $line = $this->readLine();
      if ($line === FALSE) {
        return FALSE;
      }

      if ($line === FALSE) {
        throw new Exception('Unable to read data: Read operation failed');
      }

      return $line;
    }

    /**
     * @param int $length
     * @return string|bool
     * @throws \NNTP\Exception
     * @throws \LogicException
     */
    public function readLineDotTerminated($length = 16384) {
      $line = $this->readLineRaw($length);
      return $line !== ".\r\n" ? $line : FALSE;
    }

  }
