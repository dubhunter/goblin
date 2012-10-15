<?php

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
