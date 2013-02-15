<?php

  namespace PHPNews\View;

  abstract class HTMLView implements IView {

    /**
     * @param \PHPNews\Request $request
     */
    protected $request;

    /**
     * @var \PHPNews\View\StringFormatter $stringFormatter
     */
    protected $stringFormatter;

    /**
     * @param string $title
     */
    protected function head($title) {
      $newsHost = $this->request->getAppVar('newsHost');
      include __DIR__.'/templates/head.phtml';
    }

    protected function foot() {
      include __DIR__.'/templates/foot.phtml';
    }

    /**
     * @param \PHPNews\Request $request
     */
    public function __construct($request) {
      $this->request = $request;
      $this->stringFormatter = new StringFormatter;
    }

    abstract public function render();

  }
