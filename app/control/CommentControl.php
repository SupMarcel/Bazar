<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 31.8.2018
 * Time: 13:05
 */

namespace App\Control;


use App\Forms\CommentFormFactory;
use App\Model\CommentManager;
use App\Model\UserManager;
use Nette\Application\UI\Control;
use Nette\Application\UI\Multiplier;

class CommentControl extends Control
{
    private $user;
    private $offer;
    private $comment;
    /**
     * @var CommentManager
     */
    private $commentManager;

    /** @var  UserManager */
    private $userManager;

    /** @var  CommentFormFactory */
    private $commentFormFactory;

    /**
     * CommentControl constructor.
     * @param $user
     * @param $offer
     * @param $comment
     * @param CommentManager $commentManager
     * @param CommentFormFactory $commentFormFactory
     */
    public function __construct(CommentManager $commentManager, UserManager $userManager,
                                CommentFormFactory $commentFormFactory,
                                $user = null, $offer = null, $comment = null){
        $this->user = $user;
        $this->offer = $offer;
        $this->comment = $comment;
        $this->commentManager = $commentManager;
        $this->userManager = $userManager;
        $this->commentFormFactory = $commentFormFactory;
    }

    public function render(){
        $this->template->setFile(__DIR__."/comment.latte");
        $this->template->comment = $this->commentManager->get($this->comment);
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->commentAuthor = CommentManager::COLUMN_USER;
        $this->template->commentText = CommentManager::COLUMN_TEXT;
        $this->template->loggedIn = $this->user !== null;
        $this->template->reactions = $this->commentManager->getDirectReactions($this->comment);
        $this->template->addFilter('authorname', function($id){
            $author = $this->userManager->get($id);
            return $author[UserManager::COLUMN_NAME];
        });
        $this->template->render();
    }

    public function createComponentReaction(){
        return new Multiplier(function($id){
           return new CommentControl($this->commentManager, $this->userManager, $this->commentFormFactory,
               $this->user, $this->offer, $id);
        });
    }

    public function createComponentAddReactionForm(){
        return $this->commentFormFactory->create($this->user, $this->offer, $this->comment);
    }
}