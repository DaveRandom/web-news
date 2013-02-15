<?php

  namespace PHPNews\Model;

  class ArticleSummary {

    /**
     * @var string Name of the group the message was retrieved from
     */
    private $groupName;

    /**
     * @var int ID of the message within the group the message was retrieved from
     */
    private $articleId;

    /**
     * @var string The message body
     */
    private $body;

    /**
     * @var string The message author address
     */
    private $author;

    /**
     * @var string The message date
     */
    private $date;

    /**
     * @var string The message subject
     */
    private $subject;

    /**
     * @var string The charset of the message body
     */
    private $bodyCharset = 'iso-8859-1';

    /**
     * @var string[] Associative array of attachment filenames mapped to MIME parts
     */
    private $attachments = array();

    /**
     * @var int[] IDs of previous messages in this thread
     */
    private $refs = array();

    /**
     * @var string[] Groups this message has been sent to
     */
    private $groups = array();

    /**
     * @param string $groupName Name of the group the message was retrieved from
     */
    public function setGroupName($groupName) {
      $this->groupName = $groupName;
    }

    /**
     * @return string Name of the group the message was retrieved from
     */
    public function getGroupName() {
      return $this->groupName;
    }

    /**
     * @param int $articleId ID of the message within the group the message was retrieved from
     */
    public function setArticleId($articleId) {
      $this->articleId = (int) $articleId;
    }

    /**
     * @return int ID of the message within the group the message was retrieved from
     */
    public function getArticleId() {
      return $this->articleId;
    }

    /**
     * @param string $body The message body
     */
    public function setBody($body) {
      $this->body = $body;
    }

    /**
     * @return string The message body
     */
    public function getBody() {
      return $this->body;
    }

    /**
     * @param string $bodyCharset The charset of the message body
     */
    public function setBodyCharset($bodyCharset) {
      $this->bodyCharset = $bodyCharset;
    }

    /**
     * @return string The charset of the message body
     */
    public function getBodyCharset() {
      return $this->bodyCharset;
    }

    /**
     * @param int    $mimePartNo The MIME part from which this attachment can be extracted
     * @param string $fileName   The name of the attached file
     */
    public function addAttachment($mimePartNo, $fileName = NULL) {
      $fileName = isset($fileName) ? $fileName : 'attachment_' . $mimePartNo;
      $this->attachments[$mimePartNo] = $fileName;
    }

    /**
     * @return string[] Associative array of attachment filenames mapped to MIME parts
     */
    public function getAttachments() {
      return $this->attachments;
    }

    /**
     * @param int[] $refs IDs of previous messages in this thread
     */
    public function setMeta($headers) {
      if (isset($headers['from'])) {
        $this->author = $headers['from'][0];
      }
      if (isset($headers['date'])) {
        $this->date = strtotime($headers['date'][0]);
      }
      if (isset($headers['subject'])) {
        $this->subject = $headers['subject'][0];
      }
    }

    /**
     * @return string The message author address
     */
    public function getAuthor() {
      return $this->author;
    }

    /**
     * @return string The message author address
     */
    public function getDate() {
      return $this->date;
    }

    /**
     * @return string The message author address
     */
    public function getSubject() {
      return $this->subject;
    }

    /**
     * @param int[] $refs IDs of previous messages in this thread
     */
    public function setRefs($refs) {
      $this->refs = $refs;
    }

    /**
     * @return int[] IDs of previous messages in this thread
     */
    public function getRefs() {
      return $this->refs;
    }

    /**
     * @param string[] $groups Groups this message has been sent to
     */
    public function setGroups($groups) {
      $this->groups = $groups;
    }

    /**
     * @return string[] Groups this message has been sent to
     */
    public function getGroups() {
      return $this->groups;
    }

  }
