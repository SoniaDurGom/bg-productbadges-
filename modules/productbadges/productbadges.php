<?php
/**
 * Product badges — MVP module for PrestaShop 1.7.8.x.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ProductBadge.php';

class ProductBadges extends Module
{
    public function __construct()
    {
        $this->name = 'productbadges';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = '';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Etiquetas de producto');
        $this->description = $this->l('Crea etiquetas, asígnalas a productos y muéstralas en la ficha de producto.');
        $this->confirmUninstall = $this->l('Se eliminarán todas las etiquetas y asignaciones. ¿Continuar?');

        $this->ps_versions_compliancy = array('min' => '1.7.8.0', 'max' => _PS_VERSION_);
    }

    /**
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->installDb()
            && $this->installTab()
            && $this->registerHook('displayProductAdditionalInfo');
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return $this->unregisterHook('displayProductAdditionalInfo')
            && $this->uninstallTab()
            && $this->uninstallDb()
            && parent::uninstall();
    }

    /**
     * @return bool
     */
    protected function installDb()
    {
        $file = __DIR__ . '/sql/install.php';
        if (!file_exists($file)) {
            return false;
        }
        $queries = include $file;
        if (!is_array($queries)) {
            return false;
        }
        foreach ($queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function uninstallDb()
    {
        $file = __DIR__ . '/sql/uninstall.php';
        if (!file_exists($file)) {
            return true;
        }
        $queries = include $file;
        if (!is_array($queries)) {
            return true;
        }
        foreach ($queries as $query) {
            Db::getInstance()->execute($query);
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function installTab()
    {
        if (Tab::getIdFromClassName('AdminProductBadges')) {
            return true;
        }

        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminProductBadges';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[(int) $lang['id_lang']] = $this->l('Etiquetas de producto');
        }
        $id_parent = (int) Tab::getIdFromClassName('AdminCatalog');
        $tab->id_parent = $id_parent > 0 ? $id_parent : 0;
        $tab->module = $this->name;

        return (bool) $tab->add();
    }

    /**
     * @return bool
     */
    protected function uninstallTab()
    {
        $id_tab = (int) Tab::getIdFromClassName('AdminProductBadges');
        if ($id_tab) {
            $tab = new Tab($id_tab);

            return (bool) $tab->delete();
        }

        return true;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        $id_product = $this->resolveProductIdFromHookParams($params);
        if ($id_product < 1) {
            return '';
        }

        $badges = ProductBadge::getActiveBadgesForProduct($id_product);
        if (!$badges) {
            return '';
        }

        $this->context->smarty->assign(array(
            'product_badges' => $badges,
        ));

        return $this->fetch('module:' . $this->name . '/views/templates/hook/productbadges.tpl');
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return int
     */
    protected function resolveProductIdFromHookParams(array $params)
    {
        if (!isset($params['product'])) {
            return 0;
        }

        $product = $params['product'];

        if ($product instanceof Product) {
            return (int) $product->id;
        }

        if (is_array($product)) {
            if (!empty($product['id_product'])) {
                return (int) $product['id_product'];
            }
            if (!empty($product['id'])) {
                return (int) $product['id'];
            }
        }

        if (is_object($product)) {
            if (isset($product->id_product)) {
                return (int) $product->id_product;
            }
            if (method_exists($product, 'getId')) {
                return (int) $product->getId();
            }
        }

        return 0;
    }
}
