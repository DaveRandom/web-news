<?php

  namespace PHPNews\View;
  
  class ErrorView extends HTMLView {

    /**
     * @var string $message
     */
    private $message;

    /**
     * @param \PHPNews\Request $request
     * @param string $message
     */
    public function __construct($request, $message) {
      parent::__construct($request);
      $this->message = $message;
    }

    public function render() {
      header('Content-Type: text/html; charset=utf-8');

      $this->head('Error');

      $message = $this->message;
      include __DIR__.'/templates/error.phtml';

      $this->foot();
    }

  }
