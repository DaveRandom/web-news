<?php

  namespace PHPNews\View;

  class AttachmentView extends HTMLView {

    /**
     * @var \stdClass $overview
     */
    private $fetcher;

    /**
     * @var \stdClass $overview
     */
    private $part;

    /**
     * @param \PHPNews\Request $request
     * @param \stdClass $summary
     */
    public function __construct($request, $fetcher, $part) {
      parent::__construct($request);
      $this->fetcher = $fetcher;
      $this->part = $part;
    }

    public function render() {
      $headers = $this->part->getHeaders();

      $cType = 'text/plain';
      if (isset($headers['content-type'])) {
        $cType = $headers['content-type'][0];
      }
      header('Content-Type: '.$cType);

      $this->fetcher->outputAttachment();
      exit;
    }

  }
