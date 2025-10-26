{*
 * 2007-2025 PrestaShop
 * License: AFL 3.0 (http://opensource.org/licenses/afl-3.0.php)
*}

<img id="secondimage-dynamic"
     class="product-second-image"
     src="{$second_image_url|escape:'html':'UTF-8'}"
     alt="{$second_image_name|escape:'html':'UTF-8'}"
     data-image-type="home_default"
     loading="lazy" decoding="async" fetchpriority="low" />
{if $show_meta_title}
  <div class="secondimage-meta-title" style="margin-top:.5rem;">
    {$second_image_meta_title|escape:'html':'UTF-8'}
  </div>
{/if}


