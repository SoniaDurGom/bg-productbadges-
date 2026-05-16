<?php
/**
 * Product badge entity (ObjectModel).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class ProductBadge extends ObjectModel
{
    /** @var int */
    public $id_badge;

    /** @var array<int, string>|string */
    public $name;

    /** @var string */
    public $bg_color = '#000000';

    /** @var string */
    public $text_color = '#FFFFFF';

    /** @var bool */
    public $active = true;

    /** @var string */
    public $position = 'left';

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'product_badge',
        'primary' => 'id_badge',
        'multilang' => true,
        'fields' => array(
            'name' => array(
                'type' => self::TYPE_STRING,
                'lang' => true,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255,
            ),
            'bg_color' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isColor',
                'required' => true,
                'size' => 32,
            ),
            'text_color' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isColor',
                'required' => true,
                'size' => 32,
            ),
            'active' => array(
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool',
                'required' => true,
            ),
            'position' => array(
                'type' => self::TYPE_STRING,
                'validate' => 'isCleanHtml',
                'required' => true,
                'size' => 5,
            ),
        ),
    );

    /**
     * @param string $position
     *
     * @return string
     */
    public static function normalizePosition($position)
    {
        if (!is_string($position)) {
            return 'left';
        }

        $position = strtolower(trim($position));

        return $position === 'right' ? 'right' : 'left';
    }

    /**
     * @param bool $die
     * @param bool $error_return
     *
     * @return bool|string
     */
    public function validateFields($die = true, $error_return = false)
    {
        $this->position = self::normalizePosition($this->position);

        return parent::validateFields($die, $error_return);
    }

    /**
     * @param int $id_badge
     * @param int $id_lang
     *
     * @return string
     */
    public static function resolveNameForLang($id_badge, $id_lang)
    {
        $id_badge = (int) $id_badge;
        $id_lang = (int) $id_lang;
        if ($id_badge < 1) {
            return '';
        }

        $name = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_badge_lang`'
            . ' WHERE `id_badge` = ' . $id_badge . ' AND `id_lang` = ' . $id_lang
        );
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $name = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_badge_lang`'
            . ' WHERE `id_badge` = ' . $id_badge . ' ORDER BY `id_lang` ASC'
        );

        return is_string($name) ? $name : '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveBadgesForProduct($id_product)
    {
        $id_product = (int) $id_product;
        if ($id_product < 1) {
            return array();
        }

        $id_lang = (int) Context::getContext()->language->id;

        $sql = new DbQuery();
        $sql->select('b.`id_badge`, b.`bg_color`, b.`text_color`, b.`position`, bl.`name`');
        $sql->from('product_badge', 'b');
        $sql->innerJoin(
            'product_badge_product',
            'bp',
            'bp.`id_badge` = b.`id_badge` AND bp.`id_product` = ' . $id_product
        );

        $context = Context::getContext();
        $id_shop = isset($context->shop) ? (int) $context->shop->id : 0;
        if ($id_shop > 0 && Shop::isFeatureActive()) {
            $sql->innerJoin(
                'product_shop',
                'ps',
                'ps.`id_product` = bp.`id_product` AND ps.`id_shop` = ' . $id_shop . ' AND ps.`active` = 1'
            );
        }

        $sql->leftJoin(
            'product_badge_lang',
            'bl',
            'bl.`id_badge` = b.`id_badge` AND bl.`id_lang` = ' . $id_lang
        );
        $sql->where('b.`active` = 1');
        $sql->orderBy('b.`id_badge` ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((string) $sql);
        if (!$rows) {
            return array();
        }

        foreach ($rows as &$row) {
            if (empty($row['name'])) {
                $row['name'] = self::resolveNameForLang((int) $row['id_badge'], $id_lang);
            }
            $row['position'] = self::normalizePosition(isset($row['position']) ? $row['position'] : 'left');
        }
        unset($row);

        return $rows;
    }

    /**
     * @return bool
     */
    public function delete()
    {
        if ((int) $this->id_badge < 1) {
            return false;
        }

        Db::getInstance()->delete('product_badge_product', '`id_badge` = ' . (int) $this->id_badge);
        Db::getInstance()->delete('product_badge_lang', '`id_badge` = ' . (int) $this->id_badge);

        return parent::delete();
    }
}
