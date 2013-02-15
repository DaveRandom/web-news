<?php

  namespace PHPNews\Controller;

  class ArticleController extends Controller {

    /**
     * @var \PHPNews\Model\GroupOverviewReader $overview
     */
    private $overview;

    private function validateInput() {
      $this->request->validateUrlVar('group', '/[^A-Za-z0-9.-]/', '');
      $this->request->validateUrlVar('article', 'intval');
      $this->request->validateUrlVar('part', 'intval');
    }

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\ArticleReader $article
     */
    public function __construct($request, $article) {
      $this->article = $article;

      parent::__construct($request);
    }

    public function handleRequest() {
      $this->validateInput();

      $this->article->setGroupName($this->request->getUrlVar('group'));
      $this->article->setArticleId($this->request->getUrlVar('article'));

      $summary = $this->article->getArticleSummary();
      
      $this->createView('articleview'.$this->request->getUrlVar('format'), $summary)->render();
    }

  }
