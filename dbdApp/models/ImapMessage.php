<?php

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
		$this->headers = base64_encode($headers);
	}

	public function setSubject($subject) {
		$this->subject = base64_encode($subject);
	}

	public function setBody($body) {
		$this->body = base64_encode($body);
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
		return base64_decode($this->headers);
	}

	public function getSubject() {
		return base64_decode($this->subject);
	}

	public function getBody() {
		return base64_decode($this->body);
	}
}