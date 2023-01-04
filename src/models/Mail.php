<?php

class Mail
{
    public string $body;
    public $to;
    public string $subject;

    function __construct( string $to = '', string $subject = '', string $body = '') 
    {
        if (empty($subject)) {
            $subject = 'Notifying You';
        }

        $this->to = $to;
        $this->subject = $subject;
        $this->body = $body;
    }

    public function setHeader() {
        $this->body .= file_get_contents( '../storage/email/head.html' );
    }

    public function setFooter() {
        $this->body .= file_get_contents( '../storage/email/footer.html' );
    }

    public function clearBody() {
        $this->body = '';
    }

    public function appendBody( string $body, string $line = '' ) : void {
        if ( empty( $line ) ) {
            $this->body .= "<p>{$body}</p>";
        }
        else {
            $this->body .= "<span>{$body}</span><br>";
        }
    }

    public function setBody($body) {
        $this->setHeader();
        $this->appendBody($body);
        $this->setFooter();
    }

    public function send() {
        $temp_body = $this->body;
        $this->body = '';
        //$this->set_default_template_header();
        $this->body .= $temp_body;
        //$this->set_default_template_footer();
        
        _sendMail( $this->to, $this->subject, $this->body );
    }

    /**
     * Send email notification at a later date
     * 
     * @param $when is the time to send it
     * @param $type is the type of email to send
     */
    public function sendLater(int $when = 3, string $type = 'general'): void
    {
        if (1 > $when) {
            return;
        }

        $time = _getDatetime();
        $send_at = _getDatetimeByInterval($time, $when, 'seconds');

        $db = _db();
        $db->insert('queued_emails', [
            'to' => $this->to,
            'subject' => $this->subject,
            'body' => $this->body,
            'type' => $type,
            'send_at' => $send_at,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
    }

    public function sendOTP($code, $expiresIn) {
        $this->subject = sprintf( '%s is your %s OTP', $code, getenv('APP_NAME') );

        $this->setHeader();
		$this->appendBody("
            Hi there,
            <p>To continue, use the access code below:</p>
            <p><strong style='font-size:20px;''>{$code}</strong></p>
            <p></p>
            <p>Don\'t share this code with anyone. And ignore if you didn't request a code.</p>
        ");
        $this->setFooter();

		$this->send();
    }
}