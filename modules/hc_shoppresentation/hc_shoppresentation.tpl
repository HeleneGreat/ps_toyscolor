{**
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
 *}

<div id="shop_presentation">
  <div class="content">
    <h2 class="h2">{$shop_presentation.title nofilter}</h2>
      {foreach from=$shop_presentation.text item=$text}
        {$text nofilter}
      {/foreach}
      <a href="{$urls.base_url}{$shop_presentation.button_link nofilter}" class="btn">
      {$shop_presentation.button_text nofilter}
      </a>
      <img class="cover-form" src="{$urls.theme_assets}css/form-3.svg" alt="Forme décorative">
  </div>
  <img src="{$shop_presentation.imgDir}{$shop_presentation.filename}" alt="Shop Presentation" width="420" class="presentation-img">
</div>
