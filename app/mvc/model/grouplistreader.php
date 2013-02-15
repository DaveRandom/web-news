<?php

  namespace PHPNews\Model;

  class GroupListReader {

    /**
     * @var \NNTP\Client $nntpClient
     */
    private $nntpClient;

    /**
     * @param \NNTP\Client $nntpClient
     */
    public function __construct(\NNTP\Client $nntpClient) {
      $this->nntpClient = $nntpClient;
    }

    /**
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function requestListData() {
      $command = 'LIST';
      $expect = 215;
      $this->nntpClient->command($command, $expect);
    }

    /**
     * @return \stdClass
     */
    public function getItem() {
      $line = $this->nntpClient->readLineDotTerminated();
      if ($line === FALSE) {
        return FALSE;
      }

      $parts = explode(' ', rtrim($line));
      $item = array(
        'groupName'    => $parts[0],
        'messageCount' => $parts[1] - $parts[2] + 1,
        'active'       => $parts[3] != 'n'
      );

      return (object) $item;
    }

  }
