<?php

  namespace PHPNews\Model\MIME;

  class PartFactory {

    public function create($partNo, $text, $attachment, $multipart, $fileName, $headers) {
      return new Part($partNo, $text, $attachment, $multipart, $fileName, $headers);
    }

  }
