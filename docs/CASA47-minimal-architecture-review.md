# CASA 47, Minimal Architecture Review

**Fase 1, Auditoría arquitectónica.**
Documento de trabajo para revisión técnica. Versión 1.0, 8 de septiembre de 2026.

---

## Nota metodológica previa

Este documento analiza un sistema de información público a partir **exclusivamente de
información pública**: los pliegos del expediente, los anuncios oficiales, el
comportamiento observable del portal y la documentación de los productos implicados.

**Lo que se ha hecho:** peticiones HTTP normales a recursos públicos, consultas DNS,
lectura del certificado TLS, lectura del código JavaScript servido públicamente, descarga
de los pliegos desde los datos abiertos de la Plataforma de Contratación del Sector
Público, y consulta de tarifas oficiales.

**Lo que no se ha hecho, deliberadamente:** ningún escaneo, ningún fuzzing, ningún envío
de formularios, ninguna prueba de credenciales, ninguna invocación de los endpoints de
datos descubiertos en el código, ningún acceso a zonas autenticadas.

**Convenciones.** Se distingue en todo momento `[HECHO]` de `[INFERENCIA]`, con nivel de
confianza declarado. Ninguna cifra se publica sin fuente; donde no hay precio público se
usan rangos con hipótesis explícitas, marcadas como tales.

**Lo que este documento no contiene, y no contendrá:** ninguna imputación sobre personas,
ninguna afirmación sobre la legalidad del procedimiento de contratación, ninguna
conclusión política. La adjudicataria y el organismo actuaron dentro de un procedimiento
público reglado. Lo que se discute aquí son **decisiones de arquitectura y sus
consecuencias económicas y de dependencia**, que es una discusión técnica legítima y
necesaria.

**Y una advertencia de honestidad que gobierna todo el texto:** el encargo era intentar
*destruir* la hipótesis de partida, no confirmarla. Varias conclusiones de este informe
contradicen la expectativa con la que se inició el trabajo, y se han mantenido tal cual.
Cuando una cifra intermedia resultó estar mal, se corrigió y se dejó constancia del error.

---

## 1. Resumen ejecutivo

**Qué se analizó.** El portal ciudadano de CASA 47 -Entidad Estatal de Vivienda, SEPES EPE
transformada, adscrita al Ministerio de Vivienda y Agenda Urbana- y el contrato del
Sistema Integrado de Gestión del Parque de Alquiler Asequible, expediente 132019,
adjudicado a EY Transforma Servicios de Consultoría S.L. el 26 de noviembre de 2025 por
**1.184.998 € sin IVA** a cuatro años, sobre un valor estimado de 1.880.949 €.

**Conclusión principal, que contradice la hipótesis de partida.** El importe adjudicado
**no es desproporcionado**. Equivale a 296.250 € anuales por consultoría de procesos,
desarrollo, implantación, formación, soporte y cuatro años de mantenimiento, sobre un
alcance que incluye catorce módulos SAP, una decena de servicios de la Administración
General del Estado y conformidad ENS de **nivel alto**. Construir bien este sistema cuesta
un dinero parecido, lo construya quien lo construya. **La tesis de "esto se podía hacer
por mucho menos" no se sostiene en la partida de trabajo.**

**Dónde está entonces el problema.** En tres hechos verificados documentalmente:

1. **Las licencias de plataforma están fuera del contrato.** Cita literal del pliego
   técnico, cláusula 7.3: *"El presente contrato no incluye la contratación de las
   licencias de la tecnología base que sean necesarias."* Se contratan aparte, por la
   DGRCC. El coste que se difunde públicamente como coste del sistema **no incluye la
   partida que crece con el uso**.
2. **El modelo de licenciamiento acopla el coste al éxito del servicio.** Power Pages se
   factura por usuarios únicos autenticados **por mes natural**, sin acumulación de
   capacidad no consumida. A precio de lista y con el escalón aplicable, un pico mensual
   de 50.000 ciudadanos supone del orden de 450.000 USD anuales sólo en esa línea. En la
   arquitectura propuesta esa partida es **cero**, y el coste marginal de un ciudadano más
   es de céntimos de cómputo.
3. **No hay cláusula de propiedad del código ni de reversibilidad.** La búsqueda literal
   de "propiedad", "código fuente", "cesión de derechos", "reversibilidad" y "plan de
   salida" en los textos completos del PCAP y del PPT arroja **cero coincidencias**. Es el
   hallazgo de gobernanza más serio del informe.

**Hallazgo técnico que reorienta el análisis.** El portal ciudadano **no está construido
con las capacidades declarativas de la plataforma**: es una aplicación JavaScript a medida
de **1.333.490 bytes** servida dentro de la plantilla por defecto de Power Pages -cuyo
comentario `<!-- Default studio template. Please do not modify -->` sigue en el HTML-, que
actúa como alojamiento, autenticación y proxy de datos hacia Dataverse. La justificación
habitual de una plataforma de desarrollo rápido, "no hay que programar", **no se
corresponde con lo observable**.

**Hallazgo contractual decisivo.** El pliego **no obligaba** a un CRM comercial. Cita
literal: *"Otras soluciones: Se podrán admitir otras tecnologías siempre que demuestren
interoperabilidad, escalabilidad, madurez, durabilidad y compatibilidad."* La arquitectura
que aquí se propone **era admisible en esta licitación**. La dependencia de plataforma no
procede de una imposición del pliego, sino de una decisión de diseño de la solución
ofertada.

**Qué NO ahorra la arquitectura propuesta**, y se dice por delante: no ahorra en
integración con SAP ni con la AGE, ni en conformidad ENS, ni en firma electrónica, ni en
accesibilidad, ni en mantenimiento evolutivo, ni en consultoría de procesos. **Toda esa
complejidad es intrínseca y hay que pagarla igual.** A cuatro años y con uso bajo de
plataforma, los rangos de coste de ambas opciones **se solapan**: no puede afirmarse un
ahorro.

**Qué sí cambia.** El coste de licencia por ciudadano pasa de escalar con el uso a ser
cero; el coste de salida pasa de meses a días; la dependencia económica -la única que no
se resuelve con trabajo- desaparece. Con uso medio a diez años, la diferencia estimada va
de 1,2 a 2,7 millones de euros.

**Restricción que se creía decisiva y no lo es.** La conectividad con Red SARA, exigida
por la Plataforma de Intermediación de Datos y por SIR, es **simétrica**: Power Pages se
ejecuta en Azure, también fuera de SARA, y necesita la misma pasarela. Existe la vía
institucional (NubeSARA, con convenios publicados en el BOE) y aplica igual a ambas.

**Riesgo que sí es decisivo y obliga a corregir la propuesta.** El pliego exige
certificación **ENS de nivel alto, ISO 27001, 27017 y 27018 al licitador**, no sólo al
sistema. Una arquitectura portable en manos de un proveedor no certificable es inútil. La
arquitectura corregida (§23) incorpora operación gestionada certificada con guardia 24×7
como componente, no como nota al pie, y su coste está en el modelo económico.

**Respuestas directas.**

| Pregunta | Respuesta |
|---|---|
| ¿Se puede eliminar Power Pages? | **Sí**, igualando su postura de seguridad de fábrica |
| ¿Se puede eliminar el CRM comercial como sistema de registro del expediente? | **Sí**; si se necesitan capacidades de CRM para otra cosa, se evalúan aparte |
| ¿Se puede operar con Laravel y PostgreSQL? | **Sí**, con cinco condiciones no negociables (§25) |
| ¿Qué tercera pieza es imprescindible? | Almacenamiento de objetos. Y una cuarta que no es software: la pasarela SARA |
| ¿Depende la tesis de Laravel? | **No.** Sobrevive íntegra en Java, .NET y Python |

**Lo que falta por saber** está enumerado sin adornos en §24, y ninguna conclusión de este
informe depende de esas incógnitas: modificarían magnitudes, no el sentido del análisis.

---
## 2. Qué problema está resolviendo realmente CASA 47

Antes de juzgar una solución hay que enunciar el problema con independencia de cómo se ha
resuelto. Si el enunciado es correcto, cualquiera puede proponer una solución mejor; si es
incorrecto, toda la comparación es inútil.

### 2.1 El problema, sin tecnología

Un organismo público -la Entidad Estatal de Vivienda, marca operativa CASA 47, que es
SEPES EPE transformada y adscrita al Ministerio de Vivienda y Agenda Urbana- debe:

1. **Publicar** una oferta de vivienda de alquiler asequible, comprensible y accesible.
2. **Recibir** solicitudes de hasta 50.000 ciudadanos, verificando su identidad con
   garantías jurídicas.
3. **Comprobar** que cumplen requisitos que dependen de datos que están en otras
   administraciones: ingresos, titularidad de vivienda, situación tributaria, residencia.
4. **Ordenar** a los solicitantes conforme a un baremo, de forma reproducible y
   justificable ante un recurso.
5. **Adjudicar** viviendas, notificar con efectos jurídicos y formalizar contratos.
6. **Gestionar** la relación arrendaticia durante años: recibos, incidencias, renovaciones.
7. **Rendir cuentas** de todo lo anterior: ante el ciudadano, ante el control interno,
   ante los tribunales y ante la protección de datos.

### 2.2 Qué hace difícil este problema

No es el volumen. Cincuenta mil solicitudes no son un reto técnico en 2026.

Lo difícil es la **conjunción** de tres cosas que rara vez aparecen juntas:

- **Efectos jurídicos.** Cada cambio de estado puede crear o denegar un derecho. Debe
  poder reconstruirse años después, con las reglas vigentes entonces.
- **Datos ajenos.** La elegibilidad depende de información que custodian otros organismos,
  accesible mediante protocolos impuestos y con requisitos de red específicos.
- **Población vulnerable.** Quien solicita vivienda asequible suele tener menos recursos,
  peores dispositivos y peor conectividad. La accesibilidad y el rendimiento no son
  métricas de calidad: son condiciones de acceso al derecho.

### 2.3 Lo que este problema NO es

- **No es un problema de gestión de clientes.** No hay embudo comercial ni oportunidades.
- **No es un problema de escala.** Los volúmenes son modestos.
- **No es un problema de tiempo real.** Los plazos son de días.
- **No es un problema de análisis de datos.** Hay informes, y son sencillos.

Estas cuatro negaciones importan porque las cuatro tecnologías que suelen introducirse en
proyectos así -CRM, arquitectura distribuida, mensajería en tiempo real, plataforma
analítica- responden a problemas que aquí no existen.

### 2.4 Enunciado final

> Construir un sistema de información que sostenga un procedimiento administrativo
> reglado, con verificación de datos frente a terceros, trazabilidad plena, notificación
> con efectos jurídicos, accesibilidad universal y conservación a largo plazo, para
> decenas de miles de ciudadanos y unos ciento cincuenta gestores.

Ese enunciado no contiene la palabra CRM, ni portal, ni plataforma. Es deliberado.

---

## 3. Arquitectura observable actual

Resumen del reconocimiento pasivo documentado íntegramente en
`docs/research/01-arquitectura-observable.md`. Sólo se realizaron peticiones HTTP normales
a recursos públicos, consultas DNS y lectura del certificado. No se hizo fuzzing, ni
envío de formularios, ni prueba de credenciales, ni invocación de los endpoints
descubiertos en el código.

### 3.1 Hechos observados

| Capa | Observación | Evidencia |
|---|---|---|
| DNS | `portal.casa47.es` → CNAME `casa47-prod-portal-ciudadania.powerappsportals.com` | `dig` |
| Hosting | IPs `150.171.109.83` / `2603:1061:14:52::1`, WHOIS Microsoft Corporation | `whois` |
| Perímetro | Azure Front Door + Traffic Manager | cabecera `x-azure-ref` |
| Plataforma | Power Pages | cabecera `x-ms-portal-app`, cookie `Dynamics365PortalAnalytics` |
| Plantilla | `<!-- Default studio template. Please do not modify -->`, atributo `xrm-editable-html` | HTML servido |
| TLS | 1.3, DigiCert/GeoTrust, ciclo de ~6 meses | `openssl` |
| Seguridad | HSTS con precarga, `nosniff`, `X-Frame-Options`, CSP con `frame-ancestors 'none'` | cabeceras |
| Frontend | HTML de 1.627 bytes + bundle `index.js` de **1.333.490 bytes**, montaje en `<div id="root">`, manifiesto PWA | descarga directa |
| Mapas | MapLibre + teselas de **OpenFreeMap** | bundle y CSP |
| Terceros | Microsoft Clarity, Application Insights (**Spain Central**), c.bing.com, SharePoint, Google Fonts, YouTube, Floorfy | CSP |
| Rendimiento | HTTP/2, TTFB ≈ 245 ms, gzip | `curl -w` |
| Higiene | `/robots.txt` → 404; `/sitemap.xml` → **200 con contenido de error**; sin `security.txt` | peticiones |

### 3.2 Inferencias, marcadas como tales

| Inferencia | Confianza | Base |
|---|---|---|
| El frontend es una SPA React empaquetada con Vite | Media | `id="root"`, módulo ESM único, manifiesto PWA |
| Los datos se consumen por la Web API de Dataverse | Alta | rutas `_api/accounts`, `_api/contacts`, `_api/incidents` |
| Parte de la lógica vive en flujos de Power Automate | Alta | ruta `_api/cloudflow` |
| Existen entidades a medida del integrador | Alta | prefijo `ey_dv_cus_*` en el bundle |
| El LCP real en red móvil supera ampliamente 1,5 s | Alta | 1,33 MB bloqueantes sobre TTFB de 245 ms |

### 3.3 Lo que hay que reconocer del sistema actual

La postura de seguridad perimetral observada **es buena**. Cabeceras completas, política
de contenido explícita y restrictiva, gestión automática de certificados. Cualquier
alternativa debe igualarla, y eso cuesta trabajo. Decirlo es parte de hacer un análisis
justo.

### 3.4 El hallazgo que reorienta el análisis

> El portal ciudadano **no está construido con las capacidades declarativas de la
> plataforma**. Es una aplicación JavaScript a medida servida dentro de la plantilla por
> defecto de Power Pages, que actúa como alojamiento, autenticación y proxy de datos.

Consecuencia: la justificación habitual de una plataforma de desarrollo rápido -"no hay
que programar"- **no se corresponde con lo observable en la parte pública**. Se está
asumiendo el coste del desarrollo a medida y, simultáneamente, el modelo de licenciamiento
de la plataforma.

`[Límite del hallazgo]` No hemos visto la zona autenticada. La afirmación se limita a lo
observado y no se extiende a la gestión interna.

---

## 4. Mapa funcional

Reconstruido leyendo el propio bundle público, no adivinando rutas. Detalle completo en
`docs/research/02-mapa-funcional.md`.

### 4.1 Superficie pública verificada

```text
/                                    200   Portada
├── contacto                         200   Formulario de contacto
├── noticias                         200   Listado
│   └── :slug                        200   Detalle
├── listado-convocatorias            200   Listado de convocatorias
│   └── :id                          200   Detalle de convocatoria
├── filtrado-viviendas               200   Buscador con filtros
├── viviendas
│   ├── listado                      200   Catálogo
│   ├── mapa                         200   Vista de mapa (chunk aparte)
│   └── :id                          200   Ficha de vivienda
├── solicitar-vivienda               200   Punto de entrada al trámite
├── solicitud/…                      200   Asistente de solicitud, REQUIERE SESIÓN
└── legal
    ├── aviso-legal                  200
    ├── privacidad                   200
    ├── cookies                      200
    └── accesibilidad                200
```

### 4.2 Hallazgos funcionales

1. **El 100 % del contenido se renderiza con JavaScript.** El HTML servido no contiene ni
   un solo enlace. Sin JavaScript, el portal está vacío. Esto afecta a accesibilidad, a
   indexación y a rendimiento, y es una decisión de implementación, no una necesidad.
2. **Todo el trámite exige sesión.** Sin autenticar, redirige con `?login=1`.
3. **El catálogo tiene filtros, orden y paginación** confirmados en el código: ingresos,
   número de personas, dormitorios, accesibilidad, estado.
4. **No hay sitemap real.** `/sitemap.xml` responde 200 con el contenido de la página de
   error, lo que además significa que el sitio devuelve 200 para recursos inexistentes.

### 4.3 Criterios de elegibilidad publicados

Transcritos del propio portal. Son la base del modelo de dominio de §5:

- Ingresos anuales netos de la unidad de convivencia entre **2 y 7,5 veces el IPREM**.
- Nacionalidad española o residencia legal.
- No ser titular de vivienda en propiedad, salvo excepciones.
- Estar al corriente de obligaciones con la AEAT y la Seguridad Social.
- Reglas específicas para discapacidad y movilidad reducida.

**Observación de diseño:** cuatro de los cinco criterios son verificables mediante consulta
a otras administraciones. Es exactamente el caso de uso de la Plataforma de Intermediación
de Datos, y la razón por la que ese adaptador es el más importante del sistema (§13).

**Observación de protección de datos:** el quinto criterio implica tratar datos de salud.
Confirma lo anticipado en §5.4.

### 4.4 Declaración de accesibilidad

Declara conformidad **parcial** con el RD 1112/2018, con fecha de 20 de julio de 2026.

Dos observaciones técnicas:

1. **No cita el nivel WCAG alcanzado.** El pliego exige WCAG 2.1 (PPT 4.6.8).
2. Las no conformidades están redactadas en condicional genérico -"podría no ser
   accesible"- y no se identifica una auditoría concreta con su alcance y fecha.

Se señala como observación de cumplimiento, no como imputación. Una declaración de
accesibilidad debe permitir a un ciudadano saber qué no funciona y a quién reclamar.
## 5. Modelo conceptual

El modelo no se ha copiado de una plantilla. Se ha derivado de tres fuentes: los
criterios de elegibilidad publicados en el propio portal, el flujo de solicitud
observable, y las entidades personalizadas visibles en el código público
(`ey_dv_cus_*`, que corresponden a unidad de convivencia y solicitud).

### 5.1 El núcleo del dominio, en una frase

> Una **unidad de convivencia** presenta una **solicitud** a una **convocatoria**,
> acredita su situación con **documentos** y **verificaciones**, obtiene una
> **puntuación**, y si resulta **adjudicataria** de una **vivienda** firma un
> **contrato de arrendamiento** que genera **recibos** e **incidencias**.

Todo lo demás es soporte de esa frase. Un modelo que no se pueda resumir así está
sobreconstruido.

### 5.2 Entidades

Se descarta parte de la lista tentativa del encargo y se justifica cada descarte.

**Identidad y personas**

| Entidad | Responsabilidad | Nota |
|---|---|---|
| `Person` | Persona física identificada por documento (NIF/NIE). Datos de contacto. | Separada de la cuenta: una persona puede constar en un expediente sin haberse registrado nunca. |
| `Account` | Credencial de acceso. Vincula a `Person` con el identificador federado de Cl@ve. | No guarda contraseñas si la autenticación es exclusivamente por Cl@ve. Decisión abierta (§24). |
| `StaffUser` | Empleado, gestor externo o proveedor. | Autenticación distinta a la ciudadana. |
| `Role`, `Permission` | Autorización. | |

**Unidad de convivencia**

| Entidad | Responsabilidad |
|---|---|
| `Household` | Unidad de convivencia solicitante. Agrupa a sus miembros y consolida ingresos. |
| `HouseholdMember` | Vínculo persona–unidad, con parentesco, si es titular o no, y circunstancias que afectan a la baremación (discapacidad, movilidad reducida, menores a cargo). |

**Nota crítica de diseño:** la unidad de convivencia **cambia en el tiempo** y una
solicitud debe evaluarse con la composición vigente en el momento de presentarse. Por
tanto la solicitud no referencia la unidad "viva", sino que **congela una instantánea**.
Sin eso, resolver un recurso administrativo dos años después es imposible: no se podría
demostrar con qué datos se resolvió. Esto es un requisito de trazabilidad, no una
optimización.

**Oferta**

| Entidad | Responsabilidad |
|---|---|
| `Development` | Promoción: conjunto de viviendas de una actuación. Ubicación, promotor, estado de obra. |
| `Property` | Vivienda concreta. Superficie, dormitorios, planta, accesibilidad, anejos, renta, estado. |
| `PropertyMedia` | Fotografías, planos, tour virtual. |
| `Call` | Convocatoria. Plazos, ámbito, cupos y baremo aplicable. |
| `CallProperty` | Qué viviendas entran en qué convocatoria. Relación N:M con datos propios. |

`Location` **no** se modela como entidad independiente: municipio, provincia y código
postal son atributos, y la geometría es un par de coordenadas. Una tabla de localizaciones
sólo se justificaría si hubiese que gestionar su ciclo de vida, y no lo hay. Este es un
ejemplo pequeño pero real de la regla de §1.

**Procedimiento**

| Entidad | Responsabilidad |
|---|---|
| `Application` | Solicitud. La entidad central del sistema. Estado, convocatoria, unidad congelada, puntuación, preferencias. |
| `ApplicationPreference` | Viviendas o promociones solicitadas, en orden de preferencia. |
| `Document` | Documento aportado o generado. Metadatos, hash, referencia en almacenamiento, estado de validación y de firma. |
| `Verification` | Resultado de una comprobación concreta (ingresos, titularidad, estar al corriente de pago). Guarda el origen -consulta automática o documento aportado-, la fecha y la evidencia. |
| `Score` | Baremación: puntuación total y su desglose por criterio, con la versión del baremo aplicada. |
| `Award` | Adjudicación de una vivienda a una solicitud. Aceptación, renuncia, plazo. |
| `Lease` | Contrato de arrendamiento. Vigencia, renta, fianza, firmantes. |
| `Charge` / `PaymentRecord` | Recibos emitidos y cobros registrados. |
| `Incident` | Incidencia de mantenimiento o consulta sobre una vivienda o contrato. |

**Transversales**

| Entidad | Responsabilidad |
|---|---|
| `AuditEvent` | Registro inmutable de quién hizo qué, cuándo, sobre qué y desde dónde. Sólo inserción. |
| `OutboxEvent` | Evento pendiente de entregar a un sistema externo (§10.6). |
| `NotificationRecord` | Comunicación emitida y su acuse. Distingue **notificación fehaciente** (con efectos jurídicos y plazos) de **aviso informativo**. Confundirlas es un defecto grave en un procedimiento administrativo. |
| `ExternalCallLog` | Traza de cada llamada a un servicio externo, con petición, respuesta y correlación. Necesario para poder demostrar qué respondió la Administración un día concreto. |

### 5.3 Entidades que se descartan, y por qué

- **`EligibilityCheck` como entidad propia.** Se sustituye por `Verification`, que es más
  general y sirve tanto para elegibilidad como para baremación y comprobaciones
  posteriores. Una entidad menos.
- **`Message` genérica.** El intercambio con el ciudadano son notificaciones y
  comentarios de incidencia, que ya tienen entidad. Una bandeja de mensajería genérica
  sería una funcionalidad no acreditada.
- **`User` única para ciudadanos y gestores.** Se separan deliberadamente: distinto
  ciclo de vida, distinta autenticación, distinto régimen de auditoría y distinto
  tratamiento en materia de protección de datos. Unificarlas es la clase de "elegancia"
  que después obliga a comprobaciones dispersas por todo el código.

### 5.4 Datos personales y su clasificación

Esta clasificación gobierna cifrado, retención y control de acceso, y debe existir antes
del esquema, no después.

| Categoría | Datos | Tratamiento |
|---|---|---|
| Identificativos | Nombre, NIF/NIE, domicilio, contacto | Acceso por rol; auditoría de toda lectura |
| Económicos | Renta, situación tributaria, estar al corriente | Acceso restringido; nunca en registros de log |
| **Categoría especial (art. 9 RGPD)** | Discapacidad -dato de salud-; condición de víctima de violencia de género si el baremo la contempla | Cifrado a nivel de campo; acceso a un rol específico; auditoría reforzada; **motivo obligatorio para acceder** |
| Documentales | Documentación acreditativa | Almacenamiento cifrado, acceso sólo por URL firmada de corta vigencia |

**Este punto no es un trámite.** Si el baremo pondera discapacidad o violencia de género
como es habitual en vivienda protegida-, el sistema trata datos de categoría especial y
la evaluación de impacto en protección de datos es obligatoria. Cualquier arquitectura
que se proponga debe demostrar dónde vive ese dato, quién puede leerlo y cómo se prueba
quién lo leyó. Se recoge como requisito de primer nivel en §14.

### 5.5 Volumetría, para dimensionar de verdad

Con las cifras del encargo `[NO VERIFICADAS en el pliego, §3]`:

| Tabla | Orden de magnitud a 4 años |
|---|---|
| `properties` | 10³ – 10⁴ |
| `applications` | 10⁵ |
| `household_members` | 10⁵ – 10⁶ |
| `documents` | 10⁶ |
| `audit_events` | 10⁷ |
| `charges` | 10⁶ |

**Ninguna de estas cifras es grande para PostgreSQL.** La tabla mayor es la de auditoría,
que es de sólo inserción y se particiona por fecha. No hay en este sistema ningún
problema de volumen de datos. Decirlo con números es más útil que decir "PostgreSQL
escala mucho", que es exactamente el argumento vacío que este informe se prohíbe usar.
## 6. Complejidad intrínseca frente a complejidad accidental

Esta es la sección central del informe. Si la clasificación es correcta, todo lo demás
se sigue; si es incorrecta, el resto no vale.

**Criterio de clasificación, declarado antes de aplicarlo:** una dificultad es
**intrínseca** si seguiría existiendo con cualquier tecnología, porque procede de la
norma, del procedimiento, del ecosistema de interoperabilidad o de la naturaleza del
dato. Es **accidental** si sólo existe como consecuencia de una decisión técnica
reversible.

Aplicar este criterio con honestidad implica reconocer que **la mayor parte de la
dificultad de este proyecto es intrínseca**. Quien sostenga lo contrario no ha mirado el
problema.

### 6.1 Complejidad intrínseca

| Dificultad | Por qué es intrínseca | ¿Barata de resolver? |
|---|---|---|
| Criterios de elegibilidad (2–7,5 × IPREM, titularidad, estar al corriente) | Los fija la norma | No |
| Baremación y desempate, con versionado del baremo | Afecta a derechos; debe poder reconstruirse años después | No |
| Trazabilidad para resolver recursos administrativos | Obligación jurídica | No |
| Notificación fehaciente con efectos de plazo | Régimen jurídico de la notificación | No |
| Firma electrónica y su validación | Marco eIDAS y política de firma | No |
| Consulta de datos a la AGE por SCSP | Protocolo impuesto, SOAP con XML firmado | No |
| Conectividad a Red SARA para PID y SIR | Requisito de red del ecosistema | No |
| Conformidad ENS | RD 311/2022 | No |
| RGPD con datos de categoría especial | Naturaleza del dato | No |
| Accesibilidad EN 301 549 / WCAG 2.1 AA | RD 1112/2018 | No |
| Archivo y conservación del expediente electrónico | ENI y normas técnicas de interoperabilidad | No |
| Picos de carga en apertura y cierre de convocatoria | Naturaleza del procedimiento | No |
| Concurrencia en la adjudicación (una vivienda, un adjudicatario) | Naturaleza del problema | No |
| Formación y acompañamiento a gestores | Cambio organizativo | No |
| Integración con el sistema económico | Existe y hay que hablar con él | No |

**Ninguna de estas dificultades desaparece cambiando de arquitectura.** Todas hay que
pagarlas. Esto es lo que explica legítimamente que el contrato cueste lo que cuesta.

### 6.2 Complejidad accidental

Se marca como accidental sólo lo que puede acreditarse como consecuencia de una decisión
técnica, no de la norma.

| Dificultad | Origen | Consecuencia | Evidencia |
|---|---|---|---|
| Modelo de datos en Dataverse, con esquema propietario | Elección de plataforma | El esquema no es portable ni consultable con herramientas estándar | Entidades `ey_dv_cus_*` en el código público |
| Capa de API intermedia entre la interfaz y los datos | Arquitectura de Power Pages | Cada dato atraviesa una capa de permisos y proyección adicional | Rutas `_api/...` en el bundle |
| Lógica repartida entre código y flujos de Power Automate | Modelo de la plataforma | La lógica de negocio vive en dos sitios; probar y versionar es más difícil | `_api/cloudflow` en el bundle |
| SPA de 1,33 MB para contenido esencialmente estático | Decisión de implementación | Penaliza rendimiento y accesibilidad; obliga a mantener accesibilidad de SPA | Bundle observado (§3) |
| Licencia por usuario ciudadano | Modelo de negocio de la plataforma | El coste crece con el uso del servicio público | Guía de licenciamiento (§04) |
| Posible duplicación entre modelo del CRM y modelo del expediente | Interposición de un CRM | Sincronización y conciliación permanentes | `[INFERENCIA]`, no verificable desde fuera |
| Dependencia de perfiles certificados en la plataforma | Ecosistema del producto | Reduce el mercado de mantenedores | Criterio de adjudicación: certificación de fabricante CRM, 2 % |
| Coste y dificultad de salida | Formato propietario del modelo y de los flujos | §19 | |
| Analítica con grabación de sesión sobre datos personales | Decisión de implementación | Superficie adicional de tratamiento a justificar | Clarity permitido en la CSP |

### 6.3 El caso que merece examen aparte

El hallazgo de §3 obliga a una observación que no es menor.

El portal ciudadano **no está construido con las capacidades declarativas de Power
Pages**: es una aplicación JavaScript a medida, de 1,33 MB, servida dentro de la
plantilla por defecto de la plataforma. El comentario literal
`<!-- Default studio template. Please do not modify -->` sigue en el HTML.

Esto tiene una consecuencia analítica precisa:

> Si el portal se ha programado a medida de todas formas, entonces **la justificación
> habitual de una plataforma low-code ("no hay que programar") no aplica a este caso**.
> Se está pagando el modelo de licenciamiento de una plataforma de desarrollo rápido
> mientras se asume el coste de desarrollo a medida.

Formulado como pregunta contrastable, que es como debe formularse:

> **¿Qué funciones concretas de Power Pages usa hoy el portal ciudadano, que no sean
> alojamiento, autenticación federada y exposición de datos de Dataverse?**

Es una pregunta que el organismo puede responder internamente en poco tiempo y que
determina buena parte de la discusión. Se traslada a §24.

`[Precisión necesaria]` No podemos afirmar que la zona autenticada no use capacidades de
la plataforma: no la hemos visto. La afirmación se limita a lo observado.

### 6.4 Proporción

Sin ánimo de falsa precisión, y como orientación:

- Complejidad **intrínseca**: la mayor parte del esfuerzo del proyecto. Norma,
  procedimiento, integraciones, seguridad, accesibilidad, cambio organizativo.
- Complejidad **accidental**: una fracción menor del esfuerzo de construcción, pero
  **la parte dominante del coste recurrente y de la dependencia a largo plazo**.

Esa asimetría es la tesis del informe. La arquitectura no cambia mucho lo que cuesta
construir. Cambia lo que cuesta **tener** y lo que cuesta **dejar**.
## 7. Qué aporta realmente Power Pages y un CRM comercial

Empezar por aquí no es cortesía. Un análisis que no sepa enunciar los puntos fuertes de
la opción que critica no es un análisis: es una acusación con formato técnico.

### 7.1 Capacidades reales, sin rebajarlas

| Capacidad | Valor real |
|---|---|
| Autenticación federada de ciudadanos | Integración con proveedores de identidad ya resuelta, con gestión de sesión, recuperación y registro |
| Autorización sobre datos | Permisos por tabla, por registro y por relación, declarativos, sin escribir código |
| CDN, WAF y TLS gestionados | La postura de seguridad observada en §3 es buena y viene de fábrica |
| Escalado y disponibilidad | Operados por el fabricante; el organismo no gestiona servidores |
| Modelo de datos común | Dataverse trae entidades y relaciones ya modeladas, con auditoría y control de acceso incorporados |
| Automatización visual | Power Automate permite construir flujos sin desarrollo |
| Integración con el resto del ecosistema | Continuidad con Microsoft 365, SharePoint, Teams, Power BI |
| Soporte con responsabilidad contractual | Un fabricante grande responde de la plataforma |
| Certificaciones y cumplimiento | Catálogo amplio de certificaciones y ubicación de datos declarada |
| Mercado de implantadores | Existe una oferta amplia de proveedores capaces de trabajar sobre el producto |
| Continuidad organizativa | La plataforma no depende del equipo que la implantó |

**Tres de estas capacidades merecen reconocimiento explícito**, porque son las que un
proyecto a medida suele subestimar:

1. **La postura de seguridad de fábrica.** Las cabeceras, la CSP y la gestión automática
   de certificados observadas en §3 están bien. Un equipo pequeño puede alcanzarlas, pero
   tiene que *hacerlo y mantenerlo*, y eso tiene coste.
2. **La continuidad frente a la rotación de personas.** Una plataforma reduce el riesgo
   de que el sistema quede huérfano cuando se marchan dos desarrolladores. Es un riesgo
   real de la propuesta alternativa y se aborda de frente en §22.
3. **La responsabilidad contractual.** Cuando la plataforma falla, hay a quién reclamar.
   En un sistema propio, la responsabilidad es del organismo. Esto no es un detalle
   jurídico menor para un gestor público.

**Ahora bien, ese tercer punto exige una matización que sale del propio pliego.** El
nivel de servicio realmente contratado es de **lunes a viernes de 9:00 a 18:00**, con
escalado a 24 horas sólo ante fallos que inutilicen al menos el 50 % de las
funcionalidades. **No hay compromiso de disponibilidad porcentual ni penalización
económica ligada a la disponibilidad**; las únicas penalidades son las genéricas de la
LCSP por demora y por impago a subcontratistas.

Es decir: la ventaja de "soporte enterprise" es real respecto de la plataforma, pero el
servicio contratado alrededor de ella es de horario de oficina. Cualquier comparación debe
medirse contra ese nivel de servicio, no contra uno imaginado. Un equipo propio con
guardia definida podría igualarlo o superarlo, y su coste está en §18.

### 7.2 Qué se paga sin usarse, en este caso concreto

La pregunta correcta no es si la plataforma es buena. Es **si este problema la necesita**.

| Capacidad | ¿La necesita este sistema? | Observación |
|---|---|---|
| Gestión comercial de oportunidades | No | No hay actividad comercial |
| Campañas de marketing | No | |
| Telefonía y centro de contacto integrados | No acreditado | Si se necesitase, se valoraría aparte |
| Personalización visual por usuario de negocio | Marginal | Los cuadros de mando de gestión son estables |
| Modelo de datos genérico de CRM | **Contraproducente** | Un expediente administrativo no es una oportunidad de venta; forzar el encaje añade trabajo |
| Construcción de páginas sin código | **No se está usando** | El portal es una aplicación a medida (§3, §6.3) |
| Escalado elástico masivo | No | El pico es de miles de usuarios, no de millones |
| Ecosistema de conectores | Parcial | Los conectores relevantes aquí (SCSP, Cl@ve, DEHú) **no** existen de fábrica; hay que construirlos igual |

La última fila es importante y suele pasarse por alto: **las integraciones que definen la
dificultad de este proyecto no vienen resueltas por el catálogo de conectores de la
plataforma.** Hay que construirlas en cualquier caso.

---

## 8. Qué dependencia introduce

Se separa la dependencia en cuatro capas, porque tienen coste de salida muy distinto.

| Capa | Elemento | Portabilidad | Coste de salida |
|---|---|---|---|
| Datos | Esquema propietario de Dataverse con entidades a medida | Los datos se exportan; el **esquema y su semántica** hay que reconstruirlos | Medio |
| Lógica | Flujos de Power Automate | **No portables.** Hay que reimplementarlos leyendo lo que hacen | **Alto** |
| Lógica | Reglas de negocio y complementos de la plataforma | No portables | Alto |
| Presentación | Aplicación JavaScript a medida | **Portable**, es código propio | Bajo |
| Presentación | Configuración de páginas y permisos del portal | No portable | Medio |
| Identidad | Configuración de proveedores de identidad del portal | Reconfigurable contra un adaptador propio | Bajo |
| Operación | Conocimiento del equipo, especializado en el producto | No transferible a otro stack | Medio |
| Económico | Tarifa por usuario fijada unilateralmente | Ninguna | **El más alto de todos** |

**La dependencia más seria no es técnica: es económica.** Las tres primeras filas se
resuelven con trabajo acotado y presupuestable. La última no se resuelve con trabajo:
sólo se resuelve marchándose. Y mientras no se pueda uno marchar barato, la tarifa la fija
la otra parte.

**Un dato que ilustra el riesgo, y que no es una opinión sobre el fabricante:** el
producto ha cambiado de nombre y de modelo de licenciamiento tres veces en siete años
Dynamics 365 Portals, Power Apps Portals, Power Pages- con un cambio de modelo de precio
en 2022 que pasó de inicios de sesión a capacidad por usuarios únicos mensuales. Un
sistema con horizonte de diez años debe contar con que ese modelo volverá a cambiar. No
es una crítica: es planificación.

**Y la simetría obligada:** el riesgo de discontinuidad no es exclusivo del software
propietario. En §11.6 y §22 se documenta el caso contrario. Un informe que sólo señalase
el riesgo de un lado sería propaganda.
## 9. Arquitectura mínima candidata

### 9.1 Intento de simplificarla aún más

El punto de partida propuesto era aplicación + base de datos + almacenamiento de objetos
detrás de CDN/WAF. Antes de aceptarlo, se intenta destruirlo por abajo: **¿se puede
quitar algo más?**

| Se intenta quitar | ¿Es posible? | Motivo |
|---|---|---|
| El almacenamiento de objetos, guardando documentos en la base | Técnicamente sí | **Rechazado**: infla copias, alarga restauración, encarece réplica. Es una simplificación falsa: reduce cajas del diagrama y empeora la operación. |
| El CDN/WAF, exponiendo la aplicación directamente | Técnicamente sí | **Rechazado**: la protección perimetral no es opcional en un servicio público con datos personales. Es una medida ENS. |
| La segunda instancia de aplicación | Sí | **Rechazado**: dejaría el despliegue con corte de servicio y sin tolerancia a fallo de instancia. |
| La alta disponibilidad de la base de datos | Sí | **Depende del objetivo de servicio.** Se conserva, pero se documenta que es la partida de infraestructura más cara y que es una decisión de nivel de servicio, no de arquitectura. |
| El entorno de preproducción | Sí | **Rechazado**: el ENS exige separación de entornos. |
| Los trabajadores de cola como proceso separado | Sí, ejecutándolos en las mismas instancias | **Aceptado como opción**: en volúmenes bajos pueden convivir. Se separan por aislamiento de fallos, no por capacidad. Es una decisión revisable. |

**Resultado: la arquitectura propuesta ya está en su mínimo defendible.** Sólo hay una
pieza que podría eliminarse sin daño funcional (los trabajadores separados) y su
separación se justifica por aislamiento, no por rendimiento.

Este ejercicio importa: demuestra que la propuesta ha sido sometida a la misma regla que
se aplica a la arquitectura ajena.

### 9.2 Diagrama de contenedores

```text
   Ciudadano            Gestor interno / externo        Proveedor
       │                          │                         │
       └──────────────┬───────────┴─────────────┬───────────┘
                      ▼                         ▼
              ┌───────────────────────────────────────┐
              │      CDN + WAF + TLS + rate limit     │
              └───────────────────┬───────────────────┘
                                  ▼
                        ┌───────────────────┐
                        │  Balanceador      │
                        └─────────┬─────────┘
                    ┌─────────────┴─────────────┐
                    ▼                           ▼
            ┌───────────────┐           ┌───────────────┐
            │  App 01       │           │  App 02       │
            │  (Laravel)    │           │  (Laravel)    │
            │  Web/API/     │           │               │
            │  Backoffice   │           │               │
            └───────┬───────┘           └───────┬───────┘
                    └─────────────┬─────────────┘
                                  │
            ┌─────────────────────┼─────────────────────┐
            ▼                     ▼                     ▼
    ┌───────────────┐   ┌───────────────────┐   ┌───────────────┐
    │ PostgreSQL    │   │ Trabajadores      │   │ Almacenamiento│
    │ primaria + HA │◀──│ (colas + outbox   │   │ de objetos    │
    │               │   │  + planificador)  │   │ (S3)          │
    └───────────────┘   └─────────┬─────────┘   └───────────────┘
                                  │
                                  ▼
                    ┌─────────────────────────────┐
                    │  Adaptadores de integración │
                    └──────────────┬──────────────┘
                                   │
        ┌──────────────┬───────────┼───────────┬──────────────┐
        ▼              ▼           ▼           ▼              ▼
     Cl@ve          Firma       Notifica    Catastro    Sistema económico
   (Internet)     (FIRe)         /DEHú       (REST)         (SAP)
                                   │
                                   ▼
                        ┌────────────────────────┐
                        │  Pasarela Red SARA     │
                        │  (NubeSARA / PdP)      │
                        └───────────┬────────────┘
                                    ▼
                              PID / SCSP / SIR
```

**La caja de la pasarela SARA es la única concesión estructural** que impone el
ecosistema, y como se demostró en §13, **la impone igual a la arquitectura actual**.

### 9.3 Diagrama de contexto

```text
                    ┌──────────────────────────────┐
   Ciudadano ──────▶│                              │◀────── Gestor interno
                    │   Sistema de gestión del     │
   Cl@ve   ◀───────▶│   parque de alquiler         │◀────── Gestor externo
                    │   asequible                  │
   AGE     ◀───────▶│   (CASA 47)                  │◀────── Proveedor
   (PID, DEHú,      │                              │
    SIR, FACe)      └───────────┬──────────────────┘
                                │
                                ▼
                    Sistema económico / Catastro / Registro
```

### 9.4 Componentes internos del monolito

```text
┌──────────────────────────────────────────────────────────────┐
│                     Aplicación Laravel                       │
│                                                              │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌───────────┐  │
│  │  Identity  │ │  Housing   │ │   Calls    │ │Applications│ │
│  └────────────┘ └────────────┘ └────────────┘ └───────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌───────────┐  │
│  │Eligibility │ │  Scoring   │ │   Awards   │ │  Leases   │  │
│  └────────────┘ └────────────┘ └────────────┘ └───────────┘  │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐ ┌───────────┐  │
│  │ Documents  │ │Notifications│ │ Incidents │ │   Audit   │  │
│  └────────────┘ └────────────┘ └────────────┘ └───────────┘  │
│                                                              │
│  ─────────────────── Capa de integración ─────────────────── │
│  IdentityGateway  DataVerificationGateway  SignatureGateway│
│  NotificationGateway  RegistryGateway  FinancialGateway    │
│  PropertyRegistryGateway  DocumentStorageGateway            │
└──────────────────────────────────────────────────────────────┘
```

### 9.5 Estructura del monolito modular

```text
app/
  Domains/
    Identity/        Person, Account, StaffUser, roles y políticas
    Housing/         Development, Property, media y catálogo
    Calls/           Convocatorias, plazos, cupos, baremos
    Applications/    Solicitud, unidad congelada, máquina de estados
    Eligibility/     Reglas de elegibilidad, versionadas
    Scoring/         Baremación y desempate, versionados
    Awards/          Adjudicación, aceptación, renuncia
    Leases/          Contratos, recibos, cobros
    Documents/       Metadatos, validación, firma
    Incidents/       Incidencias y su ciclo
    Notifications/   Comunicaciones y acuses
    Audit/           Eventos inmutables y consulta
  Integrations/
    Contracts/       Interfaces de puerto, sin dependencias externas
    Clave/           Adaptador SAML 2.0
    Scsp/            Adaptador SOAP con XML firmado
    Signature/       Adaptador de firma y validación
    Dehu/            Adaptador de notificación fehaciente
    Catastro/        Adaptador de consulta catastral
    Financial/       Adaptador del sistema económico
    Storage/         Adaptador de almacenamiento de objetos
    Testing/         Dobles de prueba de todos los anteriores
  Support/           Utilidades transversales, sin lógica de negocio
```

**Cómo se evita que esto se convierta en ceremonia.** El riesgo real del "monolito
modular" es que degenere en catorce capas para guardar una vivienda. Reglas explícitas:

1. **Un módulo es una carpeta con una fachada pública, no una arquitectura hexagonal
   completa.** Dentro de cada módulo se usa Eloquent con normalidad. No hay repositorios
   por encima del ORM: eso duplica una abstracción que el ORM ya proporciona.
2. **La inversión de dependencias se aplica sólo en la frontera de integración**, que es
   donde hay un motivo real: los proveedores externos cambian.
3. **Los módulos se comunican por eventos de dominio o por la fachada del otro módulo.**
   Nunca alcanzando directamente el modelo interno ajeno.
4. **La frontera se verifica automáticamente.** Una prueba de arquitectura falla la
   integración continua si un módulo importa una clase interna de otro. Sin esa prueba,
   la modularidad es una intención, no un hecho, y en cinco años no quedará nada de ella.
5. **Ningún módulo antes de que exista su segundo caso de uso.** La estructura de arriba
   es el destino, no el andamio del primer día.
## 10. Justificación componente por componente

Regla aplicada en todo el capítulo: **ningún componente entra hasta que exista un
problema concreto, medible y documentado que resuelva mejor que las piezas ya
presentes.** Cada ficha responde: problema, opciones, decisión, coste, dependencia
que genera, alternativa si la decisión resulta equivocada, y la señal objetiva que nos
obligaría a cambiar de opinión.

Esa última línea (*trigger de revisión*) es la que convierte esto en ingeniería y no en
preferencia. Una arquitectura que no declara qué evidencia la refutaría no es defendible.

---

### 10.1 Aplicación: monolito modular en Laravel

**Problema.** Servir un portal ciudadano público, un área autenticada transaccional, un
backoffice de gestión, trabajos en segundo plano y adaptadores de integración, con un
equipo pequeño y un horizonte de mantenimiento de diez años.

**Opciones consideradas.**

1. Monolito modular (una unidad desplegable, fronteras internas explícitas).
2. Microservicios por dominio.
3. Plataforma low-code + extensiones a medida (situación actual).
4. Backend "serverless" por funciones.

**Decisión: monolito modular.**

**Por qué no microservicios, en concreto para este caso.** Los microservicios compran
independencia de despliegue y de escalado por equipo, y se pagan con consistencia
distribuida, observabilidad distribuida y coste operativo permanente. Aquí:

- El sistema tiene **un solo dueño funcional** y un equipo del orden de 3-6 personas.
  La independencia de despliegue entre equipos no tiene a quién beneficiar.
- El proceso central -una solicitud que cambia de estado, con documentos, verificaciones
  y auditoría- es **fuertemente transaccional**. Es exactamente el caso en el que una
  transacción ACID local vale más que cualquier saga distribuida.
- El perfil de carga es **estacional y previsible** (aperturas y cierres de convocatoria),
  no un mosaico de subsistemas con perfiles divergentes que justifique escalar por partes.

Un microservicio aquí no resolvería un problema: crearía uno.

**Coste.** Un único artefacto que hay que desplegar entero. Riesgo de que las fronteras
internas se erosionen si no se vigilan (mitigación en §38: fronteras verificadas por test,
no por buena voluntad).

**Dependencia generada.** Framework Laravel y ecosistema PHP. Se analiza en §11 y se
somete a red team en §22.

**Alternativa si falla.** Un monolito modular con fronteras respetadas es precisamente
el punto de partida más barato para extraer un servicio el día que un módulo lo
justifique. Lo contrario (recomponer microservicios en un monolito) es mucho más caro.

**Trigger de revisión.** Que un módulo concreto necesite un perfil de escalado o una
ventana de despliegue incompatible con el resto, medido, no supuesto.

---

### 10.2 Interfaz: HTML renderizado en servidor

**Problema.** Entregar la interfaz pública y la transaccional cumpliendo WCAG 2.1 AA
y EN 301 549, con buen rendimiento en red móvil y en equipos modestos -el perfil real
de quien solicita vivienda asequible.

**Opciones.** (a) HTML renderizado en servidor con JavaScript puntual. (b) SPA que
consume una API. (c) Framework híbrido con hidratación (Inertia, Livewire, Next.js).

**Decisión: HTML renderizado en servidor (Blade), con islas de JavaScript sólo donde
haya un requisito que lo exija.**

Se admiten islas para: mapa del catálogo, filtros con actualización sin recarga,
autocompletado de municipio/vía, subida de documentos con progreso y validación previa,
y formularios largos con guardado de borrador.

**Justificación específica, no estilística.**

- **Accesibilidad.** El HTML servido funciona con lector de pantalla, con teclado y con
  JavaScript degradado *por defecto*; en una SPA la accesibilidad hay que construirla
  y sostenerla (gestión de foco entre vistas, anuncio de cambios de ruta, estados de
  carga). Para un servicio público con obligación legal de accesibilidad y revisión
  periódica, el coste de mantener esa corrección durante diez años es una partida real.
- **Evidencia del propio caso.** La observación de §3 muestra que el portal actual
  entrega **1,33 MB de JavaScript antes de pintar contenido**. Eso es exactamente el
  coste que esta decisión evita.
- **Riesgo.** El principal contraargumento honesto es que ciertos formularios
  administrativos largos, con dependencias condicionales entre campos y validación de
  unidad de convivencia, son incómodos con recargas completas. Se responde con islas
  acotadas, no convirtiendo la aplicación entera en SPA.

**Coste.** Menos interactividad "de aplicación". Hay que ser disciplinado para que las
islas no crezcan hasta convertirse en una SPA de facto.

**Trigger de revisión.** Que un flujo concreto -previsiblemente el formulario de
solicitud- resulte medible y repetidamente peor con recargas: entonces se convierte
*ese* flujo, no la aplicación.

---

### 10.3 Persistencia: PostgreSQL como datastore único

**Problema.** Almacenar el modelo administrativo con garantías transaccionales,
trazabilidad y capacidad de consulta, y poder demostrar que los datos son portables.

**Decisión: una sola PostgreSQL** que asume además sesiones, colas, cerrojos, búsqueda
textual, outbox de eventos y auditoría, mientras las cifras lo permitan (§14–§17).

**Por qué una sola.** Cada almacén adicional multiplica: copias de seguridad que
restaurar de forma coherente, un punto de fallo, un modelo de consistencia, un objeto
de auditoría ENS y una competencia que el equipo debe mantener durante diez años. Y sobre
todo: introduce el problema de **consistencia entre almacenes**, que es la fuente
habitual de incidencias en este tipo de sistemas.

**Coste.** Un componente crítico que hay que operar bien: alta disponibilidad,
recuperación a un punto en el tiempo, pruebas de restauración periódicas. No es
gratis y no debe presentarse como si lo fuera.

**Trigger de revisión.** Cifras concretas, recogidas en §14–§17, no intuiciones.

---

### 10.4 Documentos: almacenamiento de objetos compatible con S3

**Problema.** Custodiar documentación acreditativa (identidad, ingresos, contratos) con
integridad verificable, control de acceso, coste razonable y portabilidad.

**Decisión: almacenamiento de objetos con API compatible con S3.** En la base de datos
sólo van los metadatos y el hash; el contenido nunca.

**Por qué no en PostgreSQL.** Los binarios en base de datos inflan las copias de
seguridad, alargan el tiempo de restauración y encarecen la réplica, sin aportar nada:
la integridad se garantiza igual con un `checksum` almacenado junto al metadato.

**Dependencia generada.** Ninguna a un fabricante concreto: la API de S3 es hoy el
estándar de hecho, implementado por AWS, Azure (vía capa de compatibilidad), MinIO,
OVHcloud, Scaleway y proveedores nacionales. El cambio se resuelve reconfigurando el
adaptador de disco de la aplicación.

**Punto que exige honestidad.** "Compatible con S3" no siempre significa idéntico. Las
diferencias reales aparecen en URLs firmadas, políticas de ciclo de vida, cifrado con
clave gestionada por el cliente y bloqueo de objetos. La aplicación debe usar el
subconjunto común y probarse contra al menos dos implementaciones en integración
continua (MinIO en local, el proveedor real en preproducción) para que la portabilidad
sea un hecho verificado y no una promesa del diagrama.

---

### 10.5 Trabajos asíncronos: colas en PostgreSQL

**Problema.** Enviar notificaciones, llamar a servicios externos lentos, generar
documentos y procesar reglas de elegibilidad sin bloquear la petición del ciudadano.

**Opciones.** Cola en PostgreSQL (`SELECT … FOR UPDATE SKIP LOCKED`); Redis; un broker
dedicado.

**Decisión: PostgreSQL, inicialmente.**

La razón principal no es ahorrar un servidor: es que **encolar un trabajo dentro de la
misma transacción que modifica el estado del expediente elimina toda una familia de
errores** -el trabajo encolado para un cambio que luego revirtió, o el cambio confirmado
cuyo trabajo se perdió-. Con un broker externo eso exige patrones adicionales. Con
PostgreSQL es una consecuencia de la transacción.

**Coste.** Las colas en base de datos generan escrituras y trabajo de limpieza
(*vacuum*). Es un consumo real que hay que vigilar.

**Trigger de revisión, explícito.** Se introduce Redis o un broker cuando se mida, en
carga representativa: latencia de toma del trabajo sostenidamente por encima del
objetivo de servicio, o contención demostrada en la tabla de trabajos, o una necesidad
de caché de alta frecuencia que la base no absorba. Antes no.

---

### 10.6 Publicación de eventos: outbox transaccional

**Problema.** Cuando una solicitud se registra hay que avisar a sistemas externos
(notificación fehaciente, sistema económico, correo). Esos sistemas fallan, tardan y a
veces duplican. No puede ocurrir que el expediente conste registrado y la notificación
no se llegue a emitir jamás, ni al revés.

**Decisión: patrón de bandeja de salida transaccional (*transactional outbox*) sobre
PostgreSQL.** En una única transacción se escribe el cambio de estado y el evento; un
proceso trabajador lo entrega después a cada adaptador, con reintentos, retroceso
exponencial, clave de idempotencia y bandeja de fallos permanentes.

**Por qué no un bus de eventos dedicado.** Un bus resuelve un problema distinto:
distribución de alto volumen a muchos consumidores desacoplados. Aquí hay pocos
consumidores, conocidos, y el volumen es de miles de eventos, no de millones por
segundo. Lo que sí hace falta -atomicidad entre el cambio de estado y la intención de
notificar- lo da la transacción, no el bus.

**Coste.** Hay que implementar bien reintentos, idempotencia y bandeja de fallos, y
hay que **vigilarla**: una bandeja de salida sin alerta de atasco es una avería
silenciosa. Es la pieza que más disciplina operativa exige de toda la arquitectura.

**Trigger de revisión.** Que aparezca un consumidor externo real que exija reproducir
el histórico de eventos o suscripción continua. Hoy no consta ninguno.

---

### 10.7 Procedimiento administrativo: máquina de estados en el dominio

**Problema.** Modelar procedimientos reglados, con actores, plazos, requisitos y
necesidad de justificar cada cambio ante un recurso administrativo.

**Decisión: máquina de estados explícita en el dominio**, con transiciones nombradas que
validan permiso y estado previo, registran auditoría inmutable y emiten eventos, todo
dentro de una transacción.

**Por qué no un motor de procesos.** Un BPM aporta valor cuando el flujo lo modifican
personas de negocio sin desarrollo y cambia con frecuencia. Un procedimiento
administrativo cambia cuando cambia la norma (no semanalmente) y su modificación exige
igualmente análisis, pruebas y despliegue controlado, porque afecta a derechos. El motor
añadiría un segundo lugar donde vive la lógica y un segundo estado que mantener
coherente con el de la base.

**Coste.** No hay editor gráfico del flujo. Se compensa generando el diagrama a partir
del código, para que la documentación no pueda divergir de la implementación.

**Trigger de revisión.** Que se acredite necesidad real de que perfiles no técnicos
modifiquen el flujo en producción sin ciclo de despliegue.

---

### 10.8 Backoffice: la misma aplicación

**Decisión: los gestores trabajan sobre la misma aplicación, el mismo modelo y la misma
base**, bajo autorización por rol y con auditoría reforzada.

**Por qué.** La alternativa (un CRM separado) obliga a sincronizar dos modelos del mismo
expediente. Esa sincronización es, en sistemas de este tipo, la principal fuente de
divergencias y de trabajo de conciliación. Suprimirla no es un ahorro de licencias:
es la eliminación de una clase entera de defectos.

**Qué se pierde, dicho con honestidad.** Un CRM comercial trae de serie capacidades
maduras: gestión de campañas, telefonía integrada, cuadros de mando configurables por el
usuario, gestión comercial de oportunidades. Si el organismo necesita esas capacidades
como tales, hay que decirlo y valorarlo. Lo que se sostiene aquí es que **gestionar
expedientes reglados no es gestionar relaciones con clientes**, y que usar un CRM para
lo primero es pagar por un modelo que no encaja.

**Trigger de revisión.** Que aparezca una necesidad genuina de CRM -captación, campañas,
centro de llamadas- con volumen que lo justifique.

---

### 10.9 Integraciones: puertos y adaptadores

**Decisión.** El dominio depende de interfaces propias -identidad, notificación,
documento, económico, firma, registro, consulta de datos- y nunca de un proveedor
concreto. Cada proveedor real es un adaptador sustituible.

**Por qué importa aquí más que en otros proyectos.** Los servicios de la Administración
cambian: cambian de protocolo, se renombran, se sustituyen. Un sistema que ha de vivir
diez años necesita que ese cambio sea sustituir una clase y sus pruebas, no reescribir
el dominio.

**Coste.** Una capa de indirección más. Se acepta sólo en la frontera de integración,
no como estilo general. En §38 se limita explícitamente para evitar ceremonia.
## 11. Laravel frente a las alternativas

La hipótesis de partida era "Laravel encaja mejor". El encargo era intentar refutarla.
A continuación se hace el intento en serio, empezando por el rival más fuerte.

### 11.1 El rival correcto no es Next.js

Conviene decirlo de entrada, porque afecta a la calidad del argumento: **Next.js no es el
competidor natural de Laravel en este proyecto.** Next.js es, sobre todo, un framework de
interfaz con capacidades de servidor. Laravel es un framework de aplicación completo.
Compararlos requiere ser preciso sobre qué falta en cada lado.

Lo que este sistema necesita, mirando la lista de §5 y §8, es: autenticación con
federación, autorización por rol y por política sobre el propio expediente, validación
de formularios administrativos complejos, un ORM con migraciones versionadas,
transacciones, colas con reintentos, un planificador de tareas, generación y envío de
correo y notificaciones, gestión de ficheros con múltiples backends, internacionalización,
pruebas, y un backoffice.

Con Laravel, todo eso viene en la caja, mantenido por el mismo equipo, con la misma
cadencia de versiones y una única superficie de actualización.

Con Next.js hay que componerlo: Auth.js o similar para identidad, una librería de
autorización, Zod para validación, Prisma o Drizzle para ORM y migraciones, BullMQ -que
exige Redis, reintroduciendo el componente que §10.5 justifica evitar- o un servicio
externo para colas, un cron externo o funciones programadas del proveedor, Resend o
Nodemailer para correo, y un backoffice a medida o de pago.

**El resultado no es sólo "más piezas". Es más superficie de actualización y más puntos
de abandono independientes.** En un horizonte de diez años y con un equipo pequeño, ese
es un coste recurrente concreto, no una objeción estética. Cada dependencia tiene su
propio calendario de versiones mayores, su propio ritmo de parches de seguridad y su
propia probabilidad de quedar sin mantenimiento.

### 11.2 Dónde Next.js sería objetivamente mejor

Para ser justos, hay escenarios en los que ganaría:

- Si la interfaz fuese el problema difícil: una aplicación muy interactiva, con estado
  cliente rico y actualizaciones en tiempo real. **No es el caso**: son formularios,
  listados y cambios de estado.
- Si se necesitase generación estática masiva en el borde para un catálogo enorme de
  páginas públicas con tráfico global. **No es el caso**: el tráfico es nacional y el
  catálogo es pequeño.
- Si el equipo existente fuese de TypeScript y no de PHP. Esto sí es un argumento real y
  depende del organismo, no de la tecnología. Se recoge en las decisiones abiertas (§24).

### 11.3 Comparación punto por punto

| Necesidad | Laravel | Next.js | Ventaja |
|---|---|---|---|
| Render en servidor | Blade, nativo | Componentes de servidor, nativo | Empate |
| Formularios y validación | Validación integrada, servidor primero | Acciones de servidor + librería externa | Laravel |
| Autenticación | Integrada, con federación SAML/OIDC vía paquetes maduros | Auth.js u otra externa | Laravel |
| Autorización por rol y por recurso | *Policies* y *Gates* de serie | A construir | Laravel |
| ORM y migraciones | Eloquent + migraciones versionadas | Prisma/Drizzle, externos | Laravel |
| Transacciones | De serie, idiomáticas | Según ORM | Laravel |
| Colas y reintentos | De serie, con soporte de PostgreSQL | BullMQ + Redis, o servicio externo | **Laravel, y es decisivo** |
| Planificador | De serie | Externo | Laravel |
| Correo y notificaciones | De serie, multicanal | Externo | Laravel |
| Almacenamiento de ficheros | Abstracción de disco con múltiples backends | Externo | Laravel |
| Backoffice | Ecosistema maduro sobre el mismo modelo | A medida | Laravel |
| Pruebas | Integradas, con pruebas de aplicación completas | Vitest/Playwright, ensamblado | Laravel |
| Integraciones SOAP | Extensión SOAP de PHP, madura | Débil; SOAP en Node es terreno incómodo | **Laravel, y es relevante** (§13) |
| Rendimiento de interfaz | Bueno con HTML servido | Excelente si se necesita interactividad | Next.js si hiciera falta |
| Contratación de perfiles | Amplia en España | Amplia y creciente | Empate, con matices en §11.5 |
| Estabilidad de la API del framework | Alta; actualizaciones anuales previsibles | Cambios de paradigma frecuentes en años recientes | **Laravel** |

Dos filas merecen énfasis, porque no son preferencias:

- **Colas.** Es el punto donde la elección de framework arrastra la arquitectura. Con
  Laravel, la cola sobre PostgreSQL es un modo soportado de fábrica, y eso es lo que
  permite mantener un único almacén de datos. Con Next.js, la ruta habitual reintroduce
  Redis. Es decir: la decisión de interfaz acabaría imponiendo un componente de
  infraestructura. Eso es exactamente la clase de acoplamiento que este informe intenta
  evitar.
- **SOAP.** La interoperabilidad con la Administración obliga a SOAP con XML firmado
  (§13). PHP tiene soporte nativo de SOAP y un ecosistema maduro de firma XML; Node no.
  No es un detalle: es una parte sustancial del trabajo de integración.

### 11.4 Otras alternativas consideradas honestamente

- **.NET / ASP.NET Core.** Técnicamente excelente y una elección perfectamente
  defendible: madurez, rendimiento, tipado fuerte, y (punto nada menor) es donde está la
  mayor concentración de proveedores del sector público español. Su desventaja aquí es de
  *gravedad*, no de calidad: mantiene al organismo en la órbita del mismo fabricante,
  y uno de los objetivos declarados es reducir esa concentración. Si el objetivo fuese
  sólo "salir de Power Pages" y no "reducir dependencia", .NET sería una respuesta
  legítima. **Debe constar como alternativa viva, no descartada por prejuicio.**
- **Java / Spring Boot.** El estándar de facto en muchos sistemas administrativos
  españoles, con las librerías de firma y SCSP más rodadas del ecosistema. Más verboso y
  con mayor coste de desarrollo por funcionalidad para un equipo pequeño, pero con la
  mejor disponibilidad de talento con experiencia previa en AGE. **Es el competidor más
  serio de Laravel en este contexto concreto.**
- **Python / Django.** Muy cercano en filosofía a lo propuesto y con un administrador
  generado de serie. Ecosistema más débil para colas integradas (Celery exige un broker,
  reintroduciendo Redis) y para SOAP.
- **Ruby on Rails.** Comparable en ergonomía. Comunidad más pequeña en España.

### 11.5 El argumento del talento, sin autoengaño

Un tribunal técnico preguntará: *¿quién mantiene esto dentro de siete años?*

La respuesta honesta tiene dos caras, y este informe se obliga a decir las dos:

- **A favor:** PHP y Laravel tienen una base de profesionales amplia en España y un
  coste de contratación inferior al de perfiles certificados en plataformas propietarias.
  El conocimiento necesario para mantener el sistema es conocimiento general, no
  específico de un producto.
- **En contra, y hay que reconocerlo:** *no existe una comparación metodológicamente
  sólida y publicada* de disponibilidad de talento Laravel frente a .NET o Power Platform
  en el sector público español. Los recuentos de portales de empleo son heterogéneos y no
  sirven ante un tribunal. Y el sector público español contrata mayoritariamente a través
  de consultoras cuya práctica dominante es Java y .NET.

**Conclusión matizada:** la ventaja de Laravel en talento es plausible pero **no está
probada con datos publicables**. Lo que sí es demostrable, y es un argumento distinto y
más fuerte, es que **el conocimiento requerido es transferible**: cualquier desarrollador
web con experiencia en un framework MVC puede orientarse en una base de código Laravel
convencional en días, mientras que operar una implantación de Power Platform requiere
familiaridad con un producto concreto y, para ciertas tareas, socios certificados.

**Y un dato del propio pliego que juega en contra de la propuesta, y que hay que poner
encima de la mesa:** el PCAP, al definir los perfiles del equipo, enumera como
tecnologías de backend **Java, Python y Node** -y como bases de datos PostgreSQL, MySQL y
MongoDB-. **PHP no aparece.** Una oferta basada en Laravel habría tenido que argumentar
la equivalencia del perfil. No es un impedimento formal, porque la lista no es cerrada y
el pliego admite alternativas justificadas, pero es un obstáculo real en la práctica de
la contratación pública española.

Obsérvese, en cambio, que **PostgreSQL sí figura expresamente** entre las bases de datos
previstas por el pliego. La parte más discutida de nuestra propuesta -el almacén de
datos- es la que el propio pliego ya contemplaba.

Se traslada a §24 como decisión abierta: **si el organismo tiene ya un equipo .NET o Java
consolidado, la recomendación de Laravel debe reconsiderarse.** La tesis central de este
informe -monolito modular, base relacional única, HTML servido, sin plataforma
propietaria interpuesta- **no depende del lenguaje**. Sobrevive igual en .NET, en Java y
en Python. Laravel es la elección más eficiente para un equipo pequeño, no un dogma.

### 11.6 El riesgo de gobernanza de Laravel, dicho de frente

Laravel recibió en septiembre de 2024 una inversión de 57 millones de dólares de Accel.
Un revisor adversarial dirá, con razón: *acabáis de criticar la dependencia de una
empresa y proponéis un framework que ahora responde ante inversores de capital riesgo.*

Es un argumento legítimo y la respuesta no es negarlo, sino medir la diferencia:

1. Laravel es software libre bajo licencia MIT. Si su gobernanza se deteriorase, el
   código existente sigue siendo utilizable y bifurcable. Una licencia de plataforma
   propietaria que cambia de modelo de precios no ofrece esa salida.
2. El precedente relevante existe y juega en ambas direcciones: Wikimedia tuvo que
   abandonar HHVM cuando Facebook lo discontinuó unilateralmente. **El riesgo de
   discontinuidad no es exclusivo del software propietario.** Debe declararse.
3. La mitigación real no es la licencia: es **mantenerse en el uso convencional del
   framework**, sin depender de productos comerciales del ecosistema para funciones
   críticas, de modo que una migración futura de framework sea un trabajo acotado.
   Esto se convierte en una restricción de diseño explícita (§23).
## 12. PostgreSQL y modelo de persistencia

### 12.1 Qué se le pide a la base de datos, y si puede

| Responsabilidad | ¿PostgreSQL? | Justificación con cifras |
|---|---|---|
| Datos del dominio | Sí | Volúmenes de §5.5: ninguno relevante para PostgreSQL |
| Sesiones | Sí | ~150 gestores y picos de miles de ciudadanos. Trivial. |
| Colas de trabajo | Sí | `SELECT … FOR UPDATE SKIP LOCKED` sostiene del orden de 10⁴ trabajos/segundo en pruebas publicadas. La necesidad aquí es de decenas por segundo en pico. **Tres órdenes de magnitud de margen.** El límite conocido a esa escala es el mantenimiento de tuplas muertas, que a nuestro volumen no se alcanza. |
| Cerrojos distribuidos | Sí | *Advisory locks*. Se usan para garantizar que una vivienda se adjudica una sola vez. |
| Búsqueda textual | Sí | `tsvector` con índice GIN mantiene consultas por debajo de 100 ms hasta millones de filas. El catálogo es de 10³–10⁴. |
| Búsqueda aproximada | Sí | `pg_trgm` para tolerancia a erratas en municipio o vía. |
| Bandeja de salida de eventos | Sí | Es una tabla. Su valor está precisamente en compartir transacción con el dominio. |
| Auditoría | Sí | Tabla de sólo inserción, particionada por mes. 10⁷ filas a cuatro años. |
| Geolocalización | **Depende** | Ver 12.2 |

### 12.2 La decisión sobre PostGIS, razonada

Los requisitos geográficos observables son: mostrar viviendas en un mapa y, previsiblemente,
buscar por proximidad a un punto.

- Pintar marcadores en un mapa **no requiere PostGIS**: bastan dos columnas numéricas.
- Buscar dentro de un radio **tampoco lo requiere estrictamente**: la fórmula del
  semiverseno sobre columnas indexadas resuelve el caso a este volumen.
- PostGIS **sí** sería necesario si aparecieran: polígonos administrativos (buscar
  dentro de un distrito), isócronas, cálculo de rutas o análisis territorial.

**Decisión: no incluir PostGIS de partida.** Es una extensión, no un servidor nuevo, así
que el coste de añadirla después es bajo, y esa es precisamente la razón por la que no
hay que añadirla antes de necesitarla. Se documenta el disparador: **el primer requisito
que involucre geometrías de área**.

`pg_trgm` sí se incluye, porque el requisito de búsqueda tolerante a erratas en
direcciones es previsible y su coste es nulo.

### 12.3 Esquema, decisiones estructurales

Sólo las decisiones no obvias. El esquema completo pertenece a la fase 2.

**Instantánea de la unidad de convivencia.** `applications` no referencia `households`
para los datos que determinan el derecho: los **copia** en el momento de presentar. La
unidad viva sigue existiendo para el ciudadano; la solicitud conserva la que se evaluó.
Sin esto, un recurso a dos años vista es irresoluble.

**Versionado de reglas.** `eligibility_rule_version` y `scoring_rule_version` se
almacenan en la solicitud. Cuando cambie el baremo, las solicitudes anteriores siguen
siendo reconstruibles. Es un requisito jurídico disfrazado de columna.

**Estados como restricción, no como convención.** El estado es texto con una restricción
`CHECK` sobre el conjunto de valores válidos, más una tabla de transiciones registradas.
Se descarta un tipo enumerado nativo porque añadir un valor a un `ENUM` en producción es
más incómodo que modificar una restricción.

**Unicidad que expresa la regla de negocio.**
`UNIQUE (call_id, household_snapshot_nif_hash)` impide dos solicitudes de la misma unidad
a la misma convocatoria. La regla vive en la base, no sólo en la aplicación: es la única
forma de que resista a la concurrencia y a un error de código.

**Adjudicación única.** `UNIQUE (property_id) WHERE status = 'active'`, índice parcial.
Una vivienda no puede tener dos adjudicaciones vivas. Combinado con un cerrojo consultivo
durante el proceso de adjudicación, resuelve la concurrencia sin coordinación externa.

**Auditoría inmutable.** `audit_events` sólo admite inserción: se revocan `UPDATE` y
`DELETE` a nivel de permisos de la base para el usuario de la aplicación. Cada fila
encadena el hash de la anterior, de modo que una manipulación posterior sea detectable.
Esto responde a una exigencia concreta del ENS sobre integridad de la traza.

**Borrado lógico, sólo donde procede.** Se usa en `properties` y `calls`, donde el
histórico importa. **No** se usa en el modelo del expediente: un expediente
administrativo no se borra, se archiva o se suprime conforme a la política de
conservación. Aplicar borrado lógico por sistema es un antipatrón que oculta el
verdadero requisito, que es la retención.

**Cifrado de campo.** Los datos de categoría especial (§5.4) se cifran a nivel de
aplicación con claves gestionadas fuera de la base. Motivo: el cifrado en reposo del
proveedor protege frente al robo del disco, no frente a un acceso legítimo mal empleado
ni frente a una copia de seguridad manipulada.

**Retención.** Cada tabla con datos personales lleva declarada su política y hay un
trabajo programado que la ejecuta. Que un dato se conserve más de lo debido es un
incumplimiento tan real como perderlo.

### 12.4 Cuándo dejaría de bastar

Se declaran los umbrales, para que la decisión sea revisable con datos y no con
opiniones:

| Señal medida | Acción |
|---|---|
| Latencia de toma de trabajo de cola > 5 s de forma sostenida en pico | Evaluar Redis para colas |
| Escrituras en la base por encima del 70 % de capacidad durante el pico | Escalar verticalmente; luego separar lecturas |
| Consultas de catálogo por encima de 200 ms en el percentil 95 | Revisar índices; después, caché; sólo después, motor de búsqueda |
| Aparición de requisitos con geometrías de área | Añadir PostGIS |
| Un consumidor externo que exija reproducir el histórico de eventos | Evaluar un bus |

**Ninguno de estos umbrales se alcanza con las volumetrías del proyecto.** Se documentan
para que, si algún día se alcanzan, la decisión de complicar la arquitectura esté
respaldada por una medición y no por una intuición.
## 13. Arquitectura de integraciones

### 13.1 El principio, y por qué aquí es obligatorio

El dominio depende de interfaces propias. Cada servicio externo es un adaptador
sustituible. Esto no es purismo: los servicios de la Administración cambian de protocolo
y de nombre, y el sistema debe vivir diez años.

```text
Dominio ──▶ DataVerificationGateway (interfaz propia)
                        ├── ScspAdapter        (producción, SOAP firmado vía SARA)
                        ├── ManualAdapter      (verificación asistida por gestor)
                        └── FakeAdapter        (pruebas y demostrador)
```

La existencia del tercer adaptador no es un detalle de pruebas: es lo que permite
construir y validar todo el dominio **antes** de tener el alta administrativa en los
servicios reales, que es el cuello de botella real de estos proyectos.

### 13.2 Inventario, con esfuerzo y naturaleza

| Puerto | Adaptador | Protocolo | ¿Red SARA? | Esfuerzo | Naturaleza |
|---|---|---|---|---|---|
| `IdentityGateway` | Cl@ve | SAML 2.0 por navegador | **No** | Medio | Intrínseca |
| `DataVerificationGateway` | PID / SCSP | SOAP con XML firmado | **Sí** | **Alto** | Intrínseca |
| `SignatureGateway` | FIRe / @firma | Servicios web + validación | No | **Alto** | Intrínseca |
| `NotificationGateway` | DEHú / Notifica | TLS mutuo, entrante | No (a confirmar) | Medio | Intrínseca |
| `RegistryGateway` | REGAGE / SIR | Intercambio de asientos | **Sí** | Alto | Intrínseca |
| `PropertyRegistryGateway` | Catastro | REST y SOAP; libres sin autenticación | No | **Bajo** | Intrínseca |
| `FinancialGateway` | Sistema económico | Según el sistema | No | Medio-alto | Intrínseca |
| `InvoicingGateway` | FACe / Facturae | Servicios web | No | Medio | Intrínseca |
| `DocumentStorageGateway` | S3 / Alfresco (CMIS) | REST | No | Bajo | Intrínseca |
| `MailGateway` | Correo transaccional | SMTP / API | No | Bajo | Intrínseca |

**Todas son complejidad intrínseca.** Ninguna se abarata cambiando de arquitectura. Es
la observación más importante del capítulo y va en contra de la tesis que este informe
defiende: el trabajo duro de integración hay que hacerlo igual.

### 13.3 La restricción de Red SARA, resuelta

Es el argumento más fuerte que puede oponerse a una arquitectura desplegable en nube
pública, así que se aborda de frente.

**Hecho:** PID/SCSP y SIR son servicios internos de la Administración que exigen
conectividad por Red SARA. Una aplicación en nube pública no puede alcanzarlos
directamente.

**Y ahora la observación que desactiva el argumento:**

> Power Pages se ejecuta en Azure. **También está fuera de Red SARA.** Por tanto el
> sistema actual necesita exactamente la misma pasarela que necesitaría el propuesto.

Sólo existen tres caminos, y son los mismos para ambas arquitecturas:

1. **NubeSARA**, servicio de nube híbrida cuyo proveedor es la Secretaría General de
   Administración Digital, articulado por convenio con cada organismo. Hay convenios
   publicados en el BOE (Agencia Espacial Española, BOE-A-2024-26902; Instituto de la
   Juventud, BOE-A-2024-25018; Confederación Hidrográfica del Duero, BOE-A-2023-23282).
   **Es la vía canónica y hay precedente institucional publicado.**
2. **Pasarela propia** en un Punto de Presencia del organismo, que expone los servicios
   SARA hacia la aplicación mediante una API interna con autenticación reforzada.
3. **Consumo asistido**, mediante el cliente ligero SCSP operado por un empleado público.
   Es la opción de menor integración y mayor carga de trabajo manual.

**Conclusión:** la conectividad SARA es una **restricción intrínseca y simétrica**. No es
un argumento a favor de la plataforma propietaria. Quien la esgrima debe explicar por qué
Azure estaría exento de una restricción que aplicaría a la misma aplicación en otro
proveedor.

**Consecuencia de diseño**, sin embargo, sí la tiene, y se incorpora a la arquitectura
corregida (§23): el sistema debe funcionar de forma degradada cuando la verificación
automática no esté disponible, escalando a verificación documental. Un procedimiento
administrativo no puede detenerse porque un servicio externo esté caído.

### 13.4 Reglas de todos los adaptadores

1. **Idempotencia obligatoria.** Toda operación con efecto lleva clave de idempotencia.
   Reintentar no puede duplicar una notificación ni un asiento registral.
2. **Tiempos de espera y cortocircuito.** Ningún servicio externo puede bloquear una
   petición del ciudadano. Todas las llamadas salientes ocurren en trabajos asíncronos.
3. **Traza completa.** Petición, respuesta, duración y correlación se registran, con los
   datos personales enmascarados en el registro. Necesario para poder demostrar qué
   respondió un servicio un día concreto.
4. **Reintentos con retroceso exponencial y bandeja de fallos.** Con alerta al superar un
   umbral. Una bandeja de fallos sin alerta es una avería silenciosa.
5. **Contrato verificado con dobles.** Cada adaptador tiene pruebas contra respuestas
   grabadas del servicio real, para detectar cambios de contrato.
6. **Degradación explícita.** Cada puerto declara qué ocurre cuando su servicio no
   responde, y esa ruta se prueba.

### 13.5 Sobre SOAP, sin rodeos

SCSP obliga a SOAP con XML firmado. Es un protocolo incómodo y no va a cambiar a corto
plazo.

PHP tiene soporte nativo de SOAP y un ecosistema maduro de firma XML. Java lo tiene aún
mejor, y es donde está la mayor experiencia previa del sector público español en SCSP.
Node.js es el peor posicionado de los tres. Es un factor real en la elección de
plataforma (§11.3) y una razón concreta (no estética) para descartar Next.js como núcleo
del sistema.
## 14. Seguridad y ENS

**Premisa que gobierna el capítulo:** la aplicación puede ser sencilla; **la operación no
puede serlo**. Este informe no usa en ningún punto "un servidor barato" como argumento de
ahorro, y rechaza expresamente esa línea de razonamiento.

### 14.1 Categorización ENS, verificada en el pliego

No hay que inferirla. El pliego de prescripciones técnicas lo establece literalmente
(pág. 34):

> "La solución propuesta cumplirá con lo establecido en el Real Decreto 311/2022, de 3 de
> mayo, por el que se regula el Esquema Nacional de Seguridad, **de nivel alto**"

Y el pliego administrativo lo exige además como **requisito de solvencia eliminatorio**,
no como criterio puntuable: certificación de Nivel Alto en el ENS o equivalente, más
ISO 27001, ISO 27017 e ISO 27018.

**Consecuencias, que elevan el listón de cualquier propuesta alternativa:**

1. Aplica el conjunto **más exigente** de medidas del Anexo II del RD 311/2022.
2. **Auditoría externa por entidad acreditada, con periodicidad**, obligatoria.
3. El proveedor de infraestructura debe poder acreditar conformidad, lo que **restringe
   de hecho el mercado de alojamiento** y encarece la operación.
4. La certificación no es sólo del sistema: el pliego la exige **al licitador como
   organización**. Cualquier equipo que aspire a mantener este sistema debe poder
   acreditarla.

**Esto es un argumento serio en contra de la ligereza con la que a veces se propone una
alternativa "sencilla".** Se acepta íntegramente y se traslada al coste (§18) y a los
riesgos (§22). Un equipo pequeño sin certificación ENS de nivel alto **no puede** asumir
este sistema, por buena que sea su arquitectura.

### 14.2 Medidas, ordenadas por la exigencia que las origina

| Ámbito | Medida | Origen |
|---|---|---|
| Identificación | Cl@ve para ciudadanos; segundo factor obligatorio para todo el personal | ENS op.acc |
| Autorización | Control por rol **y** por política sobre el propio expediente | ENS op.acc |
| Autorización | Motivo obligatorio y registrado para acceder a datos de categoría especial | RGPD art. 9 |
| Cifrado en tránsito | TLS 1.3, HSTS con precarga | ENS mp.com |
| Cifrado en reposo | Volúmenes y copias cifrados; **además**, cifrado de campo para categoría especial | ENS mp.info |
| Trazabilidad | Registro inmutable, encadenado por hash, con reloj sincronizado | ENS op.exp |
| Segregación | Entornos separados; sin datos reales fuera de producción | ENS mp.sw |
| Secretos | Almacén de secretos; nunca en el repositorio ni en variables de entorno en claro | ENS op.exp |
| Copias | Cifradas, con restauración **probada periódicamente**, y copia en ubicación independiente | ENS mp.info |
| Continuidad | Plan documentado y ensayado | ENS op.cont |
| Perímetro | WAF, limitación de tasa, protección de denegación de servicio | ENS mp.com |
| Aplicación | Protección frente a CSRF, XSS, SSRF, inyección; consultas parametrizadas | ENS mp.sw |
| Ficheros | Análisis antivirus antes de aceptar; validación de tipo real, no de extensión; servicio desde dominio separado con `Content-Disposition: attachment` y `X-Content-Type-Options: nosniff` | ENS mp.sw |
| Ficheros | Acceso exclusivamente por URL firmada de vigencia corta | Diseño |
| Sesión | Cookies `Secure`, `HttpOnly`, `SameSite=Lax`; renovación de identificador; expiración por inactividad | ENS op.acc |
| Cadena de suministro | Inventario de dependencias, análisis de vulnerabilidades en integración continua, versiones fijadas | ENS op.pl |
| Verificación | Test de intrusión antes de producción y con periodicidad | ENS mp.sw |
| Certificación | Auditoría de conformidad ENS **nivel alto** por entidad acreditada | RD 311/2022 |
| Divulgación | Publicar `/.well-known/security.txt`, hoy ausente en el portal (§3) | Buena práctica |

### 14.3 SSRF, el riesgo característico de este sistema

Merece mención propia porque es donde este tipo de aplicaciones falla. El sistema hace
llamadas salientes a muchos servicios y acepta ficheros de usuarios. La combinación es
exactamente el terreno de la falsificación de peticiones del lado del servidor.

Medidas: lista blanca de destinos salientes, prohibición de redirecciones a direcciones
internas, resolución de nombres restringida, y salida a Internet a través de un proxy
controlado. Ninguna URL proporcionada por un usuario se solicita jamás desde el servidor.

### 14.4 Protección de datos

- **Base jurídica:** cumplimiento de misión de interés público. No consentimiento -el
  consentimiento sería revocable y haría inviable el procedimiento-, salvo para
  tratamientos accesorios.
- **Evaluación de impacto:** obligatoria. Tratamiento a gran escala, datos de categoría
  especial y evaluación sistemática con efectos sobre las personas.
- **Analítica:** cualquier herramienta de grabación de sesión sobre pantallas con datos
  personales exige justificación específica y, probablemente, exclusión de esas
  pantallas. Se señala porque en el portal actual la política de contenido permite una
  herramienta de ese tipo (§3). Es una observación técnica, no una imputación.
- **Encargados de tratamiento:** cada proveedor con acceso a datos requiere contrato y
  consta en el registro de actividades.
- **Derechos:** acceso, rectificación, supresión y portabilidad resueltos por
  funcionalidad del sistema, no por procedimiento manual.

---

## 15. Disponibilidad y recuperación

### 15.1 Separar arquitectura lógica de redundancia física

Una aplicación monolítica desplegada en dos instancias tras un balanceador, contra una
base de datos con alta disponibilidad, **sigue siendo una aplicación, una base y un
modelo**. La disponibilidad se obtiene replicando infraestructura, no fragmentando el
sistema. Confundir ambas cosas es el error que convierte "necesitamos disponibilidad" en
"necesitamos microservicios".

### 15.2 Objetivos propuestos

| Objetivo | Valor | Justificación |
|---|---|---|
| Disponibilidad en horario de atención | 99,9 % | Equivale a ~43 min/mes. Suficiente para un procedimiento con plazos de días. |
| Disponibilidad en cierre de convocatoria | 99,95 % | Ventana crítica: un corte puede impedir presentar en plazo y generar responsabilidad. |
| Punto objetivo de recuperación (RPO) | ≤ 5 min | Recuperación a un instante con archivado continuo. |
| Tiempo objetivo de recuperación (RTO) | ≤ 2 h | Restauración probada. |
| RTO en desastre total de región | ≤ 24 h | Reconstrucción desde infraestructura como código y copias. |

**No se propone 99,99 %.** Sería más caro y no está justificado por el procedimiento. Un
objetivo de servicio que nadie necesita es sobrearquitectura con formato de compromiso.

### 15.3 Medidas específicas del cierre de plazo

El riesgo real no es un fallo de infraestructura: es que **todo el mundo presente la
solicitud la última tarde**. Medidas:

- Ampliar capacidad de forma programada en las ventanas conocidas.
- Guardado de borrador continuo, para que un corte no destruya el trabajo del ciudadano.
- Cola de admisión con acuse inmediato: se registra la presentación y se procesa después.
  El sello de tiempo de presentación es lo jurídicamente relevante, no el procesamiento.
- Prueba de carga previa a cada convocatoria, con el escenario real.

### 15.4 La prueba que hace real todo lo anterior

**Restauración completa ensayada trimestralmente, en entorno limpio y cronometrada, con
acta.** Una copia de seguridad no verificada no es una copia de seguridad. Este ensayo es
además la evidencia que sostiene la afirmación de coste de salida de §19: si se puede
reconstruir el sistema desde cero en dos horas, se puede migrar de proveedor.

---

## 16. Observabilidad

Debe ser **proporcionada**. La tentación es contratar doce servicios; el resultado
habitual es que nadie mira ninguno.

| Necesidad | Solución | Por qué basta |
|---|---|---|
| Registros | Estructurados en JSON, con identificador de petición y de usuario, centralizados | Correlacionar es lo que importa |
| Errores | Un único agregador de excepciones con alerta | |
| Métricas | Series temporales con cuadros de mando: latencia p50/p95/p99, tasa de error, saturación | Las cuatro señales clásicas |
| Colas | Profundidad, antigüedad del trabajo más viejo, tasa de fallo | **Alerta obligatoria**: es el punto ciego típico |
| Bandeja de salida | Eventos sin entregar y su antigüedad | **Alerta obligatoria** (§10.6) |
| Integraciones | Tasa de error y latencia por servicio externo | Permite distinguir "falla el sistema" de "falla la AGE" |
| Disponibilidad | Sondeo externo desde fuera de la infraestructura | Comprobar desde dentro no demuestra nada |
| Trazas distribuidas | **No inicialmente** | Con un solo proceso aportan poco. Se instrumenta con OpenTelemetry para poder activarlas sin reescribir. |
| Auditoría | En base de datos, consultable desde el backoffice | Es requisito funcional, no observabilidad |

**Alertas: pocas y accionables.** Una alerta que no exige acción inmediata es un informe,
y debe ir a un panel, no al teléfono de alguien. La disciplina de mantener corta la lista
de alertas es lo que hace que se atiendan.

---

## 17. Escalabilidad, con cifras

### 17.1 Escenarios de carga realistas

Se descarta dimensionar para escalas ficticias. Escenarios derivados del procedimiento:

| Escenario | Concurrencia estimada | Perfil |
|---|---|---|
| Día normal | 20–50 usuarios | Consulta del catálogo, mayoritariamente lectura |
| Publicación de convocatoria | 500–2.000 en la primera hora | Lectura intensa, con caché |
| Cierre de plazo, última tarde | 1.000–3.000 | **Escritura intensa: el caso duro** |
| Publicación de resultados | 2.000–5.000 | Lectura muy intensa, cacheable |
| Consulta masiva de catálogo | 200–500 sostenidos | Lectura con filtros |

### 17.2 Qué hay que sostener en el peor caso

Con 3.000 usuarios concurrentes presentando solicitudes en dos horas: del orden de
**5–15 peticiones por segundo de escritura** y unas 150 de lectura.

Estas cifras son modestas. Una instancia de aplicación correctamente configurada, con
consultas indexadas, sostiene ese orden de magnitud; dos instancias dan margen y
tolerancia a fallo. El elemento a vigilar no es la CPU: son las **conexiones a la base de
datos**, que se resuelven con un agrupador de conexiones (PgBouncer en modo transacción).

### 17.3 Honestidad sobre lo que no sabemos

**No se afirma que esta arquitectura sostenga estas cargas.** Se afirma que las cifras
requeridas están dentro de lo que este tipo de sistemas sostiene habitualmente, y que
**la verificación pertenece a la fase 5**, con pruebas de carga sobre el demostrador y
resultados publicados.

Un informe que dijera "Laravel aguanta millones de usuarios" estaría cometiendo
exactamente el error que el encargo prohíbe. La afirmación defendible es más modesta y
más útil: *las cargas de este sistema son pequeñas, y lo demostraremos midiendo*.
## 18. Coste total de propiedad

### 18.0 Advertencia previa, y la conclusión que no esperábamos

Este capítulo empieza con una advertencia porque es la parte del informe donde resulta
más fácil hacer trampa, y donde una trampa destruiría todo lo demás.

**Hipótesis explícitas que gobiernan todas las cifras:**

1. Tipo de cambio: 1 USD = 0,92 EUR. Es una hipótesis declarada, no un dato del día.
2. Los precios de licencia son **de lista**. No conocemos el precio real que paga
   CASA 47. Se presentan rangos.
3. **Verificado en el pliego:** las licencias de la tecnología base **no están incluidas**
   en el importe adjudicado. Cita literal del PPT, cláusula 7.3: *"El presente contrato no
   incluye la contratación de las licencias de la tecnología base que sean necesarias.
   Estas serán contratadas a continuación siguiendo los procedimientos abiertos por la
   Dirección General de Racionalización y Centralización de la Contratación."* Esto ya no
   es hipótesis: es el dato que estructura toda la comparación.
4. Todas las cifras son sin IVA salvo indicación.
5. El coste de desarrollo de la arquitectura propuesta es una **estimación de ingeniería**,
   no una oferta. Se explicita cómo se construye para que pueda ser discutida.

**Y ahora la conclusión que conviene poner por delante, porque contradice la expectativa
con la que se inició este trabajo:**

> El importe adjudicado -1.184.998 € sin IVA por cuatro años, es decir **296.250 € al
> año**- **no es un precio desproporcionado** para el alcance contratado. Equivale
> aproximadamente al coste de un equipo de 3 a 4 personas a tarifas de consultoría,
> incluyendo consultoría de procesos, desarrollo, implantación, formación, soporte y
> mantenimiento evolutivo durante cuatro años.
>
> **La tesis de que "esto se podía hacer por mucho menos" no se sostiene en la partida de
> trabajo.** Construir bien este sistema cuesta un dinero parecido, lo construya quien lo
> construya, porque la mayor parte del coste es trabajo humano cualificado sobre
> complejidad intrínseca (§6).

Si este informe terminara aquí, la conclusión sería: el contrato de servicios es
razonable. Lo que hace falta examinar es **lo que no está en esa cifra**.

### 18.1 Estructura de coste de la arquitectura propuesta

**Desarrollo (CAPEX), estimación de ingeniería.**

Se estima por módulos y se declara el método: equipo de 4 personas -2 desarrolladores de
producto, 1 de integraciones, 1 con perfil de diseño y accesibilidad-, más dirección
técnica a tiempo parcial y auditorías externas.

| Bloque | Persona-mes | Comentario |
|---|---|---|
| Análisis funcional, procedimiento y baremo | 3 | Depende de normativa, no de tecnología |
| Diseño de interfaz y accesibilidad | 4 | Incluye sistema de componentes accesibles |
| Portal público y catálogo | 4 | |
| Identidad, cuenta y unidad de convivencia | 4 | Incluye adaptador Cl@ve |
| Solicitud, documentos y verificaciones | 8 | El bloque más grande, con razón |
| Baremación y adjudicación | 5 | Alta densidad de reglas; alta densidad de pruebas |
| Contratos, recibos e incidencias | 6 | |
| Backoffice de gestión | 7 | |
| Adaptadores de integración | 8 | Ver §13; SCSP y firma son los caros |
| Auditoría, observabilidad y operación | 3 | |
| Endurecimiento de seguridad y ENS | 3 | |
| Pruebas y automatización | incluido | Transversal, no partida aparte |
| **Total** | **55 persona-mes** | |

A una tarifa media de 8.000 €/persona-mes (coste de equipo interno con estructura, no
tarifa de consultoría) → **~440.000 €**.
A tarifa de consultoría de 12.000 €/persona-mes → **~660.000 €**.

Más auditorías externas independientes, que no deben omitirse:

| Concepto | Coste estimado |
|---|---|
| Auditoría de accesibilidad (EN 301 549 / WCAG 2.1 AA) | 12.000 – 20.000 € |
| Auditoría de seguridad y test de intrusión | 20.000 – 35.000 € |
| Auditoría de conformidad ENS **nivel alto** por entidad acreditada | 30.000 – 60.000 € |
| Evaluación de impacto en protección de datos | 8.000 – 15.000 € |

**CAPEX total estimado: 510.000 – 790.000 €.**

Se ha corregido al alza respecto de una primera estimación al confirmarse en el pliego
que el ENS exigido es de **nivel alto** y no medio.

Esta cifra se declara con incomodidad deliberada: **es alta, y es honesta**. Un informe
que dijera "150.000 €" sería más vistoso y menos defendible.

**Operación (OPEX), infraestructura, con precios verificados de Azure (§04):**

Se dimensiona sobre **el mismo proveedor que usa el sistema actual**, deliberadamente,
para que ningún ahorro pueda atribuirse a haberse mudado a un alojamiento más barato.

| Componente | Dimensión | USD/mes |
|---|---|---|
| Aplicación, 2 instancias | App Service P1v3 × 2 | 432 |
| Trabajadores de cola | App Service P1v3 × 1 | 216 |
| PostgreSQL D4ds_v5 con alta disponibilidad zonal | 4 vCPU × 2 | 620 |
| Almacenamiento de base de datos (512 GB, duplicado por HA) | | 140 |
| Copias de seguridad | ~500 GB | 52 |
| Almacenamiento de objetos (documentos, ~1,5 TB) | | 27 |
| Front Door Premium con WAF gestionado | | 330 |
| Salida de datos | ~1 TB/mes | 170 |
| Entorno de preproducción | ~30 % de producción | 500 |
| Observabilidad | | 150 |
| Correo y SMS transaccionales | | 100 |
| **Total** | | **≈ 2.740 USD/mes** |

≈ **32.900 USD/año ≈ 30.300 €/año** de infraestructura.

**Licencias de software de aplicación: 0 €.** Esta afirmación debe entenderse con
precisión: significa cero coste de licencia *por usuario y por aplicación*. **No**
significa coste cero: la infraestructura de arriba se paga igual.

**Mantenimiento y evolución.** Un sistema administrativo vivo, sujeto a cambios
normativos, necesita equipo permanente. Estimación honesta: **2 a 3 personas
equivalentes a tiempo completo**, entre 160.000 y 290.000 € anuales según se resuelva
con personal propio o contratado.

**Aquí no hay ahorro estructural frente al contrato actual, y decirlo importa.** El
mantenimiento cuesta lo que cuesta el conocimiento humano de un procedimiento
administrativo complejo.

### 18.2 Comparación a 1, 4, 5 y 10 años

**Escenario A, arquitectura actual.** Servicios según contrato verificado, más
licencias a precio de lista bajo tres supuestos de uso ciudadano. Se asume que a partir
del año 5 se renueva un contrato de servicios de magnitud similar.

**Escenario B, arquitectura propuesta.** CAPEX en el año 1, luego infraestructura y
mantenimiento.

Cifras en miles de euros, sin IVA.

| Concepto | Año 1 | 4 años | 5 años | 10 años |
|---|---|---|---|---|
| **A, Servicios contratados** (verificado) | 296 | **1.185** | 1.481 | 2.962 |
| A, Licencias, uso bajo (pico 2.000/mes) | 44 | 177 | 221 | 442 |
| A, Licencias, uso medio (pico 25.000/mes) | 207 | 828 | 1.035 | 2.070 |
| A, Licencias, uso alto (pico 50.000/mes) | 414 | 1.656 | 2.070 | 4.140 |
| A, Licencias de gestor (17 internos, D365 CS Ent.) | 20 | 79 | 98 | 197 |
| **A, Total, uso bajo** | **360** | **1.441** | **1.800** | **3.601** |
| **A, Total, uso medio** | **523** | **2.092** | **2.614** | **5.229** |
| **A, Total, uso alto** | **730** | **2.920** | **3.649** | **7.299** |
| | | | | |
| **B, Desarrollo** | 510–790 | 510–790 | 510–790 | 510–790 |
| B, Infraestructura | 30 | 121 | 152 | 303 |
| B, Mantenimiento (desde año 2) | 0 | 480–870 | 640–1.160 | 1.440–2.610 |
| B ( Auditorías periódicas (ENS alto, seguridad, accesibilidad) | ) | 110 | 145 | 290 |
| **B, Total (rango)** | **540–820** | **1.221–1.891** | **1.447–2.247** | **2.543–4.003** |

**Lectura honesta de esta tabla.**

- **A un año, la propuesta es más cara.** Hay que construirla. Cualquier presentación que
  oculte esto es propaganda.
- **A cuatro años, con uso bajo de la plataforma, ambas opciones son comparables.** El
  rango se solapa. No hay una conclusión económica clara.
- **A partir de uso medio de plataforma, y a partir del quinto año, la divergencia se
  hace estructural** y crece de forma monótona.
- **Con uso bajo de plataforma a diez años los rangos siguen solapándose** (A: 3.601 k€;
  B: 2.543–4.003 k€). En ese supuesto **no puede afirmarse un ahorro**. Hay que decirlo,
  aunque no convenga.
- **Con uso medio a diez años la diferencia va de ~1.200.000 € a ~2.700.000 €**; con uso
  alto, de ~3.300.000 € a ~4.800.000 €. El rango es amplio porque las incógnitas son
  reales y no se estrechan inventando precisión.

### 18.3 Dónde está realmente el ahorro

El análisis obliga a corregir la intuición de partida. El ahorro **no** está donde se
esperaba:

| Partida | ¿Hay ahorro? | Por qué |
|---|---|---|
| Desarrollo inicial | **No** | Cuesta lo mismo o más. La complejidad es intrínseca. |
| Mantenimiento evolutivo | **No** | Los cambios normativos cuestan igual. |
| Consultoría de procesos | **No** | Es trabajo sobre el procedimiento, no sobre tecnología. |
| Integraciones con la AGE | **No** | Complejidad intrínseca, idéntica en ambas (§6, §13). |
| Infraestructura | Poco | Ambas necesitan cómputo, base de datos y CDN. |
| **Licencia por usuario ciudadano** | **Sí, y es la partida decisiva** | Pasa de escalar con el uso a ser cero. |
| **Licencia por gestor** | Sí, menor | ~20-100 k€/año. |
| **Coste de salida** | **Sí, y es enorme** | §19. |
| **Riesgo de cambio unilateral de tarifa** | **Sí** | No cuantificable, pero real (§20). |

**La conclusión económica correcta de este informe, entonces, no es "es más barato
construirlo".** Es esta:

> El coste de construir el sistema es comparable en ambas arquitecturas. Lo que las
> separa es que una de ellas incorpora un **coste recurrente que crece con el número de
> ciudadanos atendidos y que está fijado unilateralmente por un tercero**, y la otra no.
>
> Dicho de otro modo: en la arquitectura actual, **que el servicio público tenga éxito
> aumenta la factura**. En la propuesta, no.

Ese es un argumento de política pública, no sólo de ingeniería, y es más sólido que
cualquier comparación de precio de desarrollo.

### 18.4 Coste por unidad

Con las hipótesis anteriores, y usando el escenario de uso medio a cuatro años:

| Métrica | Arquitectura actual | Arquitectura propuesta |
|---|---|---|
| Coste por ciudadano atendido y año (pico 25.000/mes) | ~8,3 € | ~0 € marginal |
| Coste marginal del ciudadano 50.001 | ~0,69 €/mes de licencia ($0,75) | céntimos de cómputo |
| Coste por 100.000 usuarios y mes (licencia) | ~46.000 € (Tier 3) | 0 € |

La fila que importa es la del **coste marginal**. Es la que determina si el sistema puede
crecer sin renegociar.
## 19. Coste de salida

Se propone tratarlo como **indicador del proyecto**, medido y publicado, no como un
principio declarado. Un sistema que no puede demostrar cuánto cuesta abandonarlo no ha
demostrado ser portable.

### 19.1 Definición operativa

> **Coste de salida** = tiempo y dinero necesarios para que otro proveedor, o el propio
> organismo, ponga el sistema en funcionamiento sobre otra infraestructura, con todos
> los datos, sin la colaboración del proveedor saliente.

La última condición es la que da valor a la métrica. Cualquier migración es fácil si el
que se va colabora.

### 19.2 Salida de la arquitectura propuesta

| Activo | Mecanismo | Estimación |
|---|---|---|
| Datos | `pg_dump` / `pg_restore`, formato abierto y documentado | Horas |
| Documentos | Copia entre almacenes compatibles con S3 | Horas a días según volumen |
| Aplicación | Imagen de contenedor OCI + código fuente | Inmediato |
| Configuración | Variables de entorno documentadas | Inmediato |
| Infraestructura | Infraestructura como código + `compose` para entorno local | Días |
| Conocimiento | Documentación de arquitectura, decisiones y ejecución | Ya escrito |
| Integraciones | Adaptadores con sus pruebas; sólo cambian los certificados | Días |

**Objetivo declarado y verificable: sistema operativo en un proveedor distinto en menos
de una semana de trabajo de un equipo pequeño.**

**Y la forma de demostrarlo, que es lo que lo convierte en ingeniería:** el ensayo
trimestral de restauración (§15.4) es el mismo procedimiento que una migración. Si se
ensaya la recuperación, se está ensayando la salida. No hace falta un simulacro adicional.

**Restricción de diseño que se deriva:** no usar ninguna capacidad exclusiva de un
proveedor sin adaptador. Ni colas gestionadas propietarias, ni funciones específicas, ni
bases de datos con extensiones no estándar. Si en algún momento se usa una, debe existir
su adaptador alternativo probado.

### 19.3 Salida de la arquitectura actual

`[ESTIMACIÓN, no verificable desde fuera]`

| Activo | Mecanismo | Dificultad |
|---|---|---|
| Datos de Dataverse | Exportación por API o servicios de exportación | Media: se obtienen los datos; **la semántica del esquema hay que reconstruirla** |
| Flujos de Power Automate | **No hay equivalente** | **Alta**: hay que leer cada flujo y reimplementarlo |
| Reglas de negocio de la plataforma | **No hay equivalente** | Alta |
| Configuración del portal | **No hay equivalente** | Media |
| Aplicación JavaScript | Código propio | **Baja**, es lo más portable del sistema |
| Permisos y roles | Configuración propietaria | Media |
| Conocimiento del equipo | Específico del producto | Media |

**Estimación honesta: meses de trabajo, no días.** No porque la plataforma sea mala, sino
porque una parte de la lógica del sistema vive en formatos que sólo esa plataforma
ejecuta. Es la consecuencia natural de una elección legítima, y hay que contabilizarla
al tomarla, no al abandonarla.

### 19.4 Métrica publicable

| Indicador | Propuesta | Actual |
|---|---|---|
| Tiempo hasta funcionar en otro proveedor | Objetivo: < 1 semana | Estimado: meses |
| Formatos propietarios sin equivalente | 0 | Flujos, reglas, configuración de portal |
| ¿Puede el organismo hacerlo sin el proveedor? | Sí, por diseño | Dudoso |
| ¿Se ensaya periódicamente? | Sí, trimestral | Desconocido |

---

## 20. Análisis de dependencia

### 20.1 Los cuatro tipos, y cuál importa

| Tipo | Actual | Propuesta |
|---|---|---|
| **De plataforma**, el software sólo se ejecuta ahí | Alta: Power Pages y Dataverse | **Ninguna**: contenedores estándar |
| **De datos**, el modelo sólo se entiende ahí | Media-alta | **Ninguna**: esquema SQL documentado |
| **Económica**, el precio lo fija otro | **Alta**: tarifa por usuario ciudadano | Baja: infraestructura en mercado competitivo |
| **De competencia**, sólo ciertos perfiles pueden mantenerlo | Media-alta: perfiles certificados | Media: perfiles web generales |

**La dependencia económica es la decisiva**, porque es la única que no se resuelve con
trabajo. Las otras tres se resuelven pagando una migración; ésta sólo se resuelve
habiéndola evitado.

### 20.2 El error que este informe no comete

Sustituir Azure por otro proveedor de nube no elimina la dependencia: la traslada. Por
eso el criterio no es "no usar Microsoft", sino:

> **El sistema debe poder ejecutarse en cualquier lugar que ofrezca ejecución de
> contenedores, PostgreSQL y almacenamiento compatible con S3.**

Los tres son estándares con múltiples implementaciones independientes y mercado
competitivo. Esa es la definición operativa de portabilidad que se adopta, y se verifica
en integración continua ejecutando las pruebas contra dos implementaciones de
almacenamiento distintas.

De hecho, la propuesta de este informe **puede desplegarse sobre Azure**, y el modelo de
coste de §18 lo hace deliberadamente. La diferencia no es dónde se ejecuta. Es que puede
dejar de ejecutarse ahí.

### 20.3 Riesgo de discontinuidad, en ambos lados

Este informe se obliga a la simetría:

| Riesgo | Actual | Propuesta |
|---|---|---|
| Cambio unilateral del modelo de precio | **Documentado**: 3 modelos en 7 años | No aplica a licencias; sí a infraestructura, en mercado competitivo |
| Retirada del producto | Posible; hay precedentes en el catálogo del fabricante | El código sigue siendo ejecutable y bifurcable (licencia MIT) |
| Cambio de gobernanza del proyecto libre | No aplica | **Real**: Laravel recibió 57 M$ de capital riesgo en 2024 |
| Abandono por la comunidad | Bajo | Bajo, pero no nulo. Precedente: HHVM en Wikimedia |

**La diferencia no es que un lado tenga riesgo y el otro no.** Es qué opciones quedan
cuando el riesgo se materializa: con software libre bajo licencia permisiva, el código
existente sigue siendo utilizable; con una licencia de plataforma, no hay esa salida.
## 21. Revisión adversarial

Dos revisores. No se les permite argumentar de forma superficial y no se les concede la
última palabra a ninguno.

---

### Ronda 1, El arquitecto enterprise ataca

**E:** Vuestra propuesta ignora por qué existen estas plataformas. No compráis software:
compráis *transferencia de riesgo*. Cuando Dataverse tiene una vulnerabilidad, la parchea
Microsoft esa noche. Cuando la tenga vuestro monolito, ¿quién la parchea? ¿Y a las tres de
la madrugada de un sábado de agosto?

**E:** El pliego exige **ENS de nivel alto certificado, ISO 27001, 27017 y 27018 al
licitador**. Eso no lo tiene un equipo pequeño. Lo tiene una organización con un
departamento de cumplimiento. Vuestra arquitectura puede ser preciosa y ser
**inadjudicable**.

**E:** Habláis de catorce módulos SAP y una decena de servicios de la AGE. Un integrador
grande tiene gente que ya ha hecho eso otras veces. Vosotros vais a descubrirlo. La curva
de aprendizaje se paga en retrasos, y un retraso en una convocatoria de vivienda tiene
coste político y humano.

**E:** Y vuestro propio análisis reconoce que **a cuatro años, con uso bajo, no ahorráis
nada**. Estáis proponiendo asumir un riesgo de ejecución enorme por un ahorro que sólo
aparece si se cumplen vuestras hipótesis de uso.

---

### Ronda 1, El ingeniero minimalista responde

**M:** Acepto lo del ENS de nivel alto. Es el mejor argumento que has hecho y no lo voy a
esquivar: **una arquitectura no es adjudicable por sí sola**. Lo que propongo no es que lo
mantenga un equipo de tres personas en un altillo: es que **quien lo mantenga, con
certificación o sin ella, no esté atado a un fabricante**. La certificación es un
requisito del proveedor. La portabilidad es una propiedad del sistema. Son cosas
distintas y las estás mezclando.

**M:** Sobre los parches: llevas razón en que el fabricante parchea su plataforma. Pero
mira lo que ya está pasando aquí. **El portal ciudadano es una aplicación JavaScript a
medida de 1,33 MB.** Ese código lo parchea el integrador, no Microsoft. La transferencia
de riesgo que describes cubre la plataforma, no cubre la aplicación, y la aplicación es
donde está la lógica.

**M:** Sobre el soporte: el propio pliego contrata **lunes a viernes de nueve a seis, sin
compromiso de disponibilidad y sin penalización por indisponibilidad**. No estamos
comparando contra soporte veinticuatro por siete. Estamos comparando contra horario de
oficina. Si el organismo quiere veinticuatro por siete, hay que contratarlo y pagarlo, y
eso vale igual en las dos arquitecturas.

**M:** Sobre SAP y la AGE: de acuerdo en la experiencia previa, y por eso mi informe
clasifica esas integraciones como **complejidad intrínseca** y **no reclama ningún ahorro
en esa partida**. No he dicho que integrar con SAP sea barato. He dicho que cuesta lo
mismo desde Laravel que desde Dynamics.

---

### Ronda 2, El enterprise afina

**E:** Entonces reconoces que la mayor parte del coste es igual. ¿Qué queda de tu tesis?

**E:** Y hay algo que no has respondido: **el modelo de datos común**. Dataverse te da
auditoría, control de acceso por registro y relaciones ya modeladas. Tú vas a escribir eso
a mano. Cada línea que escribes es una línea que puedes escribir mal. En un sistema con
datos de categoría especial, escribir a mano el control de acceso es exactamente donde se
producen las brechas.

**E:** Y otra: el pliego pide **microservicios** en el perfil del arquitecto y **React,
Angular o Vue** en el de frontend. Tu propuesta va contra las dos cosas.

---

### Ronda 2, El minimalista contraataca

**M:** Queda lo que separa a las dos arquitecturas cuando todo lo demás es igual:
**una licencia que escala con el número de ciudadanos atendidos, y un coste de salida de
meses en lugar de días.** Eso es lo que queda, y no es poco: es la diferencia entre tener
un sistema y alquilarlo.

**M:** Tu argumento del control de acceso es bueno y merece una respuesta seria, no un
eslogan. Sí, escribir autorización a mano es riesgo. Pero fíjate en lo que estás
defendiendo: dices que la plataforma da control de acceso por registro **y**, a la vez, el
portal es una aplicación a medida que consume la API de datos. Entonces la autorización
está configurada en un sitio, consumida desde otro, y **auditar que la configuración es
correcta es más difícil, no más fácil**, que leer una política de veinte líneas en un
repositorio con historial. Mi propuesta es verificable con revisión de código y con
pruebas automáticas. La tuya se verifica revisando pantallas de configuración.

**M:** Sobre los microservicios en el pliego: eso es un requisito **de perfil profesional**,
no de solución. El pliego pide un arquitecto que sepa de microservicios, no un sistema de
microservicios. Y sobre React: mi propuesta admite islas de JavaScript donde aportan
valor. Lo que rechazo es entregar 1,33 MB para pintar un listado de viviendas, que es
justamente lo que hoy penaliza el rendimiento y complica el cumplimiento de la WCAG 2.1
que el mismo pliego exige.

---

### Ronda 3, Donde ninguno cede

**E:** Sigo pensando que subestimas el riesgo de ejecución y sobreestimas la capacidad de
una administración de sostener software propio durante diez años. Las administraciones
compran plataformas porque **no quieren ser empresas de software**, y eso es una decisión
legítima, no un error técnico.

**M:** Y yo sigo pensando que un organismo que gestiona vivienda pública para cincuenta
mil personas **ya es**, quiera o no, responsable de un sistema de información crítico. La
pregunta no es si quiere serlo. Es si, cuando le suban la tarifa o retiren el producto,
tendrá alternativa. Hoy, según el propio pliego, **ni siquiera hay cláusula de propiedad
del código ni plan de reversión**.

---

### Qué queda en pie después del debate

**Argumentos del enterprise que sobreviven y obligan a corregir la propuesta:**

1. **El ENS de nivel alto certificado es un requisito de la organización mantenedora**, no
   sólo del sistema. Una arquitectura portable en manos de un proveedor no certificable no
   sirve de nada. → §23.
2. **El riesgo de ejecución de las integraciones es real** y la experiencia previa vale
   dinero. → §23: el plan debe empezar por los adaptadores más inciertos.
3. **La transferencia de riesgo tiene valor** y hay que reponerla explícitamente con
   contrato de soporte, guardia y seguro, no ignorarla. → §18 y §23.
4. **Con uso bajo de plataforma, el ahorro no está demostrado.** → §18, ya reconocido.

**Argumentos del minimalista que sobreviven:**

1. La complejidad intrínseca es idéntica en ambas y no justifica la elección de plataforma.
2. La transferencia de riesgo no cubre la aplicación a medida, que es donde está la lógica.
3. El nivel de servicio realmente contratado es de horario de oficina: la comparación debe
   hacerse contra eso.
4. La restricción de Red SARA es simétrica.
5. El coste de salida y el acoplamiento entre uso ciudadano y factura son diferencias
   reales, estructurales y no compensables.

**Argumento que ninguno de los dos puede cerrar, y que se traslada a §24:** cuánto vale,
en euros, la transferencia de riesgo al fabricante. Es una decisión del organismo sobre su
propia tolerancia al riesgo, no una cuestión técnica. Este informe no puede -ni debe-
resolverla por él.
## 22. Riesgos de nuestra propia arquitectura

Ataque deliberado a la propuesta. Cada riesgo lleva probabilidad, impacto y mitigación -o
la admisión de que no la tiene.

| # | Riesgo | Prob. | Impacto | Mitigación |
|---|---|---|---|---|
| 1 | **Factor autobús.** Dos o tres personas concentran el conocimiento y se van. | Alta | Crítico | Documentación de decisiones obligatoria; sin código sin revisar; rotación de responsabilidades; el propio informe es parte de la mitigación. **No se elimina.** |
| 2 | **El proveedor no puede certificarse en ENS nivel alto.** | Media | **Bloqueante** | Exigir la certificación al proveedor, no a la arquitectura. Puede requerir una UTE o un proveedor de operación certificado. **Es el riesgo más serio de todos.** |
| 3 | **Guardia 24×7 inexistente.** | Media | Alto | Contratar operación gestionada con guardia. **Cuesta dinero y está en §18.** No se puede resolver con voluntarismo. |
| 4 | **Retraso en el alta administrativa** en Cl@ve, PID, DEHú, SARA. | **Alta** | Alto | Iniciar los trámites el primer día; construir con adaptadores simulados; el diseño lo permite (§13.1). |
| 5 | **Subestimación del esfuerzo de SCSP y firma.** | Alta | Alto | Prototipar esos dos adaptadores **antes** de comprometer plazos. Son los que más incertidumbre tienen. |
| 6 | **Dependencia de Laravel** y de su nueva gobernanza con capital riesgo. | Baja | Medio | Licencia MIT; uso convencional del framework; ningún producto comercial del ecosistema en ruta crítica. |
| 7 | **PHP no figura en los perfiles del pliego.** |, | Medio | Real. La tesis del informe no depende del lenguaje (§11.4): sobrevive en Java y en .NET. |
| 8 | **Erosión de la modularidad** con los años. | Alta | Medio | Prueba de arquitectura que rompe la integración continua (§38.4). |
| 9 | **Deuda de accesibilidad** tras la entrega. | Media | Alto | Comprobaciones automáticas en integración continua + auditoría externa periódica. Obligación legal. |
| 10 | **El sistema económico SAP impone su ritmo.** | Alta | Medio | Adaptador asíncrono con bandeja de salida; el procedimiento no se bloquea por SAP. |
| 11 | **Cambio normativo** que altere baremo o procedimiento. | **Segura** | Medio | Reglas versionadas (§12.3). El cambio se paga igual en cualquier arquitectura. |
| 12 | **Concentración en un proveedor de nube pese a la portabilidad.** | Media | Medio | Verificar la portabilidad en integración continua contra dos implementaciones, no declararla. |
| 13 | **La operación se descuida** por parecer "sencilla". | **Alta** | Alto | Es el riesgo cultural. Se combate con ensayo trimestral de restauración y con alertas accionables. |
| 14 | **Nadie licita.** La arquitectura es correcta y no hay quien la mantenga en el mercado español. | Media | Alto | Publicar el código; usar tecnologías con mercado amplio; no exigir experiencia en producto propietario. |

**Los riesgos 1, 2, 3 y 13 no se eliminan: se financian.** Cualquier propuesta que los
presente como resueltos está mintiendo. Su coste está en §18 y es una de las razones por
las que el CAPEX estimado es alto.

---

## 23. Arquitectura corregida después del red team

Los cambios respecto de §9, con su motivo:

**1. La operación se contrata; no se improvisa.** Se añade explícitamente un proveedor de
operación gestionada con certificación ENS de nivel alto y guardia 24×7. **Es un
componente de la arquitectura, no una nota al pie.** Responde a los riesgos 2 y 3, que son
los que podían invalidar toda la propuesta.

**2. Modo degradado obligatorio en toda verificación externa.** Cada puerto declara y
prueba su comportamiento cuando el servicio no responde: la verificación automática cae a
verificación documental asistida por un gestor. El procedimiento nunca se detiene por un
servicio ajeno. Responde al riesgo 4.

**3. Los adaptadores inciertos se prototipan primero.** SCSP y firma electrónica se
abordan en la primera iteración, contra los entornos de pruebas oficiales, antes de
comprometer plazos. Responde al riesgo 5.

**4. La pasarela SARA es un componente explícito**, con su propio ciclo de vida y su
propio plan -convenio NubeSARA o punto de presencia propio-. Deja de ser una flecha en un
diagrama.

**5. Prueba de arquitectura en la integración continua.** La modularidad se verifica
automáticamente o no existe. Responde al riesgo 8.

**6. Prueba de portabilidad en la integración continua.** Las pruebas se ejecutan contra
dos implementaciones de almacenamiento de objetos. La portabilidad se demuestra, no se
declara. Responde al riesgo 12.

**7. Cláusulas contractuales como parte de la arquitectura.** Propiedad del código
fuente, publicación en el directorio de aplicaciones reutilizables conforme al artículo
157 de la Ley 40/2015, plan de reversión documentado y ensayado, y titularidad de
dominios, secretos y copias en el organismo. **El pliego actual no contiene ninguna de
estas cláusulas** (§3, adenda). Es el cambio más barato de todos y el de mayor efecto.

**8. Lo que NO se ha cambiado, y por qué.** Ninguna crítica del red team ha justificado
introducir microservicios, un bus de eventos, un motor de procesos, un motor de búsqueda
externo ni Redis. Todos los ataques que prosperaron fueron sobre **organización,
operación y contrato**, no sobre la forma del sistema. Es un resultado significativo y se
declara como tal.

---

## 23 bis. Propiedad y gobierno del sistema

Derivado del hallazgo de §3: los pliegos no contienen cláusula de propiedad intelectual,
cesión de código ni reversibilidad.

**Lo que el organismo debería poseer, en cualquier arquitectura:**

| Activo | Situación deseable |
|---|---|
| Código fuente y su historial | Repositorio propiedad del organismo, con acceso del proveedor |
| Documentación de arquitectura y decisiones | En el mismo repositorio |
| Esquema de base de datos y migraciones | En el repositorio; el esquema es documentación |
| Pruebas automatizadas | En el repositorio. Sin pruebas no hay transferencia real |
| Definición de la integración continua y del despliegue | En el repositorio |
| Infraestructura como código | En el repositorio |
| Titularidad de dominios y certificados | Del organismo |
| Custodia de secretos | Del organismo; el proveedor accede, no posee |
| Datos y copias de seguridad | Del organismo, en cuentas del organismo |
| Plan de reversión | Documentado y **ensayado**, con acta |

**Ningún proveedor debe ser requisito operativo.** El criterio de verificación es directo
y comprobable: *si mañana el proveedor cesa su actividad, ¿puede el organismo poner el
sistema en marcha en otro sitio con lo que tiene en su poder?* Si la respuesta es no, la
propiedad es nominal.

El artículo 157 de la Ley 40/2015 establece la reutilización de aplicaciones entre
administraciones a través del directorio correspondiente. Publicar el código no es sólo
buena práctica: es coherente con el régimen legal aplicable.

---

## 24. Decisiones abiertas

Las que dependen de información que el organismo tiene y nosotros no. Se listan sin
resolver, porque resolverlas por conjetura sería el peor defecto que podría tener este
informe.

| # | Decisión | Por qué es necesaria | Quién puede cerrarla |
|---|---|---|---|
| 1 | **¿Qué capacidades de Power Pages se usan hoy realmente**, más allá de alojamiento, autenticación y exposición de datos? | Determina cuánto de la plataforma se está pagando sin usar (§6.3) | El organismo, en pocas horas |
| 2 | **¿Cuál es el coste real de licencias** ya contratado o previsto por la DGRCC? | Sin él, el TCO es un rango amplio | El organismo |
| 3 | **¿Cuál es el pico mensual real de ciudadanos autenticados?** | Es la variable que domina el coste de plataforma | Analítica del portal |
| 4 | **¿El baremo pondera datos de categoría especial** (discapacidad, violencia de género)? | Determina cifrado, control de acceso y la evaluación de impacto | Bases de la convocatoria |
| 5 | **¿Tiene SEPES punto de presencia en Red SARA o convenio NubeSARA?** | Condiciona el plan de integración | El organismo |
| 6 | **¿Cómo consume hoy el sistema la Plataforma de Intermediación?** | Verificaría la simetría argumentada en §13.3 | El organismo |
| 7 | **¿Existe cláusula de propiedad del código en el Anexo I** del contrato? | No estaba en PCAP ni en PPT | El organismo |
| 8 | **¿Hay equipo interno**, y en qué tecnología? | Puede cambiar la recomendación de lenguaje (§11.5) | El organismo |
| 9 | **¿Cuánto vale para el organismo la transferencia de riesgo** al fabricante? | El punto que la revisión adversarial no pudo cerrar | Dirección del organismo |
| 10 | **¿Es viable jurídicamente** que el organismo posea y publique el código? | Determina el modelo de gobierno | Asesoría jurídica |
| 11 | ¿Se exige firma electrónica del ciudadano, o basta Cl@ve con acuse? | Cambia sustancialmente el esfuerzo de §13 | Bases del procedimiento |
| 12 | ¿Cuántas viviendas y convocatorias hay realmente? | Confirmaría el dimensionado de §17 | El organismo |

---

## 25. Recomendación final

### ¿Podemos eliminar Power Pages?

**Sí.**

Las funciones que Power Pages presta a este sistema -alojamiento con protección
perimetral, autenticación federada y exposición autorizada de datos- son sustituibles por
componentes estándar cuyo coste y comportamiento son conocidos. El argumento decisivo no
es de coste: es que **el portal ciudadano ya está programado a medida** (§3, §6.3), de
modo que la principal justificación de una plataforma de desarrollo rápido no aplica al
caso observable.

Con una condición: la nueva solución debe **igualar o superar** la postura de seguridad de
fábrica observada en §3 -cabeceras, política de contenido, gestión de certificados-, y eso
exige trabajo explícito y sostenido.

### ¿Podemos eliminar el CRM comercial?

**Sí, para la gestión del expediente. Con una reserva expresa.**

Un procedimiento administrativo reglado no es una relación comercial. Modelarlo sobre un
producto pensado para ciclos de venta añade trabajo de adaptación y una segunda
representación del mismo expediente.

La reserva: si el organismo necesita capacidades genuinas de CRM -campañas, centro de
contacto, gestión comercial de suelos- eso debe evaluarse aparte y por sus propios
méritos. La conclusión es que **no debe ser el sistema de registro del expediente**, no
que no pueda existir para otra cosa.

Nota relevante: **el pliego no obligaba a un CRM comercial.** Admitía expresamente
"otras tecnologías […] siempre que demuestren interoperabilidad, escalabilidad, madurez,
durabilidad y compatibilidad". La arquitectura aquí propuesta era admisible.

### ¿Podemos operar con Laravel y PostgreSQL?

**Sí, con cinco condiciones que no son negociables.**

1. Que el proveedor de operación acredite **certificación ENS de nivel alto**, ISO 27001,
   27017 y 27018. Es requisito del pliego y no depende de la arquitectura.
2. Que exista **guardia 24×7 contratada**, con su coste explícito.
3. Que se resuelva la **conectividad con Red SARA** por convenio NubeSARA o por punto de
   presencia propio.
4. Que el organismo posea **código, datos, secretos y capacidad de reproducir la
   infraestructura**, con plan de reversión ensayado.
5. Que la **portabilidad y la modularidad se verifiquen automáticamente**, no se declaren.

Sin las cinco, la propuesta es peor que la actual. Con las cinco, es mejor por lo que se
argumenta en §18, §19 y §20.

**Y la advertencia final sobre el lenguaje:** la tesis no depende de Laravel. Sobrevive
íntegra en Java, en .NET y en Python. Laravel es la opción más eficiente para un equipo
pequeño; si el organismo tiene equipo consolidado en otra tecnología, **debe usar la
suya**. Lo que no debe hacer es interponer una plataforma propietaria entre el ciudadano y
su propio expediente sin haberlo justificado.

### ¿Qué tercera pieza es estrictamente imprescindible?

Además de la aplicación y de PostgreSQL: **el almacenamiento de objetos**. Es la única
tercera pieza obligatoria, y lo es porque guardar binarios en la base de datos degrada
copias, restauración y réplica sin aportar nada.

**Y una cuarta, que no es software:** la **pasarela hacia Red SARA**. No la impone nuestra
arquitectura, la impone el ecosistema, y afecta por igual a cualquier solución.

Todo lo demás -Redis, motor de búsqueda, bus de eventos, motor de procesos, orquestador de
contenedores, microservicios- **no ha logrado justificar su existencia** en ninguna
sección de este informe.

### Stack mínimo defendible

```text
Aplicación        Laravel (PHP), monolito modular, HTML renderizado en servidor
                  con islas de JavaScript acotadas
Datos             PostgreSQL 17 con pg_trgm
                  (sesiones, colas, cerrojos, búsqueda, outbox y auditoría incluidos)
Documentos        Almacenamiento de objetos compatible con S3
Empaquetado       Imagen OCI
Perímetro         CDN + WAF + TLS
Correo            Servicio de correo transaccional
Observabilidad    Registros estructurados + métricas + errores + sondeo externo
Integración       Adaptadores propios; pasarela SARA para PID y SIR
Infraestructura   Infraestructura como código
```

Nueve elementos. Ninguno propietario. Ninguna licencia por usuario.

### ¿Qué partes del contrato explican legítimamente un coste elevado?

1. Consultoría de procesos sobre un procedimiento administrativo real.
2. Integración con catorce módulos SAP, SIGES.Net, SQL Server y Alfresco.
3. Integración con una decena de servicios de la AGE, con sus altas y sus certificados.
4. Conformidad ENS de **nivel alto**, con auditoría acreditada.
5. Firma electrónica y validación conforme a la política de firma.
6. Accesibilidad WCAG 2.1 con revisión periódica.
7. Protección de datos con evaluación de impacto y categoría especial.
8. Cuatro años de mantenimiento evolutivo, con 9.000 horas comprometidas.
9. Formación e implantación en un organismo en construcción.
10. Migración de datos y convivencia con sistemas existentes.

**Esta lista es larga a propósito.** Un análisis serio tiene que reconocer que la mayor
parte del importe adjudicado corresponde a trabajo real sobre complejidad real. **1.184.998 €
por cuatro años (296.250 € anuales) no es un precio desproporcionado para este alcance.**

### ¿Qué partes parecen consecuencia de la arquitectura elegida?

1. **El coste de licencias, que es adicional al contrato** y escala con el número de
   ciudadanos que usan el servicio.
2. La dependencia de perfiles certificados en un producto concreto.
3. La lógica repartida entre código y flujos de la plataforma.
4. El coste de salida, estimado en meses.
5. La entrega de 1,33 MB de JavaScript para contenido esencialmente estático, con su
   impacto en rendimiento y en accesibilidad.
6. La posible duplicación entre el modelo del CRM y el del expediente `[INFERENCIA]`.

### ¿Qué no sabemos todavía?

1. El coste real de las licencias ya contratadas o previstas.
2. El pico mensual real de ciudadanos autenticados.
3. Qué capacidades de la plataforma se usan realmente.
4. Cómo se resuelve hoy la conectividad con Red SARA.
5. Si existe cláusula de propiedad del código en el Anexo I del contrato.
6. El comportamiento y el rendimiento de la zona autenticada.
7. La composición y la tecnología del equipo interno del organismo.
8. Si el baremo pondera datos de categoría especial.
9. El número real de viviendas y convocatorias.
10. Cuánto vale para el organismo la transferencia de riesgo al fabricante.

**Ninguna conclusión de este informe depende de estas incógnitas.** Todas ellas
modificarían magnitudes, no el sentido del análisis. Se declaran para que quien lo revise
sepa exactamente dónde están sus límites.
## Anexo A. Estrategia de pruebas

La forma de la pirámide se deriva de dónde está el riesgo, no de una convención.

| Nivel | Qué cubre | Por qué ahí |
|---|---|---|
| **Unitarias** | Reglas de elegibilidad, baremación, cálculo de ingresos, transiciones de estado | Es donde un error deniega un derecho. Densidad máxima, casos límite exhaustivos. |
| **De funcionalidad** | Recorridos completos por HTTP: presentar solicitud, subir documento, adjudicar | Prueban el sistema como se usa, incluidas autorización y transacción |
| **De integración** | Cada adaptador contra respuestas grabadas del servicio real | Detecta cambios de contrato del servicio externo |
| **De contrato** | Que el doble de prueba y el adaptador real se comportan igual | Sin esto, los dobles mienten |
| **De arquitectura** | Que ningún módulo importa clases internas de otro | La modularidad se verifica o no existe (§38) |
| **De accesibilidad** | Comprobación automática en cada página, más revisión manual con teclado y lector | Obligación legal |
| **De extremo a extremo** | Sólo el recorrido crítico completo | Caros y frágiles: pocos y bien elegidos |
| **De carga** | Escenarios de §17, antes de cada convocatoria | Verifica el dimensionado con datos |

**Regla que gobierna todo lo anterior:** toda regla que determine un derecho lleva prueba
con sus casos límite, y el cambio de una regla obliga a añadir la prueba del caso nuevo
antes que el código. No es dogma de metodología: es que un error en el baremo es un error
con nombre y apellidos.

---

## Anexo B. Rendimiento y capacidad

### Objetivos, y por qué son alcanzables

| Métrica | Objetivo | Justificación |
|---|---|---|
| Lighthouse Rendimiento | ≥ 95 | Alcanzable con HTML servido y JavaScript acotado |
| Lighthouse Accesibilidad | ≥ 95 | Necesario; la herramienta automática cubre ~30 % de la WCAG, el resto es manual |
| LCP | < 1,5 s | Con HTML servido, el contenido principal llega en la primera respuesta |
| INP | < 200 ms | Poca interactividad de cliente |
| CLS | < 0,1 | Reservar espacio para imágenes |
| TTFB | < 300 ms | Comparable a los 245 ms observados |
| JavaScript entregado | **< 100 KB** en la portada | Frente a los 1.333.490 bytes observados |
| Peticiones para primera pintura | < 15 | |
| Consultas por página | < 20, sin N+1 | Verificado automáticamente |
| Latencia p95 de página de catálogo | < 300 ms | |
| Latencia p99 | < 800 ms | |

**La métrica que resume el argumento es la del JavaScript entregado.** Si el demostrador
sirve el mismo contenido con menos de 100 KB frente a 1,33 MB, la afirmación de §10.2 deja
de ser una preferencia arquitectónica y pasa a ser un hecho medido.

### Escenarios de carga

Los definidos en §17: día normal, publicación de convocatoria, cierre de plazo,
publicación de resultados, consulta masiva de catálogo. Para cada uno se medirá
peticiones por segundo, CPU, memoria, conexiones a la base de datos, latencias y errores.

**No se escalará nada hasta que un dato lo justifique.**

---

## Anexo C. Demostradores previstos

**No forman parte de esta fase.** Se enuncian para fijar qué tendrá que probar el
experimento, de modo que no se pueda mover la portería después.

**Demostrador A, portal público.** Catálogo, promociones, convocatorias, filtros, fichas,
imágenes, mapa, requisitos, diseño adaptable y accesibilidad, con datos públicos. Objetivo:
medir cuánto código e infraestructura requiere realmente la experiencia pública.

**Demostrador B, aplicación transaccional.** Autenticación, unidad de convivencia,
solicitud, documentos, verificación, cambio de estado, adjudicación y notificación. Las
integraciones oficiales se implementan con adaptadores simulados; **la lógica de dominio
es real**, incluidas transacciones, autorización y auditoría.

**Regla de honestidad del experimento:** el demostrador no puede omitir seguridad,
accesibilidad ni auditoría para parecer más pequeño. Un demostrador que las omitiera
demostraría algo distinto de lo que se pretende.

---

## Anexo D. Comparador requisito por requisito

| Requisito | CASA 47 actual | Arquitectura propuesta | Dependencias | Coste relativo | Portabilidad |
|---|---|---|---|---|---|
| Autenticación de ciudadanos | Power Pages + Dataverse | Adaptador Cl@ve (SAML 2.0) en la aplicación | Ninguna nueva | Menor recurrente | Alta |
| Autenticación de personal | Plataforma | Aplicación + segundo factor | Ninguna | Menor | Alta |
| Autorización | Permisos declarativos de la plataforma | Roles y políticas en código, revisables y probadas | Ninguna | Igual | Alta |
| Almacén de datos | Dataverse | PostgreSQL | Ninguna | **Muy menor** | **Alta** (`pg_dump`) |
| Portal público | Power Pages + SPA de 1,33 MB | Blade con HTML servido | Ninguna | Menor | Alta |
| Catálogo y búsqueda | Funcionalidad de plataforma | PostgreSQL con `tsvector` y `pg_trgm` | Ninguna | Menor | Alta |
| Mapa | MapLibre + OpenFreeMap | **Igual** | Igual | Igual | Alta |
| Documentos | Capacidad de fichero de Dataverse | Almacenamiento S3 + metadatos | Ninguna | Menor | Alta |
| Automatización | Power Automate | Colas y eventos de dominio en el repositorio | Ninguna | Menor | **Alta** (hoy: nula) |
| Procedimiento | Configuración de plataforma | Máquina de estados en el dominio | Ninguna | Igual | Alta |
| Backoffice | CRM comercial | Misma aplicación, mismo modelo | Ninguna | Menor | Alta |
| Integración SCSP | A construir | A construir | **Red SARA (simétrica)** | **Igual** | Igual |
| Integración firma | A construir | A construir | @firma / FIRe | **Igual** | Igual |
| Integración SAP | A construir | A construir | SAP | **Igual** | Igual |
| Notificación fehaciente | A construir | A construir | DEHú | **Igual** | Igual |
| Perímetro y WAF | Azure Front Door | CDN + WAF equivalente | Proveedor de CDN | Igual | Alta |
| Alta disponibilidad | Gestionada por el fabricante | Dos instancias + base con HA | Proveedor | Mayor operación | Alta |
| Copias y recuperación | Gestionadas | Propias, con ensayo trimestral | Proveedor | Mayor operación | Alta |
| Conformidad ENS alto | Heredada de la plataforma + del sistema | **Del sistema y del operador** | Auditor acreditado | **Mayor** |, |
| Auditoría | Auditoría de Dataverse | Tabla inmutable encadenada por hash | Ninguna | Igual | Alta |
| Accesibilidad | Parcial, sin nivel declarado | WCAG 2.1 AA verificada | Ninguna | Menor de mantener | Alta |
| **Licencia por ciudadano** | **Escala con el uso** | **0 €** | Ninguna | **Muy menor** |, |
| **Coste de salida** | Meses | Días | Ninguna | **Muy menor** |, |

**Las filas que deciden son las tres últimas y las cuatro marcadas "Igual".** Las
"Igual" demuestran que este informe no reclama ahorros que no existen. Las últimas
demuestran dónde está la diferencia real.

---

## Anexo E. Métricas que se publicarán

Al terminar el experimento, y no antes:

```text
Líneas de código de producción
Dependencias directas y transitivas
Contenedores en producción
Bases de datos
Servicios distintos que operar
Coste mensual de infraestructura
Coste de licencias de aplicación
Tiempo de despliegue
Tiempo de restauración de copia (medido)
Tiempo de migración a otro proveedor (medido)
Lighthouse: rendimiento y accesibilidad
JavaScript entregado en la portada
Capacidad de carga sostenida
Coste por usuario
Coste por solicitud tramitada
Coste por cada 100.000 usuarios
```

**Todas serán reproducibles**: repositorio público, infraestructura como código y método
de medición documentado. Una métrica que no se puede reproducir no sirve para nada en una
auditoría.

---

## Anexo F. Fuentes

**Documentación contractual (fuente primaria verificada)**

- BOE-B-2025-28508, anuncio de licitación, expediente 132019. Valor estimado
  1.880.949,00 € sin IVA.
- BOE-B-2026-771, formalización. Adjudicataria EY Transforma Servicios de Consultoría,
  S.L. (B88428404), 1.184.998,00 € sin IVA, 26/11/2025, 8 ofertas presentadas (2 PYME).
- **PCAP del expediente 132019**, 46 páginas, pliego de cláusulas administrativas
  particulares.
- **PPT del expediente 132019**, 40 páginas, pliego de prescripciones técnicas.
- Memoria justificativa del expediente 132019.
- Obtenidos del fichero de datos abiertos de sindicación de la Plataforma de Contratación
  del Sector Público `licitacionesPerfilesContratanteCompleto3_202507.zip`, entrada con
  `ContractFolderID=132019`, nodos CODICE `LegalDocumentReference` y
  `TechnicalDocumentReference`. Identidad de cada PDF verificada por su encabezado antes
  de citarlo.

**Marco normativo**

- Real Decreto 311/2022, Esquema Nacional de Seguridad (BOE-A-2022-7191).
- Real Decreto 1112/2018, accesibilidad de sitios web del sector público.
- Ley 40/2015, artículo 157, reutilización de aplicaciones entre administraciones.
- BOE-A-2015-14215, prescripciones técnicas de Cl@ve.
- BOE-A-2017-8018, condiciones técnicas de conexión a la Red SARA.
- BOE-A-2011-13173, norma técnica de interoperabilidad de conexión a la Red SARA.
- BOE-A-2024-26902, BOE-A-2024-25018, BOE-A-2023-23282, convenios NubeSARA.

**Tarifas**

- Microsoft Power Platform Licensing Guide, edición de diciembre de 2025, pp. 16-17 y 21.
- Páginas oficiales de precios de Power Pages, Dynamics 365 y Power Automate,
  consultadas el 8 de septiembre de 2026.
- Azure Retail Prices API (`prices.azure.com/api/retail/prices`), consultada el
  8 de septiembre de 2026.

**Observación técnica del portal**

- Reconocimiento pasivo de `portal.casa47.es`, 8 de septiembre de 2026. Método y
  evidencia completa en `docs/research/01-arquitectura-observable.md` y
  `docs/research/02-mapa-funcional.md`.

**Informes de investigación completos**

| Fichero | Contenido |
|---|---|
| `docs/research/01-arquitectura-observable.md` | DNS, TLS, cabeceras, cookies, frontend, terceros, rendimiento |
| `docs/research/02-mapa-funcional.md` | Rutas verificadas, criterios de elegibilidad, accesibilidad |
| `docs/research/03-contrato-y-pliegos.md` | Expediente, adjudicación y adenda con los pliegos completos |
| `docs/research/04-costes-licencias-infra.md` | Tarifas verificadas de licencias e infraestructura |
| `docs/research/05-integraciones-age-y-normativa.md` | Cl@ve, SCSP, DEHú, SIR, Catastro, ENS, y adenda sobre Red SARA |
| `docs/research/06-evidencia-stack-y-precedentes.md` | Sostenibilidad del stack, talento, capacidad técnica, precedentes |

---

## Condición de parada

**Esta fase termina aquí. No se escribe código.**

El documento se entrega para revisión conjunta de las decisiones arquitectónicas. Sólo
cuando la arquitectura quede aprobada se pasará a la fase 2 (diseño funcional y modelo de
datos), después a la 3 (diseño de interfaz), la 4 (implementación del demostrador) y la 5
(medición y comparación económica).

La carga de la prueba es nuestra. Este documento no demuestra la tesis: **establece las
condiciones bajo las cuales podrá demostrarse con software funcionando y métricas
reproducibles.**
