<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 27.9.2018
 * Time: 15:39
 */

namespace App\Model;


use Nette;
use Nette\Security\Passwords;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Database\Explorer;


class RegistrationManager
{
    /** @var  Sender */
    private $sender;
    public function __construct(Sender $sender)
    {
        $this->sender = $sender;
    }

    public function sendRegisterEmail($username, $password, $email){
        $template = $this->sender->createTemplate();
        $template->setFile(__DIR__ . '/emailTemplates/register.latte');
        $template->username = $username;
        $template->password = $password;
        $message = new Message;
        $message->setFrom("supdaniel@seznam.cz")
            ->addTo($email)
            ->setSubject("Registrace Vašeho účtu na Bubovickém bazaru")
            ->setHtmlBody($template);
        $mailer = new SendmailMailer;
        $mailer->send($message);
    }
}