<?php

  namespace PHPNews\Controller;

  class GroupController extends Controller {

    /**
     * @var \PHPNews\Model\GroupOverviewReader $overview
     */
    private $overview;

    private function validateInput() {
      $this->request->validateUrlVar('format', function($value) {
        return in_array(strtolower($value), array('rss', 'rdf', 'atom')) ? $value : 'html';
      });
      $this->request->validateUrlVar('group', '/[^A-Za-z0-9.-]/', '');
      $this->request->validateUrlVar('i', 'intval');
    }

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\GroupOverviewReader $overview
     */
    public function __construct($request, $overview) {
      $this->overview = $overview;

      parent::__construct($request);
    }

    public function handleRequest() {
      $this->validateInput();

      $this->overview->setGroupName($this->request->getUrlVar('group'));
      $this->overview->setStartPosition($this->request->getUrlVar('i'));

      $this->overview->loadGroupData();
      $this->overview->requestOverviewData();

     // var_dump($this->request);
      $this->createView('groupview'.$this->request->getUrlVar('format'), $this->overview)->render();
    }

  }
