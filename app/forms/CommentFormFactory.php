<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 29.8.2018
 * Time: 20:44
 */

namespace App\Forms;


use App\Model\CommentAndOfferManager;
use App\Model\CommentManager;
use Nette\Forms\Form;

class CommentFormFactory
{
    /** @var FormFactory */
    private $factory;
    /** @var  CommentAndOfferManager */
    private $commentAndOfferManager;
    private $user;
    private $offer;
    private $reaction;

    public function __construct(FormFactory $formFactory,
        CommentAndOfferManager $commentAndOfferManager){
        $this->factory = $formFactory;
        $this->commentAndOfferManager = $commentAndOfferManager;
        $this->user = null;
        $this->offer = null;
        $this->reaction = null;
    }

    public function create($user, $offer, $reaction = null){
        $this->user = $user;
        $this->reaction = $reaction;
        $this->offer = $offer;
        $form = $this->factory->create();
        $form->addTextArea("text","Text");
        if($reaction === null){
            $form->addSubmit("addComment", "Přidej komentář");
        } else {
            $form->addSubmit("addReaction", "Přidej reakci");
        }
        $form->onSuccess[] = function(Form $form, $values){
            $array = [
                CommentManager::COLUMN_TEXT => $values["text"],
                CommentManager::COLUMN_OFFER => $this->offer,
                CommentManager::COLUMN_USER => $this->user,
                CommentManager::COLUMN_COMMENT => $this->reaction
            ];
            $this->commentAndOfferManager->addComment($array);
        };
        return $form;
    }
}