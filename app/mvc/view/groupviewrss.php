<?php

  namespace PHPNews\View;

  class GroupViewRSS {

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
     * @var \DOMElement $channel
     */
    private $channel;

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

      $root = $this->doc->appendChild($this->doc->createElement('rss'));
      $root->setAttribute('version', '2.0');
      $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');

      $this->channel = $root->appendChild($this->doc->createElement('channel'));
    }

    private function createChannelHeader() {
      $elements = array(
        'title' => $this->request->getAppVar('newsHost').': '.$this->request->getUrlVar('group'),
        'link' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group'),
        'description' => $this->request->getUrlVar('group').' newsgroup at '.$this->request->getAppVar('newsHost')
      );

      $this->appendElementsFromArray($this->channel, $elements);
      $this->channel->appendChild($this->doc->createElementNS('http://www.w3.org/2005/Atom', 'atom:link'))
        ->setAttribute('rel', 'self')->ownerElement
        ->setAttribute('type', 'application/rss+xml')->ownerElement
        ->setAttribute('href', 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group'));
    }

    /**
     * @param \stdClass $item
     */
    private function createChannelItem(\stdClass $item) {
      $itemEl = $this->channel->appendChild($this->doc->createElement('item'));

	    list($authorName, $authorEmail) = $this->stringFormatter->formatEmailAuthor($item->author, '');
      $elements = array(
        'guid' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group').'/a/'.$item->id,
        'link' => 'http://'.$this->request->getServerVar('HTTP_HOST').'/'.$this->request->getUrlVar('group').'/a/'.$item->id,
        'title' => $this->stringFormatter->formatEmailSubject($item->subject, ''),
        'pubDate' => date('r', $item->timestamp),
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
      header('Content-Type: application/rss+xml; charset=utf-8');

      $this->createBaseDocument();
      $this->createChannelHeader();
      $this->createChannelItems();

      echo $this->doc->saveXML();
    }

  }
