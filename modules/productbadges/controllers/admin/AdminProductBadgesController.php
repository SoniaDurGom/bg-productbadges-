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
        $this->lang = false;

        parent::__construct();

        $this->meta_title = $this->module->l('Etiquetas de producto');
        $this->toolbar_title = $this->module->l('Etiquetas de producto');

        $this->fields_list = array(
            'id_badge' => array(
                'title' => $this->module->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ),
            'name' => array(
                'title' => $this->module->l('Nombre'),
            ),
            'bg_color' => array(
                'title' => $this->module->l('Fondo'),
                'callback' => 'renderColorCell',
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-lg',
            ),
            'text_color' => array(
                'title' => $this->module->l('Color del texto'),
                'callback' => 'renderColorCell',
                'orderby' => false,
                'search' => false,
                'class' => 'fixed-width-lg',
            ),
            'active' => array(
                'title' => $this->module->l('Activo'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-sm',
                'orderby' => false,
            ),
        );

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->module->l('Etiqueta'),
                'icon' => 'icon-certificate',
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->module->l('Nombre'),
                    'name' => 'name',
                    'required' => true,
                ),
                array(
                    'type' => 'color',
                    'label' => $this->module->l('Color de fondo'),
                    'name' => 'bg_color',
                    'desc' => $this->module->l('Formato #RRGGBB (selector de color).'),
                ),
                array(
                    'type' => 'color',
                    'label' => $this->module->l('Color del texto'),
                    'name' => 'text_color',
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->module->l('Activo'),
                    'name' => 'active',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->module->l('Sí'),
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->module->l('No'),
                        ),
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->module->l('Guardar'),
            ),
        );
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
                'desc' => $this->module->l('Volver al listado de etiquetas'),
                'icon' => 'process-icon-back',
            );
        } else {
            $this->page_header_toolbar_btn['assign_to_products'] = array(
                'href' => self::$currentIndex . '&assign=1&token=' . $this->token,
                'desc' => $this->module->l('Asignar a productos'),
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
            $this->errors[] = $this->module->l('No tienes permiso para ver esta página.');

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
     * @return void
     */
    protected function processLoadProductForAssign()
    {
        if (!$this->checkToken()) {
            $this->errors[] = $this->module->l('Token de seguridad no válido.');

            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        if (!Validate::isUnsignedId($id_product) || $id_product < 1) {
            $this->errors[] = $this->module->l('Introduce un ID de producto válido.');

            return;
        }
        if (!Product::existsInDatabase($id_product, 'product')) {
            $this->errors[] = $this->module->l('Producto no encontrado.');

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
            $this->errors[] = $this->module->l('Token de seguridad no válido.');

            return;
        }

        $id_product = (int) Tools::getValue('id_product');
        if (!Validate::isUnsignedId($id_product) || $id_product < 1) {
            $this->errors[] = $this->module->l('Introduce un ID de producto válido.');

            return;
        }
        if (!Product::existsInDatabase($id_product, 'product')) {
            $this->errors[] = $this->module->l('Producto no encontrado.');

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
                $this->errors[] = $this->module->l('Una o más etiquetas no son válidas.');

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

        $badges = Db::getInstance()->executeS(
            'SELECT `id_badge`, `name`, `active` FROM `' . _DB_PREFIX_ . 'product_badge` ORDER BY `id_badge` ASC'
        );
        if (!$badges) {
            $badges = array();
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
            'lbl_heading' => $this->module->l('Asignar etiquetas a un producto'),
            'lbl_product_id' => $this->module->l('ID de producto'),
            'lbl_load' => $this->module->l('Cargar producto'),
            'lbl_help_select' => $this->module->l('Marca las etiquetas para este producto y guarda.'),
            'lbl_badges' => $this->module->l('Etiquetas'),
            'lbl_no_badges' => $this->module->l('Aún no hay etiquetas. Créalas desde el listado.'),
            'lbl_inactive' => $this->module->l('etiqueta inactiva'),
            'lbl_save' => $this->module->l('Guardar asignaciones'),
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
