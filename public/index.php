<?php

  require __DIR__.'/../config.inc.php';
  require __DIR__.'/../autoload.inc.php';

  $request = new \PHPNews\Request;
  $request->validateUrlVar('page', 'strtolower');

  $request->setAppVar('newsHost', NNTP_HOST);
  $request->setAppVar('cacheDir', CACHE_DIR);

  $request->validateAppVar('newsHost', function($value) use($request) {
    return in_array($value, array('localhost', '127.0.0.1')) ? $request->getServerVar('HTTP_HOST') : $value;
  });
  $request->validateAppVar('cacheDir', function($value) {
    return preg_match('#^(?:[a-zA-Z]:)?[\/]#', $value, $matches) ? $value : __DIR__.'/../'.$value;
  });

  $nntpClient = new \NNTP\Client(NNTP_HOST);

  switch ($request->getUrlVar('page')) {
    case 'group':
      $controller = new \PHPNews\Controller\GroupController(
        $request,
        new \PHPNews\Model\GroupOverviewReader($nntpClient)
      );
      break;

    case 'article':
      $controller = new \PHPNews\Controller\ArticleController(
        $request,
        new \PHPNews\Model\ArticleSummarizer(
          $nntpClient,
          new \PHPNews\Model\MIME\ParserFactory($nntpClient)
        )
      );
      break;

    case 'attach':
      $controller = new \PHPNews\Controller\AttachmentController(
        $request,
        new \PHPNews\Model\AttachmentFetcher(
          $nntpClient,
          new \PHPNews\Model\MIME\ParserFactory($nntpClient)
        )
      );
      break;

    case 'index':
    default:
      $controller = new \PHPNews\Controller\IndexController(
        $request,
        new \PHPNews\Model\GroupListReader($nntpClient)
      );
      break;
  }

  $controller->handleRequest();
