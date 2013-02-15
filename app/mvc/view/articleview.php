<?php

  namespace PHPNews\View;

  class ArticleView extends HTMLView {

    /**
     * @var \stdClass $overview
     */
    private $summary;

    /**
     * @param \PHPNews\Request $request
     * @param \stdClass $summary
     */
    public function __construct($request, $summary) {
      parent::__construct($request);
      $this->summary = $summary;
    }

    private function htmlifyBody($body) {
      // XSS/CSRF
      $body = trim($body)."\n"; // some versions of PCRE throw up without the LF
      $body = $this->stringFormatter->escapeHTMLOutput($body);

      $patterns = array(
        // Extra line breaks
        '/-+BEGIN PGP SIGNED MESSAGE-+\r?\n(?:Hash: [^\r\n]*)?(?:\r?\n)*(.*)-+BEGIN PGP SIGNATURE-+.*/si' => '$1',

        // Extra line breaks
        '/(\r?\n)\1\1+/' => '\1\1',

        // Linkify links
        '/((mailto|https?|ftp|nntp|news):.+?)(&gt;|\s|\)|\.\s|$)/i' => '<a href="\1">\1</a>\3',

        // Quotes
        '/^&gt;.*?\r?\n(?:&gt;.*?\r?\n)*(?!&gt;)/m' => '<div class="quote">\0</div>',
        '/^(&gt;.*?)\s+(\r?\n)/m' => '\1\2',

        // Signatures
        '/[\r\n](?:-- ?|___+)\r?\n(.*?)(\n[^\n]*wrote:.*?\n&gt;|\s*$)/s' => '<hr /><div class="signature">$1</div><hr />$2',
        '/<hr \/>\s*$/' => '',
      );

      $body = preg_replace(array_keys($patterns), array_values($patterns), $body);


      return nl2br(trim($body), TRUE);
    }

    private function body() {
      $summary = $this->summary;
      $stringFormatter = $this->stringFormatter;

      $groupName = $summary->getGroupName();
      $articleId = $summary->getArticleId();

      $subject = $stringFormatter->formatEmailSubject($summary->getSubject());
      $date = $stringFormatter->formatLocalTimestamp($summary->getDate());

      list($authorName, $authorEmail) = $stringFormatter->formatEmailAuthor($summary->getAuthor());
      if ($authorEmail) {
        $author = '<a href="mailto:'
                . $stringFormatter->escapeHTMLOutput(urlencode($authorEmail))
                . '" class="email fn n">'
                . $stringFormatter->escapeHTMLOutput($authorName)
                . '</a>';
      } else {
        $author = $stringFormatter->escapeHTMLOutput($authorName);
      }

      $body = $stringFormatter->toUTF8($summary->getBody(), $summary->getBodyCharset());
      $body = $this->htmlifyBody($body);

      $refs = array();
      foreach ($summary->getRefs() as $label => $link) {
        $refs[$label + 1] = $stringFormatter->escapeHTMLOutput($link);
      }

      $groups = array();
      foreach ($summary->getGroups() as $label => $link) {
        $groups[$stringFormatter->escapeHTMLOutput($label)] = $stringFormatter->escapeHTMLOutput($link);
      }

      $attachments = array();
      foreach ($summary->getAttachments() as $partId => $name) {
        $attachments[$partId] = $stringFormatter->escapeHTMLOutput($name);
      }

      include __DIR__.'/templates/article.phtml';
    }

    public function render() {
      header('Content-Type: text/html; charset=utf-8');

      $this->head($this->request->getUrlVar('group'));
      $this->body();
      $this->foot();
    }

  }
