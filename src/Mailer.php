<?php

namespace Sowe\Framework;

use \PHPMailer\PHPMailer\PHPMailer;
use \PHPMailer\PHPMailer\SMTP;
use \PHPMailer\PHPMailer\Exception;

class Mailer
{
    protected $hostname;
    protected $username;
    protected $password;
    protected $sender;
    protected $sendername;
    protected $auth;
    protected $security;
    
    public $error;
    protected $mail;

    public function __construct(
        $hostname,
        $username,
        $password,
        $sender,
        $sendername,
        $auth = true,
        $security = PHPMailer::ENCRYPTION_STARTTLS
    ) {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->sender = $sender;
        $this->sendername = $sendername;
        $this->auth = $auth;
        $this->security = $security;
    }

    public function new()
    {
        $this->mail = new PHPMailer(true);

        $this->mail->isSMTP();
        $this->mail->SMTPAuth   = $this->auth;
        $this->mail->Host       = $this->hostname;
        $this->mail->Username   = $this->username;
        $this->mail->Password   = $this->password;

        switch ($this->security) {
            case PHPMailer::ENCRYPTION_STARTTLS:
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $this->mail->Port       = 587;
                break;
            case PHPMailer::ENCRYPTION_SMTPS:
                $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $this->mail->Port       = 465;
                break;
            default:
                $this->mail->Port       = 25;
                break;
        }

        $this->mail->setFrom($this->sender, $this->sendername);
        return $this;
    }
    
    public function debug()
    {
        $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
        return $this;
    }

    public function to($address, $name = '')
    {
        $this->mail->addAddress($address, $name = '');
        return $this;
    }

    public function cc($address)
    {
        $this->mail->addCC($address);
        return $this;
    }
    public function bcc($address)
    {
        $this->mail->addBCC($address);
        return $this;
    }

    public function subject($subject)
    {
        $this->mail->Subject = $subject;
        return $this;
    }

    public function body($body)
    {
        $this->mail->isHTML(false);
        $this->mail->Body = $body;
        return $this;
    }

    public function htmlbody($body)
    {
        $this->mail->isHTML(true);
        $this->mail->Body = $body;
        return $this;
    }

    public function altbody($body)
    {
        $this->mail->AltBody = $body;
        return $this;
    }

    public function attachment(
        $path,
        $name = '',
        $encoding = 'base64',
        $type = '',
        $disposition = 'attachment'
    ) {
        $this->mail->addAttachment($path, $name, $encoding, $type, $disposition);
        return $this;
    }

    public function send()
    {
        try {
            if ($this->mail->send()) {
                return true;
                $this->error = false;
            }
        } catch (phpmailerException $e) {
            $this->error = $e->errorMessage();
        } catch (Exception $e) {
            $this->error = $e->getMessage();
        }
        return false;
    }
}
