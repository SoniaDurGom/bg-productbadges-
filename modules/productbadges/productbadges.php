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
    const CONFIG_ENABLED = 'PRODUCTBADGES_ENABLED';
    const CONFIG_SHOW_LIST = 'PRODUCTBADGES_SHOW_LIST';
    const CONFIG_SHOW_PRODUCT = 'PRODUCTBADGES_SHOW_PRODUCT';
    const CONFIG_MAX_PER_PRODUCT = 'PRODUCTBADGES_MAX_PER_PRODUCT';

    public function __construct()
    {
        $this->name = 'productbadges';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'Sonia';
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
            && $this->installConfiguration()
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
            && $this->uninstallConfiguration()
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

        return $this->upgradeMultilangSchema();
    }

    /**
     * Migración mínima: mueve name a product_badge_lang en instalaciones previas.
     *
     * @return bool
     */
    protected function upgradeMultilangSchema()
    {
        $db = Db::getInstance();
        $prefix = _DB_PREFIX_;

        if (!$db->execute(
            'CREATE TABLE IF NOT EXISTS `' . $prefix . 'product_badge_lang` (
                `id_badge` INT UNSIGNED NOT NULL,
                `id_lang` INT UNSIGNED NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id_badge`, `id_lang`),
                KEY `id_lang` (`id_lang`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;'
        )) {
            return false;
        }

        $columns = $db->executeS('SHOW COLUMNS FROM `' . $prefix . 'product_badge` LIKE \'name\'');
        if (!$columns) {
            return true;
        }

        $badges = $db->executeS('SELECT `id_badge`, `name` FROM `' . $prefix . 'product_badge`');
        if ($badges) {
            $languages = Language::getLanguages(false);
            foreach ($badges as $badge) {
                foreach ($languages as $language) {
                    $id_badge = (int) $badge['id_badge'];
                    $id_lang = (int) $language['id_lang'];
                    $exists = (int) $db->getValue(
                        'SELECT COUNT(*) FROM `' . $prefix . 'product_badge_lang`'
                        . ' WHERE `id_badge` = ' . $id_badge . ' AND `id_lang` = ' . $id_lang
                    );
                    if ($exists) {
                        continue;
                    }
                    $db->insert('product_badge_lang', array(
                        'id_badge' => $id_badge,
                        'id_lang' => $id_lang,
                        'name' => pSQL($badge['name']),
                    ));
                }
            }
        }

        return $db->execute('ALTER TABLE `' . $prefix . 'product_badge` DROP COLUMN `name`');
    }

    /**
     * @param string $version
     *
     * @return bool
     */
    public function upgrade($version)
    {
        return $this->upgradeMultilangSchema();
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
    protected function installConfiguration()
    {
        return Configuration::updateValue(self::CONFIG_ENABLED, 1)
            && Configuration::updateValue(self::CONFIG_SHOW_LIST, 0)
            && Configuration::updateValue(self::CONFIG_SHOW_PRODUCT, 1)
            && Configuration::updateValue(self::CONFIG_MAX_PER_PRODUCT, 0);
    }

    /**
     * @return bool
     */
    protected function uninstallConfiguration()
    {
        return Configuration::deleteByName(self::CONFIG_ENABLED)
            && Configuration::deleteByName(self::CONFIG_SHOW_LIST)
            && Configuration::deleteByName(self::CONFIG_SHOW_PRODUCT)
            && Configuration::deleteByName(self::CONFIG_MAX_PER_PRODUCT);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            if ($this->processConfiguration()) {
                $output .= $this->displayConfirmation($this->l('Configuración actualizada correctamente.'));
            } else {
                $output .= $this->displayError($this->l('No se pudo guardar la configuración.'));
            }
        }

        return $output . $this->renderForm();
    }

    /**
     * @return bool
     */
    protected function processConfiguration()
    {
        $enabled = (int) Tools::getValue(self::CONFIG_ENABLED);
        $showList = (int) Tools::getValue(self::CONFIG_SHOW_LIST);
        $showProduct = (int) Tools::getValue(self::CONFIG_SHOW_PRODUCT);
        $maxPerProduct = (int) Tools::getValue(self::CONFIG_MAX_PER_PRODUCT);

        if (!Validate::isUnsignedInt((string) $maxPerProduct)) {
            return false;
        }

        return Configuration::updateValue(self::CONFIG_ENABLED, $enabled ? 1 : 0)
            && Configuration::updateValue(self::CONFIG_SHOW_LIST, $showList ? 1 : 0)
            && Configuration::updateValue(self::CONFIG_SHOW_PRODUCT, $showProduct ? 1 : 0)
            && Configuration::updateValue(self::CONFIG_MAX_PER_PRODUCT, $maxPerProduct);
    }

    /**
     * @return string
     */
    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->module = $this;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->identifier = $this->name;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => (int) $this->context->language->id,
        );

        return $helper->generateForm($this->getConfigForm());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getConfigForm()
    {
        return array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => $this->l('Configuración'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Activar módulo'),
                            'name' => self::CONFIG_ENABLED,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_ENABLED),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Mostrar en listado de productos'),
                            'name' => self::CONFIG_SHOW_LIST,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_SHOW_LIST),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Mostrar en ficha de producto'),
                            'name' => self::CONFIG_SHOW_PRODUCT,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_SHOW_PRODUCT),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Máximo de etiquetas por producto'),
                            'name' => self::CONFIG_MAX_PER_PRODUCT,
                            'class' => 'fixed-width-sm',
                            'suffix' => $this->l('etiquetas'),
                            'desc' => $this->l('0 = sin límite. Solo valores enteros positivos.'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Guardar'),
                    ),
                ),
            ),
        );
    }

    /**
     * @param string $prefix
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getConfigSwitchValues($prefix)
    {
        return array(
            array(
                'id' => $prefix . '_on',
                'value' => 1,
                'label' => $this->l('Sí'),
            ),
            array(
                'id' => $prefix . '_off',
                'value' => 0,
                'label' => $this->l('No'),
            ),
        );
    }

    /**
     * @return array<string, int>
     */
    protected function getConfigFormValues()
    {
        return array(
            self::CONFIG_ENABLED => (int) $this->getConfigurationValue(self::CONFIG_ENABLED, 1),
            self::CONFIG_SHOW_LIST => (int) $this->getConfigurationValue(self::CONFIG_SHOW_LIST, 0),
            self::CONFIG_SHOW_PRODUCT => (int) $this->getConfigurationValue(self::CONFIG_SHOW_PRODUCT, 1),
            self::CONFIG_MAX_PER_PRODUCT => (int) $this->getConfigurationValue(self::CONFIG_MAX_PER_PRODUCT, 0),
        );
    }

    /**
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    protected function getConfigurationValue($key, $default)
    {
        $value = Configuration::get($key);

        return $value === false ? $default : (int) $value;
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
