<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 28.8.2018
 * Time: 11:38
 */

namespace App\Model;


class CommentManager extends BaseManager
{

    const
        TABLE_NAME = 'komentar',
        COLUMN_ID = 'id',
        COLUMN_USER = 'uzivatel',
        COLUMN_TEXT = 'text',
        COLUMN_OFFER = 'nabidka',
        COLUMN_COMMENT = 'komentar',
        COLUMN_TIME = 'time';

    public function addComment($properties){
        $array = [
            self::COLUMN_USER => $properties[self::COLUMN_USER],
            self::COLUMN_TEXT => $properties[self::COLUMN_TEXT],
            self::COLUMN_OFFER => $properties[self::COLUMN_OFFER],
            self::COLUMN_COMMENT => $properties[self::COLUMN_COMMENT]];
        $this->database->table(self::TABLE_NAME)->insert($array);
    }

    public function getCommentsByOffer($offer){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_OFFER, $offer)->where(self::COLUMN_COMMENT, null);
    }

    public function getDirectReactions($comment = null,int $limit = null, int $offset = null){
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_COMMENT, $comment)
                                                       ->order('time DESC') 
                                                       ->limit($limit, $offset)->fetchAll();
    }
    
    public function getCountReakcions($comment = null) {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_COMMENT, $comment)->count('*');
    }

    public function getCommentsByUser($user){
        return $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_USER, $user);
    }

	public function removeCommentsByUser($user){
		$comments = $this->getCommentsByUser($user);
		foreach($comments as $comment){
			$this->removeReactionsOfComment($comment[self::COLUMN_ID]);
			$comment->delete();
		}
	}
	
    public function removeCommentsByOffer($offer){
        $comments = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_OFFER, $offer);
        while($comments->count() > 0){
            foreach($comments as $comment){
                $commentID = $comment[CommentManager::COLUMN_ID];
                if($this->getDirectReactions($commentID)->count() != 0){
					$this->removeReactionsOfComment($commentID);
                }
				$comment->delete();
            }
            $comments = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_OFFER, $offer);
        }
    }
	
	protected function removeReactionsOfComment($commentID){
		$reactions = $this->getDirectReactions($commentID);
		foreach($reactions as $reaction){
			$reactionID = $reaction[self::COLUMN_ID];
			$this->removeReactionsOfComment($reactionID);
			$this->reaction->delete();
		}
	}
}