<?php

  namespace PHPNews\Controller;

  class IndexController extends Controller {

    /**
     * @var \PHPNews\Model\GroupListReader $list
     */
    private $list;

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\GroupListReader $list
     */
    public function __construct($request, $list) {
      $this->list = $list;

      parent::__construct($request);
    }

    public function handleRequest() {
      if ($this->request->getServerVar('REQUEST_URI') !== '/') {
        var_dump($this->request);
        exit;
  //      header($this->request->getServerVar('SERVER_PROTOCOL').' 301 Moved Permanently');
  //      header('Location: http://'.$this->request->getServerVar('HTTP_HOST').'/');
      }

      $this->list->requestListData();
      $this->createView('indexview', $this->list)->render();
    }

  }
