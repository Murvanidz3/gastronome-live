-- Gastronome.live Database Schema
-- Run this in phpMyAdmin or MySQL CLI

CREATE DATABASE IF NOT EXISTS `gastronome`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gastronome`;

CREATE TABLE IF NOT EXISTS `products` (
  `id`         INT            NOT NULL AUTO_INCREMENT,
  `image_url`  VARCHAR(255)   DEFAULT NULL,
  `barcode`    VARCHAR(64)    NOT NULL,
  `name`       VARCHAR(255)   NOT NULL,
  `quantity`   INT            NOT NULL DEFAULT 0,
  `price`      DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `comment`    TEXT           DEFAULT NULL,
  `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_barcode` (`barcode`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
