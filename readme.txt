=== PiruSEO ===
Contributors: pirulug
Tags: seo, meta tags, sitemap, schema, open graph, breadcrumbs
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2026-11-02
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin completo de SEO para WordPress con análisis en tiempo real, vista previa SERP, Open Graph, Twitter Cards, Schema JSON-LD, sitemap XML y migas de pan.

== Description ==

PiruSEO te ayuda a optimizar el posicionamiento en buscadores (SEO) de tu sitio WordPress con una interfaz nativa y ligera.

Funcionalidades incluidas:

* Análisis SEO y de legibilidad en español en tiempo real (Gutenberg y Editor Clásico).
* Vista previa SERP de Google en modo escritorio y móvil.
* Plantillas de títulos con variables (`%%title%%`, `%%sitename%%`, `%%sep%%`, `%%year%%`, etc.).
* Control de `noindex`, `nofollow` y URL canónica personalizada.
* Etiquetas Open Graph y Twitter / X Cards.
* Datos estructurados Schema.org en formato JSON-LD (`WebSite`, `Organization`/`Person`, `WebPage`, `Article`, `BreadcrumbList`).
* Mapa del sitio XML en `/piruseo-sitemap.xml` con estilo XSL y soporte para reglas personalizadas en `robots.txt`.
* Migas de pan mediante shortcode `[piruseo_breadcrumbs]` o función `piruseo_breadcrumbs()`.

== Installation ==

1. Sube la carpeta `wp-plugin-piruseo` al directorio `/wp-content/plugins/`.
2. Activa el plugin desde el menú **Plugins** de WordPress.
3. Configura tus preferencias en **PiruSEO** dentro del panel de administración.

== Frequently Asked Questions ==

= ¿Dónde puedo ver mi sitemap XML? =
En `https://tusitio.com/piruseo-sitemap.xml`. Si aparece un error 404 al activarlo, guarda nuevamente los ajustes en **Ajustes > Enlaces permanentes**.

= ¿Cómo muestro las migas de pan? =
Usa el shortcode `[piruseo_breadcrumbs]` en tu contenido o llama a `piruseo_breadcrumbs()` en la plantilla de tu tema.

== Changelog ==

= 2026-11-02 =
* Añadida documentación del plugin (`README.md` y `readme.txt`).

= 2026-11-01 =
* Adaptación de estilos del administrador a las clases y paleta nativas de WordPress Core.
* Implementación del sistema centralizado de versionado (`AAAA-MM-CAMBIO`).
