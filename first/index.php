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

error_reporting(E_ALL);
// Все ошибки теперь фатальны
set_error_handler(function ($code, $message, $file, $line, $args) {
    fwrite(STDERR, "ERROR: $message at $file:$line\nBacktrace: ");
    fwrite(STDERR, print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1));
    throw new \ErrorException($message, $code);
});


$db = new \PDO(
    "mysql:dbname=fst;host=localhost",
    't99342',
    't99342',
    [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
        PDO::MYSQL_ATTR_COMPRESS     => true,
    ]
);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);

require_once __DIR__.'/src/Positronium/NewsFeed/Feed.php';
$feed = new Positronium\NewsFeed\Feed($db);

$newId = $feed->addNew("Запуск МКС", [1]);

print_r($feed->getNewsByCategories());
print_r($feed->getNewsByCategories([2]));

$feed->userLikesNew(1, $newId);
$feed->userLikesNew(2, $newId);
$feed->userDislikesNew(1, $newId);

print_r($feed->getNewLikes(1));
print_r($feed->getNewLikes($newId));

$feed->deleteNew($newId);