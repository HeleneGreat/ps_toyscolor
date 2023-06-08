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
class MainProduct extends ObjectModel
{
    /**
     * Identifier of MainProduct
     *
     * @var int
     */
    public $id_mainproduct;

    /**
     * String of MainProduct title
     *
     * @var array
     */
    public $title = array();

    /**
     * HTML format of MainProduct values
     *
     * @var array
     */
    public $text1;

    /**
     * HTML format of MainProduct values
     *
     * @var array
     */
    public $text2;

    /**
     * HTML format of MainProduct values
     *
     * @var array
     */
    public $text3;

    /**
     * The image that goes next to the text
     *
     * @var array
     */
    public $picture1 = array();
    /**
     * The image that goes next to the text
     *
     * @var array
     */
    public $picture2 = array();

    /**
     * The image that goes next to the text
     *
     * @var array
     */
    public $picture3 = array();


    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table' => 'hc_mainproduct',
        'primary' => 'id_mainproduct',
        'multilang' => true,
        'multilang_shop' => true,
        'fields' => [
            'id_mainproduct' => ['type' => self::TYPE_NOTHING, 'validate' => 'isUnsignedId'],
            // Lang fields
            'title' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'text1' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'text2' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'text3' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'picture1' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'picture2' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
            'picture3' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true],
        ],
    ];

    /**
     * Return the MainProduct ID By shop ID
     *
     * @param int $shopId
     *
     * @return bool|int
     */
    public static function getMainProductIdByShop($shopId)
    {
        $sql = 'SELECT p.`id_mainproduct` FROM `ps_hc_mainproduct` p
		LEFT JOIN `ps_hc_mainproduct_shop` ish ON ish.`id_mainproduct` = p.`id_mainproduct`
		WHERE ish.`id_shop` = ' . (int) $shopId;

        if ($result = Db::getInstance()->executeS($sql)) {
            return (int) reset($result)['id_mainproduct'];
        }

        return false;
    }

    public function associateWithShop($shop)
    {
        $db = Db::getInstance();
    
        // Vérifier si l'association existe déjà
        $existingAssociation = $db->getValue(
            'SELECT COUNT(*) 
            FROM '._DB_PREFIX_.'hc_mainproduct_shop 
            WHERE id_mainproduct = '.(int)$this->id.' 
            AND id_shop = '.(int)$shop);
    
        if ($existingAssociation) {
            return true; // L'association existe déjà, pas besoin de l'ajouter à nouveau
        }
    
        // Insérer l'association dans la table ps_hc_mainproduct_shop
        $sql = 'INSERT INTO '._DB_PREFIX_.'hc_mainproduct_shop (id_mainproduct, id_shop) VALUES ('.(int)$this->id_mainproduct.', '.(int)$shop.')';
        $result = $db->execute($sql);
    
        return $result;
    }
}