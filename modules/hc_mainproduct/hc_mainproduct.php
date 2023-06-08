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
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\ObjectPresenter;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

require_once _PS_MODULE_DIR_ . 'hc_mainproduct/classes/MainProduct.php';
class Hc_Mainproduct extends Module implements WidgetInterface
{
    // Equivalent module on PrestaShop 1.6, sharing the same data
    const MODULE_16 = 'blockcmsinfo';

    /**
     * @var string Template used by widget
     */
    private $templateFile;

    public function __construct()
    {
        $this->name = 'hc_mainproduct';
        $this->author = 'HeleneGreat';
        $this->version = '1.0.0';
        $this->need_instance = 0;
        $this->tab = 'front_office_features';

        $this->bootstrap = true;
        parent::__construct();

        Shop::addTableAssociation('mainproduct', ['type' => 'shop']);

        $this->displayName = $this->trans('Main product', [], 'Modules.MainProduct.Admin');
        $this->description = $this->trans('This module allows you to showcase your main product for your clients to identify it easily. You can add up to 3  images and 3 small texts.');

        $this->ps_versions_compliancy = ['min' => '1.7.4.0', 'max' => _PS_VERSION_];

        $this->templateFile = 'module:hc_mainproduct/hc_mainproduct.tpl';
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

        return $this->runInstallSteps();
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
                CREATE TABLE IF NOT EXISTS `ps_hc_mainproduct` (
                `id_mainproduct` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                PRIMARY KEY (`id_mainproduct`),
                UNIQUE KEY `unique_mainproduct` (`id_mainproduct`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        $return = $return && Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `ps_hc_mainproduct_shop` (
                `id_mainproduct` INT(10) UNSIGNED NOT NULL,
                `id_shop` INT(10) UNSIGNED NOT NULL,
                PRIMARY KEY (`id_mainproduct`, `id_shop`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 ;'
        );

        $return = $return && Db::getInstance()->execute('
                CREATE TABLE IF NOT EXISTS `ps_hc_mainproduct_lang` (
                `id_mainproduct` INT UNSIGNED NOT NULL,
                `id_shop` INT(10) UNSIGNED NOT NULL,
                `id_lang` INT(10) UNSIGNED NOT NULL ,
                `title` text NOT NULL,
                `text1` text NOT NULL,
                `text2` text NOT NULL,
                `text3` text NOT NULL,
                `picture1` text NOT NULL,
                `picture2` text NOT NULL,
                `picture3` text NOT NULL,
                PRIMARY KEY (`id_mainproduct`, `id_lang`, `id_shop`)
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

        return Db::getInstance()->execute('DROP TABLE IF EXISTS `ps_hc_mainproduct`, `ps_hc_mainproduct_shop`, `ps_hc_mainproduct_lang`');
    }

    /**
     * Affiche le contenu du module dans le back-office de PrestaShop
     * @return string
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('savehc_mainproduct')) {
            if (!Tools::getValue('title' . (int) Configuration::get('PS_LANG_DEFAULT'), false)) {
                $output = $this->displayError($this->trans('Please fill out all fields.', [], 'Admin.Notifications.Error'));
            } else {
                $update = $this->processSaveMainProduct();

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


    public function checkPictureFile($file, $pictureNumber)
    {
        if (isset($file['tmp_name']) && !empty($file['tmp_name']))  {
            if ($error = ImageManager::validateUpload($file, 4000000)) {
                return $this->displayError($error);
            } else {
                $ext = substr($file['name'], strrpos($file['name'], '.') + 1);
                $file_name = uniqid('hc_') . '.' . $ext;

                if (!move_uploaded_file($file['tmp_name'], _PS_MODULE_DIR_ . 'hc_mainproduct/img/' . $file_name)) {
                    return $this->displayError($this->trans('An error occurred while attempting to upload the file.', [], 'Admin.Notifications.Error'));
                } else {
                    return $file_name;
                }
            }
            // Supprimer l'ancienne image si elle existe
            if (Configuration::hasContext('picture'.$pictureNumber, $lang['id_lang'], Shop::getContext())) {
                $old_filename = Configuration::get('picture'.$pictureNumber, $lang['id_lang']);
                @unlink(_PS_MODULE_DIR_ . 'hc_mainproduct/img/' . $old_filename);
            }
        }
    }


    /**
     *  Enregistre les informations de présentation de la boutique soumises via le formulaire d'administration
     * @return bool
     */
    public function processSaveMainProduct()
    {
        $shops = Tools::getValue('checkBoxShopAsso_configuration', [$this->context->shop->id]);
        $title = [];
        $text1 = [];
        $text2 = [];
        $text3 = [];
        $picture1 = [];
        $picture2 = [];
        $picture3 = [];
        $values = [];
        $languages = Language::getLanguages(false);

        if (isset($_FILES['picture1'])) {
            $values['picture1'] = $this->checkPictureFile($_FILES['picture1'], 1);
        }
        if (isset($_FILES['picture2'])) {
            $values['picture2'] = $this->checkPictureFile($_FILES['picture2'], 2);
        }
        if (isset($_FILES['picture3'])) {
            $values['picture3'] = $this->checkPictureFile($_FILES['picture3'], 3);
        }

        
        foreach ($languages as $lang) {
            $title[$lang['id_lang']] = (string) Tools::getValue('title');
            $text1[$lang['id_lang']] = (string) Tools::getValue('text1_' . $lang['id_lang']);
            $text2[$lang['id_lang']] = (string) Tools::getValue('text2_' . $lang['id_lang']);
            $text3[$lang['id_lang']] = (string) Tools::getValue('text3_' . $lang['id_lang']);
            $picture1[$lang['id_lang']] = $values['picture1'];
            $picture2[$lang['id_lang']] = $values['picture2'];
            $picture3[$lang['id_lang']] = $values['picture3'];
        }

        $saved = true;
        foreach ($shops as $shop) {
            Shop::setContext(Shop::CONTEXT_SHOP, $shop);
            $mainproduct = new MainProduct(Tools::getValue('id_mainproduct', 1));
            $mainproduct->title = $title;
            $mainproduct->text1 = $text1;
            $mainproduct->text2 = $text2;
            $mainproduct->text3 = $text3;
            $mainproduct->picture1 = $picture1;
            $mainproduct->picture2 = $picture2;
            $mainproduct->picture3 = $picture3;
            $saved = $saved && $mainproduct->save();
            if ($saved) {
                // Insertion de l'association dans la table ps_hc_mainproduct_shop
                $mainproduct->associateWithShop($shop);
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
                'title' => $this->trans('Display your main product', [], 'Modules.MainProduct.Admin'),
            ],
            'input' => [
                'id_mainproduct' => [
                    'type' => 'hidden',
                    'name' => 'id_mainproduct',
                ],
                // // ID product
                // 'id_mainproduct' => [
                //     'type' => 'text',
                //     'label' => $this->trans('ID product', [], 'Modules.MainProduct.Admin'),
                //     'name' => 'id_mainproduct',
                //     'size' => 200,
                // ],
                // Title
                'title' => [
                    'type' => 'text',
                    'label' => $this->trans('Title', [], 'Modules.MainProduct.Admin'),
                    'name' => 'title',
                    'size' => 200,
                ],
                // Text1
                'text1' => [
                    'type' => 'textarea',
                    'label' => $this->trans('Text block 1', [], 'Modules.MainProduct.Admin'),
                    'lang' => true,
                    'name' => 'text1',
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ],
                // Text2
                'text2' => [
                    'type' => 'textarea',
                    'label' => $this->trans('Text block 2', [], 'Modules.MainProduct.Admin'),
                    'lang' => true,
                    'name' => 'text2',
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ],
                // Text3
                'text3' => [
                    'type' => 'textarea',
                    'label' => $this->trans('Text block 3', [], 'Modules.MainProduct.Admin'),
                    'lang' => true,
                    'name' => 'text3',
                    'cols' => 40,
                    'rows' => 10,
                    'class' => 'rte',
                    'autoload_rte' => true,
                ],
                // Picture1
                'picture1' => [
                    'type' => 'file',
                    'label' => $this->trans('Image 1', [], 'Modules.MainProduct.Admin'),
                    'name' => 'picture1',
                    'display_image' => true,
                ],
                // Picture2
                'picture2' => [
                    'type' => 'file',
                    'label' => $this->trans('Image 2', [], 'Modules.MainProduct.Admin'),
                    'name' => 'picture2',
                    'display_image' => true,
                ],
                // Picture3
                'picture3' => [
                    'type' => 'file',
                    'label' => $this->trans('Image 3', [], 'Modules.MainProduct.Admin'),
                    'name' => 'picture3',
                    'display_image' => true,
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
        ];

        if (Shop::isFeatureActive() && Tools::getValue('id_mainproduct') == false) {
            $fields_form['input'][] = [
                'type' => 'shop',
                'label' => $this->trans('Shop association', [], 'Admin.Global'),
                'name' => 'checkBoxShopAsso_theme',
            ];
        }

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = 'hc_mainproduct';
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
        $helper->submit_action = 'savehc_mainproduct';

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
        $idMainproduct = MainProduct::getMainProductIdByShop($idShop);
        
        Shop::setContext(Shop::CONTEXT_SHOP, $idShop);
    
        if ($idMainproduct) {
            $mainproduct = new MainProduct((int) $idMainproduct);

            $fields_value['id_mainproduct'] = $idMainproduct;
            $fields_value['title'] = isset($mainproduct->title[1]) ? $mainproduct->title[1] : '';
            $fields_value['text1'] = $mainproduct->text1;
            $fields_value['text2'] = $mainproduct->text2;
            $fields_value['text3'] = $mainproduct->text3;
            $fields_value['picture1'] = isset($mainproduct->picture1[1]) ? $mainproduct->picture1[1] : '';
            $fields_value['picture2'] = isset($mainproduct->picture2[1]) ? $mainproduct->picture2[1] : '';
            $fields_value['picture3'] = isset($mainproduct->picture3[1]) ? $mainproduct->picture3[1] : '';
        } else {
            $fields_value['id_mainproduct'] = "";
            $fields_value['title'] = "";
            $fields_value['text1'] = "";
            $fields_value['text2'] = "";
            $fields_value['text3'] = "";
            $fields_value['picture1'] = [];
            $fields_value['picture2'] = [];
            $fields_value['picture3'] = [];
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
        if (!$this->isCached($this->templateFile, $this->getCacheId('hc_mainproduct'))) {
            $this->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        }

        return $this->fetch($this->templateFile, $this->getCacheId('hc_mainproduct'));
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
        $idMainproduct = MainProduct::getMainProductIdByShop($idShop);
        $data = [];
        if ($idMainproduct) {
            $mainproduct = new MainProduct((int) $idMainproduct);
            $data['id_mainproduct'] = $idMainproduct;
            $data['title'] = $mainproduct->title[1];
            $data['text1'] = $mainproduct->text1;
            $data['text2'] = $mainproduct->text2;
            $data['text3'] = $mainproduct->text3;
            $data['picture1'] = $mainproduct->picture1[1];
            $data['picture2'] = $mainproduct->picture2[1];
            $data['picture3'] = $mainproduct->picture3[1];
            $data['imgDir'] = $this->_path . DIRECTORY_SEPARATOR . 'img' . DIRECTORY_SEPARATOR;
            $data['id_lang'] = $this->context->language->id;
            $data['id_shop'] = $this->context->shop->id;
        }

        unset($data['id']);
        // dump($idMainproduct);die;
        return ['main_product' => $data];
    }

    /**
     * Add MainProduct when adding a new Shop
     *
     * @param array{cookie: Cookie, cart: Cart, altern: int, old_id_shop: int, new_id_shop: int} $params
     */
    public function hookActionShopDataDuplication(array $params)
    {
        if ($infoId = MainProduct::getMainProductIdByShop($params['old_id_shop'])) {
            Shop::setContext(Shop::CONTEXT_SHOP, $params['old_id_shop']);
            $oldInfo = new MainProduct($infoId);

            Shop::setContext(Shop::CONTEXT_SHOP, $params['new_id_shop']);
            $newInfo = new MainProduct($infoId, null, $params['new_id_shop']);
            $newInfo->text1 = $oldInfo->text1;

            $newInfo->save();
        }
    }
}
