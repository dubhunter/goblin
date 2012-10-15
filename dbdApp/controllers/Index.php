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
		echo 'USAGE: dbdcli -a migrate --imap-host=HOST --imap-user=USER --imap-pass=PASS --google-domain=DOMAIN --google-user=USER --google-pass=PASS --google-label=LABEL --limit=LIMIT' . PHP_EOL;
		echo 'USAGE: dbdcli -a backup --imap-host=HOST --imap-user=USER --imap-pass=PASS --imap-box=INBOX --dir=DIR' . PHP_EOL;
		echo 'USAGE: dbdcli -a restore --google-domain=DOMAIN --google-user=USER --google-pass=PASS --google-label=LABEL --dir=DIR --limit=LIMIT' . PHP_EOL;
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
		$label = $this->getParam('google-label');

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

			$response = $google->postMessage($msg, $label);

			echo ($response->ok ? 'OK' : 'ERR') . PHP_EOL;
			ob_flush();

			if ($limit > 0 && $i >= $limit) break;
			$i++;
		}
	}

	public function doBackup() {
		$imap = $this->getImap(
			$this->getParam('imap-host') ?: 'localhost',
			$this->getParam('imap-box'),
			$this->getParam('imap-user'),
			$this->getParam('imap-pass')
		);

		$dir = $this->getParam('dir') ?: realpath(DBD_APP_DIR . '../backups') . '/' . $this->getParam('imap-user') . '/';
		$limit = $this->getParam('limit') ?: -1;


		if ($this->getParam('imap-box')) {
			$dir .= $this->getParam('imap-box') . '/';
		}

		if (!is_dir($dir)) {
			mkdir($dir, 0775, true);
		}

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

			$filename = microtime(true) . '.goblin';

			$data = serialize($msg);
			file_put_contents($dir . $filename, $data);

			echo $filename . PHP_EOL;
			ob_flush();

			if ($limit > 0 && $i >= $limit) break;
			$i++;
		}
	}

	public function doRestore() {
		$dir = rtrim($this->getParam('dir'), '/') . '/';

		if (!is_dir($dir)) {
			die ('Cannot open dir: ' . $dir . PHP_EOL);
		}

		try {
			$google = new GoogleMailClient($this->getParam('google-domain'), $this->getParam('google-user'), $this->getParam('google-pass'));
		} catch (GoogleException $e) {
			die($e->getMessage() . PHP_EOL);
		}
		$label = $this->getParam('google-label');

		$limit = $this->getParam('limit') ?: -1;

		$files = wmFileSystem::scanDir($dir, 1);
		$n = count($files);

		echo PHP_EOL;

		echo $n . ' Messages' . PHP_EOL . PHP_EOL;
		ob_flush();

		$i = 1;

		foreach ($files as $file) {
			echo $i . ' of ' . $n . ' - ';
			$serial = file_get_contents($dir . $file['name']);
			echo $serial;
			$msg = unserialize($serial);
			echo $msg->getSubject() . ' ......';
			ob_flush();

//			$response = $google->postMessage($msg, $label);
//
//			echo ($response->ok ? 'OK' : 'ERR') . PHP_EOL;
			ob_flush();

			if ($limit > 0 && $i >= $limit) break;
			$i++;
		}
	}
}
