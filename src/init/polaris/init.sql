CREATE DATABASE if not exists `polaris`;
USE `polaris`;

CREATE TABLE `polaris_pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(100) NOT NULL,
  `redirect` varchar(255) NOT NULL,
  `page_title` varchar(100) NOT NULL,
  `file` varchar(100) NOT NULL,
  `title_seo` varchar(100) NOT NULL,
  PRIMARY KEY (`page_id`)
);

INSERT INTO `polaris_pages` (`url`, `redirect`, `page_title`, `file`, `title_seo`) VALUES
('/debug', '', 'Debug', 'Debug/Debug', 'Debug');