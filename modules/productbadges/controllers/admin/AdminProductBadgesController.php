<?php
/**
 * Back office: badge CRUD and product assignment (no product-tab hooks).
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminProductBadgesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'product_badge';
        $this->className = 'ProductBadge';
        $this->identifier = 'id_badge';
        $this->lang = true;

        parent::__construct();

        if ($this->module instanceof ProductBadges) {
            $this->module->ensureDatabaseSchema();
        }

        $this->meta_title = $this->l('Product badges');
        $this->toolbar_title = $this->l('Product badges');

        $this->fields_list = array(
            'id_badge' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'name' => array(
                'title' => $this->l('Name'),
            ),
            'bg_color' => array(
                'title' => $this->l('Background'),
                'callback' => 'renderColorCell',
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-lg',
            ),
            'text_color' => array(
                'title' => $this->l('Text color'),
                'callback' => 'renderColorCell',
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-lg',
            ),
            'active' => array(
                'title' => $this->l('Active'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'orderby' => false,
            ),
            'position' => array(
                'title' => $this->l('Position'),
                'type' => 'text',
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ),
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Badge'),
                'icon' => 'icon-certificate',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Name'),
                    'name' => 'name',
                    'lang' => true,
                    'required' => true,
                ),
                array(
                    'type' => 'color',
                    'label' => $this->l('Background color'),
                    'name' => 'bg_color',
                    'desc' => $this->l('Format #RRGGBB (color picker).'),
                ),
                array(
                    'type' => 'color',
                    'label' => $this->l('Text color'),
                    'name' => 'text_color',
                ),
                array(
                    'type' => 'radio',
                    'label' => $this->l('Position'),
                    'name' => 'position',
                    'values' => array(
                        array(
                            'id' => 'position_left',
                            'value' => 'left',
                            'label' => $this->l('Top left'),
                        ),
                        array(
                            'id' => 'position_right',
                            'value' => 'right',
                            'label' => $this->l('Top right'),
                        ),
                    ),
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Active'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No'),
                        ),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Delete selected'),
                'icon' => 'icon-trash',
            ),
        );
    }

    /**
     * @param bool $isNewTheme
     *
     * @return void
     */
    public function setMedia($isNewTheme = false)
    {
        parent::setMedia($isNewTheme);

        if (Tools::getIsset('assign') || in_array($this->display, array('edit', 'add', 'view'), true)) {
            return;
        }

        $this->addJS($this->module->getPathUri() . 'views/js/admin-bulk-delete.js');
        Media::addJsDef(array(
            'productBadgesBulkDelete' => array(
                'listId' => $this->table,
                'confirmTitle' => $this->trans('Delete selection', array(), 'Admin.Notifications.Warning'),
                'confirmMessage' => $this->l('Delete selected badges? Product assignments will be removed.'),
                'confirmButton' => $this->trans('Delete', array(), 'Admin.Actions'),
                'cancelButton' => $this->trans('Cancel', array(), 'Admin.Actions'),
                'emptySelection' => $this->l('You must select at least one badge to delete.'),
            ),
        ));
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function l($string, $specific = false, $class = null, $addslashes = false, $htmlentities = true)
    {
        return $this->module->l($string, 'AdminProductBadgesController');
    }

    /**
     * @return void
     */
    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        if (Tools::getIsset('assign')) {
            $this->page_header_toolbar_btn['back_to_list'] = array(
                'href' => self::$currentIndex . '&token=' . $this->token,
                'desc' => $this->l('Back to badge list'),
                'icon' => 'process-icon-back',
            );
        } else {
            $id_badge = (int) Tools::getValue($this->identifier);
            if ($this->display === 'edit' && $id_badge > 0) {
                $this->page_header_toolbar_btn['delete'] = array(
                    'href' => self::$currentIndex
                        . '&' . $this->identifier . '=' . $id_badge
                        . '&delete' . $this->table
                        . '&token=' . $this->token,
                    'desc' => $this->l('Delete'),
                    'icon' => 'process-icon-delete',
                );
            }

            $this->page_header_toolbar_btn['assign_to_products'] = array(
                'href' => self::$currentIndex . '&assign=1&token=' . $this->token,
                'desc' => $this->l('Assign to products'),
                'icon' => 'process-icon-anchor',
            );
        }
    }

    /**
     * @return void
     */
    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = $this->l('You do not have permission to view this page.');

            return;
        }

        if (Tools::getIsset('assign')) {
            $this->context->smarty->assign($this->getAssignTemplateVars());
            $this->content = $this->context->smarty->fetch($this->getTemplatePath() . 'assign.tpl');
            $this->context->smarty->assign(array(
                'content' => $this->content,
            ));

            return;
        }

        parent::initContent();
    }

    /**
     * @return mixed
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitProductbadgesLoad')) {
            $this->processLoadProductForAssign();

            return;
        }
        if (Tools::isSubmit('submitAssignproductbadges')) {
            $this->processSaveAssignment();

            return;
        }

        return parent::postProcess();
    }

    /**
     * @return ProductBadge|bool
     */
    public function processDelete()
    {
        $id_badge = (int) Tools::getValue($this->identifier);
        if ($id_badge < 1) {
            $this->errors[] = $this->l('Badge not found.');

            return false;
        }

        $badge = new ProductBadge($id_badge);
        if (!Validate::isLoadedObject($badge)) {
            $this->errors[] = $this->l('Badge not found.');

            return false;
        }

        if (!$badge->delete()) {
            $this->errors[] = $this->l('Could not delete the badge.');

            return false;
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=1&token=' . $this->token);
    }

    /**
     * @return bool
     */
    protected function processBulkDelete()
    {
        if (!is_array($this->boxes) || empty($this->boxes)) {
            $this->errors[] = $this->l('You must select at least one badge to delete.');

            return false;
        }

        $deleted = 0;
        foreach ($this->boxes as $id_badge) {
            $id_badge = (int) $id_badge;
            if ($id_badge < 1) {
                continue;
            }

            $badge = new ProductBadge($id_badge);
            if (!Validate::isLoadedObject($badge)) {
                continue;
            }

            if ($badge->delete()) {
                ++$deleted;
            } else {
                $this->errors[] = $this->l('Could not delete badge ID') . ' ' . $id_badge;
            }
        }

        if ($deleted < 1 && empty($this->errors)) {
            $this->errors[] = $this->l('No badges were deleted.');

            return false;
        }

        if (!empty($this->errors) && $deleted > 0) {
            Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=1&token=' . $this->token);
    }

    /**
     * @return void
     */
    protected function processLoadProductForAssign()
    {
        if (!$this->checkToken()) {
            $this->errors[] = $this->l('Invalid security token.');

            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        if (!Validate::isUnsignedId($id_product) || $id_product < 1) {
            $this->errors[] = $this->l('Please enter a valid product ID.');

            return;
        }
        if (!Product::existsInDatabase($id_product, 'product')) {
            $this->errors[] = $this->l('Product not found.');

            return;
        }

        Tools::redirectAdmin(
            self::$currentIndex . '&assign=1&id_product=' . $id_product . '&token=' . $this->token
        );
    }

    /**
     * @return void
     */
    protected function processSaveAssignment()
    {
        if (!$this->checkToken()) {
            $this->errors[] = $this->l('Invalid security token.');

            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        if (!Validate::isUnsignedId($id_product) || $id_product < 1) {
            $this->errors[] = $this->l('Please enter a valid product ID.');

            return;
        }
        if (!Product::existsInDatabase($id_product, 'product')) {
            $this->errors[] = $this->l('Product not found.');

            return;
        }

        $badges = Tools::getValue('badges');
        if (!is_array($badges)) {
            $badges = array();
        }

        $id_badges = array();
        foreach ($badges as $id_badge) {
            $id_badges[] = (int) $id_badge;
        }
        $id_badges = array_unique(array_filter($id_badges));

        foreach ($id_badges as $id_badge) {
            if ($id_badge < 1) {
                continue;
            }
            $row = Db::getInstance()->getRow(
                'SELECT `id_badge` FROM `' . _DB_PREFIX_ . 'product_badge` WHERE `id_badge` = ' . $id_badge
            );
            if (!$row) {
                $this->errors[] = $this->l('One or more badges are invalid.');

                return;
            }
        }

        $db = Db::getInstance();
        $db->delete('product_badge_product', '`id_product` = ' . $id_product);
        foreach ($id_badges as $id_badge) {
            if ($id_badge < 1) {
                continue;
            }
            $db->insert('product_badge_product', array(
                'id_badge' => $id_badge,
                'id_product' => $id_product,
            ));
        }

        Tools::redirectAdmin(
            self::$currentIndex . '&assign=1&conf=4&id_product=' . $id_product . '&token=' . $this->token
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAssignTemplateVars()
    {
        $id_product = (int) Tools::getValue('id_product');

        $id_lang = (int) $this->context->language->id;
        $badges = Db::getInstance()->executeS(
            'SELECT b.`id_badge`, b.`active`, bl.`name`'
            . ' FROM `' . _DB_PREFIX_ . 'product_badge` b'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'product_badge_lang` bl'
            . ' ON (b.`id_badge` = bl.`id_badge` AND bl.`id_lang` = ' . $id_lang . ')'
            . ' ORDER BY b.`id_badge` ASC'
        );
        if (!$badges) {
            $badges = array();
        } else {
            foreach ($badges as &$badge) {
                if (empty($badge['name'])) {
                    $badge['name'] = ProductBadge::resolveNameForLang((int) $badge['id_badge'], $id_lang);
                }
            }
            unset($badge);
        }

        $selected = array();
        if ($id_product > 0) {
            $rows = Db::getInstance()->executeS(
                'SELECT `id_badge` FROM `' . _DB_PREFIX_ . 'product_badge_product` WHERE `id_product` = ' . $id_product
            );
            if ($rows) {
                foreach ($rows as $r) {
                    $selected[(int) $r['id_badge']] = true;
                }
            }
        }

        $product_label = '';
        if ($id_product > 0 && Product::existsInDatabase($id_product, 'product')) {
            $product_label = '#' . $id_product;
            $id_lang = (int) $this->context->language->id;
            $id_shop = (int) $this->context->shop->id;
            $pl = Db::getInstance()->getRow(
                'SELECT `name` FROM `' . _DB_PREFIX_ . 'product_lang` WHERE `id_product` = ' . $id_product
                . ' AND `id_lang` = ' . $id_lang
                . ' AND (`id_shop` = ' . $id_shop . ' OR `id_shop` = 0)'
                . ' ORDER BY `id_shop` DESC'
            );
            if ($pl && isset($pl['name']) && $pl['name'] !== '') {
                $product_label .= ' — ' . $pl['name'];
            }
        }

        return array(
            'assign_action' => self::$currentIndex . '&assign=1&token=' . $this->token,
            'controller_token' => $this->token,
            'id_product' => $id_product,
            'badges_rows' => $badges,
            'selected_badges' => $selected,
            'product_label' => $product_label,
            'lbl_heading' => $this->l('Assign badges to a product'),
            'lbl_product_id' => $this->l('Product ID'),
            'lbl_load' => $this->l('Load product'),
            'lbl_help_select' => $this->l('Select badges for this product, then save.'),
            'lbl_badges' => $this->l('Badges'),
            'lbl_no_badges' => $this->l('No badges yet. Create them from the list first.'),
            'lbl_inactive' => $this->l('inactive badge'),
            'lbl_save' => $this->l('Save assignments'),
        );
    }

    /**
     * @param string $value
     * @param array<string, mixed> $row
     *
     * @return string
     */
    public function renderColorCell($value, $row)
    {
        unset($row);
        $safe = Validate::isColor($value) ? $value : '#000000';

        return '<span style="display:inline-block;width:22px;height:22px;border:1px solid #ccc;vertical-align:middle;background:'
            . Tools::safeOutput($safe)
            . ';"></span> <span class="text-muted">' . Tools::safeOutput($safe) . '</span>';
    }
}
