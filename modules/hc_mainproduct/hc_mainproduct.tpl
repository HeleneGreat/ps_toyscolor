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

<div id="main-product">
{if $main_product != []}
  <div class="content">
    <h2 class="h2">{$main_product.title nofilter}</h2>
    <img src="{$main_product.imgDir}{$main_product.picture1}" alt="Shop Presentation 1" width="420" class="presentation-img1">
      {foreach from=$main_product.text1 item=$text}
        <p class="text1">{$text nofilter}</p>
      {/foreach}
      <img src="{$main_product.imgDir}{$main_product.picture2}" alt="Shop Presentation 2" width="420" class="presentation-img2">
      <img src="{$main_product.imgDir}{$main_product.picture3}" alt="Shop Presentation 3" width="420" class="presentation-img3">
      {foreach from=$main_product.text2 item=$text}
        <p class="text2">{$text nofilter}</p>
      {/foreach}
      {foreach from=$main_product.text3 item=$text}
        <p class="text3">{$text nofilter}</p>
      {/foreach}
      
      <a href="{$urls.base_url}" class="btn">
      Voir le produit
      </a>
      
      <img class="cover-form" src="{$urls.theme_assets}css/form-2.svg" alt="Forme décorative">
  </div>
 
{/if}
</div>
