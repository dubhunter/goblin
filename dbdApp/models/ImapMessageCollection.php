<?php

class ImapMessageCollection implements Iterator, ArrayAccess, Countable {

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
	 * (PHP 5 &gt;= 5.1.0)<br/>
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 */
	public function count() {
		return count($this->msgIds);
	}

	/**
	 * Return the current element
	 * @link http://php.net/manual/en/iterator.current.php
	 * @return mixed Can return any type.
	 */
	public function current() {
		return $this->getImapMessage($this->msgIds[$this->index]);
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

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Whether a offset exists
	 * @link http://php.net/manual/en/arrayaccess.offsetexists.php
	 * @param mixed $offset <p>
	 * An offset to check for.
	 * </p>
	 * @return boolean true on success or false on failure.
	 * </p>
	 * <p>
	 * The return value will be casted to boolean if non-boolean was returned.
	 */
	public function offsetExists($offset) {
		return isset($this->msgIds[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to retrieve
	 * @link http://php.net/manual/en/arrayaccess.offsetget.php
	 * @param mixed $offset <p>
	 * The offset to retrieve.
	 * </p>
	 * @return mixed Can return all value types.
	 */
	public function offsetGet($offset) {
		return $this->getImapMessage($this->msgIds[$offset]);
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to set
	 * @link http://php.net/manual/en/arrayaccess.offsetset.php
	 * @param mixed $offset <p>
	 * The offset to assign the value to.
	 * </p>
	 * @param mixed $value <p>
	 * The value to set.
	 * </p>
	 * @throws Exception
	 * @return void
	 */
	public function offsetSet($offset, $value) {
		throw new Exception('Cannot set...');
	}

	/**
	 * (PHP 5 &gt;= 5.0.0)<br/>
	 * Offset to unset
	 * @link http://php.net/manual/en/arrayaccess.offsetunset.php
	 * @param mixed $offset <p>
	 * The offset to unset.
	 * </p>
	 * @throws Exception
	 * @return void
	 */
	public function offsetUnset($offset) {
		throw new Exception('Cannot unset...');
	}

	/**
	 * @param $msgId
	 * @return ImapMessage
	 */
	protected function getImapMessage($msgId) {
		$m = new ImapMessage();

		$overview = imap_fetch_overview($this->imap, $msgId, 0);

		$m->setFlagged($overview[0]->flagged);
		$m->setUnread(!$overview[0]->seen);
		$m->setDraft($overview[0]->draft || preg_match('/Draft/', $this->mbox));
		$m->setDeleted($overview[0]->deleted || preg_match('/Deleted/', $this->mbox));
		$m->setSent(preg_match('/Sent/', $this->mbox));

		if (!(preg_match('/^INBOX$/', $this->mbox) || preg_match('/^\[Gmail\]/', $this->mbox))) {
			$label = preg_replace('/^INBOX\./', '', $this->mbox);
			$m->setLabel($label);
		}

		$m->setSubject($overview[0]->subject);
		$m->setHeaders(imap_fetchheader($this->imap, $msgId));
		$m->setBody(imap_body($this->imap, $msgId, FT_PEEK));

		return $m;
	}
}
