<?php

  namespace PHPNews;

  class Request {

    /**
     * @var array $urlVars
     */
    private $dataStore = array();

    /**
     * Fetches a value from the data store by key and name
     *
     * @param string $storeKey
     * @param scalar $name
     * @return mixed
     */
    private function getVar($storeKey, $name) {
      return isset($this->dataStore[$storeKey][$name]) ? $this->dataStore[$storeKey][$name] : NULL;
    }

    /**
     * Fetches a value from the data store by key and name and validates it
     *
     * @param string $storeKey
     * @param scalar $name
     * @param mixed $op
     * @return bool
     */
    private function validateVar($storeKey, $name, $op) {
      if (!isset($this->dataStore[$storeKey][$name])) {
        $this->dataStore[$storeKey][$name] = NULL;
      }

      $args = func_get_args();
      array_splice($args, 0, 2, array($this->dataStore[$storeKey][$name]));
      $this->dataStore[$storeKey][$name] = call_user_func_array(array($this, 'validateValue'), $args);

      return isset($this->dataStore[$storeKey][$name]);
    }

    /**
     * Validates a value using a specified operation and returns the modified value
     *
     * @param mixed $value
     * @param mixed $op
     * @param mixed $replace
     * @return mixed
     */
    private function validateValue($value, $op, $replace = NULL) {
      if (is_callable($op)) {
        $args = func_get_args();
        array_splice($args, 1, 1);
        $result = call_user_func_array($op, $args);
      } else if (isset($replace)) {
        if (is_callable($replace)) {
          $result = preg_replace_callback($op, $replace, $value);
        } else {
          $result = preg_replace($op, $replace, $value);
        }
      } else {
        $result = preg_match($op, $value) ? $value : NULL;
      }

      return $result;
    }

    /**
     * @param array $appVars
     */
    public function collectData(array $appVars = NULL) {
      $this->dataStore['url']    = isset($_GET)    ? $_GET    : $this->dataStore['url'];
      $this->dataStore['post']   = isset($_POST)   ? $_POST   : $this->dataStore['post'];
      $this->dataStore['cookie'] = isset($_COOKIE) ? $_COOKIE : $this->dataStore['cookie'];
      $this->dataStore['server'] = isset($_SERVER) ? $_SERVER : $this->dataStore['server'];
      $this->dataStore['env']    = isset($_ENV)    ? $_ENV    : $this->dataStore['env'];
      unset($_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER, $_ENV);

      if (isset($appVars)) {
        $this->dataStore['app'] = array_merge((array) $this->dataStore['app'], $appVars);
      }
    }

    /**
     * @param scalar $name
     * @param mixed $value
     */
    public function setAppVar($name, $value) {
      $this->dataStore['app'][$name] = $value;
    }

    /**
     * @param array $appVars
     */
    public function __construct(array $appVars = NULL) {
      $this->collectData($appVars);
    }

    /**
     * Routes calls to get and validate functions
     *
     * @param string $methodName
     * @param array $args
     * @return mixed
     */
    public function __call($methodName, $args) {
      $methodName = strtolower($methodName);
      $dataKeys = 'url|post|cookie|server|env|app';

      switch (TRUE) {
        case preg_match("/^get({$dataKeys})var$/", $methodName, $matches):
          if (!isset($args[0]) || !is_scalar($args[0])) {
            throw new \InvalidArgumentException('Name for value must be scalar');
          }

          return $this->getVar($matches[1], $args[0]);

        case preg_match("/^validate({$dataKeys})var$/", $methodName, $matches):
          if (!isset($args[0]) || !is_scalar($args[0])) {
            throw new \InvalidArgumentException('Name for value must be scalar');
          }
          if (!isset($args[1])) {
            throw new \InvalidArgumentException('Validation operation must be specified');
          }

          array_unshift($args, $matches[1]);
          return call_user_func_array(array($this, 'validateVar'), $args);

        default:
          throw new \BadMethodCallException('Call to undefined method: '.$methodName);
      }
    }

  }
