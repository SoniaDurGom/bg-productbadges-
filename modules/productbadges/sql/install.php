<?php
/**
 * Database install SQL for productbadges (PrestaShop 1.7).
 *
 * @return array<int, string>
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge` (
    `id_badge` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `bg_color` VARCHAR(32) NOT NULL,
    `text_color` VARCHAR(32) NOT NULL,
    `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_badge`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge_lang` (
    `id_badge` INT UNSIGNED NOT NULL,
    `id_lang` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_badge`, `id_lang`),
    KEY `id_lang` (`id_lang`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_badge_product` (
    `id_badge` INT UNSIGNED NOT NULL,
    `id_product` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id_badge`, `id_product`),
    KEY `id_product` (`id_product`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

return $sql;
