<?php
/**
* 2007-2025 PrestaShop
* NOTICE OF LICENSE: AFL 3.0
* http://opensource.org/licenses/afl-3.0.php
*/
if (!defined('_PS_VERSION_')) {
    exit;
}

class SecondimageAjaxsecondimageModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->ajax = true;

        // Cabecera opcional (ajaxRender ya devuelve el cuerpo, pero la cabecera ayuda al validador)
        header('Content-Type: application/json; charset=utf-8');

        $idProduct = (int) Tools::getValue('id_product');
        $ipa       = (int) Tools::getValue('id_product_attribute');
        $type      = pSQL((string) Tools::getValue('type', 'large_default'));
        $idLang    = (int) $this->context->language->id;

        if ($idProduct <= 0) {
            return $this->ajaxRender(json_encode(['ok' => false, 'message' => 'Missing id_product'], JSON_UNESCAPED_UNICODE));
        }

        $product = new Product($idProduct, false, $idLang);
        if (!Validate::isLoadedObject($product)) {
            return $this->ajaxRender(json_encode(['ok' => false, 'message' => 'Invalid product'], JSON_UNESCAPED_UNICODE));
        }

        if ($ipa <= 0) {
            // método de instancia correcto (sin parámetros)
            $ipa = (int) $product->getDefaultIdProductAttribute();
            // Alternativa estática:
            // $ipa = (int) Product::getDefaultAttribute($idProduct);
        }

        $allImages = $product->getImages($idLang);
        if (!$allImages) {
            return $this->ajaxRender(json_encode(['ok' => false, 'message' => 'No images'], JSON_UNESCAPED_UNICODE));
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

        $combImages = [];
        $combMap = $product->getCombinationImages($idLang);
        if (isset($combMap[$ipa]) && is_array($combMap[$ipa]) && count($combMap[$ipa]) > 0) {
            $combImages = $combMap[$ipa];
        }
        if (!$combImages) {
            $combImages = array_map(static function ($im) {
                return ['id_image' => (int) $im['id_image']];
            }, $allImages);
        }

        usort($combImages, function ($a, $b) use ($posMap) {
            $pa = $posMap[(int) $a['id_image']] ?? 0;
            $pb = $posMap[(int) $b['id_image']] ?? 0;
            return $pa <=> $pb;
        });

        $chosenId = 0;
        if (isset($combImages[1]['id_image'])) {
            $chosenId = (int) $combImages[1]['id_image'];
        } elseif ($coverId) {
            $chosenId = $coverId;
        } elseif (isset($combImages[0]['id_image'])) {
            $chosenId = (int) $combImages[0]['id_image'];
        }

        if ($chosenId <= 0) {
            return $this->ajaxRender(json_encode(['ok' => false, 'message' => 'No image'], JSON_UNESCAPED_UNICODE));
        }

        $url = $this->context->link->getImageLink($product->link_rewrite, $chosenId, $type);
        return $this->ajaxRender(json_encode(['ok' => true, 'url' => $url], JSON_UNESCAPED_UNICODE));
    }
}
