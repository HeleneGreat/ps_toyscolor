<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    HeleneGreat
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\ObjectPresenter;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

require_once _PS_MODULE_DIR_ . 'hc_shoppresentation/classes/ShopPresentation.php';
class Hc_Shoppresentation extends Module implements WidgetInterface
{
    // Equivalent module on PrestaShop 1.6, sharing the same data
    const MODULE_16 = 'blockcmsinfo';

    /**
     * @var string Template used by widget
     */
    private $templateFile;

    public function __construct()
    {
        $this->name = 'hc_shoppresentation';
        $this->author = 'HeleneGreat';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->tab = 'front_office_features';

        $this->bootstrap = true;
        parent::__construct();

        Shop::addTableAssociation('presentation', ['type' => 'shop']);

        $this->displayName = $this->trans('Shop presentation', [], 'Modules.ShopPresentation.Admin');
        $this->description = $this->trans('A module to add a picture, a small description of your shop and a link to your about page');

        $this->ps_versions_compliancy = ['min' => '1.7.4.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:hc_shoppresentation/hc_shoppresentation.tpl';
    }

    /**
     * @return bool
     */
    public function install()
    {
        // Remove 1.6 equivalent module to avoid DB issues
        if (Module::isInstalled(self::MODULE_16)) {
            return $this->installFrom16Version();
        }

        return $this->runInstallSteps()
            && $this->installFixtures();
    }

    /**
     * @return bool
     */
    public function runInstallSteps()
    {
        return parent::install()
            && $this->installDB()
            && $this->registerHook('displayHome')
            && $this->registerHook('actionShopDataDuplication');
    }

    /**
     * @return bool
     */
    public function installFrom16Version()
    {
        require_once _PS_MODULE_DIR_ . $this->name . '/classes/MigrateData.php';
        $migration = new MigrateData();
        $migration->retrieveOldData();

        $oldModule = Module::getInstanceByName(self::MODULE_16);
        if ($oldModule) {
            $oldModule->uninstall();
        }

        return $this->uninstallDB()
            && $this->runInstallSteps()
            && $migration->insertData();
    }

    /**
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall() && $this->uninstallDB();
    }

    /**
     * @return bool
     */
    public function installDB()
    {
        $return = Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `ps_hc_presentation` (
                `id_presentation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id_presentation`),
                UNIQUE KEY `unique_presentation` (`id_presentation`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        $return = $return && Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `ps_hc_presentation_shop` (
                `id_presentation` INT(10) UNSIGNED NOT NULL,
                `id_shop` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_presentation`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        $return = $return && Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `ps_hc_presentation_lang` (
                `id_presentation` INT UNSIGNED NOT NULL,
                `id_shop` INT(10) UNSIGNED NOT NULL,
                `id_lang` INT(10) UNSIGNED NOT NULL ,
                `title` text NOT NULL,
                `text` text NOT NULL,
                `button_text` text NOT NULL,
                `button_link` text NOT NULL,
                `filename` text NOT NULL,
                PRIMARY KEY (`id_presentation`, `id_lang`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        return $return;
    }

    /**
     * @param bool $drop_table
     *
     * @return bool
     */
    public function uninstallDB($drop_table = true)
    {
        if (!$drop_table) {
            return true;
        }

        return Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_hc_presentation`, `ps_hc_presentation_shop`, `ps_hc_presentation_lang`');
    }

    /**
     * Affiche le contenu du module dans le back-office de PrestaShop
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('savehc_shoppresentation')) {
            if (!Tools::getValue('text_' . (int) Configuration::get('PS_LANG_DEFAULT'), false)) {
                $output = $this->displayError($this->trans('Please fill out all fields.', [], 'Admin.Notifications.Error'));
            } else {
                $update = $this->processSaveShopPresentation();

                if (!$update) {
                    $output = '<div class="alert alert-danger conf error">'
                        . $this->trans('An error occurred on saving.', [], 'Admin.Notifications.Error')
                        . '</div>';
                }

                $this->_clearCache($this->templateFile);

                if ($update) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&conf=4');
                }
            }
        }

        return $output . $this->renderForm();
    }

    /**
     *  Enregistre les informations de présentation de la boutique soumises via le formulaire d'administration
     * @return bool
     */
    public function processSaveShopPresentation()
    {
        $shops = Tools::getValue('checkBoxShopAsso_configuration', [$this->context->shop->id]);
        $text = [];
        $title = [];
        $button_text = [];
        $button_link = [];
        $filename = [];
        $languages = Language::getLanguages(false);

        if (isset($_FILES['filename'])
            && isset($_FILES['filename']['tmp_name'])
            && !empty($_FILES['filename']['tmp_name'])
        ) {
            if ($error = ImageManager::validateUpload($_FILES['filename'], 4000000)) {
                return $this->displayError($error);
            } else {
                $ext = substr($_FILES['filename']['name'], strrpos($_FILES['filename']['name'], '.') + 1);
                $file_name = "hc_shoppresentation." . $ext;

                if (!move_uploaded_file($_FILES['filename']['tmp_name'], _PS_MODULE_DIR_ . 'hc_shoppresentation/img/' . $file_name)) {
                    return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                } else {
                    $values['filename'] = $file_name;
                }
            }
            // Supprimer l'ancienne image si elle existe
            if (Configuration::hasContext('filename', $lang['id_lang'], Shop::getContext())) {
                $old_filename = Configuration::get('filename', $lang['id_lang']);
                @unlink(_PS_MODULE_DIR_ . 'hc_shoppresentation/img/' . $old_filename);
            }
        }

        
        
        foreach ($languages as $lang) {
            $text[$lang['id_lang']] = (string) Tools::getValue('text_' . $lang['id_lang']);
            $title[$lang['id_lang']] = (string) Tools::getValue('title');
            $button_text[$lang['id_lang']] = (string) Tools::getValue('button_text');
            $button_link[$lang['id_lang']] = (string) Tools::getValue('button_link');
            $filename[$lang['id_lang']] = $values['filename'];
        }

        $saved = true;
        foreach ($shops as $shop) {
            Shop::setContext(Shop::CONTEXT_SHOP, $shop);
            $presentation = new ShopPresentation(Tools::getValue('id_presentation', 1));
            $presentation->text = $text;
            $presentation->title = $title;
            $presentation->button_text = $button_text;
            $presentation->button_link = $button_link;
            $presentation->filename = $filename;
            $saved = $saved && $presentation->save();
            if ($saved) {
                // Insertion de l'association dans la table ps_hc_presentation_shop
                $presentation->associateWithShop($shop);
            }
        }

        return $saved;
    }

    /**
     *  Génère le formulaire d'administration du module
     * @return string
     */
    protected function renderForm()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $fields_form = [
            'tinymce' => false,
            'legend' => [
                'title' => $this->trans('Shop presentation block', [], 'Modules.ShopPresentation.Admin'),
            ],
            'input' => [
                'id_presentation' => [
                    'type' => 'hidden',
                    'name' => 'id_presentation',
                ],
                // Title
                'title' => [
                    'type' => 'text',
                    'label' => $this->trans('Title', [], 'Modules.ShopPresentation.Admin'),
                    'name' => 'title',
                    'size' => 200,
                ],
                // Picture
                'filename' => [
                    'type' => 'file',
                    'label' => $this->trans('Image', [], 'Modules.ShopPresentation.Admin'),
                    'name' => 'filename',
                    'display_image' => true,
                ],
                // Textarea
                'content' => [
                    'type' => 'textarea',
                    'label' => $this->trans('Text block', [], 'Modules.ShopPresentation.Admin'),
                    'lang' => true,
                    'name' => 'text',
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ],
                // Button text
                'button_text' => [
                    'type' => 'text',
                    'label' => $this->trans('Button text', [], 'Modules.ShopPresentation.Admin'),
                    'name' => 'button_text',
                    'size' => 200,
                ],
                // Button link
                'button_link' => [
                    'type' => 'text',
                    'label' => $this->trans('Link', [], 'Modules.ShopPresentation.Admin'),
                    'name' => 'button_link',
                    'size' => 200,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
        ];

        if (Shop::isFeatureActive() && Tools::getValue('id_presentation') == false) {
            $fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->trans('Shop association', [], 'Admin.Global'),
                'name' => 'checkBoxShopAsso_theme',
            ];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = 'hc_shoppresentation';
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        foreach (Language::getLanguages(false) as $lang) {
            $helper->languages[] = [
                'id_lang' => $lang['id_lang'],
                'iso_code' => $lang['iso_code'],
                'name' => $lang['name'],
                'is_default' => ($default_lang == $lang['id_lang'] ? 1 : 0),
            ];
        }

        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->toolbar_scroll = true;
        $helper->title = $this->displayName;
        $helper->submit_action = 'savehc_shoppresentation';

        $helper->fields_value = $this->getFormValues();

        return $helper->generateForm([['form' => $fields_form]]);
    }

    /**
     * Obtenir les valeurs actuelles du formulaire d'administration
     * @return array<string, mixed>
     */
    public function getFormValues()
    {
        $fields_value = [];
        $idShop = $this->context->shop->id;
        $idPresentation = ShopPresentation::getShopPresentationIdByShop($idShop);
        
        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
     
        if ($idPresentation) {
            $presentation = new ShopPresentation((int) $idPresentation);

            $fields_value['text'] = $presentation->text;
            $fields_value['id_presentation'] = $idPresentation;
            $fields_value['title'] = $presentation->title[1];
            $fields_value['button_text'] = $presentation->button_text[1];
            $fields_value['button_link'] = $presentation->button_link[1];
            $fields_value['filename'] = $presentation->filename[1];
        }
        
        return $fields_value;
    }

    /**
     * @param string|null $hookName
     * @param array $configuration
     *
     * @return string
     */
    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('hc_shoppresentation'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('hc_shoppresentation'));
    }

    /**
     * @param string|null $hookName
     * @param array $configuration
     *
     * @return array<string, mixed>
     */
    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $idShop = $this->context->shop->id;
        $idPresentation = ShopPresentation::getShopPresentationIdByShop($idShop);
        if ($idPresentation) {
            $presentation = new ShopPresentation((int) $idPresentation);
            $data['text'] = $presentation->text;
            $data['id_presentation'] = $idPresentation;
            $data['title'] = $presentation->title[1];
            $data['button_text'] = $presentation->button_text[1];
            $data['button_link'] = $presentation->button_link[1];
            $data['filename'] = $presentation->filename[1];
            $data['imgDir'] = $this->_path . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
        }

        $data['id_lang'] = $this->context->language->id;
        $data['id_shop'] = $this->context->shop->id;
        unset($data['id']);
        
        return ['shop_presentation' => $data];
    }

    /**
     * @return bool
     */
    public function installFixtures()
    {
        $return = true;
        $tabTexts = [
            [
                'text' => '<h2>Shop presentation</h2>
<p><strong class="dark">Lorem ipsum dolor sit amet conse ctetu</strong></p>
<p>Sit amet conse ctetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit.</p>',
            ],
        ];

        $shopsIds = Shop::getShops(true, null, true);
        $languages = Language::getLanguages(false);
        $text = [];

        foreach ($tabTexts as $tab) {
            $presentation = new ShopPresentation();
            foreach ($languages as $lang) {
                $text[$lang['id_lang']] = $tab['text'];
            }
            $presentation->text = $text;
            $return = $return && $presentation->add();
        }

        if ($return && count($shopsIds) > 1) {
            foreach ($shopsIds as $idShop) {
                Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
                $presentation->text = $text;
                $return = $return && $presentation->save();
            }
        }

        return $return;
    }

    /**
     * Add ShopPresentation when adding a new Shop
     *
     * @param array{cookie: Cookie, cart: Cart, altern: int, old_id_shop: int, new_id_shop: int} $params
     */
    public function hookActionShopDataDuplication(array $params)
    {
        if ($infoId = ShopPresentation::getShopPresentationIdByShop($params['old_id_shop'])) {
            Shop::setContext(Shop::CONTEXT_SHOP, $params['old_id_shop']);
            $oldInfo = new ShopPresentation($infoId);

            Shop::setContext(Shop::CONTEXT_SHOP, $params['new_id_shop']);
            $newInfo = new ShopPresentation($infoId, null, $params['new_id_shop']);
            $newInfo->text = $oldInfo->text;

            $newInfo->save();
        }
    }
}
