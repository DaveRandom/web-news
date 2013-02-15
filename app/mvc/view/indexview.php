<?php

  namespace PHPNews\View;

  class IndexView extends HTMLView {

    /**
     * @var \PHPNews\Model\GroupListReader $list
     */
    private $list;

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\GroupListReader $overview
     */
    public function __construct($request, $list) {
      parent::__construct($request);
      $this->list = $list;
    }

    private function body() {
      $newsHost = $this->stringFormatter->escapeHTMLOutput($this->request->getAppVar('newsHost'));

      include __DIR__.'/templates/index.phtml';
    }

    public function render() {
      header('Content-Type: text/html; charset=utf-8');

      $this->head('PHP News');
      $this->body();
      $this->foot();
    }

  }
