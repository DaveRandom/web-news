<?php

  namespace PHPNews\Controller;

  abstract class Controller {

    /**
     * @var \PHPNews\Request $request
     */
    protected $request;

    /**
     * Set to true when handleError() is called to prevent infinte loops
     *
     * @var bool $handlingError
     */
    private $handlingError = FALSE;

    /**
     * @param \PHPNews\Request $request
     */
    public function __construct(\PHPNews\Request $request) {
      $this->request = $request;
      set_exception_handler(array($this, 'uncaughtExceptionHandler'));
    }

    /**
     * @param \Exception $e
     */
    public function uncaughtExceptionHandler($e) {
      $this->handleError($e->getMessage());
    }

    /**
     * @param string $name
     * @return \PHPNews\View\IView
     */
    protected function createView($name) {
      $className = "\\PHPNews\\View\\{$name}";

      $args = func_get_args();
      $args[0] = $this->request;

      $reflect = new \ReflectionClass($className);
      return $reflect->newInstanceArgs($args);
    }

    /**
     * @param string $message
     */
    protected function handleError($message) {
      if (!$this->handlingError) {
        $this->handlingError = TRUE;

        $this->createView('errorview', $message)->render();
      }
    }

    abstract public function handleRequest();

  }
