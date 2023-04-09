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
use Nette\Utils\Paginator;

class CommentControl extends Control
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
    private $paginator;
   
  
    
    
    /**
     * CommentControl constructor.
      * @param $offer
     * @param $comment
     * @param CommentManager $commentManager
     * @param CommentFormFactory $commentFormFactory
     */
    public function __construct(CommentManager $commentManager, UserManager $userManager,
                                CommentFormFactory $commentFormFactory,  $offer = null, $comment = null, Paginator $paginator = null ){
        $this->userManager = $userManager;
        $this->offer = $offer;
        $this->commentManager = $commentManager;
        $this->commentFormFactory = $commentFormFactory;
        $this->comment = $comment;
        $this->paginator = $paginator;
     }
     
    public function handlePage($page = 1) {
        $reactionsCount = $this->commentManager->getCountReakcions($this->comment);
        $this->paginator->setItemCount($reactionsCount);
        $_SESSION["commentsPage"] = $page;
   }
    
    
    
    public function render(){
        if($this->comment == 0){
            $this->comment = null;
            $reactionsCount = $this->commentManager->getCountReakcions($this->comment);
            $this->paginator->setItemCount($reactionsCount);
            $this->paginator->setItemsPerPage(3); // počet položek na stránce
            if (!empty($_SESSION["commentsPage"])){
             $this->paginator->setPage($_SESSION["commentsPage"]);   
            } else {
                    $this->paginator->setPage(1);
            }
            $this->template->paginator = $this->paginator;
            $this->template->reactions = $this->commentManager->getDirectReactions($this->comment, $this->paginator->getLength(),$this->paginator->getOffset());
        }else{
              $this->template->reactions = $this->commentManager->getDirectReactions($this->comment);
         }
        $this->template->setFile(__DIR__."/comment.latte");
        $this->template->formBootstrap = __DIR__ . '../../presenters/templates/form-bootstrap3.latte'; 
        $this->template->comment = $this->commentManager->get($this->comment);
        $this->template->commentID = CommentManager::COLUMN_ID;
        $this->template->commentAuthor = CommentManager::COLUMN_USER;
        $this->template->commentText = CommentManager::COLUMN_TEXT;
        $this->template->addFilter('authorname', function($id){
            $comment = $this->commentManager->get($this->comment);
            $author = $this->userManager->get($comment[CommentManager::COLUMN_USER]);
            return $author[UserManager::COLUMN_NAME];
        });
        $this->template->render();
    }

    public function createComponentReaction(){
        return new Multiplier(function( $commentId = null){
           return new CommentControl($this->commentManager, $this->userManager, $this->commentFormFactory,
                $this->offer, $commentId );
        });
    }

    public function createComponentAddReactionForm(){
        return $this->commentFormFactory->createFormComment($this->getPresenter()->getUser()->id, $this->offer, $this->comment);
    }
    
    
}