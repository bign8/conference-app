<?php

require_once( __DIR__ . '/PHPMailer/PHPMailerAutoload.php' );

class myMail extends PHPMailer {
	function __construct() {
		parent::__construct();
		$this->setFrom(config::myMail_from_email, config::myMail_from_name);
		$this->isHTML(true);
	}

	public function notify($subject, $html) {

		// Add Custom Footer to messages
		// $html .= file_get_contents(__DIR__ . '/foot.html');

		$this->addAddress(config::myMail_notify_email, config::myMail_notify_name);
		$this->Subject = $subject;
		$this->Body    = $html;
		$this->AltBody = strip_tags($html);
		return $this->send();
	}

	public function sendMsg($subject, $html, $to, $name = '', $hasFoot = true) {

		// Add Custom Footer to messages
		// if ($hasFoot) $html .= file_get_contents(__DIR__ . '/foot.html');

		$this->addAddress($to, $name);
		$this->Subject = $subject;
		$this->Body    = $html;
		$this->AltBody = strip_tags($html);
		return $this->send();
	}
}