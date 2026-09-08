# Mapa funcional público — portal.casa47.es

Fecha de análisis: 2026-09-08. Analista: subagente investigador (Claude Code).

## 1. Metodología y límites

- Navegación pública, solo peticiones `GET`, sin fuzzing, sin formularios, sin login. Menos de 30 peticiones HTTP realizadas en total.
- Se descargó la portada (`curl https://portal.casa47.es/`), `robots.txt`, `sitemap.xml`, `manifest.json` y el bundle JS principal `index.js` (referenciado directamente por la portada vía `<script type="module" src="./index.js">`, es decir, es un recurso público que el propio navegador cargaría).
- **Hallazgo estructural crítico**: el portal es una SPA React (Vite build) servida como plantilla de **Microsoft Power Pages / Dynamics 365 Dataverse** ("Default studio template. Please do not modify", div `xrm-editable-html`). El HTML crudo servido por el servidor **no contiene contenido de negocio**: solo `<div id="root"></div>` y la carga del bundle. Todo el contenido (textos, catálogo, convocatorias, legales) se renderiza en cliente vía JavaScript y llamadas a una API interna `/_api/...` (Dataverse Web API).
- Consecuencia metodológica: no existen `<a href>` reales en el HTML servido para "seguir" (no hay enlaces que extraer con curl). Las rutas documentadas abajo se han identificado leyendo las **cadenas de rutas del propio router** dentro de `index.js` (recurso público ya descargado, no adivinación) y verificando después con `curl -o /dev/null -w "%{http_code}"` que el servidor responde. El servidor de Power Pages es un **catch-all SPA**: devuelve HTTP 200 y el mismo shell para cualquier ruta de cliente válida o inválida dentro del router de React (no se puede diferenciar "existe" de "no existe" solo por el código HTTP; se valida por presencia real de la ruta en el código de rutas del bundle).
- `robots.txt` → HTTP 404 (con página de error 404 propia de Power Pages, "Page Not Found"). `sitemap.xml` → HTTP 200 pero con el mismo contenido de página "Page Not Found" (o sea, tampoco existe un sitemap real; el servidor devuelve 200 indiscriminadamente para *.xml* también, salvo `robots.txt` que sí devuelve 404 real).
- No se han enviado formularios, no se ha iniciado sesión, no se ha llamado a `/_api/*` (esos endpoints están detrás de autenticación / CORS same-origin y quedan fuera del alcance permitido).

## 2. Árbol de rutas verificadas (código HTTP del servidor; contenido real solo vía JS)

```
https://portal.casa47.es/                                   200  (SPA shell, portada)
├── /Home                                                    200  (alias de portada, visto en router)
├── /contacto                                                200  (página de contacto)
├── /noticias                                                200  (listado de noticias)
│   └── /noticias/:slug                                      200* (detalle de noticia — patrón de ruta detectado, no instancia concreta navegada)
├── /listado-convocatorias                                   200  (catálogo de convocatorias)
│   └── /listado-convocatorias/:id                           200* (ficha de convocatoria — patrón de ruta)
├── /filtrado-viviendas                                      200  (buscador/filtro de viviendas, home del catálogo)
├── /viviendas/listado                                       200  (listado de viviendas paginado)
├── /viviendas/mapa                                          200  (vista mapa; carga lazy `MapaViviendas.js`)
│   └── /viviendas/:id                                       200* (ficha de vivienda — patrón de ruta)
├── /solicitar-vivienda                                      200  (página informativa/guía del proceso de solicitud)
├── /solicitud                                                200  (raíz del asistente de solicitud)
│   ├── /solicitud/inicio                                    200  (paso inicial; REQUIERE AUTENTICACIÓN — redirige a login si no hay sesión)
│   ├── /solicitud/convocatoria                              200  (selección de convocatoria — auth)
│   ├── /solicitud/:solicitudId/datos-personales             200* (auth)
│   ├── /solicitud/:solicitudId/unidad-convivencia           200* (auth)
│   ├── /solicitud/:solicitudId/ingresos                     200* (auth)
│   ├── /solicitud/:solicitudId/seleccion-vivienda            200* (auth)
│   ├── /solicitud/:solicitudId/no-cumple-requisitos          200* (auth)
│   ├── /solicitud/:solicitudId/confirmacion                 200* (auth)
│   ├── /solicitud/:solicitudId/firma                          200* (auth)
│   ├── /solicitud/:solicitudId/resumen                        200* (auth)
│   └── /solicitud/detalle/:id                                200* (auth, "Detalle de la solicitud" ya presentada)
├── /legal/aviso-legal                                        200  (Aviso legal)
├── /legal/privacidad                                         200  (Política de privacidad)
├── /legal/cookies                                            200  (Política de cookies)
├── /legal/accesibilidad                                      200  (Declaración de accesibilidad)
├── /error, /error-login                                      200  (páginas de error internas del router)
├── /MapaViviendas.js                                         200  (chunk JS lazy del mapa de viviendas)
├── /manifest.json                                            200  (manifest PWA)
├── /index.js                                                 200  (bundle principal, 1.33 MB, minificado)
├── /robots.txt                                               404  (no existe; el servidor sí distingue este caso)
└── /sitemap.xml                                              200  (pero devuelve el shell/"Page Not Found" — no hay sitemap real)
```
`*` = ruta con parámetro (`:id`, `:solicitudId`), verificado el patrón en el router de `index.js`, no se navegó a un ID concreto (evitando invención de rutas/IDs).

Endpoints de API internos detectados por lectura del bundle (NO invocados, están detrás de sesión/CORS same-origin): `/_api/contacts`, `/_api/accounts`, `/_api/incidents`, `/_api/cloudflow`, y entidades personalizadas Dataverse con prefijo `ey_` (p. ej. `ey_dv_cus_unidad_convivencia`, `ey_dv_cus_solicitud`). Esto confirma arquitectura **Power Pages sobre Dataverse (Dynamics 365)**.

## 3. Tabla resumen

| Ruta | Tipo | Propósito | Requiere JS | Requiere auth |
|---|---|---|---|---|
| `/` , `/Home` | Portada | Landing, hero, pasos del proceso, FAQ | Sí (100% JS) | No |
| `/contacto` | Informativa | Datos/canales de contacto | Sí | No |
| `/noticias` | Listado | Actualidad de Casa 47 | Sí | No |
| `/listado-convocatorias` | Listado | Catálogo de convocatorias con filtros (estado, orden) | Sí | No |
| `/listado-convocatorias/:id` | Ficha | Detalle de convocatoria: resumen, zonas, requisitos, comprobaciones, fechas, documentación, viviendas asociadas | Sí | No |
| `/filtrado-viviendas` | Formulario/Listado | Entrada al buscador: nº personas + ingresos anuales → filtra resultados | Sí | No |
| `/viviendas/listado` | Listado | Catálogo de viviendas paginado, con filtros y orden | Sí | No |
| `/viviendas/mapa` | Listado (mapa) | Mismo catálogo en vista mapa (chunk lazy `MapaViviendas.js`) | Sí | No (la app marca esta ruta en un conjunto especial de rutas "públicas siempre", junto a `/error`) |
| `/viviendas/:id` | Ficha | Detalle de vivienda: precio, ingresosMin/Max, personasMin/Max, habitaciones, superficie, condiciones de acceso | Sí | No |
| `/solicitar-vivienda` | Informativa (guía) | Explica el proceso de solicitud paso a paso, requisitos y documentación | Sí | No |
| `/solicitud`, `/solicitud/inicio`, `/solicitud/convocatoria` y todos los pasos del asistente | Formulario multi-paso | Alta de solicitud de vivienda (datos personales, unidad de convivencia, ingresos, selección de vivienda, declaración responsable, firma, resumen) | Sí | **Sí** (redirige a `?login=1` si no hay sesión) |
| `/legal/aviso-legal` | Legal | Aviso legal | Sí | No |
| `/legal/privacidad` | Legal | Política de privacidad | Sí | No |
| `/legal/cookies` | Legal | Política de cookies | Sí | No |
| `/legal/accesibilidad` | Legal | Declaración de accesibilidad | Sí | No |

## 4. Fichas detalladas

### 4.1 Portada `/`
- Título: "Casa 47 — Viviendas para alquiler asequible". Meta description: "Casa 47, Entidad Estatal de Vivienda: convocatorias y viviendas para alquiler asequible...".
- Contenido (según strings del bundle): hero, pasos del proceso ("Revisa los requisitos", "Selecciona...", etc.), bloque de acordeón FAQ con preguntas "¿Qué necesito para participar?", "¿Cómo presento una solicitud?", "Estado de mi solicitud". CTA hacia `/solicitar-vivienda` ("Ver todas las preguntas frecuentes").
- Todo el contenido se pinta por JS; el HTML crudo (`curl`) solo trae `<div id="root"></div>`.
- Enlaces salientes detectados en el bundle: LinkedIn (`es.linkedin.com/company/casa47`), Facebook (`facebook.com/casa47gob`), Instagram (`instagram.com/casa47gob`), X (`x.com/Casa47gob`), TikTok, YouTube, Microsoft Clarity (analítica), buzón de quejas y sugerencias en `www.casa47.es/buzon-de-quejas-y-sugerencias`, plantillas PDF en Azure Front Door (`recursos-bhh9e8dkgkb0b6e9.a03.azurefd.net`), enlace informativo a la Sede electrónica de la Agencia Tributaria (registro Cl@ve PIN/certificado) y a la Sede Electrónica de la Seguridad Social. **No se detecta enlace directo a Cl@ve como método de login del propio portal en el bundle analizado** — el login de la solicitud (`?login=1`) no expone en el código analizado el proveedor de identidad exacto; requeriría inspección adicional (fuera de alcance permitido, ya que implicaría iniciar el flujo de autenticación).

### 4.2 Catálogo de viviendas (`/viviendas/listado`, `/viviendas/mapa`, `/filtrado-viviendas`)
- Filtros observados en el código: número de personas de la unidad de convivencia, ingresos anuales del hogar (input numérico con formato "31.520"), campo "personas" y "accesibilidad" (checkbox/desplegable, ancho fijo en CSS `lvfu-fc-field-accesibilidad`), número de dormitorios, "medidas de accesibilidad", estado, y orden ("Ordenar por" con opciones dinámicas `Ve[e]`).
- Contador dinámico de resultados: string `{total} viviendas encontradas` — el número real de elementos del catálogo **no se pudo determinar sin ejecutar JS/llamar a la API**, por lo que no se transcribe una cifra (evitar inventar dato).
- Paginación: sí, mediante estado de página (`g(1)`, `L(1)` se resetean al cambiar filtro/orden), típico de listado paginado cliente + fetch a API.
- Vista mapa: componente separado cargado de forma perezosa (`import('./MapaViviendas.js')`, confirmado accesible con `GET /MapaViviendas.js` → HTTP 200). No se identificó en el bundle principal la librería de mapas (Leaflet/Google Maps/Mapbox no aparecen como strings en `index.js`; probablemente está en el propio chunk `MapaViviendas.js`, no inspeccionado por presupuesto de peticiones). `[NO VERIFICADO]` qué proveedor de mapas usa.
- Campos de ficha de vivienda (`/viviendas/:id`), extraídos de plantillas del bundle: ingresosMin, ingresosMax, personasMin/personasMax, habitaciones, superficie (m²), municipio/CP, precio, "condiciones para acceder".

### 4.3 Convocatorias (`/listado-convocatorias`, `/listado-convocatorias/:id`)
- Listado con filtro por estado y orden ("Convocatorias de Vivienda... plazos, requisitos y viviendas asociadas").
- Ficha de convocatoria con secciones ancladas (scroll-spy): `resumen`, `zonas`, `requisitos`, `comprobaciones`, `fechas`, `documentacion`, `viviendas` — navegación interna por anclas con `IntersectionObserver`.
- Bloque "Requisitos de Participación" con tarjetas (Residencia, Ingresos, Propiedad, etc.) y una sección expandible "Ver detalle completo de los requisitos" con la lista literal transcrita en el punto 5.
- Documentación mencionada como necesaria para presentar solicitud: "Certificados de cumplimiento de los requisitos: estar al corriente de pagos con la AEAT y la Seguridad Social, no tener viviendas en propiedad, etc." y "Documentos acreditativos de tus ingresos (Declaración de la Renta...)".

### 4.4 Asistente de solicitud (`/solicitud/...`)
- Flujo de pasos: `inicio-solicitud → datos-personales → unidad-convivencia → ingresos → seleccion-vivienda → confirmacion → firma → resumen`, con desvío a `no-cumple-requisitos` si la unidad de convivencia no cumple ingresos u otros criterios.
- **Requiere autenticación**: función de guarda de ruta comprueba sesión (`Qt()`) y si no existe redirige a `/solicitud/inicio?login=1` guardando la ruta de origen (`state:{from:...}`).
- Campos del formulario "Unidad de convivencia": número total de personas (con mínimo/máximo parametrizado, variables `hd`/`ud` en el bundle, valores no legibles en claro), ficha por miembro (nombre, apellidos, tipo y número de documento, discapacidad sí/no, etc.).
- Campos del formulario "Ingresos": ingresos por miembro, ingresos del hogar (autorrellenado, solo lectura), número de personas (autorrellenado).
- Validación de ingresos: mensajes explícitos "Los ingresos totales de la unidad de convivencia no alcanzan el mínimo exigido por la convocatoria (2 IPREM)" / "...superan el máximo permitido... (7,5 IPREM)".
- Declaración responsable con checkbox obligatorio antes de firmar; firma descrita como "Firma básica (no criptográfica) mediante identificación..." (texto truncado en el bundle, no se pudo confirmar el método exacto sin ver el chunk completo del componente de firma). `[NO VERIFICADO]` el mecanismo de firma exacto (¿OTP, contraseña, Cl@ve?).
- Resumen final genera un documento con secciones numeradas: 3) Unidad de convivencia, 4) Ingresos, 5) Viviendas solicitadas, 6) (declaración/firma, corte del extracto).

## 5. Criterios de elegibilidad transcritos literalmente

Fuente: cadenas de texto embebidas en `https://portal.casa47.es/index.js` (contenido de la ficha `/listado-convocatorias/:id`, sección "Requisitos de Participación"). Cita textual:

> "Para participar necesitas formar una unidad de convivencia cuyos miembros tengan nacionalidad española o residencia legal en España, no tener vivienda en propiedad (salvo excepciones) y acreditar unos ingresos dentro de los límites de la convocatoria. Cada convocatoria puede incluir requisitos adicionales."

Bloque resumen corto (tarjetas de la home del catálogo, objeto `ro` en el bundle):

> ingresos: "Ingresos totales de la unidad de convivencia entre 16.800 y 63.000 €/neto (2 y 7,5 veces el IPREM)"
> residencia: "Disponer de nacionalidad o residencia legal en España"
> propiedad: "No poseer ninguna otra vivienda en propiedad"

Lista de detalle completo ("Ver detalle completo de los requisitos", ficha de convocatoria):

> "Todos los miembros de la unidad deben tener nacionalidad española o residencia legal en España."
> "Al menos un miembro de la unidad de convivencia debe ser mayor de edad o ser una o un menor emancipado."
> "Al menos, un miembro de la unidad debe hallarse en plena posesión de su capacidad jurídica y de obrar y que no esté incursa en prohibición para contratar de conformidad con la Ley de Contratos [del Sector Público]..."
> "Ninguna de las personas que conforman la unidad de convivencia podrán ser propietarias de otra vivienda (propietarios, nudos propietarios, titulares del derecho de superficie o usufructo), exceptuando los siguientes casos: [lista truncada en el fragmento capturado]."
> "La suma de los ingresos anuales netos de todos los miembros de la unidad de convivencia, correspondientes al IRPF del ejercicio 2025 debe estar comprendida entre 2 y 7,5 veces el IPREM (Indicador Público de Renta de Efectos Múltiples), que actualmente oscila entre 16.800 € y 63.000 € anuales."
> "Para la determinación de los ingresos netos de las personas que hayan presentado la Declaración de la Renta se deberá reflejar la suma de las cantidades que... [texto truncado, siempre referidos al ejercicio fiscal de 2025]."
> "Todos los miembros de la unidad de convivencia deberán estar al corriente de pago con la Seguridad Social y de sus obligaciones tributarias. En el caso de las personas residentes en el País Vasco y Navarra, los miembros de la unidad de convivencia deberán estar al corriente del pago con la Seguridad Social y con las Haciendas Forales."
> "En el caso de las viviendas reservadas a unidades de convivencia con algún miembro con discapacidad, éste deberá acreditar un grado de discapacidad igual o superior... [truncado]."
> "...en el caso de las viviendas adaptadas, será necesario que alguno de los miembros de la unidad de convivencia tenga reconocida movilidad reducida, entendiendo por tal una limitación final de movilidad igual o superior al 25% conforme al Baremo de Limitaciones en las Actividades de Movilidad (BLAM)."

Declaración responsable exigida en el paso de confirmación:

> "Declaro responsablemente que tanto yo mismo/a, como la unidad de convivencia en su conjunto que represento, y todos sus miembros, cumplimos todos los requisitos establecidos en las bases de esta convocatoria de alquiler asequible de vivienda para ser adjudicatarios/as del alquiler de una de estas viviendas, en concreto los ingresos anuales de la unidad de convivencia el año anterior se situaron en los límites que marca la convocatoria, los miembros de la misma no tenemos deudas tributarias ni con la Seguridad Social, no disponemos de una vivienda en propiedad (salvo las excepciones recogidas en las bases de la convocatoria), y al menos una... [texto truncado en el fragmento capturado]."

Otros criterios de exclusión/renta detectados en el flujo "no cumple requisitos":

> "Renta: la cuota debe suponer entre el 15 % y el 30 % de los {importe} €/año del hogar..." (regla de esfuerzo económico admisible sobre la renta del hogar; fórmula exacta no completamente legible en el fragmento).

Todas las citas anteriores provienen de `https://portal.casa47.es/index.js` (recurso descargado el 2026-09-08); no de una página HTML navegable con URL propia, dado que el contenido se inyecta en tiempo de ejecución en las rutas `/listado-convocatorias/:id` y `/filtrado-viviendas` / `/viviendas/listado`.

## 6. Declaración de accesibilidad — transcripción y valoración

Fuente: texto embebido en `index.js`, sección `accesibilidad` del objeto de contenidos legales, correspondiente a la ruta `/legal/accesibilidad`.

- **Marco normativo declarado**: Real Decreto 1112/2018, de 7 de septiembre, sobre accesibilidad de sitios web y apps móviles del sector público. **No se cita explícitamente un nivel WCAG (A/AA/AAA)** en el texto capturado; el RD 1112/2018 remite a la norma UNE-EN 301549 (equivalente a WCAG 2.1 nivel AA), pero el portal no lo menciona literalmente en el fragmento accesible. `[NO VERIFICADO]` si en otra parte del texto se cita WCAG 2.1 AA explícitamente — no apareció en la búsqueda realizada.
- **Situación de cumplimiento declarada**: "Este portal es parcialmente conforme con el Real Decreto 1112/2018 debido a la posible existencia de aspectos no conformes, pendientes de revisión o mejora."
- **No conformidades reconocidas** (apartado "a) Falta de conformidad con el Real Decreto 1112/2018"):
  - Uso incorrecto o mejorable de la jerarquía de encabezados en determinadas páginas.
  - Imágenes, iconos o elementos gráficos con alternativas textuales insuficientes.
  - Enlaces cuyo texto no es suficientemente descriptivo.
  - Formularios con etiquetas, ayudas, validaciones o mensajes de error pendientes de mejora.
  - Componentes interactivos que pueden requerir ajustes para navegación por teclado/tecnologías de asistencia.
  - Tablas de datos posiblemente no completamente etiquetadas para lectores de pantalla.
  - Documentos descargables en PDF u otros formatos que pueden no cumplir íntegramente los criterios.
  - Contenidos embebidos o de terceros fuera del control de Casa 47.
- **Carga desproporcionada**: "No aplica, salvo que se identifique y justifique expresamente para contenidos concretos."
- **Contenido fuera del ámbito de la legislación**: posibles archivos ofimáticos/PDF anteriores a la entrada en vigor de los requisitos.
- **Canal de quejas/reclamación**: formulario en `https://www.casa47.es/buzon-de-quejas-y-sugerencias`, teléfono 91 556 50 15, correo `lopd@casa47.es`, procedimiento de reclamación conforme al art. 13 del RD 1112/2018.
- **Fecha de última actualización declarada**: "20 de julio de 2026" (misma fecha que el Aviso legal, lo que sugiere una publicación/plantilla generada en bloque, no necesariamente una revisión de accesibilidad específica).
- **Valoración del analista**: la declaración es genérica/plantilla (usa condicionales tipo "podría no ser completamente accesible" en casi todos los puntos, sin auditoría concreta con hallazgos numerados ni fecha de auditoría técnica). No se declara nivel WCAG explícito. No hay enlace a informe de auditoría o metodología de evaluación (autoevaluación/evaluación externa) en el texto capturado.

## 7. Superficie existente no accesible sin autenticación (deducida de enlaces/código, sin entrar)

- Todo el asistente de solicitud (`/solicitud/inicio`, `/solicitud/convocatoria`, y los pasos `datos-personales`, `unidad-convivencia`, `ingresos`, `seleccion-vivienda`, `no-cumple-requisitos`, `confirmacion`, `firma`, `resumen`) exige sesión iniciada; sin sesión, el router redirige a `/solicitud/inicio?login=1`.
- `/solicitud/detalle/:id` — vista de detalle de una solicitud ya presentada por el ciudadano (requiere sesión y pertenencia de la solicitud).
- Endpoints de backend Dataverse (`/_api/contacts`, `/_api/accounts`, `/_api/incidents`, `/_api/cloudflow`, entidades `ey_dv_cus_*` como `unidad_convivencia` y `solicitud`) — API interna autenticada, no invocada en este análisis.
- No se ha detectado, dentro del alcance permitido, el proveedor de identidad exacto usado en el login (¿Azure AD B2C, Cl@ve, credenciales propias del portal?): `[NO VERIFICADO]`.

## 8. Preguntas abiertas

1. ¿Qué proveedor de identidad se usa en `?login=1` (Cl@ve, Azure AD B2C, credenciales propias)? No se pudo determinar sin iniciar el flujo de login.
2. ¿Cuántas viviendas y convocatorias hay realmente publicadas ahora mismo? No se pudo obtener sin ejecutar el JS/llamar a la API de datos (fuera del alcance de "no fuzzing / no llamadas a API").
3. ¿Qué librería de mapas usa `/viviendas/mapa`? Está en el chunk `MapaViviendas.js`, no inspeccionado por límite de peticiones.
4. El RD 1112/2018 exige referenciar la norma UNE-EN 301549 / WCAG 2.1 AA; el texto capturado no la cita literalmente — ¿existe una versión más completa de la declaración con esa referencia que no se haya extraído en el fragmento leído?
5. ¿Por qué `sitemap.xml` devuelve HTTP 200 con contenido de "Page Not Found" en vez de 404 como `robots.txt`? Posible inconsistencia de configuración de Power Pages, sin impacto funcional para el ciudadano pero relevante para SEO/indexación.
6. El mecanismo de firma en el paso "firma" se describe como "Firma básica (no criptográfica)" en un fragmento truncado — falta confirmar el método exacto (OTP por SMS/email, contraseña, etc.).
