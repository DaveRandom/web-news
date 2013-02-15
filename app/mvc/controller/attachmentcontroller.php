<?php

  namespace PHPNews\Controller;

  class AttachmentController extends Controller {

    /**
     * @var \PHPNews\Model\GroupOverviewReader $overview
     */
    private $fetcher;

    private function validateInput() {
      $this->request->validateUrlVar('group', '/[^A-Za-z0-9.-]/', '');
      $this->request->validateUrlVar('article', 'intval');
      $this->request->validateUrlVar('part', 'intval');
    }

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\ArticleReader $article
     */
    public function __construct($request, $fetcher) {
      $this->fetcher = $fetcher;

      parent::__construct($request);
    }

    public function handleRequest() {
      $this->validateInput();

      $this->fetcher->setGroupName($this->request->getUrlVar('group'));
      $this->fetcher->setArticleId($this->request->getUrlVar('article'));
      $this->fetcher->setPartId($this->request->getUrlVar('part'));

      $part = $this->fetcher->findAttachment();
      
      $this->createView('attachmentview', $this->fetcher, $part)->render();
    }

  }
