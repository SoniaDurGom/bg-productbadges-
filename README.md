# productbadges

Módulo para **PrestaShop 1.7** que permite crear etiquetas visuales reutilizables (badges) y asignarlas a productos del catálogo (por ejemplo: «NUEVO», «OFERTA», «EXCLUSIVO»).

**Versión del módulo:** 1.0.7

---

## 1. Versión del entorno

Entorno de prueba local con **Docker**.

| Componente | Versión |
|------------|---------|
| **PrestaShop** | **1.7.8.11** |
| **PHP (probado en Docker)** | **8.1** |

El código del módulo es compatible con **PHP 7.4** y **8.1** (sintaxis y APIs usadas en PrestaShop 1.7.8.x). La validación documentada se ha realizado con **PHP 8.1** en el contenedor local.

**Compatibilidad PrestaShop:** `1.7.8.0` – versión actual de la tienda (`ps_versions_compliancy` del módulo).

**Tema de referencia en frontend:** Classic (PrestaShop 1.7).

---

## 2. Descripción del módulo

`productbadges` gestiona etiquetas visuales asociadas a productos:

- **Back office:** CRUD de badges y pantalla de asignación producto ↔ badge (relación **muchos a muchos**).
- **Front office:** muestra las badges activas sobre la imagen del producto (esquina superior izquierda o derecha), en ficha y, si está habilitado, en listados (categoría, home, búsqueda).

Cada badge incluye:

- Texto **multilenguaje** (tabla `_lang`)
- Color de fondo y color de texto
- Posición: `left` (superior izquierda) o `right` (superior derecha)
- Estado activo / inactivo

---

## 3. Instalación

### Requisitos

- PrestaShop **1.7.8.x** (probado en **1.7.8.11**)
- PHP **7.4** u **8.1** (probado con **8.1** en Docker)
- Módulo copiado en la ruta estándar de PrestaShop

### Pasos

1. Copiar la carpeta del módulo en:
   ```
   /modules/productbadges
   ```
   (en este repositorio la ruta es `modules/productbadges/` respecto a la raíz del proyecto).

2. En el back office: **Módulos → Gestor de módulos**, buscar **Product badges** / **Etiquetas de producto** e **Instalar**.

3. Si no se ven cambios en el front office, limpiar caché:
   - **Parámetros avanzados → Rendimiento → Limpiar caché**
   - o borrar `var/cache/` en entornos de desarrollo.

4. Configurar el módulo (ver sección 4).

### Desinstalación

Desde el gestor de módulos: **Desinstalar**. Se eliminan tablas, hooks, pestaña de administración y claves de configuración (`PRODUCTBADGES_*`).

---

## 4. Funcionalidad principal

### 4.1. Configuración global del módulo

**Módulos → Product badges → Configurar**

| Opción | Clave | Descripción |
|--------|-------|-------------|
| Activar módulo | `PRODUCTBADGES_ENABLED` | Interruptor global |
| Mostrar en listado de productos | `PRODUCTBADGES_SHOW_LIST` | Categoría, home y búsqueda |
| Mostrar en ficha de producto | `PRODUCTBADGES_SHOW_PRODUCT` | Página de producto |
| Máximo de etiquetas por producto | `PRODUCTBADGES_MAX_PER_PRODUCT` | `0` = sin límite |

### 4.2. Gestión de badges (CRUD)

**Catálogo → Product badges** (`AdminProductBadges`)

- Crear, editar y eliminar badges (individual o masivo).
- Campos: nombre por idioma, colores, posición (izquierda / derecha), activo.
- Solo las badges **activas** se muestran en el front office.

### 4.3. Asignación a productos

En la misma sección: botón **Assign to products** / **Asignar a productos**.

1. Introducir el **ID de producto** y pulsar cargar.
2. Marcar las badges deseadas.
3. Guardar.

Un producto puede tener varias badges; una badge puede asignarse a varios productos.

### 4.4. Visualización en frontend

- **Ficha de producto:** badges sobre la imagen (hooks de cover / miniaturas; fallback si el tema no expone esos hooks).
- **Listados:** categoría, página de inicio y resultados de búsqueda, si **Mostrar en listado** está activo y el tema incluye el hook de listado (ver limitaciones).

Traducciones de interfaz del módulo: archivos en `modules/productbadges/translations/` (**es** y **en**).

---

## 5. Consideraciones técnicas

### Arquitectura

```
modules/productbadges/
├── productbadges.php              # Instalación, hooks, configuración
├── classes/ProductBadge.php       # ObjectModel (multilang)
├── controllers/admin/AdminProductBadgesController.php
├── sql/install.php | uninstall.php
├── views/templates/hook/productbadges.tpl
├── views/css/productbadges.css
└── translations/                  # es, en
```

### Base de datos

| Tabla | Uso |
|-------|-----|
| `{prefix}product_badge` | Datos base: colores, posición, activo |
| `{prefix}product_badge_lang` | Nombre de la badge por idioma |
| `{prefix}product_badge_product` | Relación N:N badge ↔ producto |

### APIs PrestaShop utilizadas

- **ObjectModel** (`ProductBadge`) con definición multilenguaje.
- **ModuleAdminController** + **HelperList** para el CRUD en back office.
- **HelperForm** en `getContent()` para la configuración del módulo.
- **Configuration** para opciones globales (por tienda en multitienda).
- **Db::getInstance()** / **DbQuery** para consultas y asignaciones.
- **$this->l()** y ficheros en `translations/` para cadenas de UI.

### Hooks registrados

| Hook | Uso |
|------|-----|
| `displayProductCover` | Badges sobre imagen en ficha (si el tema lo define) |
| `displayAfterProductThumbs` | Alternativa Classic en zona de imagen |
| `displayProductAdditionalInfo` | Fallback en ficha si no hay hook de imagen |
| `displayProductListReviews` | Badges en miniatura de listado (Classic) |
| `actionFrontControllerSetMedia` | Carga de CSS solo en producto / categoría / index / búsqueda |

### Dependencias

- **Sin Composer**
- **Sin librerías JavaScript externas** en front office
- En back office: script propio `admin-bulk-delete.js` usando **jQuery** (ya cargado por PrestaShop)

### Seguridad (resumen)

- Validación server-side (`Validate`, ObjectModel).
- IDs numéricos casteados en consultas SQL.
- Escapado en plantillas Smarty (`escape:'html':'UTF-8'`).
- Tokens en formularios de asignación en back office.

---

## 6. Decisiones técnicas (breve)

**Sin librerías externas**  
Se usan únicamente APIs nativas de PrestaShop 1.7 para mantener el módulo ligero, instalable en cualquier tienda 1.7.8.x sin gestión de dependencias y alineado con las restricciones del ejercicio.

**Hooks en lugar de overrides**  
Los overrides modifican el núcleo o el tema y complican actualizaciones. Los hooks permiten integrar la visualización sin tocar archivos del core, con fallbacks según los hooks que exponga cada tema.

**Tabla `_lang` para multilenguaje**  
El nombre de la badge es contenido traducible por idioma de tienda. El patrón `product_badge` + `product_badge_lang` sigue la convención de PrestaShop (equivalente al multilang de ObjectModel) y separa datos comunes (colores, posición) de textos localizados.

**Configuración por tienda, datos de badge globales**  
En multitienda, las opciones del módulo se guardan por contexto de tienda; las badges y las asignaciones a productos son compartidas (comportamiento coherente con catálogo compartido; no se exige diferenciación por tienda en el enunciado).

---

## 7. Limitaciones

| Limitación | Detalle |
|------------|---------|
| **Dependencia del tema** | La posición en listados depende de que el tema llame a `displayProductListReviews` (p. ej. tema **Classic**). Otros temas pueden requerir registrar hooks adicionales. |
| **Asignación por ID de producto** | No hay buscador de producto en la pantalla de asignación; hay que conocer el `id_product`. |
| **Sin tests automatizados** | No se incluyen tests unitarios ni de integración en el repositorio. |
| **Multitienda** | Compatibilidad **parcial** (`MULTISTORE_COMPATIBILITY_PARTIAL`): configuración por tienda; badges y asignaciones globales. |
| **Rendimiento en listados** | Una consulta por producto al renderizar cada miniatura (aceptable en catálogos pequeños/medianos). |
| **Borrado de producto** | Al eliminar un producto del catálogo, las filas en `product_badge_product` pueden quedar huérfanas hasta limpieza manual o futura mejora. |

---

## 8. Historial de desarrollo (referencia)

El módulo se ha construido en commits incrementales (estructura base, configuración, multilenguaje, traducciones UI, posición en imagen, listados, borrado masivo, multitienda), no en un único commit monolítico.

---

## Autor

Sonia — módulo `productbadges` para prueba técnica PrestaShop 1.7.8.x.
