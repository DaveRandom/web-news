<?php

  namespace PHPNews\View;

  class StringFormatter {

    const SUBJECT_FIRSTLINEONLY = 1;

    /**
     * @param array $matches
     * @return string
     */
    public function decodeHeader($matches) {
      if (strtolower($matches['encoding']) == 'b') {
        $text = base64_decode($matches['text']);
      } else {
        $text = quoted_printable_decode($matches['text']);
      }

      return $this->toUTF8($text, $matches['charset']);
    }

    /**
     * @param string $header
     * @return string
     */
    private function parseHeader($header) {
      if (strpos($header, '=?') === FALSE) {
        $result = $this->toUTF8($header, 'iso-8859-1');
      } else {
        $expr = '/=\?(?P<charset>.+?)\?(?P<encoding>[qb])\?(?P<text>.+?)(?:\?=|$)/i';
        $result = preg_replace_callback($expr, array($this, 'decodeHeader'), $header);
      }

      return $result;
    }

    /**
     * @param string $str
     * @param string $charset
     * @return string
     */
    public function toUTF8($string, $charset) {
      if (str_replace('-', '', strtolower($charset)) == 'utf8') {
        return $string;
      }

      $charset = $charset ?: 'iso-8859-1';
      $result = @iconv($charset, 'UTF-8//IGNORE', $string);

      if ($result === FALSE) {
        $result = $string;
      }

      return $result;
    }

    /**
     * @param string $address
     * @return string
     */
    public function spamProtect($address) {
      // php.net addresses are not protected!
      if (!preg_match('/^(.+)@php\.net/i', $address)) {
        $translate = array('@' => ' at ', '.' => ' dot ');
        $address = strtr($address, $translate);
      }
      return $address;
    }

    /**
     * @param string $author
     * @return array
     */
    public function formatEmailAuthor($author) {
      $author = $this->parseHeader($author);

      $name = $email = $author;
      if (preg_match('/^\s*(.+)\s+\("?(.+?)"?\)\s*$/', $author, $matches)) {
        $name = $matches[2];
        $email = $matches[1];
      } else if (preg_match('/^\s*"?(.+?)"?\s*<(.+)>\s*$/', $author, $matches)) {
        $name = $matches[1];
        $email = $matches[2];
      } else {
        $name = $this->spamProtect($author);
        $email = $author;
      }

      $email = filter_var($email, FILTER_VALIDATE_EMAIL);
      $email = $this->spamProtect($email);

      return array($name, $email);
    }

    /**
     * @param string $subject
     * @return string
     */
    public function formatEmailSubject($subject, $lineLength = 100, $flags = 0) {
      $subject = $this->parseHeader($subject);
      $subject = preg_replace('/^\s*([a-z]{2}:\s*)?(?:[a-z]{2}:\s*)*\[(?:PHP|PEAR)(?:-.*?)?]\s*(?:[a-z]{2}:\s*)*/i', '$1', $subject);

      if ($flags & self::SUBJECT_FIRSTLINEONLY) {
        if (strlen($subject) > $lineLength) {
          $subject = substr($subject, 0, $lineLength) . '...';
        }
      } else {
        $subject = wordwrap($subject, $lineLength);
      }

      return $subject;
    }

    /**
     * @param int $timestamp
     * @return string
     */
    public function formatLocalTimestamp($timestamp) {
      return strftime('%c', $timestamp);
    }

    /**
     * @param string $string
     * @return string
     */
    public function escapeHTMLOutput($string) {
      $string = htmlspecialchars($string, ENT_QUOTES | ENT_IGNORE, 'utf-8');
      $string = preg_replace_callback('/^\s(\s+)/', function($matches) {
        return str_repeat('&nbsp;', strlen($matches[1]));
      }, $string);

      return $string;
    }

  }
