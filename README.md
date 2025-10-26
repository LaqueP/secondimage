# secondimage — Módulo PrestaShop 8.x

Módulo para mostrar la **segunda imagen** de un producto, teniendo en cuenta la **combinación seleccionada**. Incluye un **hook** para plantillas y un **controlador AJAX** + **JavaScript** que actualiza la imagen dinámicamente cuando el usuario cambia los atributos.

> Compatible con **PrestaShop 8.x** y **PHP 8.2**.

---

## Características

* Obtiene la **segunda imagen** por **posición** de la combinación activa.
* *Fallbacks* automáticos si la combinación no tiene dos imágenes: **cover** → **primera imagen** del producto.
* **Hook** `{hook h='secondImage' ...}` para insertar en cualquier plantilla.
* **AJAX** + **JS** para actualizar la imagen al cambiar de combinación (observa `#product-details[data-product]`).
* Parámetros opcionales para **lazy-load** y visualización del **meta-title**.
* Estructura y *guards* compatibles con el **validador** de módulos.

---

## Estructura

```
secondimage/
├── secondimage.php
├── controllers/
│   └── front/
│       └── ajaxsecondimage.php
├── views/
│   ├── js/
│   │   └── secondimage.js
│   └── templates/
│       └── hook/
│           └── secondimage.tpl
├── .htaccess
└── index.php
```

* `secondimage.php`: clase principal del módulo y hook `secondImage`.
* `controllers/front/ajaxsecondimage.php`: controlador que devuelve la URL de la segunda imagen (JSON).
* `views/js/secondimage.js`: escucha cambios de combinación y actualiza la imagen.
* `views/templates/hook/secondimage.tpl`: plantilla que renderiza el `<img>` y opcionalmente el **meta-title**.

---

## Instalación

1. Copia la carpeta `secondimage/` en `modules/`.
2. En el Back Office, ve a **Módulos** → **Gestor de módulos** y **instala** *secondimage*.
3. Vacía la caché de Smarty si tenías el tema en producción.

> Si usas CCC/minificación de JS/CSS, recuerda **regenerar** los assets tras instalar o actualizar el módulo.

---

## Uso en plantillas

### Opción recomendada (módulo renderiza el `<img>`)

En tu plantilla (por ej. `templates/catalog/_partials/...` o un tab de producto), inserta:

```smarty
{hook h='secondImage'
      product=$product
      id_product_attribute=$product.currentCombination.id_product_attribute|default:0
      type='home_default'
      lazy=true
      show_meta_title=true}
```

* `product`: objeto/array de producto disponible en el contexto.
* `id_product_attribute`: **comb. seleccionada** (usa `|default:0` si no estás seguro).
* `type`: tipo de imagen configurado en **Diseño → Ajustes de imágenes** (p.ej. `home_default`, `large_default`).
* `lazy`: `true|false` (por defecto `true`).
* `show_meta_title`: `true|false` para mostrar el meta-title bajo la imagen.

### Plantilla del módulo (`secondimage.tpl`)

Renderiza un `<img>` con `id="secondimage-dynamic"` y atributos necesarios para la actualización dinámica. También imprime el **meta-title** si está habilitado.

---

## Hook expuesto

* **Nombre:** `secondImage`
* **Llamada típica:**

```smarty
{hook h='secondImage' product=$product id_product_attribute=$product.currentCombination.id_product_attribute type='home_default'}
```

* **Parámetros opcionales:**

  * `type` (string): tipo de imagen (p.ej. `home_default`), por defecto `large_default`.
  * `lazy` (bool): activar/desactivar `loading="lazy"`.
  * `show_meta_title` (bool): mostrar meta-title bajo la imagen.

---

## Funcionamiento (AJAX + JS)

* El módulo registra `views/js/secondimage.js` vía `hookDisplayHeader`.
* El JS:

  * Lee el JSON del producto en `#product-details[data-product]`.
  * Obtiene `id_product_attribute` del JSON.
  * Observa cambios del atributo `data-product` con **MutationObserver**.
  * En cada cambio, llama al controlador `ajaxsecondimage` con `id_product`, `id_product_attribute` y `type`.
  * Si la respuesta es `ok: true`, actualiza `src` del `#secondimage-dynamic`.

> Si tu tema carga el contenido del tab vía Ajax o Quick View, puedes registrar el JS sin condicionar por `php_self` o también en `displayFooter`.

---

## Controlador AJAX

* Ruta:

```
index.php?fc=module&module=secondimage&controller=ajaxsecondimage&
  id_product=ID&id_product_attribute=IPA&type=TYPE
```

* Respuesta JSON:

```json
{ "ok": true, "url": "https://.../home_default/imagen.jpg" }
```

* En caso de error:

```json
{ "ok": false, "message": "No image" }
```

---

## Seguridad y cumplimiento del validador

* **Guard**: todos los PHP comienzan con `if (!defined('_PS_VERSION_')) { exit; }`.
* **Licencia**: encabezado AFL 3.0 en PHP/JS/TPL.
* **Estructura**: carpeta del módulo `secondimage/` sin niveles adicionales.
* **.htaccess**: `Options -Indexes` en la raíz del módulo.
* **Firmas válidas**:

  * Uso de `$product->getDefaultIdProductAttribute()` (método de instancia, sin parámetros) o `Product::getDefaultAttribute((int)$product->id)`.
  * `json_encode()` en lugar de `Tools::jsonEncode()`.

---

## Solución de problemas

* **La imagen no cambia al seleccionar combinación**

  * Verifica que `#product-details` existe y tiene `data-product` con `id_product_attribute`.
  * Asegúrate de que `views/js/secondimage.js` está cargado (DevTools → Sources).
  * Limpia caché Smarty y regenera CCC.
  * Inspecciona Network → peticiones a `ajaxsecondimage` y revisa si `id_product_attribute` cambia.

* **El hook imprime HTML escapado dentro de `src`**

  * Estás mezclando modos. Si usas el hook dentro de atributos, el tpl del módulo debe **devolver solo la URL**. Si dejas que el módulo pinte el `<img>`, **no lo metas** dentro de otro `<img>`.

* **En listados/categoría**

  * Normalmente no hay combinación seleccionada; el módulo realizará *fallback* a la combinación por defecto.

---

## Rendimiento

* `loading="lazy"` + `decoding="async"` por defecto.
* `fetchpriority="low"` si `lazy=true`; puedes forzarlo a "high" con `lazy=false` para imágenes críticas.

---

## Personalización

* Puedes cambiar `data-image-type` en el tpl para usar otro `type`.
* Para mostrar más metadatos (p.ej. nombre del producto con atributos), puedes leerlos del JSON de `data-product` y añadirlos al tpl.

---

## Registro de cambios

* **1.1.1**

  * Compatibilidad validador: `json_encode`, guard en PHP, headers AFL en JS/TPL, `.htaccess`.
  * JS robusto con `MutationObserver` sobre `#product-details[data-product]`.
  * Opción `show_meta_title` y envío de `meta_title` al tpl.
* **1.1.0**

  * Soporte de combinación seleccionada (`id_product_attribute`).
* **1.0.0**

  * Primera versión: segunda imagen por producto.

---

## Licencia

Distribuido bajo **Academic Free License (AFL 3.0)**.

---

## Soporte

Si necesitas adaptar la detección de combinación a un tema específico, o extender el módulo (multi-instancia en página, `<picture>` con `srcset`, etc.), abre un issue o contacta con el autor del módulo.
