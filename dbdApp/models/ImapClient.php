<?php

class ImapClient {

	protected $host;
	protected $mbox;
	protected $user;
	protected $pass;
	protected $imap;

	public function __construct($host, $mbox, $user, $pass) {
		$this->host = '{' . $host . ':993/imap/ssl/novalidate-cert}';
		$this->mbox = $mbox ?: '';
		$this->user = $user;
		$this->pass = $pass;
		$this->imap = imap_open($this->host . $this->mbox, $this->user, $this->pass);
		if (!$this->imap) {
			throw new ImapException(imap_last_error());
		}
	}

	public function __destruct() {
		if ($this->imap) {
			imap_close($this->imap);
		}
	}

	public function getMailboxes() {
		$return = array();
		$boxes = imap_list($this->imap, $this->host . $this->mbox, '*');
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