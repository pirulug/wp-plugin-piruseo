# PiruSEO

Plugin completo de SEO para WordPress inspirado en Yoast SEO, con interfaz 100% integrada en los estilos nativos de WordPress Admin.

**Versión actual:** `2026-11-02`  
**Requiere WordPress:** 5.8 o superior  
**Requiere PHP:** 7.4 o superior  
**Licencia:** GPL-2.0-or-later

---

## Características principales

- **Análisis SEO en tiempo real** (compatible con Gutenberg y Editor Clásico):
  - Verificación de frase clave objetivo en título SEO, meta descripción, slug (URL) y primer párrafo.
  - Cálculo de densidad y frecuencia de frase clave.
  - Control de longitud del título SEO (30–60 caracteres) y meta descripción (120–160 caracteres) con barras de progreso.
  - Conteo de palabras del contenido, enlaces internos/externos y atributos `alt` de imágenes.
  - Semáforo SEO en el editor y en las columnas del listado de entradas y páginas.
- **Análisis de legibilidad en español**:
  - Longitud de párrafos y frases, uso de subtítulos (`H2`/`H3`/`H4`) y conectores/palabras de transición.
- **Vista previa SERP de Google**:
  - Simulación en tiempo real para escritorio y móvil.
- **Plantillas de título con variables dinámicas**:
  - `%%title%%`, `%%sitename%%`, `%%sitedesc%%`, `%%sep%%`, `%%excerpt%%`, `%%category%%`, `%%author%%`, `%%term_title%%`, `%%searchphrase%%`, `%%page%%`, `%%year%%`.
- **Metadatos técnicos y sociales**:
  - `<meta name="description">`, `<meta name="robots">` (`noindex`/`nofollow` por entrada o por tipo de archivo) y `<link rel="canonical">` sin duplicar la etiqueta de WordPress.
  - **Open Graph** (Facebook, LinkedIn, WhatsApp) y **Twitter / X Cards** con selector de imagen desde la biblioteca de medios.
  - Verificación para **Google Search Console**, **Bing Webmaster Tools** y **Yandex Webmaster**.
- **Schema.org JSON-LD (`@graph`)**:
  - Genera entidades `Organization`/`Person`, `WebSite` (con `SearchAction`), `WebPage`, `Article` y `BreadcrumbList`.
- **Sitemap XML y `robots.txt`**:
  - Sitemap automático en `/piruseo-sitemap.xml` con hoja de estilos XSL (`/piruseo-sitemap.xsl`), excluyendo contenidos marcados como `noindex`.
  - Reglas personalizadas para el `robots.txt` virtual de WordPress e inyección automática de la directiva `Sitemap:`.
- **Migas de pan (Breadcrumbs)**:
  - Shortcode `[piruseo_breadcrumbs]` y función de plantilla `piruseo_breadcrumbs()`.

---

## Instalación

1. Copia la carpeta `wp-plugin-piruseo` dentro de `wp-content/plugins/`.
2. Entra al panel de administración de WordPress → **Plugins** y activa **PiruSEO**.
3. Ve a **PiruSEO** en el menú lateral para configurar los ajustes generales, plantillas de título, redes sociales y sitemap.
4. Si `/piruseo-sitemap.xml` devuelve error 404, ve a **Ajustes → Enlaces permanentes** y haz clic en **Guardar cambios** para regenerar las reglas de reescritura.

---

## Uso de migas de pan (Breadcrumbs)

**Mediante shortcode** (en cualquier entrada, página o widget):

```text
[piruseo_breadcrumbs]
[piruseo_breadcrumbs separator="/"]
```

**En una plantilla PHP de tu tema:**

```php
<?php
if ( function_exists( 'piruseo_breadcrumbs' ) ) {
    piruseo_breadcrumbs();
}
?>
```

---

## Estructura del proyecto

```text
wp-plugin-piruseo/
├── wp-plugin-piruseo.php            # Punto de entrada y registro de hooks
├── VERSION                          # Control centralizado de versión (AAAA-MM-CAMBIO)
├── CHANGELOG.md                     # Historial de versiones
├── README.md                        # Documentación del proyecto
├── readme.txt                       # Formato estándar de WordPress.org
├── assets/
│   ├── css/
│   │   └── admin.css                # Estilos alineados con WordPress Core Admin
│   └── js/
│       └── admin-seo.js             # Vista previa SERP y analizador SEO/legibilidad
└── includes/
    ├── class-piruseo-helpers.php    # Opciones por defecto, variables y utilidades
    ├── class-piruseo-settings.php   # Página de configuración en wp-admin
    ├── class-piruseo-metabox.php    # Meta box en el editor y columnas SEO
    ├── class-piruseo-frontend.php   # Salida de etiquetas SEO, Social y JSON-LD
    ├── class-piruseo-sitemap.php    # Sitemap XML, XSL y filtro de robots.txt
    └── class-piruseo-breadcrumbs.php# Shortcode y helper de migas de pan
```

---

## Versionado

Este proyecto utiliza el formato de versionado **`AAAA-MM-CAMBIO`** (por ejemplo, `2026-11-01`, `2026-11-02`).  
La versión activa se lee desde el archivo [`VERSION`](VERSION) y el detalle de cada cambio se registra en [`CHANGELOG.md`](CHANGELOG.md).
