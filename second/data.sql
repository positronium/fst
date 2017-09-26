CREATE DATABASE IF NOT EXISTS fst;
USE fst;

GRANT ALL PRIVILEGES ON fst.* TO 't99342'@'localhost' IDENTIFIED BY 't99342';

DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(32) NOT NULL,
  `gender` tinyint(2) NOT NULL,
  `email` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

INSERT INTO users VALUES
(null, 'Jane', 0, 'jane@mailinator.com,jane_doe@mail.ru'),
(null, 'Joy',  0, 'joy@mailinator.com'),
(null, 'John', 1, 'john@mailinator.com,john_doe@yandex.ru,john_doe@mail.ru'),
(null, 'Nemo', 1, '')
;