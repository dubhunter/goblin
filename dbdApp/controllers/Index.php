<?php

class Index extends GMController {

	protected function getImap($host, $mbox, $user, $pass) {
		echo 'Connecting to: ' . $host . '/' . $mbox . ' ' . $user . ':' . $pass . PHP_EOL . PHP_EOL;
		ob_flush();
		try {
			return new ImapClient($host, $mbox, $user, $pass);
		} catch (Exception $e) {
			die($e->getMessage() . PHP_EOL);
		}
	}

	protected function listMailboxes(ImapClient $imap) {

		$boxes = $imap->getMailboxes();
		if (count($boxes) == 0) {
			echo "No mailboxes to list..." . PHP_EOL;
			ob_flush();
			return;
		}

		foreach ($boxes as $box) {
			echo $box . PHP_EOL;
			ob_flush();
		}
	}

	public function doDefault() {
		echo 'USAGE: dbdcli -a list --imap-host=HOST --imap-user=USER --imap-pass=PASS --imap-box=INBOX' . PHP_EOL;
		echo 'USAGE: dbdcli -a migrate --imap-host=HOST --imap-user=USER --google-domain=DOMAIN --google-user=USER --google-pass=PASS --google-label=LABEL --limit=LIMIT' . PHP_EOL;
	}

	public function doList() {
		$imap = $this->getImap(
			$this->getParam('imap-host') ?: 'localhost',
			$this->getParam('imap-box'),
			$this->getParam('imap-user'),
			$this->getParam('imap-pass')
		);

		$this->listMailboxes($imap);

		echo PHP_EOL;
		echo $imap->getMessageCount() . ' Messages' . PHP_EOL . PHP_EOL;
		ob_flush();
	}

	public function doMigrate() {
		$imap = $this->getImap(
			$this->getParam('imap-host') ?: 'localhost',
			$this->getParam('imap-box'),
			$this->getParam('imap-user'),
			$this->getParam('imap-pass')
		);

		$google = new GoogleMailClient($this->getParam('google-domain'), $this->getParam('google-user'), $this->getParam('google-pass'));

		$limit = $this->getParam('limit') ?: -1;

		$this->listMailboxes($imap);

		$n = $imap->getMessageCount();

		echo PHP_EOL;

		echo $n . ' Messages' . PHP_EOL . PHP_EOL;
		ob_flush();

		$i = 1;

		/** @var $msg ImapMessage */
		foreach ($imap->getMessages() as $msg) {
			echo $i . ' of ' . $n . ' - ';
			echo $msg->getSubject() . ' ......';
			ob_flush();

			$response = $google->postMessage($msg, $this->getParam('google-label'));

			echo ($response->ok ? 'OK' : 'ERR') . PHP_EOL;
			ob_flush();

			if ($limit > 0 && $i >= $limit) break;
			$i++;
		}
	}
}
