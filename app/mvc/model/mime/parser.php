<?php

  namespace PHPNews\Model\MIME;

  use \NNTP\Client as NNTPClient;

  class Parser {

    const BODY_OUTPUT   = 0x01;
    const BODY_RETURN   = 0x02;
    
    const DECODE_BASE64 = 0x10;
    const DECODE_QP     = 0x20;

    private $nntpClient;
    
    private $partFactory;
    
    private $messageHeaders;

    private $outerMultipartBoundary;

    private $isMultipart = false;

    private $isText = false;

    private $atBoundary = false;

    private $end = false;

    private $currentPartNo = 0;

    private $currentPart;

    public function __construct(NNTPClient $nntpClient, PartFactory $partFactory) {
      $this->nntpClient = $nntpClient;
      $this->partFactory = $partFactory;
    }

    private function loadMessageHeaders() {
      $headers = $this->readHeaderBlock();

      if (isset($headers['content-type']) && $contentType = $this->getContentTypeParts($headers['content-type'][0])) {
        if ($contentType['type'] === 'text') {
          if ($contentType['subtype'] === 'plain') {
            $this->isText = true;
          }
        } else if ($contentType['type'] === 'multipart') {
          if (($contentType['subtype'] === 'mixed' || substr($contentType['subtype'], 0, 3) === 'alt') && !empty($contentType['params']['boundary'])) {
            $this->isMultipart = true;
            $this->outerMultipartBoundary = $contentType['params']['boundary'];
          }
        }
      } else {
       $this->isText = true;
      }
      
      if (!$this->isText && !$this->isMultipart) {
        // We can't do anything with this message
        $this->end = true;
      }
      
      $this->messageHeaders = $headers;
    }

    private function getContentTypeParts($string) {
      if (!preg_match('#^(\S+)/([^;\s]+);?(.*)#', $string, $matches)) {
        return false;
      }

      return array(
        'type' => strtolower($matches[1]),
        'subtype' => strtolower($matches[2]),
        'params' => $this->parseHeaderTokens($matches[3]),
      );
    }

    private function getContentDispositionParts($string) {
      $tokens = $this->parseHeaderTokens($string);

      $disposition = key($tokens);
      unset($tokens[$disposition]);

      return array(
        'disposition' => strtolower($disposition),
        'params' => $tokens,
      );
    }

    private function parseHeaderTokens($params) {
      $expr = '%
        (?P<key>(?!<=)[^\\s()<>@,;:\\\\"/[\\]?=]+)?         # key is always RFC2045 token
          (?:
            =
            (?:
              "(?P<dqval>[^"\\\\]*(?:\\\\.[^"\\\\]*)*)"     # double quoted string
             |\'(?P<sqval>[^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\' # single quoted string
             |(?P<tkval>[^\\s()<>@,;:\\\\"/[\\]?=]+)        # RFC2045 token
            )?
          )?
        (?:;\s*|$)                                          # semi-colon or end of subject
      %xs';
      preg_match_all($expr, $params, $parts);

      $result = array();
      foreach ($parts['key'] as $i => $key) {
        $key = strtolower($key);
        $val = '';

        if ($parts['dqval'][$i] !== '') {
          $val = $parts['dqval'][$i];
        } else if ($parts['sqval'][$i] !== '') {
          $val = $parts['sqval'][$i];
        } else if ($parts['tkval'][$i] !== '') {
          $val = $parts['tkval'][$i];
        }

        if ($key !== '') {
          $result[$key] = $val;
        } else if ($val !== '') {
          $result[$i] = $val;
        }
      }

      return $result;
    }

    private function readHeaderBlock() {
      $headers = array();
      $lastHeader = '';
      $this->atBoundary = $hasMore = false;

      while ($line = $this->nntpClient->readLineDotTerminated()) {
        if (!trim($line)) { // end of headers
          $hasMore = true;
          break;
        } else if ($line[0] == ' ' || $line[0] == "\t") { // continuation of previous header
          $lastHeader .= ' '.trim($line);
        } else if (preg_match('/^([^:]+)\s*:\s*(.*?)\s*\r?\n$/', $line, $matches)) { // valid header
          $name = strtolower($matches[1]);
          if (!isset($headers[$name])) {
            $headers[$name] = array();
            $key = 0;
          } else {
            $key = count($headers[$name]);
          }
          $headers[$name][$key] = $matches[2];
          $lastHeader = &$headers[$name][$key];
        }
      }

      if (!$hasMore) {
        $this->end = true;
      }

      return $headers;
    }

    private function findNextBoundary($boundary, $flags = 0) {
      $data = '';

      while ($line = $this->nntpClient->readLineDotTerminated()) {
        if (isset($boundary) && substr($line, 0, strlen($boundary) + 2) === '--'.$boundary) {
          if (rtrim($line) === '--'.$boundary.'--') {
            $this->end = true;
          }

          break;
        }

        if ($line[0] === '.') {
          $data = substr($line, 1);
        }

        switch (TRUE) {
          case $flags & self::DECODE_BASE64:
            $line = base64_decode($line);
            break;

          case $flags & self::DECODE_QP:
            $line = quoted_printable_decode($line);
            break;
        }

        if ($flags & self::BODY_RETURN) {
          $data .= $line;
        }
        if ($flags & self::BODY_OUTPUT) {
          echo $line;
        }
      }

      $this->atBoundary = true;

      if ($flags & self::BODY_RETURN) {
        return $data;
      }
    }

    private function makePartFromPlaintextMessage() {
      $this->currentPart = $this->partFactory->create(1, true, false, false, null, $this->messageHeaders);

      $this->end = true;
    }

    private function makePartFromMultipartMessage() {
      // WARNING: within this method, nightmares become reality
      // If you touch this you will probably break it

      if (!$this->atBoundary) {
        $this->findNextBoundary($this->outerMultipartBoundary);
      }
      $headers = $this->readHeaderBlock();

      if (isset($headers['content-type'])) {
        $contentType = $this->getContentTypeParts($headers['content-type'][0]);
      } else {
        $contentType = array('type' => 'text', 'subtype' => 'plain');
      }

      if (isset($headers['content-disposition'])) {
        $contentDisposition = $this->getContentDispositionParts($headers['content-disposition'][0]);
      } else {
        $contentDisposition = array('disposition' => 'inline');
      }

      $multipart = $contentType['type'] === 'multipart';
      $text = $contentType['type'] === 'text';

      $attachment = true;
      switch (true) {
        // correct
        case isset($contentDisposition['params']['filename']):
          $fileName = $contentDisposition['params']['filename'];
          break;

        // all wrong, but they do happen
        case isset($contentDisposition['params']['name']):
          $fileName = $contentDisposition['params']['name'];
          break;
        case isset($contentType['params']['filename']):
          $fileName = $contentType['params']['filename'];
          break;
        case isset($contentType['params']['name']):
          $fileName = $contentType['params']['name'];
          break;

        // you'd think we'd check this first, but older versions of thunderbird and possibly others are stupid and
        // state everything to be inline. At this point we have to make up a filename because we weren't given one
        // in even a vaguely sensible way.
        case $contentDisposition['disposition'] === 'attachment':
          $fileName = 'attachment_part'.$this->currentPartNo;
          break;

        // at this stage we assume the content is inline, although if it's not text we can't do anything with it
        default:
          $attachment = false;
          $fileName = null;
          break;
      }

      $this->currentPart = $this->partFactory->create($this->currentPartNo, $text, $attachment, $multipart, $fileName, $headers);
    }

    private function extractCurrentPartBody($findFlags) {
      $headers = $this->currentPart->getHeaders();

      if (isset($headers['content-transfer-encoding'])) {
        switch (strtolower($headers['content-transfer-encoding'][0])) {
          case 'base64':
            $findFlags |= self::DECODE_BASE64;
            break;
          case 'quoted-printable':
            $findFlags |= self::DECODE_QP;
            break;
        }
      }

      return $this->findNextBoundary($this->outerMultipartBoundary, $findFlags);
    }

    public function nextPart() {
      if (!isset($this->messageHeaders)) {
        $this->loadMessageHeaders();
      }

      if ($this->end) {
        return NULL;
      }

      $this->currentPartNo++;

      if ($this->isText) {
        $this->makePartFromPlaintextMessage();
      } else {
        $this->makePartFromMultipartMessage();
      }

      return $this->currentPart;
    }

    public function getCurrentPartBody() {
      return $this->extractCurrentPartBody(self::BODY_RETURN);
    }

    public function getCurrentPartBodyCharset() {
      $charset = 'iso-8859-1';

      $headers = $this->currentPart->getHeaders();
      if (isset($headers['content-type'])) {
        $parts = $this->getContentTypeParts($headers['content-type'][0]);
        if (isset($parts['params']['charset'])) {
          $charset = $parts['params']['charset'];
        }
      }

      return $charset;
    }

    public function outputCurrentPartBody() {
      $this->extractCurrentPartBody(self::BODY_OUTPUT);
    }

    public function extractTextBodyFromMultipart() {
      // This method may need implementing at some point
      // I have not yet found a message in which it is required, but if someone sends a
      // HTML message with an attachment to the list then it will throw up without it
      return 'This message is in a format that is not yet implemented, please inform the maintainer of the list.';
    }

    public function getMessageHeaders() {
      return $this->messageHeaders;
    }

  }
