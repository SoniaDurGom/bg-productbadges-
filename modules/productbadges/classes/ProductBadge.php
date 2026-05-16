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

    /** @var string */
    public $name;

    /** @var string */
    public $bg_color = '#000000';

    /** @var string */
    public $text_color = '#FFFFFF';

    /** @var bool */
    public $active = true;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = array(
        'table' => 'product_badge',
        'primary' => 'id_badge',
        'fields' => array(
            'name' => array(
                'type' => self::TYPE_STRING,
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
        ),
    );

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getActiveBadgesForProduct($id_product)
    {
        $id_product = (int) $id_product;
        if ($id_product < 1) {
            return array();
        }

        $sql = new DbQuery();
        $sql->select('b.`id_badge`, b.`name`, b.`bg_color`, b.`text_color`');
        $sql->from('product_badge', 'b');
        $sql->innerJoin(
            'product_badge_product',
            'bp',
            'bp.`id_badge` = b.`id_badge` AND bp.`id_product` = ' . $id_product
        );
        $sql->where('b.`active` = 1');
        $sql->orderBy('b.`id_badge` ASC');

        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS((string) $sql);

        return $rows ? $rows : array();
    }

    /**
     * @return bool
     */
    public function delete()
    {
        Db::getInstance()->delete('product_badge_product', '`id_badge` = ' . (int) $this->id_badge);

        return parent::delete();
    }
}
