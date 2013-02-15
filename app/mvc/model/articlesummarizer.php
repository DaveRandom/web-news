<?php

  namespace PHPNews\Model;

  use \NNTP\Client as NNTPClient;
  use \PHPNews\Model\MIME\ParserFactory as MIMEParserFactory;

  class ArticleSummarizer {

    /**
     * @var \NNTP\Client $nntpClient
     */
    private $nntpClient;

    /**
     * @var \PHPNews\Model\MIME\ParserFactory $mimeParser
     */
    private $mimeParserFactory;

    /**
     * @var string $groupName
     */
    private $groupName;

    /**
     * @var int $articleId
     */
    private $articleId;

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

    private function getRefsFromHeaders($headers) {
      if (!empty($headers['references'])) {
        $refList = preg_split('/\s+/', $headers['references'][0], -1, PREG_SPLIT_NO_EMPTY);
      } else if (!empty($headers['in-reply-to'])) {
        $refList = preg_split('/\s+/', $headers['in-reply-to'][0], -1, PREG_SPLIT_NO_EMPTY);
      } else {
        return array();
      }

      $result = array();

      foreach ($refList as $i => $ref) {
        if (strlen($ref) <= 504 && preg_match('/^<.+?>$/', $ref)) {
          $response = $this->nntpClient->command('XPATH '.$ref, 223);
          if (preg_match('#^.*?/(.+?)(?:/|$)#', $response, $matches)) {
            $result[] = '/'.urlencode($this->groupName).'/a/'.urlencode($matches[1]);
          }
        }
      }
      
      return $result;
    }

    private function getGroupsFromHeaders($headers) {
      $result = array();

      if (!empty($headers['newsgroups'])) {
        $groupList = preg_split('/\s*,\s*/', $headers['newsgroups'][0], -1, PREG_SPLIT_NO_EMPTY);

        foreach($groupList as $group) {
          $result[$group] = '/'.urlencode($group);
        }
      }

      return $result;
    }

    private function buildArticleSummary($parser) {
      $summary = new ArticleSummary; // worth a factory? Not sure

      $summary->setGroupName($this->groupName);
      $summary->setArticleId($this->articleId);

      while ($part = $parser->nextPart()) {
        if ($part->isAttachment()) {
          $summary->addAttachment($part->getPartNo(), $part->getFileName());
        } else if ($part->isMultipart()) {
          if ($summary->getBody() === NULL) {
            $body = $parser->extractTextBodyFromMultipart();
            if ($body !== FALSE) {
              $summary->setBody($body);
            }
          }
        } else if ($part->isText() && $summary->getBody() === NULL) {
          $summary->setBodyCharset($parser->getCurrentPartBodyCharset());
          $summary->setBody($parser->getCurrentPartBody());
        }
      }

      $messageHeaders = $parser->getMessageHeaders();

      $summary->setMeta($messageHeaders);

      $summary->setRefs($this->getRefsFromHeaders($messageHeaders));
      $summary->setGroups($this->getGroupsFromHeaders($messageHeaders));

      return $summary;
    }

    public function getArticleSummary() {
      $this->requestArticleData($this->nntpClient);

      $parser = $this->mimeParserFactory->create($this->nntpClient);
      $summary = $this->buildArticleSummary($parser);
      
      return $summary;
    }
  }
