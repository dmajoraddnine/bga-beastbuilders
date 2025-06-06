-- ------
-- BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
-- beastbuilders implementation : © Sunwolf Studios, Inc. info@beastbuildersgame.com
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----
-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.


-- @TODO: can we preload the static information (characters, animals, etc) here instead of the Game PHP code?
  -- or maybe that's bad for future expansion implementation?


CREATE TABLE IF NOT EXISTS `biome` (
  `id`                   int(10) unsigned NOT NULL,
  `display_name`         varchar(64) NOT NULL,
  `basic_buff_family_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `family` (
  `id`           int(10) unsigned NOT NULL,
  `display_name` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


-- @TODO: BREAK UP SECTIONS INTO SUBTABLES
CREATE TABLE IF NOT EXISTS `animal` (
  `id`           int(10) unsigned NOT NULL,
  `display_name` varchar(256) NOT NULL,
  `family_id`    int(10) unsigned NOT NULL,
  `speed`        int(2) unsigned NOT NULL,
  `behavior`     varchar(32) NOT NULL,
  `threat`       varchar(32) NOT NULL,
  `defense`      varchar(32) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `character` (
  `id`            int(10) unsigned NOT NULL AUTO_INCREMENT,
  `display_name`  varchar(128) NOT NULL,
  `slug`          varchar(128) NOT NULL,
  `flavor_text`   varchar(256) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- @TODO: is it possible to use 1 table for all cards?
CREATE TABLE IF NOT EXISTS animal_deck (
  card_id             int(10) unsigned NOT NULL AUTO_INCREMENT,
  card_type           varchar(16) NOT NULL,
  card_type_arg       int(11) NOT NULL,
  card_location       varchar(16) NOT NULL,
  card_location_arg   int(11) NOT NULL,
  card_pending_action varchar(16) NULL,
  PRIMARY KEY (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS biome_deck (
  card_id           int(10) unsigned NOT NULL AUTO_INCREMENT,
  card_type         varchar(16) NOT NULL,
  card_type_arg     int(11) NOT NULL,
  card_location     varchar(16) NOT NULL,
  card_location_arg int(11) NOT NULL,
  PRIMARY KEY (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


-- CREATE TABLE IF NOT EXISTS beast (

-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `player` ADD `selected_character_id` INT UNSIGNED DEFAULT NULL;
