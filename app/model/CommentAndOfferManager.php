<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 24.9.2018
 * Time: 13:19
 */

namespace App\Model;

use Latte;
use Nette\Mail\Message;
use Nette\Mail\SendmailMailer;
use Nette\Security\User;

class CommentAndOfferManager
{
    /** @var  OfferManager */
    private $offerManager;
    /** @var  CommentManager */
    private $commentManager;
    /** @var  UserManager */
    private $userManager;
    /** @var  Sender */
    private $sender;

    public function __construct(OfferManager $offerManager, CommentManager $commentManager,
    UserManager $userManager, Sender $sender)
    {
        $this->offerManager = $offerManager;
        $this->commentManager = $commentManager;
        $this->userManager = $userManager;
        $this->sender = $sender;
    }


    public function addComment($properties){
        $this->commentManager->addComment($properties);
        $text = $properties[CommentManager::COLUMN_TEXT];
        $offer = $properties[CommentManager::COLUMN_OFFER];
        $author = $properties[CommentManager::COLUMN_USER];
        $reaction = $properties[CommentManager::COLUMN_COMMENT] == null ? false : true;
        if($reaction === true){
            $this->addCommentSendMail($offer, $text, $author, $reaction, false);
        }
        $this->addCommentSendMail($offer, $text, $author, $reaction);
    }

    public function addCommentSendMail($offerID, $text, $authorId, $reaction = false, $customItem = true){
        $offer = $this->offerManager->get($offerID);
        $title = $offer[OfferManager::COLUMN_TITLE];
        $price = $offer[OfferManager::COLUMN_PRICE];
        $description = $offer[OfferManager::COLUMN_DESCRIPTION];
        $authorID = $offer[OfferManager::COLUMN_USER];
        $author = $this->userManager->get($authorID);
        $commentAuthor = $this->userManager->get($authorId);
        $email = $customItem === true ? $author[UserManager::COLUMN_EMAIL] :
        $commentAuthor[UserManager::COLUMN_EMAIL];
        $canSend = $customItem === true ? $author[UserManager::COLUMN_EMAIL_SUBSCRIPTION] > 0 :
            $commentAuthor[UserManager::COLUMN_EMAIL_SUBSCRIPTION] > 0;
        if($canSend === true){
            $template = $this->sender->createTemplate();
            $file = __DIR__ . '/emailTemplates/addComment.latte';
            $subject = "P??id??n?? koment????e k Va???? polo??ce ve Va??em ????tu Bubovick?? bazar";
            if($reaction === true){
                $file = $customItem === false ? __DIR__ .'/emailTemplates/reactionToComment.latte' :
                    __DIR__ .'/emailTemplates/reactionToItem.latte';
                $subject = $customItem === true ? "P??id??n?? reakce na koment???? k Va???? polo??ce ve Va??em ????tu Bubovick?? bazar" :
                    "P??id??n?? reakce na V???? koment???? ve Va??em ????tu Bubovick?? bazar";
            }
            $template->setFile($file);
            $template->offerId = $offerID;
            $template->title = $title;
            $template->price = $price;
            $template->description = mb_strlen($description) > 50 ?
                mb_substr($description, 0, 50)."..." : $description;
            if($reaction === false){
                $template->textOfComment = $text;
            } else {
                $template->textOfReaction = $text;
            }
            $template->userId = $authorID;
            $message = new Message;
            $message->setFrom("supdaniel@seznam.cz")
                ->addTo($email)
                ->setSubject($subject)
                ->setHtmlBody($template);
            $mailer = new SendmailMailer;
            $mailer->send($message);
        }
    }


}