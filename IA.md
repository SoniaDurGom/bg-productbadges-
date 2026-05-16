# Uso de IA en este proyecto

Documentación del uso de inteligencia artificial durante el desarrollo del módulo **productbadges** para PrestaShop 1.7.8.11 (prueba técnica, entorno Docker local, tema Classic).

---

## 1. Herramientas utilizadas


| Herramienta                       | Versión / Modelo                    | Modo de uso                                          | Aprox. % del trabajo |
| --------------------------------- | ----------------------------------- | ---------------------------------------------------- | -------------------- |
| Cursor (agente en IDE)            | Auto / Composer (según sesión)      | Implementación y correcciones en el repo             | ~65%                 |
| ChatGPT (web)                     | **GPT-5.3-mini**.                   | Redacción de prompts iniciales y consultas puntuales | ~12%                 |
| Cursor (MCP integrado)            | Navegador / filesystem del proyecto | Consulta puntual de contexto                         | ~3%                  |
| Documentación PrestaShop / GitHub | 1.7.8.x                             | Verificación manual de hooks y plantillas Classic    | ~8%                  |
| Sin IA (pruebas manuales, BO/FO)  | —                                   | Validación en tienda, capturas, ajustes finales      | ~12%                 |


### Flujo habitual: ChatGPT → Cursor

1. **ChatGPT:** preparar o pulir el **prompt inicial** (contexto del MVP, restricciones, estructura del mensaje) antes de pegarlo en Cursor.
2. **Cursor:** ejecutar ese prompt sobre el código del módulo y las iteraciones siguientes (correcciones, nuevas funcionalidades).

**ChatGPT no escribió código del módulo** de forma directa; se usó sobre todo para:

- Generar y mejorar **prompts de arranque** que luego se copiaron a Cursor.
- Consultas sobre **Docker** para levantar el entorno local y probar el módulo desarrollado.
- Consultas sobre uso de **Git**
- Consultas sobre **Cursor** (cómo plantear tareas largas y dividir funcionalidades).

El código PrestaShop (PHP, SQL, tpl, CSS del módulo) se desarrolló y corrigió principalmente en **Cursor**.

---

## 2. Configuración del proyecto

### CLAUDE.md / AGENTS.md

**Ninguno** en el repositorio.

Se priorizó un MVP rápido con instrucciones en cada mensaje del chat (restricciones explícitas: sin Composer, sin JS salvo BO puntual, PHP 7.4/8.1). No se creó fichero de agente persistente para no duplicar lo ya descrito en el enunciado y en `[README.md](README.md)`.

Referencia local de Cursor: `[.cursor/README.md](.cursor/README.md)`.

### settings.json u otra configuración equivalente

No se versionó `settings.json` de Cursor ni de VS Code en el repo.

La configuración activa fue la **global**  (modelo, permisos de terminal, MCPs habilitados en la instalación local de Cursor). No hay ruta en el repositorio que referenciar.

---

## 3. Skills personalizadas

**Ninguna**.

---

## 4. Slash commands personalizados

**Ninguno.**

---

## 5. Sub-agentes invocados

Uso ocasional del **modo agente** de Cursor (exploración de ficheros, parches en cadena, terminal). No se guardaron definiciones de sub-agentes en el repo.

- **Plan Mode / Task tool externos:** no usados de forma sistemática.
- **Transcripts:** generados por Cursor en disco local (`agent-transcripts/`), **no** incluidos en Git.

---

## 6. MCPs (Model Context Protocol)


| MCP                              | Para qué lo usaste                                    | ¿Qué te aportó?                                    |
| -------------------------------- | ----------------------------------------------------- | -------------------------------------------------- |
| Filesystem / workspace de Cursor | Lectura y búsqueda en el repo                         | Navegación y edición directa del módulo            |
| cursor-ide-browser               | Pruebas puntuales en front (cuando estaba disponible) | Verificación visual; no imprescindible para el MVP |
| GitHub / Context7 / otros        | —                                                     | No conectados de forma habitual                    |


Con más tiempo, un MCP de **documentación PrestaShop 1.7.8** podría haber reducido errores en nombres de hooks y constantes de multitienda.

---

## 7. Prompts importantes

### Prompt 0 — Preparación del contexto (ChatGPT → Cursor)

- **Herramienta:** ChatGPT (web), luego Cursor
- **Prompt (resumen):** Prompt estructurado para Cursor que describa el módulo `productbadges` (PS 1.7.8.11, Docker, sin Composer, MVP con CRUD, asignación, hook en ficha, tablas mínimas, lista de restricciones explícitas).
- **Qué generó (resumen):** Borrador de mensaje largo con secciones (contexto, objetivo, restricciones, entregables) listo para pegar en el chat de Cursor.
- **Qué hice con el output:** Lo edité, quitando y añadiendo puntos del enunciado y lo usé como **primer mensaje** en Cursor. La idea era generar un producto minimo viable sobre el que continuar desarrollando en Cursor.

### Consultas ChatGPT

- **Docker:** Relativas al uso, comandos, rutas típicas...
- **Git:**  consulta de comandos. Por ejemplo: Corrección de erratas tipográficas en commits.
- **Cursor:** cómo trocear las iteraciones y tareas para no mezclar demasiados objetivos en un solo mensaje. MVP → configuración → multilang → frontend).

### Prompt 1 — Definición del MVP

- **Herramienta:** Cursor
- **Prompt:** Especificación inicial del módulo `productbadges` (PrestaShop 1.7.8.x, Docker, sin Composer, CRUD + asignación + hook en ficha, tablas mínimas, simplificaciones explícitas).
- **Qué generó (resumen):** Propuesta de arquitectura (ObjectModel, AdminController, hooks, SQL) y posterior implementación del esqueleto.
- **Qué hice con el output:** Acepté el diseño simplificado (opción CRUD + pantalla de asignación) y pedí continuar con la implementación.

### Prompt 2 — Pantalla de configuración

- **Herramienta:** Cursor
- **Prompt:** Implementar `getContent()` + HelperForm con `PRODUCTBADGES_ENABLED`, `SHOW_LIST`, `SHOW_PRODUCT`, `MAX_PER_PRODUCT`.
- **Qué generó (resumen):** Métodos de instalación/guardado en `Configuration` y formulario en BO.
- **Qué hice con el output:** Acepté con revisión y realicé las pruebas en el gestor de módulos.

### Prompt 3 — Multilenguaje del nombre (badge)

- **Herramienta:** Cursor
- **Prompt:** Campo `name` multilang en ObjectModel + `product_badge_lang`, migración mínima, sin tocar hooks.
- **Qué generó (resumen):** Cambios en `ProductBadge`, SQL y formulario BO.
- **Qué hice con el output:** Revisé el correcto funcionamiento en la tienda desde dos idiomas (Inglés y Español) y validé los cambios.

### Prompt 4 — Traducciones de la UI del BO

- **Herramienta:** Cursor
- **Prompt:** Traducir interfaz ES/EN con `$this->l()` y ficheros `translations/es.php`, `en.php`.
- **Qué generó (resumen):** Cadenas en inglés en código + claves en `translations/`.
- **Qué hice con el output:** Modifiqué el prompt tras detectar que no salían español. Realicé pruebas desde el entrono local, generé las traducciones.

### Prompt 5 — Posición left/right en frontend

- **Herramienta:** Cursor
- **Prompt:** Campo `position`, CSS absoluto sobre imagen, sin JS, default `left`.
- **Qué generó (resumen):** Columna `position`, radio en BO, tpl + CSS.
- **Qué hice con el output:** Faltaba una columna en BD que generaba un error. Revertí el prompt teniendo esto en cuenta.

### Prompt 6 — Badges en listados

- **Herramienta:** Cursor
- **Prompt:** Hook `displayProductListReviews`, respetar `SHOW_LIST`, reutilizar tpl, sin duplicar lógica.
- **Qué generó (resumen):** Hook registrado, `renderProductBadgesHtml()`, CSS de miniatura.
- **Qué hice con el output:** Acepté; iteré varias veces en CSS por solapamientos con elementos del core. Ejemplo: la etiqueta de "Nuevo"

### Prompt 7 — Borrado masivo con modal BO

- **Herramienta:** Cursor
- **Prompt: Añadir el borrado masivo ya que no había quedado correctamente hecho en el prompt 1.** Sustituir `window.confirm()` del bulk delete por modal del back office + JS en el módulo.
- **Qué generó (resumen):** `Añadió el borrado en masa aunque con un alert que más adelante cambié por un modal.`
- **Qué hice con el output:** Probé manualmente, ajusté traducciones del modal y acepté.

### Prompt 8 — Multitienda (configuración)

- **Herramienta:** Cursor
- **Prompt:** Configuration por tienda, badges compartidas, consultas FO con contexto de shop, sin rehacer el módulo.
- **Qué generó (resumen):** `updateConfigurationValue` / `getConfigurationValue` con `id_shop`, join `product_shop` en FO.
- **Qué hice con el output:** Corregí constante inexistente `MULTISTORE_COMPATIBILITY_DEFAULT`. Este punto causaba un error en el gestor de módulos.

---

## 8. Errores de la IA que detecté

### Error 1 — Redefinición de `checkToken()`

- **Qué generó la IA (mal):** Método `protected function checkToken()` en `AdminProductBadgesController`.
- **Por qué estaba mal:** En PS 1.7 el padre exige `public`; provocaba *Compile Error* al entrar al listado.
- **Cómo lo corregiste:** Eliminé la redefinición y usé `parent::checkToken()`.

### Error 2 — Traducciones del BO en español

- **Qué generó la IA (mal):** Claves `adminproductbadgescontroller_`* en `es.php` o textos en español embebidos en `l()` sin dominio coherente.
- **Por qué estaba mal:** PrestaShop resuelve dominio `AdminProductBadgesController` → prefijo `adminproductbadges_`*; las claves no coincidían.
- **Cómo lo corregiste:** Cadenas fuente en inglés en `l()`, claves corregidas en `translations/es.php`, segundo parámetro `'AdminProductBadgesController'` en `module->l()`.

### Error 3 — Columna `position` inexistente

- **Qué generó la IA (mal):** Uso del campo `position` sin migración en instalaciones ya existentes.
- **Por qué estaba mal:** *Unknown column 'position' in 'field list'* al guardar.
- **Cómo lo corregiste:** `upgradePositionColumn()` + `ensureDatabaseSchema()` en carga del módulo/BO.

### Error 4 — Badges solo en bloque de información (Classic)

- **Qué generó la IA (mal):** Confiar solo en `displayProductCover` para la imagen.
- **Por qué estaba mal:** En tema Classic el hook visible sobre la imagen es `displayAfterProductThumbs`; el CSS en el hook del body llegaba tarde.
- **Cómo lo corregiste:** Hook adicional, `actionFrontControllerSetMedia` para CSS, estilos en tpl.

### Error 5 — Constante multitienda inexistente

- **Qué generó la IA (mal):** `Module::MULTISTORE_COMPATIBILITY_DEFAULT` en el constructor.
- **Por qué estaba mal:** En **1.7.8.11** no existe esa constante; rompía el gestor de módulos.
- **Cómo lo corregiste:** Sustituí por `MULTISTORE_COMPATIBILITY_PARTIAL` con comprobación `defined()`.

### Error 6 — Layout de varias badges (ancho y solapamientos)

- **Qué generó la IA (mal):** Stacks al 50% del ancho con flex `stretch`; overlay con `height: 0` y `max-height` fijo en listados.
- **Por qué estaba mal:** Barras negras más anchas que el texto; badges sobre el precio o fuera de la miniatura.
- **Cómo lo corregiste:** `.productbadges-wrapper`, `width: fit-content` y reglas de esitlos distintas para `--list` y `--product para adaptarse correctamente al espacio disponible`.

---

## 9. Partes que NO usé IA

- **Docker / docker-compose:** despliegue base del entorno según la prueba; dudas puntuales resueltas con ChatGPT, no con Cursor.
- **Pruebas finales** en navegador con cada iteracción o nueva funcionalidad.
- **Revisiónde textos** README orientados a entrega..
- **Decisiones de producto** del MVP. Que no incluir.

---

## 10. Reflexión final

- **Qué te ahorró la IA:** 
  - ChatGPT me ayudó a encuadrar mejor los prompts, resolver dudas de arquitectura y tooling (Docker, Git, estructura de módulo en PrestaShop) y a tomar decisiones rápidas cuando había varias opciones posibles. 
  - Cursor aceleró mucho la generación del esqueleto del módulo y permitió iterar rápido sobre errores reales al integrarlo con el tema Classic y con el soporte de multitienda.
- **En qué te entorpeció:** 
  - En algunos casos Cursor asumió comportamientos o APIs que no encajaban del todo con la versión de Prestashop utilizada a pesar de incluirla en los prompts, lo que obligó a revisar y ajustar parte del código generado, especialmente en hooks y en la lógica de multitienda. También hubo varias iteraciones en el sistema de traducciones y en el CSS del frontend hasta conseguir un comportamiento estable con múltiples etiquetas.
  - ChatGPT a veces proponía soluciones más amplias de lo necesario, por lo que fue importante filtrar bien antes de aplicarlas en Cursor.
- **Qué cambiarías si lo repitieras:** 
  - Estructuraría mejor el uso de la IA desde el inicio del proyecto. Mantendría un archivo base tipo `AGENTS.md` con las restricciones claras (PrestaShop 1.7.8.11, tema Classic, sin sobreingeniería) y un conjunto de prompts iniciales validados para evitar desviaciones.
  - Trabajaría con una separación más clara de “agentes” o modos de uso dentro de la IA: uno orientado a desarrollo e implementación, otro específico para debugging cuando aparecen errores, y otro tipo “ask” para dudas técnicas o exploración de soluciones. Esto ayuda a no mezclar contexto y a reducir dependencia directa de ChatGPT en el flujo diario de desarrollo.

