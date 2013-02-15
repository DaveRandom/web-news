<?php

  namespace PHPNews\View;

  class GroupViewHTML extends HTMLView {

    /**
     * @var \PHPNews\Model\GroupOverviewReader $overview
     */
    private $overview;

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\GroupOverviewReader $overview
     */
    public function __construct($request, $overview) {
      parent::__construct($request);
      $this->overview = $overview;
    }

    private function body() {
      $groupName = $this->stringFormatter->escapeHTMLOutput($this->overview->getGroupName());
    
      $start = $this->overview->getStartPosition();
      $end = $this->overview->getEndPosition();
      $first = $this->overview->getFirstArticle();
      $last = $this->overview->getLastArticle();
      $total = $this->overview->getTotalArticles();
      $perPage = $this->overview->getArticlesPerPage();
      $previous = $this->overview->getPrevPageStartPosition();
      $next = $this->overview->getNextPageStartPosition();

      $isFirstPage = $this->overview->isFirstPage();
      $isLastPage = $this->overview->isLastPage();

      include __DIR__.'/templates/group.phtml';
    }

    public function render() {
      header('Content-Type: text/html; charset=utf-8');

      $this->head($this->request->getUrlVar('group'));
      $this->body();
      $this->foot();
    }

  }
