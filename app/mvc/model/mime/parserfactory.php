<?php

  namespace PHPNews\Model\MIME;

  use \NNTP\Client as NNTPClient;

  class ParserFactory {

    public function create(NNTPClient $nntpClient) {
      return new Parser($nntpClient, new PartFactory);
    }

  }
