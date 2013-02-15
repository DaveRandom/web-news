<?php

  spl_autoload_register(function($classPath) {
    $dirMappings = array(
      'phpnews'             => 'app',
      'phpnews\\model'      => 'app/mvc/model',
      'phpnews\\view'       => 'app/mvc/view',
      'phpnews\\controller' => 'app/mvc/controller',
      'nntp'                => 'app/nntp',
    );

    $classPathParts = explode('\\', strtolower(ltrim($classPath, '\\')));
    $className = array_pop($classPathParts);
    $dirPathParts = array($className.'.php');

    while ($classPathParts) {
      $mapPath = implode('\\', $classPathParts);
      if (isset($dirMappings[$mapPath])) {
        $dirPath = $dirMappings[$mapPath];
        break;
      }
      array_unshift($dirPathParts, array_pop($classPathParts));
    }

    if (isset($dirPath)) {
      array_unshift($dirPathParts, $dirPath);
    }
    array_unshift($dirPathParts, rtrim(__DIR__, '/'));
    $filePath = implode('/', $dirPathParts);

    if (is_file($filePath)) {
      require $filePath;
    }
  });
