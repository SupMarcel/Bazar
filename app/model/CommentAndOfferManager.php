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
use Nette\Localization\ITranslator;

class CommentAndOfferManager
{
    /** @var  OfferManager */
    private $offerManager;
    /** @var  CommentManager */
    private $commentManager;
    /** @var  UserManager */
    private $userManager;
    
    private $translator;
    /** @var  Sender */
    private $sender;

    public function __construct(OfferManager $offerManager, CommentManager $commentManager,
    UserManager $userManager, ITranslator $translator, Sender $sender)
    {
        $this->offerManager = $offerManager;
        $this->commentManager = $commentManager;
        $this->userManager = $userManager;
        $this->translator = $translator;
        $this->sender = $sender;
    }


    public function addComment($properties){
        $this->commentManager->addComment($properties);
        $text = $properties[CommentManager::COLUMN_TEXT];
        $offer = $properties[CommentManager::COLUMN_OFFER];
        $author = $properties[CommentManager::COLUMN_USER];
        $reaction = $properties[CommentManager::COLUMN_COMMENT] == null ? false : true;
        if($reaction === true){
            $this->addCommentSendMail($offer, $text, $author, $reaction);
        }
        $this->addCommentSendMail($offer, $text, $author, false);
    }

    public function addCommentSendMail($offerID, $text, $authorId, $reaction = false){
        $offer = $this->offerManager->get($offerID);
        $title = $offer[OfferManager::COLUMN_TITLE];
        $price = $offer[OfferManager::COLUMN_PRICE];
        $description = $offer[OfferManager::COLUMN_DESCRIPTION];
        $authorID = $offer[OfferManager::COLUMN_USER];
        $author = $this->userManager->get($authorID);
        $commentAuthor = $this->userManager->get($authorId);
        $addresses = $this->addressesForEmail($author, $commentAuthor);
        if(!empty($addresses)){
            foreach ($addresses as $address){
                if($address['author'] == true && count($addresses)== 1){
                    if($reaction === false){
                        $subject = "Přidání Vašeho komentáře k Vaší položce ve Vašem účtu Bubovický bazar"; 
                        $file = __DIR__ . '/emailTemplates/addComment.latte';
                    } else {
                        $subject = "Přidání Vaší reakce ke komentáři Vaší položky ve Vašem účtu Bubovický bazar"; 
                        $file = __DIR__ . '/emailTemplates/reactionToComment.latte';
                    }
                } else if($address['author'] == true && count($addresses) > 1){
                    if($reaction === false){
                        $this->translator->setLocale($address['language']);
                        $subject = $this->translator->translate('messages.comentAndOfferManager.Adding_comment'); 
                        $file = __DIR__ . '/emailTemplates/addComment.latte';
                    } else {
                        $subject = "Přidání reakce na Váš komentář k Vaší položce ve Vašem účtu Bubovický bazar"; 
                        $file = __DIR__ . '/emailTemplates/reactionToComment.latte';
                    }
                } else{
                    if($reaction === false){
                        $this->translator->setLocale($address['language']);
                        $subject = $this->translator->translate('messages.comentAndOfferManager.Adding_comment'); 
                        $file = __DIR__ . '/emailTemplates/reactionToItem.latte';
                    } else {
                        $subject = "Přidání Vaší reakce na komentář k požce ve Vašem účtu Bubovický bazar"; 
                        $file = __DIR__ . '/emailTemplates/reactionToComment.latte';
                    } 
                }
                
                $template = $this->sender->createTemplate();
                $template->setTranslator($this->translator, $address['language']);
                $template->textOfComment = $text;
                $template->textOfReaction = $text;
                $template->offerId = $offerID;
                $template->title = $title;
                $template->price = $price;
                $template->description = mb_strlen($description) > 50 ? mb_substr($description, 0, 50)."..." : $description;
                $email = $address['email']; 
                $template->setFile($file); 
                $template->userId = $address['id'];
                $message = new Message;
                $message->setFrom("order@localhost.cz")
                        ->addTo($email)
                        ->setSubject($subject)
                        ->setHtmlBody($template);
                $mailer = new SendmailMailer;
                $mailer->send($message);
            }
        }    
       
        
    }
    
    private function addressesForEmail($author, $commentAuthor) {
            $authorActive = $author[UserManager::COLUMN_EMAIL_SUBSCRIPTION] > 0 ? $author : null;
            $commentAuthorActive = $commentAuthor[UserManager::COLUMN_EMAIL_SUBSCRIPTION] > 0 ? $commentAuthor : null;
            $addresses = [];
            if ($authorActive !=null && $commentAuthorActive != null){
                if($authorActive->id == $commentAuthorActive->id ){
                    $authorActive = $authorActive->toArray();
                    $authorActive['author'] = true ;
                    array_push($addresses, $authorActive );
                }else{
                     $commentAuthorActive = $commentAuthorActive->toArray();
                     $commentAuthorActive['author'] = false ;
                     array_push($addresses, $commentAuthorActive );
                     $authorActive = $authorActive->toArray();
                     $authorActive['author'] = true ;
                     array_push($addresses, $authorActive );
                }
            }
            elseif ($authorActive !=null && $commentAuthorActive == null){
                    $authorActive = $authorActive->toArray();
                    $authorActive['author'] = true ;
                    array_push($addresses, $authorActive );
            }
            elseif ($authorActive ==null && $commentAuthorActive != null) {
                    $commentAuthorActive = $commentAuthorActive->toArray();
                    $commentAuthorActive['author'] = false ;
                    array_push($addresses, $commentAuthorActive );
            } else{
                  return [];
            }
            return $addresses;
        }
    


}