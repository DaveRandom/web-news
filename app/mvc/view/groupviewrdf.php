<?php

  namespace PHPNews\View;

  class GroupViewRDF {

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

      $this->root = $this->doc->appendChild($this->doc->createElementNS('http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'rdf:RDF'));
      $this->root->setAttribute('xmlns', 'http://my.netscape.com/rdf/simple/0.9/');
    }

    private function createChannelHeader() {
      $channel = $this->root->appendChild($this->doc->createElement('channel'));

      $elements = array(
        'title' => $this->request->getAppVar('newsHost').': '.$this->request->getUrlVar('group'),
        'link' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group'),
        'description' => $this->request->getUrlVar('group').' newsgroup at '.$this->request->getAppVar('newsHost')
      );

      $this->appendElementsFromArray($channel, $elements);
    }

    /**
     * @param \stdClass $item
     */
    private function createChannelItem(\stdClass $item) {
      $itemEl = $this->root->appendChild($this->doc->createElement('item'));

	    list($authorName, $authorEmail) = $this->stringFormatter->formatEmailAuthor($item->author, '');
      $elements = array(
        'title' => $this->stringFormatter->formatEmailSubject($item->subject, ''),
        'link' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group').'/a/'.$item->id,
        'description' => $authorEmail ? $authorName.' ['.$authorEmail.']' : $authorName
      );

      $this->appendElementsFromArray($itemEl, $elements);
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
