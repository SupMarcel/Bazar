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
        COLUMN_COMMENT = 'komentar';

    public function addComment($properties){
        $count = $this->database->table(self::TABLE_NAME)->count(self::COLUMN_ID);
        $maxID = $this->database->table(self::TABLE_NAME)->max(self::COLUMN_ID);
        $max = $count == 0 ? 1 : $maxID + 1;
        $array = [self::COLUMN_ID => $max,
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

    public function getDirectReactions($comment){
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_COMMENT, $comment);
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