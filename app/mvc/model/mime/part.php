<?php

  namespace PHPNews\Model\MIME;

  class Part {

    private $partNo;

    private $text;
    
    private $attachment;

    private $multipart;

    private $fileName;

    private $headers;

    public function __construct($partNo, $text, $attachment, $multipart, $fileName, $headers) {
      $this->partNo = $partNo;
      $this->text = $text;
      $this->attachment = $attachment;
      $this->multipart = $multipart;
      $this->fileName = $fileName;
      $this->headers = $headers;
    }

    public function getPartNo() {
      return $this->partNo;
    }

    public function isText() {
      return $this->text;
    }

    public function isAttachment() {
      return $this->attachment;
    }

    public function isMultipart() {
      return $this->multipart;
    }

    public function getFileName() {
      return $this->fileName;
    }

    public function getHeaders() {
      return $this->headers;
    }

  }
