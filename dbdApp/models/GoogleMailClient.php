<?php

class GoogleMailClient {
	
	const EOL = "\r\n";
	
	protected $domain;
	protected $user;
	protected $pass;
	protected $endpoint;
	protected $token;
	
	public function __construct($domain, $user, $pass) {
		$this->domain = $domain;
		$this->user = $user;
		$this->pass = $pass;
		$this->endpoint = 'https://apps-apis.google.com/a/feeds/migration/2.0/' . $this->domain . '/' . $this->user . '/mail';

		$response = Requests::post('https://www.google.com/accounts/ClientLogin', array(
			'data' => array(
				'Email' => $this->user . '@' . $this->domain,
				'Passwd' => $this->pass,
				'accountType' => 'HOSTED',
				'service' => 'apps',
			),
		));

		foreach (preg_split('/\n/', $response->text) as $line) {
			$line = explode('=', $line, 2);
			if ($line[0] == 'Auth') {
				$this->token = $line[1];
			}
		}

		if (!$this->token) {
			throw new GoogleException('Error getting Google auth token');
		}
	}
	
	public function getGoogleAuthToken() {
		return $this->token;
	}
	
	public function postMessage(ImapMessage $msg, $label = false) {
		$id = '--=Bound_' . md5(time());

		$part1 = "Content-Type: application/atom+xml" . self::EOL . self::EOL;
		$part1 .= "<?xml version='1.0' encoding='UTF-8'?><entry xmlns='http://www.w3.org/2005/Atom' xmlns:apps='http://schemas.google.com/apps/2006'>" . PHP_EOL;
		$part1 .= "<category scheme='http://schemas.google.com/g/2005#kind' term='http://schemas.google.com/apps/2006#mailItem'/>" . PHP_EOL;
		$part1 .= "<atom:content xmlns:atom='http://www.w3.org/2005/Atom' type='message/rfc822'/>" . PHP_EOL;
		if ($msg->isFlagged()) {
			$part1 .= "<apps:mailItemProperty value='IS_STARRED'/>" . PHP_EOL;
		}
		if ($msg->isUnread()) {
			$part1 .= "<apps:mailItemProperty value='IS_UNREAD'/>" . PHP_EOL;
		}
		if ($msg->isDraft()) {
			$part1 .= "<apps:mailItemProperty value='IS_DRAFT'/>" . PHP_EOL;
		} else if ($msg->isDeleted()) {
			$part1 .= "<apps:mailItemProperty value='IS_TRASH'/>" . PHP_EOL;
		} else if ($msg->isSent()) {
			$part1 .= "<apps:mailItemProperty value='IS_SENT'/>" . PHP_EOL;
		} else if ($msg->getLabel()) {
			$part1 .= "<apps:label labelName='" . $msg->getLabel() . "'/>" . PHP_EOL;
		} else if ($label) {
			$part1 .= "<apps:label labelName='" . $label . "'/>" . PHP_EOL;
		} else {
			$part1 .= "<apps:mailItemProperty value='IS_INBOX'/>" . PHP_EOL;
		}
		$part1 .= "<apps:label labelName='PHP-Import'/>" . PHP_EOL;
		$part1 .= "</entry>" . PHP_EOL;

		$part2 = "Content-Type: message/rfc822" . self::EOL . self::EOL;
		$part2 .= $msg->getHeaders() . self::EOL;
		$part2 .= $msg->getBody() . self::EOL;

		$data = '--' . $id . self::EOL;
		$data .= $part1 . self::EOL;
		$data .= '--' . $id . self::EOL;
		$data .= $part2 . self::EOL;
		$data .= '--' . $id . '--' . self::EOL . self::EOL;

		$opts = array(
			'headers' => array(
				'Content-Type' => 'multipart/related;boundary="' . $id . '"',
				'Authorization' => 'GoogleLogin auth=' . $this->token,
			),
			'data' => $data,
		);

		return Requests::post($this->endpoint, $opts);
	}
}
