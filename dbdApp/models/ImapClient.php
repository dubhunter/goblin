<?php

class ImapClient {

	protected $host;
	protected $mbox;
	protected $user;
	protected $pass;
	protected $imap;

	public function __construct($host, $mbox, $user, $pass) {
		$this->host = '{' . $host . ':993/imap/ssl/novalidate-cert}';
		$this->mbox = $mbox ?: 'INBOX';
		$this->user = $user;
		$this->pass = $pass;
		$this->imap = imap_open($this->host . $this->mbox, $this->user, $this->pass);
		if (!$this->imap) {
			throw new ImapException(imap_last_error());
		}
	}

	public function __destruct() {
		imap_close($this->imap);
	}

	public function getMailboxes() {
		$return = array();
		$boxes = imap_list($this->imap . $this->mbox, $this->host, '*');
		if (!is_array($boxes)) {
			return $return;
		}
		foreach ($boxes as $box) {
			$return[] = $box;
		}
		return $return;
	}

	public function getMessageCount() {
		return imap_num_msg($this->imap);
	}

	public function getMessages() {
		return new ImapMessageCollection(
			$this->imap,
			$this->mbox,
			imap_sort($this->imap, SORTARRIVAL, 0)
		);
	}
}

class ImapMessage {

	protected $flagged = false;
	protected $unread = false;
	protected $draft = false;
	protected $deleted = false;
	protected $sent = false;

	protected $folder; //??

	protected $subject;
	protected $headers;
	protected $body;

	public function setFlagged($flagged) {
		$this->flagged = $flagged;
	}

	public function setUnread($unread) {
		$this->unread = $unread;
	}

	public function setDraft($draft) {
		$this->draft = $draft;
	}

	public function setDeleted($deleted) {
		$this->deleted = $deleted;
	}

	public function setSent($sent) {
		$this->sent = $sent;
	}

	public function setHeaders($headers) {
		$this->headers = $headers;
	}

	public function setSubject($subject) {
		$this->subject = $subject;
	}

	public function setBody($body) {
		$this->body = $body;
	}

	public function isFlagged() {
		return $this->flagged ? true : false;
	}

	public function isUnread() {
		return $this->unread ? true : false;
	}

	public function isDraft() {
		return $this->draft ? true : false;
	}

	public function isDeleted() {
		return $this->deleted ? true : false;
	}

	public function isSent() {
		return $this->sent ? true : false;
	}

	public function getHeaders() {
		return $this->headers;
	}

	public function getSubject() {
		return $this->subject;
	}

	public function getBody() {
		return $this->body;
	}
}

class ImapMessageCollection implements Iterator {

	protected $imap;
	protected $mbox;
	protected $index;
	protected $msgIds = array();

	public function __construct($imap, $mbox, $msgIds) {
		$this->imap = $imap;
		$this->mbox = $mbox;
		$this->msgIds = $msgIds;
		$this->index = 0;
	}

	/**
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current() {
		$msg = $this->msgIds[$this->index];

		$m = new ImapMessage();

		$overview = imap_fetch_overview($this->imap, $msg, 0);

		$m->setFlagged($overview[0]->flagged);
		$m->setUnread(!$overview[0]->seen);
		$m->setDraft($overview[0]->draft || preg_match('/Draft/', $this->mbox));
		$m->setDeleted($overview[0]->deleted || preg_match('/Deleted/', $this->mbox));
		$m->setSent(preg_match('/Sent/', $this->mbox));

		$m->setSubject($overview[0]->subject);
		$m->setHeaders(imap_fetchheader($this->imap, $msg));
		$m->setBody(imap_body($this->imap, $msg, FT_PEEK));

		return $m;
	}

	/**
	 * Move forward to next element
	 * @link http://php.net/manual/en/iterator.next.php
	 * @return void Any returned value is ignored.
	 */
	public function next() {
		++$this->index;
	}

	/**
	 * Return the key of the current element
	 * @link http://php.net/manual/en/iterator.key.php
	 * @return mixed scalar on success, or null on failure.
	 */
	public function key() {
		return $this->index;
	}

	/**
	 * Checks if current position is valid
	 * @link http://php.net/manual/en/iterator.valid.php
	 * @return boolean The return value will be casted to boolean and then evaluated.
	 * Returns true on success or false on failure.
	 */
	public function valid() {
		return isset($this->msgIds[$this->index]);
	}

	/**
	 * Rewind the Iterator to the first element
	 * @link http://php.net/manual/en/iterator.rewind.php
	 * @return void Any returned value is ignored.
	 */
	public function rewind() {
		$this->index = 0;
	}
}

class ImapException extends Exception {}
