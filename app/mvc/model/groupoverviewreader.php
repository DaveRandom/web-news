<?php

  namespace PHPNews\Model;

  class GroupOverviewReader {

    /**
     * @var \NNTP\Client $nntpClient
     */
    private $nntpClient;

    /**
     * @var string $groupName
     */
    private $groupName;

    /**
     * @var int $lastArticle
     */
    private $startPosition;

    /**
     * @var int $lastArticle
     */
    private $endPosition;

    /**
     * @var int $lastArticle
     */
    private $firstArticle;

    /**
     * @var int $lastArticle
     */
    private $lastArticle;

    /**
     * @var int $articlesPerPage
     */
    private $articlesPerPage = 20;

    /**
     * @param \NNTP\Client $nntpClient
     */
    public function __construct(\NNTP\Client $nntpClient) {
      $this->nntpClient = $nntpClient;
    }

    /**
     * @param string $name
     */
    public function setGroupName($name) {
      $this->groupName = (string) $name;
    }

    /**
     * @return string
     */
    public function getGroupName() {
      return $this->groupName;
    }

    /**
     * @param int $position
     */
    public function setStartPosition($position) {
      $this->startPosition = (int) $position;
    }

    /**
     * @return int
     */
    public function getStartPosition() {
      return $this->startPosition;
    }

    /**
     * @return int
     */
    public function getEndPosition() {
      return $this->endPosition;
    }

    /**
     * @return int
     */
    public function getFirstArticle() {
      return $this->firstArticle;
    }

    /**
     * @return int
     */
    public function getLastArticle() {
      return $this->lastArticle;
    }

    /**
     * @return int
     */
    public function getTotalArticles() {
      return $this->lastArticle - $this->firstArticle + 1;
    }

    /**
     * @param int $perPage
     */
    public function setArticlesPerPage($perPage) {
      $this->articlesPerPage = (int) $perPage;
    }

    /**
     * @return int
     */
    public function getArticlesPerPage() {
      return $this->articlesPerPage;
    }

    /**
     * @return int
     */
    public function getPrevPageStartPosition() {
      $prevPageStart = $this->startPosition - $this->articlesPerPage;
      $firstPageStart = $this->firstArticle;
      return max($prevPageStart, $firstPageStart);
    }

    /**
     * @return int
     */
    public function getNextPageStartPosition() {
      $nextPageStart = $this->startPosition + $this->articlesPerPage;
      $lastPageStart = ($this->getTotalArticles() - $this->articlesPerPage) + 1;
      return min($nextPageStart, $lastPageStart);
    }

    /**
     * @return bool
     */
    public function isFirstPage() {
      return $this->firstArticle >= $this->startPosition;
    }

    /**
     * @return bool
     */
    public function isLastPage() {
      $lastPageStart = ($this->getTotalArticles() - $this->articlesPerPage) + 1;
      return $this->startPosition >= $lastPageStart;
    }

    /**
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     */
    public function loadGroupData() {
      if (!$this->nntpClient->isConnected()) {
        $this->nntpClient->connect();
      }

      $command = "GROUP {$this->groupName}";
      $expect = 211;
      $response = $this->nntpClient->command($command, $expect);

      $start = $this->startPosition;
      $diff = $this->articlesPerPage - 1;

      list($total, $first, $last, $group) = preg_split('/\s+/', $response);
      if (!$start || $start > $last - $diff || $start < $first) {
        $start = $last - $first > $diff ? $last - $diff : $first;
      }
      $end = min($last, $start + $diff);

      $this->startPosition = (int) $start;
      $this->endPosition   = (int) $end;
      $this->firstArticle  = (int) $first;
      $this->lastArticle   = (int) $last;
    }

    /**
     * @throws \NNTP\Exception
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function requestOverviewData() {
      if (!isset($this->startPosition, $this->endPosition)) {
        throw new \LogicException('Unable to request overview data without start and end position');
      }

      $command = "XOVER {$this->startPosition}-{$this->endPosition}";
      $expect = 224;
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

      $parts = explode("\t", rtrim($line), 9);
      $item = array(
        'id'        => (int) $parts[0],
        'subject'   => $parts[1],
        'author'    => $parts[2],
        'timestamp' => strtotime($parts[3]),
        'lines'     => $parts[7],
        'subject'   => $parts[1],
      );

      return (object) $item;
    }

  }
