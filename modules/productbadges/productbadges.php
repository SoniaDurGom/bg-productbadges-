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
    /** @var bool */
    protected static $badgesRenderedOnImage = false;

    const CONFIG_ENABLED = 'PRODUCTBADGES_ENABLED';
    const CONFIG_SHOW_LIST = 'PRODUCTBADGES_SHOW_LIST';
    const CONFIG_SHOW_PRODUCT = 'PRODUCTBADGES_SHOW_PRODUCT';
    const CONFIG_MAX_PER_PRODUCT = 'PRODUCTBADGES_MAX_PER_PRODUCT';

    public function __construct()
    {
        $this->name = 'productbadges';
        $this->tab = 'front_office_features';
        $this->version = '1.0.7';
        $this->author = 'Sonia';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        if (property_exists($this, 'multistoreCompatibility')
            && defined(Module::class . '::MULTISTORE_COMPATIBILITY_PARTIAL')) {
            $this->multistoreCompatibility = Module::MULTISTORE_COMPATIBILITY_PARTIAL;
        }

        $this->displayName = $this->l('Product badges');
        $this->description = $this->l('Create badges, assign them to products, and display them on the product page.');
        $this->confirmUninstall = $this->l('All badges and product assignments will be deleted. Continue?');

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
            && $this->registerHook('displayProductCover')
            && $this->registerHook('displayAfterProductThumbs')
            && $this->registerHook('displayProductAdditionalInfo')
            && $this->registerHook('displayProductListReviews')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return $this->unregisterHook('displayProductCover')
            && $this->unregisterHook('displayAfterProductThumbs')
            && $this->unregisterHook('displayProductAdditionalInfo')
            && $this->unregisterHook('displayProductListReviews')
            && $this->unregisterHook('actionFrontControllerSetMedia')
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

        return $this->upgradeMultilangSchema()
            && $this->upgradePositionColumn();
    }

    /**
     * Ejecuta migraciones pendientes (instalaciones ya existentes sin actualizar módulo).
     *
     * @return bool
     */
    public function ensureDatabaseSchema()
    {
        static $checked = false;
        if ($checked) {
            return true;
        }
        $checked = true;

        if (!$this->upgradeMultilangSchema() || !$this->upgradePositionColumn()) {
            return false;
        }

        if (!$this->isRegisteredInHook('displayProductCover')) {
            $this->registerHook('displayProductCover');
        }
        if (!$this->isRegisteredInHook('displayAfterProductThumbs')) {
            $this->registerHook('displayAfterProductThumbs');
        }
        if (!$this->isRegisteredInHook('actionFrontControllerSetMedia')) {
            $this->registerHook('actionFrontControllerSetMedia');
        }
        if (!$this->isRegisteredInHook('displayProductListReviews')) {
            $this->registerHook('displayProductListReviews');
        }

        return true;
    }

    /**
     * Carga CSS en ficha de producto (antes del render del hook en el body).
     *
     * @param array<string, mixed> $params
     *
     * @return void
     */
    public function hookActionFrontControllerSetMedia($params)
    {
        unset($params);

        if (!isset($this->context->controller->php_self)) {
            return;
        }

        $phpSelf = $this->context->controller->php_self;

        if ($phpSelf === 'product' && $this->isModuleEnabledForProductPage()) {
            $this->registerProductBadgesAssets();

            return;
        }

        if ($this->isProductListController($phpSelf) && $this->isModuleEnabledForProductList()) {
            $this->registerProductBadgesAssets();
        }
    }

    /**
     * Añade columna position en instalaciones previas.
     *
     * @return bool
     */
    protected function upgradePositionColumn()
    {
        $db = Db::getInstance();
        $table = _DB_PREFIX_ . 'product_badge';

        $columns = $db->executeS('SHOW COLUMNS FROM `' . $table . '` LIKE \'position\'');
        if ($columns) {
            return true;
        }

        if ($db->execute(
            'ALTER TABLE `' . $table . '` ADD `position` VARCHAR(5) NOT NULL DEFAULT \'left\' AFTER `active`'
        )) {
            return true;
        }

        return $db->execute(
            'ALTER TABLE `' . $table . '` ADD `position` VARCHAR(5) NOT NULL DEFAULT \'left\''
        );
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
        return $this->ensureDatabaseSchema()
            && $this->registerHook('displayProductCover')
            && $this->registerHook('displayProductListReviews')
            && $this->ensureConfigurationForAllShops();
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
        $defaults = array(
            self::CONFIG_ENABLED => 1,
            self::CONFIG_SHOW_LIST => 0,
            self::CONFIG_SHOW_PRODUCT => 1,
            self::CONFIG_MAX_PER_PRODUCT => 0,
        );

        $success = true;
        foreach ($this->getConfigurationShopIds() as $idShop) {
            foreach ($defaults as $key => $value) {
                $success = $success && Configuration::updateValue($key, $value, false, null, (int) $idShop);
            }
        }

        return $success;
    }

    /**
     * Crea valores por tienda en instalaciones multitienda ya existentes.
     *
     * @return bool
     */
    protected function ensureConfigurationForAllShops()
    {
        if (!Shop::isFeatureActive()) {
            return true;
        }

        $defaults = array(
            self::CONFIG_ENABLED => 1,
            self::CONFIG_SHOW_LIST => 0,
            self::CONFIG_SHOW_PRODUCT => 1,
            self::CONFIG_MAX_PER_PRODUCT => 0,
        );

        $success = true;
        foreach ($this->getConfigurationShopIds() as $idShop) {
            foreach ($defaults as $key => $default) {
                $current = Configuration::get($key, null, null, (int) $idShop);
                if ($current === false || $current === null || $current === '') {
                    $success = $success && Configuration::updateValue($key, $default, false, null, (int) $idShop);
                }
            }
        }

        return $success;
    }

    /**
     * @return array<int, int>
     */
    protected function getConfigurationShopIds()
    {
        $shopIds = Shop::getShops(false, null, true);
        if (!is_array($shopIds) || empty($shopIds)) {
            $defaultShop = (int) Configuration::get('PS_SHOP_DEFAULT');

            return $defaultShop > 0 ? array($defaultShop) : array(1);
        }

        return array_map('intval', $shopIds);
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
        $this->ensureDatabaseSchema();

        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            if ($this->processConfiguration()) {
                $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
            } else {
                $output .= $this->displayError($this->l('Could not save settings.'));
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

        return $this->updateConfigurationValue(self::CONFIG_ENABLED, $enabled ? 1 : 0)
            && $this->updateConfigurationValue(self::CONFIG_SHOW_LIST, $showList ? 1 : 0)
            && $this->updateConfigurationValue(self::CONFIG_SHOW_PRODUCT, $showProduct ? 1 : 0)
            && $this->updateConfigurationValue(self::CONFIG_MAX_PER_PRODUCT, $maxPerProduct);
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
                        'title' => $this->l('Settings'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Enable module'),
                            'name' => self::CONFIG_ENABLED,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_ENABLED),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show on product list'),
                            'name' => self::CONFIG_SHOW_LIST,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_SHOW_LIST),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => $this->l('Show on product page'),
                            'name' => self::CONFIG_SHOW_PRODUCT,
                            'is_bool' => true,
                            'values' => $this->getConfigSwitchValues(self::CONFIG_SHOW_PRODUCT),
                        ),
                        array(
                            'type' => 'text',
                            'label' => $this->l('Maximum badges per product'),
                            'name' => self::CONFIG_MAX_PER_PRODUCT,
                            'class' => 'fixed-width-sm',
                            'suffix' => $this->l('badges'),
                            'desc' => $this->l('0 = no limit. Positive integers only.'),
                        ),
                    ),
                    'submit' => array(
                        'title' => $this->l('Save'),
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
                'label' => $this->l('Yes'),
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
     * @param mixed  $value
     *
     * @return bool
     */
    protected function updateConfigurationValue($key, $value)
    {
        if (Shop::isFeatureActive() && Shop::getContext() === Shop::CONTEXT_ALL) {
            $success = true;
            foreach ($this->getConfigurationShopIds() as $idShop) {
                $success = $success && Configuration::updateValue($key, $value, false, null, (int) $idShop);
            }

            return $success;
        }

        return Configuration::updateValue(
            $key,
            $value,
            false,
            (int) Shop::getContextShopGroupID(true),
            (int) Shop::getContextShopID(true)
        );
    }

    /**
     * Lee configuración según tienda/grupo del contexto activo (FO o BO).
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    protected function getConfigurationValue($key, $default)
    {
        $idShopGroup = (int) Shop::getContextShopGroupID(true);
        $idShop = (int) Shop::getContextShopID(true);

        $value = Configuration::get($key, null, $idShopGroup, $idShop);

        if (($value === false || $value === null || $value === '') && Shop::isFeatureActive() && $idShop > 0) {
            $value = Configuration::get($key, null, 0, 0);
        }

        if ($value === false || $value === null || $value === '') {
            return (int) $default;
        }

        return (int) $value;
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
            $tab->name[(int) $lang['id_lang']] = $this->l('Product badges');
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
    public function hookDisplayProductCover($params)
    {
        return $this->renderProductBadgesOnImage($params);
    }

    /**
     * Hook usado por el tema Classic en la zona de imagen del producto.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayAfterProductThumbs($params)
    {
        return $this->renderProductBadgesOnImage($params);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayProductAdditionalInfo($params)
    {
        if ($this->usesImageHookForProductPage()) {
            return '';
        }

        return $this->renderProductBadges($params, false);
    }

    /**
     * Listados (categoría, home, búsqueda) — tema Classic: dentro de thumbnail-container.
     *
     * @param array<string, mixed> $params
     *
     * @return string
     */
    public function hookDisplayProductListReviews($params)
    {
        return $this->renderProductBadgesInList($params);
    }

    /**
     * @return bool
     */
    protected function usesImageHookForProductPage()
    {
        return $this->isRegisteredInHook('displayAfterProductThumbs')
            || $this->isRegisteredInHook('displayProductCover');
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string
     */
    protected function renderProductBadgesOnImage(array $params)
    {
        if (self::$badgesRenderedOnImage) {
            return '';
        }

        $html = $this->renderProductBadges($params, true);
        if ($html !== '') {
            self::$badgesRenderedOnImage = true;
        }

        return $html;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return string
     */
    protected function renderProductBadgesInList(array $params)
    {
        if (!$this->isModuleEnabledForProductList()) {
            return '';
        }

        return $this->renderProductBadgesHtml($params, true, 'list');
    }

    /**
     * @param array<string, mixed> $params
     * @param bool                $onCover true = sobre imagen; false = bloque bajo ficha (fallback)
     *
     * @return string
     */
    protected function renderProductBadges(array $params, $onCover = true)
    {
        if (!$this->isModuleEnabledForProductPage()) {
            return '';
        }

        return $this->renderProductBadgesHtml($params, $onCover, 'product');
    }

    /**
     * @param array<string, mixed> $params
     * @param bool                $onCover
     * @param string              $placement list|product
     *
     * @return string
     */
    protected function renderProductBadgesHtml(array $params, $onCover, $placement = 'product')
    {
        $this->ensureDatabaseSchema();

        $id_product = $this->resolveProductIdFromHookParams($params);
        if ($id_product < 1) {
            return '';
        }

        $badges = ProductBadge::getActiveBadgesForProduct($id_product);
        if (!$badges) {
            return '';
        }

        $max = (int) $this->getConfigurationValue(self::CONFIG_MAX_PER_PRODUCT, 0);
        if ($max > 0 && count($badges) > $max) {
            $badges = array_slice($badges, 0, $max);
        }

        $this->registerProductBadgesAssets();

        $badgesLeft = array();
        $badgesRight = array();
        foreach ($badges as $badge) {
            if (isset($badge['position']) && $badge['position'] === 'right') {
                $badgesRight[] = $badge;
            } else {
                $badgesLeft[] = $badge;
            }
        }

        $this->context->smarty->assign(array(
            'productbadges_on_cover' => $onCover,
            'productbadges_placement' => $placement === 'list' ? 'list' : 'product',
            'product_badges_left' => $badgesLeft,
            'product_badges_right' => $badgesRight,
            'productbadges_left_count' => count($badgesLeft),
            'productbadges_right_count' => count($badgesRight),
            'productbadges_css' => $this->getPathUri() . 'views/css/productbadges.css',
        ));

        return $this->fetch('module:' . $this->name . '/views/templates/hook/productbadges.tpl');
    }

    /**
     * @return bool
     */
    protected function isModuleEnabled()
    {
        return (int) $this->getConfigurationValue(self::CONFIG_ENABLED, 1) !== 0;
    }

    /**
     * @return bool
     */
    protected function isModuleEnabledForProductPage()
    {
        return $this->isModuleEnabled()
            && (int) $this->getConfigurationValue(self::CONFIG_SHOW_PRODUCT, 1) !== 0;
    }

    /**
     * @return bool
     */
    protected function isModuleEnabledForProductList()
    {
        return $this->isModuleEnabled()
            && (int) $this->getConfigurationValue(self::CONFIG_SHOW_LIST, 0) !== 0;
    }

    /**
     * @param string $phpSelf
     *
     * @return bool
     */
    protected function isProductListController($phpSelf)
    {
        return in_array($phpSelf, array('category', 'index', 'search'), true);
    }

    /**
     * @return void
     */
    protected function registerProductBadgesAssets()
    {
        if (!isset($this->context->controller) || !is_object($this->context->controller)) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-productbadges-front',
            'modules/' . $this->name . '/views/css/productbadges.css',
            array(
                'media' => 'all',
                'priority' => 150,
            )
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return int
     */
    protected function resolveProductIdFromHookParams(array $params)
    {
        if (isset($params['product'])) {
            $id = $this->extractProductId($params['product']);
            if ($id > 0) {
                return $id;
            }
        }

        $id = (int) Tools::getValue('id_product');
        if ($id > 0) {
            return $id;
        }

        if (isset($this->context->controller) && is_object($this->context->controller)) {
            if (property_exists($this->context->controller, 'id_product')) {
                $id = (int) $this->context->controller->id_product;
                if ($id > 0) {
                    return $id;
                }
            }
            if (method_exists($this->context->controller, 'getTemplateVarProduct')) {
                $product = $this->context->controller->getTemplateVarProduct();
                $id = $this->extractProductId($product);
                if ($id > 0) {
                    return $id;
                }
            }
            if (property_exists($this->context->controller, 'php_self')
                && $this->context->controller->php_self === 'product'
                && property_exists($this->context->controller, 'product')
            ) {
                $id = $this->extractProductId($this->context->controller->product);
                if ($id > 0) {
                    return $id;
                }
            }
        }

        $product = $this->context->smarty->getTemplateVars('product');
        if ($product) {
            return $this->extractProductId($product);
        }

        return 0;
    }

    /**
     * @param mixed $product
     *
     * @return int
     */
    protected function extractProductId($product)
    {
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
            if (isset($product->id)) {
                return (int) $product->id;
            }
            if (method_exists($product, 'getId')) {
                return (int) $product->getId();
            }
        }

        return 0;
    }
}
