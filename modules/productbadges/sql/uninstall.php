<?php
/**
 * Database uninstall SQL for productbadges (PrestaShop 1.7).
 *
 * @return array<int, string>
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = array();

$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_badge_product`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_badge_lang`';
$sql[] = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'product_badge`';

return $sql;
