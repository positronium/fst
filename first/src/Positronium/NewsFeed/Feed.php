<?php

/*
 * Copyright (C) 2017 petr
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Positronium\NewsFeed;

/**
 * Description of Feed
 *
 * @author petr
 */
class Feed
{

    /**
     * @var \PDO
     */
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     *
     * @param string $content
     * @param int[] $categories
     * @throws \PDOException
     * @throws \RuntimeException
     */
    public function addNew($content, array $categories = [])
    {
        try {
            $this->pdo->beginTransaction();

            if (!$this->pdo->prepare("INSERT INTO New (content) VALUES (?)")->execute([$content])) {
                throw new \RuntimeException("Cannot insert new");
            }
            $newId = $this->pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
            if (!$newId) {
                throw new \RuntimeException("Incorrect created new Id ". var_export($newId, 1));
            }
            $st = $this->pdo->prepare("INSERT INTO NewToCategory (newId, categoryId) VALUES (?, ?)");
            foreach ($categories as $catId) {
                if (!$st->execute([$newId, $catId])) { // TODO - bind с типизацией здесь и далее
                    throw new \RuntimeException("Cannot insert new#$newId category#$catId");
                }
            }
            $this->pdo->commit();
            return $newId;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    /**
     *
     * @param int $newId
     * @return $this
     * @throws \RuntimeException
     */
    public function deleteNew($newId)
    {
        $st = $this->pdo->prepare("DELETE FROM New WHERE id IN (?)"); // Связи удалятся каскадно
        if (!$st->execute([$newId])) {
            throw new \RuntimeException("Cannot delete new#$newId");
        }
        return $this;
    }

    /**
     *
     * @param int[] $categories Массив категорий для фильтрации. Если пусто - выведутся все
     * @return \stdClass[] Массив объектов новостей с полями id и content
     * @throws \RuntimeException
     */
    public function getNewsByCategories(array $categories = [])
    {
        $intCats = array_map(function($cat) { return (int)$cat; }, $categories); // TODO \InvalidArgumentException
        // Не делайте так, Doctrine DBAL умеет разворачивать массивы.
        $catFilter = $intCats ? " nc.categoryId IN (".implode($intCats).") " : ' 1 = 1 ';
        $st = $this->pdo->prepare("
            SELECT DISTINCT n.id, n.content
            FROM `New` n
            JOIN `NewToCategory` nc ON n.id = nc.newId
            WHERE $catFilter");
        if (!$st->execute()) {
            throw new \RuntimeException("Cannot execute news query statement");
        }
        return $st->fetchAll(\PDO::FETCH_OBJ); // TODO - отдельные сущности вместо \stdClass
    }

    /**
     *
     * @param int $userId
     * @param int $newId
     * @return $this
     * @throws \RuntimeException
     */
    public function userLikesNew($userId, $newId)
    {
        $st = $this->pdo->prepare("
            INSERT INTO UserLikesNew (userId, newId, lastLiked) VALUES (?, ?, UNIX_TIMESTAMP())
            ON DUPLICATE KEY UPDATE lastLiked = UNIX_TIMESTAMP()");
        if (!$st->execute([$userId, $newId])) {
            throw new \RuntimeException("Cannot execute user like statement");
        }
        return $this;
    }

    /**
     *
     * @param int $userId
     * @param int $newId
     * @return $this
     * @throws \RuntimeException
     */
    public function userDislikesNew($userId, $newId)
    {
        $st = $this->pdo->prepare("DELETE FROM UserLikesNew WHERE userId = ? AND newId = ?");
        if (!$st->execute([$userId, $newId])) {
            throw new \RuntimeException("Cannot execute user dislike statement");
        }
        return $this;
    }

    /**
     *
     * @param int $newId
     * @return \stdClass[]
     * @throws \RuntimeException
     */
    public function getNewLikes($newId)
    {
        $st = $this->pdo->prepare("
            SELECT u.id as userId, u.name as userName
            FROM `UserLikesNew` l
            JOIN `User` u ON u.id = l.userId
            WHERE l.newId = ?");
        if (!$st->execute([$newId])) {
            throw new \RuntimeException("Cannot execute new likes query statement");
        }
        return $st->fetchAll(\PDO::FETCH_OBJ);
    }
}
