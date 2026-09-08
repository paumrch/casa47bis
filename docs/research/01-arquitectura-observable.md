# 01 — Arquitectura observable de portal.casa47.es

**Fecha de observación:** 2026-09-08
**Método:** reconocimiento pasivo. Peticiones HTTP GET/HEAD normales, consultas DNS/WHOIS
y lectura del certificado TLS. Lectura estática del bundle JavaScript servido públicamente.

**Qué NO se ha hecho, deliberadamente:** ningún fuzzing, ningún escaneo de directorios,
ningún envío de formularios, ningún POST, ninguna prueba de credenciales, ninguna
invocación de los endpoints de datos descubiertos en el código, ningún intento de
acceder a zonas autenticadas. Este documento no es un pentest ni lo pretende.

**Convención:** `[HECHO]` = observado directamente, con evidencia. `[INFERENCIA]` =
deducción, con nivel de confianza declarado. Nunca se presenta una inferencia como certeza.

---

## 1. DNS y hosting

- `[HECHO]` `portal.casa47.es` resuelve por CNAME a
  `casa47-prod-portal-ciudadania.powerappsportals.com`, que a su vez resuelve a
  infraestructura `*.azurefd.net` (Azure Front Door) precedida de Azure Traffic Manager.
- `[HECHO]` Direcciones resueltas: `150.171.109.83` (A) y `2603:1061:14:52::1` (AAAA).
  WHOIS de ambas: **Microsoft Corporation (MSFT)**, ASN de Azure.
- `[HECHO]` El apex `casa47.es` **no tiene registro A**: no hay web en el dominio raíz.
- `[HECHO]` Servidores de nombres del dominio: `ns1.cp2gestion-dtc-ib.com` /
  `ns2.cp2gestion-dtc-ib.com`. El DNS autoritativo lo gestiona un tercero, no Microsoft
  ni Cloudflare.
- `[HECHO]` `casa47.es` MX → `casa47-es.mail.protection.outlook.com`. Correo en
  Microsoft 365. El SPF incluye `spf.protection.outlook.com` y adicionalmente el rango
  `192.148.212.219–224`.
  `[INFERENCIA, confianza baja]` ese rango podría corresponder a un proveedor de envío
  transaccional adicional; no se ha podido atribuir.
- `[NO VERIFICADO]` Titular registral de `casa47.es`: `whois.nic.es` rechazó la consulta
  desde este entorno ("not authorised").

**Lectura:** el nombre del CNAME, `casa47-prod-portal-ciudadania`, es en sí mismo un
dato: identifica el entorno (`prod`) y el producto contratado (`portal-ciudadania`).

## 2. TLS

- `[HECHO]` TLS 1.3. Emisor: DigiCert / GeoTrust TLS RSA CA G1.
- `[HECHO]` SAN único: `portal.casa47.es`. Validez 2026-07-29 → 2027-01-29.
- `[INFERENCIA, confianza alta]` El ciclo de ~6 meses y el emisor son los propios de la
  gestión automática de certificados de Azure Front Door. La organización no gestiona
  su propio certificado.

## 3. Cabeceras HTTP

- `[HECHO]` Cabeceras de seguridad presentes: `Strict-Transport-Security` con `preload`,
  `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, y una
  `Content-Security-Policy` explícita y detallada, incluyendo `frame-ancestors 'none'`.
- `[HECHO]` Cabeceras que identifican la plataforma: `x-ms-request-id`,
  `x-ms-portal-app: s-c68bbe8f-…-POc` (identificador de la aplicación Power Pages),
  `x-azure-ref` (Azure Front Door).
- `[HECHO]` HTTP/2. No se observó `alt-svc` (sin HTTP/3 anunciado).

**Valoración honesta:** la postura de cabeceras de seguridad del portal es **buena**, y
en buena medida viene "de fábrica" con la plataforma. Cualquier arquitectura alternativa
debe igualar esto como mínimo; no es un punto débil del sistema actual.

## 4. Cookies fijadas sin autenticación

- `[HECHO]` `WebPageCaching`, `Dynamics365PortalAnalytics` — `Secure`, `HttpOnly`,
  `SameSite=Lax`.
- `[HECHO]` `ARRAffinity`, `ARRAffinitySameSite` — `Secure`, `HttpOnly`,
  `SameSite=None` / sin atributo.
- `[INFERENCIA, confianza alta]` `ARRAffinity` es la cookie de afinidad de sesión de
  Azure App Service; `Dynamics365PortalAnalytics` es propia de Power Pages / Dynamics 365
  Portals. Confirman la plataforma.

## 5. Naturaleza del frontend — hallazgo principal

- `[HECHO]` El HTML de la raíz pesa **1.627 bytes** comprimidos con gzip y contiene el
  comentario literal `<!-- Default studio template. Please do not modify -->` y el
  atributo `xrm-editable-html`, marcas inequívocas de la plantilla por defecto del
  estudio de Power Pages.
- `[HECHO]` Ese HTML mínimo carga `<script type="module" src="./index.js">`, un bundle
  único de **1.333.490 bytes**, y monta la aplicación sobre `<div id="root">`. Existe
  además un `manifest.json` de PWA.
- `[HECHO]` En el bundle aparecen `maplibre` (motor de mapas) y referencias a Google
  Fonts (`Poppins`).
- `[INFERENCIA, confianza media]` Por el patrón `id="root"`, el módulo ESM único y el
  manifest PWA, se trata muy probablemente de una SPA React empaquetada con Vite,
  embebida dentro del layout de Power Pages.

**Esto es lo más relevante del reconocimiento.** El portal ciudadano **no está
construido con las capacidades declarativas de Power Pages**. Power Pages actúa aquí,
en lo esencial, como *hosting con autenticación y proxy de datos* para una aplicación
JavaScript a medida. La consecuencia analítica es directa: el argumento "se eligió una
plataforma low-code para no programar" no se sostiene frente a lo observable, porque
la interfaz ciudadana está programada a medida de todas formas.

Consecuencia adicional: un bundle de 1,33 MB entregado antes de pintar nada penaliza
LCP, INP y accesibilidad, y hace que todo el contenido público dependa de JavaScript.

## 6. Backend de datos

- `[HECHO]` En el bundle aparecen rutas `_api/accounts`, `_api/contacts`,
  `_api/incidents` y `_api/cloudflow`.
- `[INFERENCIA, confianza alta]` Es el patrón estándar de Power Pages: la SPA consume la
  **Dataverse Web API** expuesta por el portal y dispara **Cloud Flows de Power Automate**.
  Las entidades `account`, `contact` e `incident` son tablas estándar de Dataverse/Dynamics.
- **Ninguno de estos endpoints ha sido invocado.** Solo se han leído del código público.

## 7. Terceros y dependencias externas

Deducidos de la `Content-Security-Policy`, que enumera explícitamente los orígenes
permitidos `[HECHO]`:

| Origen permitido | Función | Naturaleza |
|---|---|---|
| Microsoft Clarity | Analítica de comportamiento y grabación de sesión | Microsoft |
| `spaincentral-0.in.applicationinsights.azure.com` | Telemetría de aplicación (Azure Monitor) | Microsoft, región **Spain Central** |
| `c.bing.com` | Telemetría / publicidad | Microsoft |
| `tiles.openfreemap.org` | Teselas de mapa — **OpenFreeMap**, no Google Maps | Tercero abierto |
| `casa47.sharepoint.com` | Documentación / contenido | Microsoft |
| Google Fonts | Tipografía (`Poppins`) | Google |
| YouTube | Vídeo embebido | Google |
| `floorfy.com`, `app.floorfy.com` | Tours virtuales inmobiliarios | Tercero comercial |

- `[HECHO]` Assets propios (incluidas plantillas PDF como
  `autorizacion_inclusion_convocatoria_CASA47` y `modelo_representacion_CASA`) se sirven
  desde un CDN de Microsoft dedicado: `recursos-bhh9e8dkgkb0b6e9.a03.azurefd.net`.

**Nota para el análisis de coste:** el mapa ya usa una fuente abierta y gratuita
(OpenFreeMap + MapLibre). Es decir, la propia solución actual **ya demuestra** que la
capa de mapas no requiere un servicio propietario de pago.

**Nota para el análisis RGPD:** Clarity implica grabación/reproducción de sesión sobre
un portal que tratará datos personales sensibles. Es un punto a examinar en la sección
de protección de datos, no una acusación.

## 8. Ficheros públicos convencionales

- `[HECHO]` `/robots.txt` → **404** (con página 404 personalizada de Power Pages).
- `[HECHO]` `/sitemap.xml` → **200**, pero el cuerpo es idéntico a la página
  "Page Not Found". Es decir: no hay sitemap real, y además el sitio devuelve 200 para
  un recurso inexistente. Anomalía observada.
- `[HECHO]` `/.well-known/security.txt` → **404**. No hay canal declarado de divulgación
  responsable de vulnerabilidades.
- `[HECHO]` `/favicon.ico` → 200.

## 9. Rendimiento observable

- `[HECHO]` TTFB ≈ **245 ms**. HTTP/2. HTML raíz comprimido con `gzip`
  (no `br` en la respuesta observada). `/index.js` con `max-age=3600` y `ETag`.
- `[INFERENCIA, confianza alta]` Con un TTFB de 245 ms y un bundle bloqueante de 1,33 MB,
  el LCP real en red móvil será muy superior a los objetivos que nos hemos fijado
  (< 1,5 s). Pendiente de medición formal con Lighthouse en la fase de benchmark.

---

## Resumen de la pila observable

| Capa | Tecnología | Base |
|---|---|---|
| DNS autoritativo | Proveedor tercero (`cp2gestion-dtc-ib.com`) | `[HECHO]` |
| CDN / WAF / TLS | Azure Front Door + Traffic Manager | `[HECHO]` |
| Hosting de aplicación | Microsoft Power Pages (Azure App Service subyacente) | `[HECHO]` |
| Frontend | SPA JavaScript a medida (probable React+Vite), 1,33 MB | `[HECHO]` / `[INFERENCIA media]` |
| API de datos | Dataverse Web API (`_api/…`) | `[INFERENCIA alta]` |
| Automatización | Power Automate Cloud Flows (`_api/cloudflow`) | `[INFERENCIA alta]` |
| Mapas | MapLibre + OpenFreeMap | `[HECHO]` |
| Tours virtuales | Floorfy (SaaS tercero) | `[HECHO]` |
| Analítica | Microsoft Clarity + Application Insights (Spain Central) | `[HECHO]` |
| Documentos/contenido | SharePoint Online | `[HECHO]` |
| Correo | Microsoft 365 (Exchange Online) | `[HECHO]` |
| Región de datos | Spain Central (al menos para telemetría) | `[HECHO]` |

## Lo que NO se puede saber desde fuera

- El modelo de datos real en Dataverse: tablas propias, campos, relaciones.
- Si existe Dynamics 365 detrás del portal, o solo Dataverse.
- Cuántos flujos de Power Automate hay y qué hacen.
- Qué integraciones con la AGE están efectivamente en producción.
- El coste real de licencias, que depende de descuentos no públicos.
- El reparto real de esfuerzo entre configuración de plataforma y desarrollo a medida.
- Los tiempos de respuesta de la zona autenticada y su comportamiento bajo carga.
- La categoría ENS declarada y el estado de su certificación.

Estas incógnitas se recogen en la sección "Qué no sabemos todavía" del informe final.
Ninguna conclusión del informe puede depender de ellas.
