<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer versions in the future.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Secondimage extends Module
{
    public function __construct()
    {
        $this->name = 'secondimage';
        $this->tab = 'front_office_features';
        $this->version = '1.1.1';
        $this->author = 'Tu Nombre / Empresa';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Product second image');
        $this->description = $this->l('Exposes a hook to obtain the second image of the selected combination (by position), with fallbacks.');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('secondImage')
            && $this->registerHook('displayHeader');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

public function hookDisplayHeader()
{
  if ('product' === $this->context->controller->php_self) {
    $this->context->controller->registerJavascript(
      'module-secondimage',
      'modules/'.$this->name.'/views/js/secondimage.js',
      ['position' => 'bottom', 'priority' => 150]
    );
  }
}


    /**
     * {hook h='secondImage' product=$product id_product_attribute=$product.currentCombination.id_product_attribute type='large_default'}
     */
    public function hookSecondImage($params)
    {
        $context = $this->context;
        $idLang  = (int) $context->language->id;

        // Producto
        $product = null;
        if (isset($params['product'])) {
            if (is_array($params['product']) && isset($params['product']['id_product'])) {
                $product = new Product((int) $params['product']['id_product'], false, $idLang);
            } elseif (is_object($params['product']) && isset($params['product']->id)) {
                $product = new Product((int) $params['product']->id, false, $idLang);
            }
        } elseif ((int) Tools::getValue('id_product')) {
            $product = new Product((int) Tools::getValue('id_product'), false, $idLang);
        }
        if (!$product || !Validate::isLoadedObject($product)) {
            return '';
        }

        // IPA: param > request > por defecto
        $ipa = 0;
        if (isset($params['id_product_attribute'])) {
            $ipa = (int) $params['id_product_attribute'];
        } elseif ((int) Tools::getValue('id_product_attribute')) {
            $ipa = (int) Tools::getValue('id_product_attribute');
        }
        if ($ipa <= 0) {
            // Instancia, sin parámetros
            $ipa = (int) $product->getDefaultIdProductAttribute();
            // Alternativa estática:
            // $ipa = (int) Product::getDefaultAttribute((int)$product->id);
        }

        // Todas las imágenes (para posición y cover)
        $allImages = $product->getImages($idLang);
        if (!$allImages) {
            return '';
        }
        $posMap = [];
        $coverId = 0;
        foreach ($allImages as $im) {
            $idImg = (int) $im['id_image'];
            $posMap[$idImg] = isset($im['position']) ? (int) $im['position'] : 0;
            if (!empty($im['cover'])) {
                $coverId = $idImg;
            }
        }

        // Imágenes de la combinación
        $combImages = [];
        $combMap = $product->getCombinationImages($idLang); // [ipa => [ [id_image], ... ] ]
        if (isset($combMap[$ipa]) && is_array($combMap[$ipa]) && count($combMap[$ipa]) > 0) {
            $combImages = $combMap[$ipa];
        }
        if (!$combImages) {
            $combImages = array_map(static function ($im) {
                return ['id_image' => (int) $im['id_image']];
            }, $allImages);
        }

        // Orden por posición
        usort($combImages, function ($a, $b) use ($posMap) {
            $pa = $posMap[(int) $a['id_image']] ?? 0;
            $pb = $posMap[(int) $b['id_image']] ?? 0;
            return $pa <=> $pb;
        });

        // Segunda imagen (fallbacks)
        $chosenId = 0;
        if (isset($combImages[1]['id_image'])) {
            $chosenId = (int) $combImages[1]['id_image'];
        } elseif ($coverId) {
            $chosenId = $coverId;
        } elseif (isset($combImages[0]['id_image'])) {
            $chosenId = (int) $combImages[0]['id_image'];
        }
        if ($chosenId <= 0) {
            return '';
        }

        $type = isset($params['type']) && is_string($params['type']) ? pSQL($params['type']) : 'large_default';
        $url  = $context->link->getImageLink($product->link_rewrite, $chosenId, $type);

$context->smarty->assign([
    'second_image_url'        => $url,
    'second_image_name'       => $product->name,
    'second_image_meta_title' => !empty($product->meta_title) ? $product->meta_title : $product->name,
    'id_product_attribute'    => $ipa,
    'lazy'                    => isset($params['lazy']) ? (bool) $params['lazy'] : true,
    'show_meta_title'         => isset($params['show_meta_title']) ? (bool) $params['show_meta_title'] : true, // <- opcional
]);



        return $this->display(__FILE__, 'views/templates/hook/secondimage.tpl');
    }
}
