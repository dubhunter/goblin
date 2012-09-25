<?php
/**
 * Index.php :: Index Controller Class File
 *
 * @package dbdMVC
 * @version 1.1
 * @author Don't Blink Design <info@dontblinkdesign.com>
 * @copyright Copyright (c) 2006-2009 by Don't Blink Design
 */

/**
 * dbdMVC Index Sample Controller Class
 * @package dbdMVC
 * @uses dbdController
 */
class Index extends GMController {
	
	protected function getGoogleAuthToken($domain, $user, $pass) {

		$response = Requests::post('https://www.google.com/accounts/ClientLogin', array(
			'data' => array(
				'Email' => $user . '@' . $domain,
				'Passwd' => $pass,
				'accountType' => 'HOSTED',
				'service' => 'apps',
			),
		));

		foreach (preg_split('/\n/', $response->text) as $line) {
			$line = explode('=', $line, 2);
			if ($line[0] == 'Auth') {
				return $line[1];
			}
		}
		
		return null;
	}
	
	protected function getImap($host, $user, $pass) {
		echo 'Connecting to: ' . $host . ' ' . $user . ':' . $pass . PHP_EOL . PHP_EOL;
		ob_flush();
		$imap = imap_open($host, $user, $pass) or die(imap_last_error() . PHP_EOL);
		return $imap;
	}
	
	protected function listMailboxes($imap, $host) {
		$boxes = imap_list($imap, $host, '*');
		if (!is_array($boxes)) {
			echo "No mailboxes to list..." . PHP_EOL;
			ob_flush();
			return;
		}

		foreach (imap_list($imap, $host, '*') as $box) {
			echo $box . PHP_EOL;
			ob_flush();
		}
	}

	public function doDefault() {
		echo 'USAGE: dbdcli -a list --imap-host=HOST --imap-user=USER --imap-pass=PASS --imap-box=INBOX' . PHP_EOL;
		echo 'USAGE: dbdcli -a migrate --imap-host=HOST --imap-user=USER --google-domain=DOMAIN --google-user=USER --google-pass=PASS --google-label=LABEL --limit=LIMIT' . PHP_EOL;
	}

	public function doList() {
		$host = $this->getParam('imap-host');
		$mbox = urldecode($this->getParam('imap-box') ?: 'INBOX');
		$host = '{' . $host . ':993/imap/ssl/novalidate-cert}' . $mbox;
		$user = urldecode($this->getParam('imap-user'));
		$pass = $this->getParam('imap-pass');
		
		$imap = $this->getImap($host, $user, $pass);
		
		$this->listMailboxes($imap, $host);

		echo PHP_EOL;
		echo imap_num_msg($imap) . ' Messages' . PHP_EOL . PHP_EOL;
		ob_flush();

		imap_close($imap);
	}

	public function doMigrate() {
		$host = $this->getParam('imap-host');
		$mbox = urldecode($this->getParam('imap-box') ?: 'INBOX');
		$host = '{' . $host . ':993/imap/ssl/novalidate-cert}' . $mbox;
		$user = urldecode($this->getParam('imap-user'));
		$pass = $this->getParam('imap-pass');

		$domain = $this->getParam('google-domain');
		$guser = urldecode($this->getParam('google-user'));
		$gpass = $this->getParam('google-pass');

		$limit = $this->getParam('limit') ?: -1;

		$googleEndpoint = 'https://apps-apis.google.com/a/feeds/migration/2.0/' . $domain . '/' . $guser . '/mail';
		
		$token = $this->getGoogleAuthToken($domain, $guser, $gpass);

		$imap = $this->getImap($host, $user, $pass);
		
		$this->listMailboxes($imap, $host);
		
		$n = imap_num_msg($imap);

		echo PHP_EOL;

		echo $n . ' Messages' . PHP_EOL . PHP_EOL;
		ob_flush();

		$msgs = imap_sort($imap, SORTARRIVAL, 0);

		$i = 1;
		$eol = "\r\n";

		foreach ($msgs as $msg) {
			echo $i . ' of ' . $n . ' - ';
			$overview = imap_fetch_overview($imap, $msg, 0);
			echo $overview[0]->subject;
			ob_flush();
			
			$headers = imap_fetchheader($imap, $msg);
			
			$body = imap_body($imap, $msg, FT_PEEK);

			$id = '--=Bound_' . md5(time());

			$part1 = "Content-Type: application/atom+xml" . $eol . $eol;
			$part1 .= "<?xml version='1.0' encoding='UTF-8'?><entry xmlns='http://www.w3.org/2005/Atom' xmlns:apps='http://schemas.google.com/apps/2006'>" . PHP_EOL;
			$part1 .= "<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/apps/2006#mailItem'/>" . PHP_EOL;
			$part1 .= "<atom:content xmlns:atom='http://www.w3.org/2005/Atom' type='message/rfc822'/>" . PHP_EOL;
			if ($overview[0]->flagged) {
				$part1 .= "<apps:mailItemProperty value='IS_STARRED'/>" . PHP_EOL;
			}
            if (!$overview[0]->seen) {
                    $part1 .= "<apps:mailItemProperty value='IS_UNREAD'/>" . PHP_EOL;
            }
            if ($overview[0]->draft || preg_match('/Draft/', $mbox)) {
                    $part1 .= "<apps:mailItemProperty value='IS_DRAFT'/>" . PHP_EOL;
            } else if ($overview[0]->deleted || preg_match('/Deleted/', $mbox)) {
                    $part1 .= "<apps:mailItemProperty value='IS_TRASH'/>" . PHP_EOL;
            } else if (preg_match('/Sent/', $mbox)) {
                    $part1 .= "<apps:mailItemProperty value='IS_SENT'/>" . PHP_EOL;
            } else if ($this->getParam('google-label')) {
                    $part1 .= "<apps:label labelName='" . urldecode($this->getParam('google-label')) . "'/>" . PHP_EOL;
            } else {
                    $part1 .= "<apps:mailItemProperty value='IS_INBOX'/>" . PHP_EOL;
            }
			$part1 .= "<apps:label labelName='PHP-Import'/>" . PHP_EOL;
			$part1 .= "</entry>" . PHP_EOL;

			$part2 = "Content-Type: message/rfc822" . $eol . $eol;
			$part2 .= $headers . $eol;
			$part2 .= $body . $eol;

			$data = '--' . $id . $eol;
			$data .= $part1 . $eol;
			$data .= '--' . $id . $eol;
			$data .= $part2 . $eol;
			$data .= '--' . $id . '--' . $eol . $eol;

			$opts = array(
				'headers' => array(
					'Content-Type' => 'multipart/related;boundary="' . $id . '"',
					'Authorization' => 'GoogleLogin auth=' . $token,
				),
				'data' => $data,
			);

			$response = Requests::post($googleEndpoint, $opts);
			echo ' ......' . ($response->ok ? 'OK' : 'ERR') . PHP_EOL;
			ob_flush();

			if ($limit > 0 && $i >= $limit) break;
			$i++;
		}

		imap_close($imap);
	}
}
