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

class CommentControl_1 extends Control
{
   
    private $offer;
    private $comment;
    /**
     * @var CommentManager
     */
    private $commentManager;
/** @var  CommentFormFactory */
    private $commentFormFactory;
    private $userManager;
    
    /**
     * CommentControl constructor.
      * @param $offer
     * @param $comment
     * @param CommentManager $commentManager
     * @param CommentFormFactory $commentFormFactory
     */
    public function __construct(CommentManager $commentManager, UserManager $userManager,
                                CommentFormFactory $commentFormFactory,  $offer = null, $comment = null){
        $this->userManager = $userManager;
        $this->offer = $offer;
       
        $this->commentManager = $commentManager;
        $this->commentFormFactory = $commentFormFactory;
        $this->comment = $comment;
    }

    public function render(){
        dump($this->comment);
        dump($this->offer);
        $comment = $this->commentManager->get($this->comment);
        $this->template->setFile(__DIR__."/comment.latte");
        $this->template->comment = $comment;
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->commentAuthor = CommentManager::COLUMN_USER;
        $this->template->commentText = CommentManager::COLUMN_TEXT;
        $this->template->reactions = $this->commentManager->getDirectReactions($this->comment);
        $this->template->addFilter('authorname', function($id){
            $comment = $this->commentManager->get($this->comment);
            $author = $this->userManager->get($comment[CommentManager::COLUMN_USER]);
            return $author[UserManager::COLUMN_NAME];
        });
        $this->template->render();
    }

    public function createComponentReaction(){
        return new Multiplier(function($id){
           return new CommentControl($this->commentManager, $this->userManager, $this->commentFormFactory,
                $this->offer, $id );
        });
    }

    public function createComponentAddReactionForm(){
        return $this->commentFormFactory->createFormComment( $this->offer, $this->comment);
    }
}