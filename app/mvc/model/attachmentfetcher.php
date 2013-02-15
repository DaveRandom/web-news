<?php

  namespace PHPNews\Model;

  use \NNTP\Client as NNTPClient;
  use \PHPNews\Model\MIME\ParserFactory as MIMEParserFactory;

  class AttachmentFetcher {

    /**
     * @var \NNTP\Client $nntpClient
     */
    private $nntpClient;

    /**
     * @var \PHPNews\Model\MIME\ParserFactory $mimeParser
     */
    private $mimeParserFactory;

    /**
     * @var \PHPNews\Model\MIME\Parser
     */
    private $parser;

    /**
     * @var string $groupName
     */
    private $groupName;

    /**
     * @var int $articleId
     */
    private $articleId;

    /**
     * @var int $articleId
     */
    private $partId;

    /**
     * @param \NNTP\Client $nntpClient
     */
    public function __construct(NNTPClient $nntpClient, MIMEParserFactory $mimeParserFactory) {
      $this->nntpClient = $nntpClient;
      $this->mimeParserFactory = $mimeParserFactory;
    }

    /**
     * @param string $name
     */
    public function setGroupName($name) {
      $this->groupName = $name;
    }

    /**
     * @param string $name
     */
    public function setArticleId($id) {
      $this->articleId = $id;
    }

    /**
     * @param string $name
     */
    public function setPartId($id) {
      $this->partId = $id;
    }

    /**
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    private function requestArticleData($nntpClient) {
      if (!$this->nntpClient->isConnected()) {
        $this->nntpClient->connect();
      }

      $command = 'GROUP '.$this->groupName;
      $expect = 211;
      $nntpClient->command($command, $expect);

      $command = 'ARTICLE '.$this->articleId;
      $expect = 220;
      $nntpClient->command($command, $expect);
    }

    private function findMessagePart($parser) {
      for ($i = 0; $i < $this->partId && $part = $parser->nextPart(); $i++);

      if (!$part) {
        throw new \LogicException('The requested message part does not exist');
      }
      if (!$part->isAttachment()) {
        throw new \LogicException('The requested message part is not an attachment');
      }

      return $part;
    }

    public function findAttachment() {
      $this->requestArticleData($this->nntpClient);

      $this->parser = $this->mimeParserFactory->create($this->nntpClient);

      return $this->findMessagePart($this->parser);
    }

    public function outputAttachment() {
      $this->parser->outputCurrentPartBody();
    }
  }
