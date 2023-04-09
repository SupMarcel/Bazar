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


class CommentFormFactory extends FormFactory
{
    /** @var  CommentAndOfferManager */
    private $commentAndOfferManager;

    public function __construct(CommentAndOfferManager $commentAndOfferManager){
        $this->commentAndOfferManager = $commentAndOfferManager;;
    }

    public function createFormComment($userId, $offerId, $commentId = null){
        $form = $this->create();
        $form->addTextArea("text","Text");
        if($commentId === null){
            $form->addSubmit("addComment", "Přidej komentář");
        } else {
            $form->addSubmit("addReaction", "Přidej reakci");
        }
        $form->onSuccess[] = function(Form $form, $values) use($userId, $offerId, $commentId) {
            $array = [
                CommentManager::COLUMN_TEXT => $values["text"],
                CommentManager::COLUMN_OFFER => intval($offerId),
                CommentManager::COLUMN_USER => $userId,
                CommentManager::COLUMN_COMMENT => $commentId
            ];
            $this->commentAndOfferManager->addComment($array);
        };
        return $form;
    }
}