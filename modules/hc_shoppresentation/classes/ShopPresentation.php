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
class ShopPresentation extends ObjectModel
{
    /**
     * Identifier of ShopPresentation
     *
     * @var int
     */
    public $id_presentation;

    /**
     * String of ShopPresentation title
     *
     * @var array
     */
    public $title;

    /**
     * HTML format of ShopPresentation values
     *
     * @var array
     */
    public $text;

    /**
     * Text showed inside the button
     *
     * @var array
     */
    public $button_text;

    /**
     * Where the button links to
     *
     * @var array
     */
    public $button_link;

    /**
     * The image that goes next to the text
     *
     * @var array
     */
    public $filename;

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hc_presentation',
        'primary' => 'id_presentation',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            'id_presentation' => ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId'],
            // Lang fields
            'text' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'title' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'button_text' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'button_link' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'filename' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ],
    ];

    /**
     * Return the ShopPresentation ID By shop ID
     *
     * @param int $shopId
     *
     * @return bool|int
     */
    public static function getShopPresentationIdByShop($shopId)
    {
        $sql = 'SELECT p.`id_presentation` FROM `ps_hc_presentation` p
		LEFT JOIN `ps_hc_presentation_shop` ish ON ish.`id_presentation` = p.`id_presentation`
		WHERE ish.`id_shop` = ' . (int) $shopId;

        if ($result = Db::getInstance()->executeS($sql)) {
            return (int) reset($result)['id_presentation'];
        }

        return false;
    }

    public function associateWithShop($shop)
    {
        $db = Db::getInstance();
    
        // Vérifier si l'association existe déjà
        $existingAssociation = $db->getValue('SELECT COUNT(*) FROM '._DB_PREFIX_.'hc_presentation_shop WHERE id_presentation = '.(int)$this->id.' AND id_shop = '.(int)$shop);
    
        if ($existingAssociation) {
            return true; // L'association existe déjà, pas besoin de l'ajouter à nouveau
        }
    
        // Insérer l'association dans la table ps_hc_presentation_shop
        $sql = 'INSERT INTO '._DB_PREFIX_.'hc_presentation_shop (id_presentation, id_shop) VALUES ('.(int)$this->id.', '.(int)$shop.')';
        $result = $db->execute($sql);
    
        return $result;
    }
}