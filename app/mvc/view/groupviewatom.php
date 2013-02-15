<?php

  namespace PHPNews\View;

  class GroupViewAtom {

    /**
     * @param \PHPNews\Request $request
     */
    private $request;

    /**
     * @var \PHPNews\Model\GroupOverviewReader $overview
     */
    private $overview;

    /**
     * @var \PHPNews\View\StringFormatter $stringFormatter
     */
    private $stringFormatter;

    /**
     * @var \DOMDocument $doc
     */
    private $doc;

    /**
     * @var \DOMElement $root
     */
    private $root;

    /**
     * @param \PHPNews\Request $request
     * @param \PHPNews\Model\GroupOverviewReader $overview
     */
    public function __construct($request, $overview) {
      $this->request = $request;
      $this->overview = $overview;

      $this->stringFormatter = new StringFormatter;
    }

    /**
     * @param \DOMElement $targetElement
     * @param array $data
     */
    private function appendElementsFromArray(\DOMElement $targetElement, array $data) {
      foreach ($data as $name => $content) {
        $targetElement->appendChild($this->doc->createElement($name))
          ->appendChild($this->doc->createTextNode($content));
      }
    }

    private function createBaseDocument() {
      $this->doc = new \DOMDocument('1.0', 'utf-8');
      $this->doc->formatOutput = TRUE;

      $this->root = $this->doc->appendChild($this->doc->createElement('feed'));
      $this->root->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
    }

    private function createChannelHeader() {
      $elements = array(
        'id' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlParam('group'),
        'title' => $this->request->getAppVar('newsHost').': '.$this->request->getUrlParam('group'),
        'updated' => date('c') // TODO: Make this reflect the time of the last message
      );

      $this->appendElementsFromArray($this->root, $elements);

      $this->root->appendChild($this->doc->createElement('link'))
        ->setAttribute('rel', 'self')->ownerElement
        ->setAttribute('href', $elements['id'].'?format=atom');

      $this->root->appendChild($this->doc->createElement('author'))
        ->appendChild($this->doc->createElement('name'))
          ->appendChild($this->doc->createTextNode($this->request->getUrlParam('group')));
    }

    /**
     * @param \stdClass $item
     */
    private function createChannelItem(\stdClass $item) {
      $itemEl = $this->root->appendChild($this->doc->createElement('entry'));

	    list($authorName, $authorEmail) = $this->stringFormatter->formatEmailAuthor($item->author, '');
      $elements = array(
        'id' => 'http://'.$this->request->getServerParam('HTTP_HOST').'/'.$this->request->getUrlParam('group').'/a/'.$item->id,
        'title' => $this->stringFormatter->formatEmailSubject($item->subject, ''),
        'updated' => date('c', $item->timestamp)
      );

      $this->appendElementsFromArray($itemEl, $elements);

      $itemEl->appendChild($this->doc->createElement('link'))
        ->setAttribute('rel', 'alternate')->ownerElement
        ->setAttribute('href', $elements['id']);

      $itemEl->appendChild($this->doc->createElement('author'))
        ->appendChild($this->doc->createElement('name'))
          ->appendChild($this->doc->createTextNode($authorName));
    }

    private function createChannelItems() {
      while ($item = $this->overview->getItem()) {
        $this->createChannelItem($item);
      }
    }

    public function render() {
      header('Content-Type: text/xml; charset=utf-8');

      $this->createBaseDocument();
      $this->createChannelHeader();
      $this->createChannelItems();

      echo $this->doc->saveXML();
    }

  }
