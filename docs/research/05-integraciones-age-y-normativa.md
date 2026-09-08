# Integraciones con la Administración Pública española y marco normativo

Fecha de investigación: 2026-09-08. Fuentes: administracionelectronica.gob.es (PAe/CTT), BOE, clave.gob.es, catastro.hacienda.gob.es, facturae.gob.es, GitHub oficial ctt-gob-es. Metodología: búsqueda web dirigida a fuentes oficiales; no se ha accedido a áreas restringidas a organismos dados de alta (ver advertencia de barrera de entrada en cada ficha donde aplica).

**Advertencia metodológica**: varias de estas plataformas NO publican documentación técnica completa (WSDL, esquemas XSD, manuales de integración detallados) en abierto; sólo la exponen tras la firma de un convenio de adhesión y alta en el Centro de Atención a Usuarios correspondiente. Esto en sí mismo es un hallazgo relevante para la estimación de esfuerzo: **no se puede diseñar el adaptador final sin darse de alta primero**, sólo aproximar el contrato de la interfaz.

---

## 1. Cl@ve (identidad)

- **Qué hace**: plataforma común de identificación, autenticación y firma electrónica del sector público estatal, actúa de intermediario (broker SAML) entre el proveedor de servicio (la sede electrónica del organismo) y los proveedores de identidad (DNIe/certificado, Cl@ve PIN, Cl@ve Permanente, Cl@ve Móvil, eIDAS de otros países UE).
- **Protocolo**: SAML 2.0 como mecanismo de federación entre la sede y el nodo Cl@ve (Proxy-Clave); no hay evidencia en fuentes oficiales de un endpoint OpenID Connect propio y documentado para integradores generales — lo que sí existe con OIDC en el ecosistema hispanohablante es *ClaveÚnica*, que es el sistema homólogo **de Chile** (wikiguias.digital.gob.cl), no debe confundirse con el Cl@ve español. **[NO VERIFICADO]** si Cl@ve España ofrece ya un flujo OIDC en producción para organismos: las fuentes oficiales localizadas (BOE-A-2015-14215, Resolución de 14/12/2015, prescripciones técnicas del sistema Cl@ve) describen el modelo SAML/federación; no se ha encontrado un manual técnico OIDC equivalente al de PID/SCSP. Recomendación: confirmarlo directamente con el CAU de Cl@ve antes de diseñar el adaptador.
  - Fuente niveles: la propia Cl@ve define "niveles de aseguramiento en la calidad de la autenticación (QAA)" según la clasificación de seguridad del servicio, siguiendo las recomendaciones del ENS (clave.gob.es/clave_Home).
- **Requisitos de alta**: la incorporación de un organismo se realiza mediante **convenio con el Ministerio de Hacienda y Función Pública** (o red autonómica equivalente, p.ej. Proxy-Clave de la Junta de Andalucía), que fija condiciones técnicas, económicas y organizativas — BOE-A-2015-14215 (Resolución de 14/12/2015, DTIC).
- **¿Exige Red SARA?**: no se ha localizado una declaración explícita oficial que diga "obligatorio estar en Red SARA para Cl@ve"; a diferencia de PID (ver §5), el acceso de un ciudadano a Cl@ve es vía Internet público (login.clave.gob.es); la integración organismo↔Cl@ve documentada en guías de terceros (Proxy-Clave Andalucía) es SAML sobre HTTPS estándar. **[NO VERIFICADO]** si el punto de metadatos/certificados de intercambio exige circuito SARA en todos los casos — depende del convenio firmado.
- **Estado actual (2026)**: Cl@ve Permanente y Cl@ve Móvil activas y en expansión (clave.gob.es/clave-movil); Cl@ve PIN se retiró progresivamente en favor de Cl@ve Permanente en los últimos años **[NO VERIFICADO exactitud de fecha de retirada, no confirmada en fuente oficial durante esta investigación]**.
- **SDK/librerías oficiales**: no se ha encontrado un SDK oficial multi-lenguaje publicado en abierto por Cl@ve; la integración se hace implementando un Service Provider SAML 2.0 estándar (librerías genéricas: `python3-saml`, `OneLogin`, `Spring Security SAML`, etc.), no un SDK propietario.
- **Esfuerzo estimado**: **medio**. Complejidad intrínseca (SAML es un protocolo maduro y bien soportado, la parte no trivial es el convenio administrativo, el certificado de organismo y el entorno de preproducción, no el código). Complejidad accidental: ausencia de documentación pública completa hasta el alta.
- **Fuentes**: [Cl@ve Móvil](https://clave.gob.es/clave-movil/que-es/aviso-clave-movil), [BOE-A-2015-14215](https://www.boe.es/buscar/act.php?id=BOE-A-2015-14215), [Guía Proxy-Clave (Junta de Andalucía)](https://desarrollo.juntadeandalucia.es/sites/default/files/2023-06/Gui%CC%81aIntegracio%CC%81nAplicacionesEnProxyClave-0100.pdf).

## 2. Firma electrónica: AutoFirma, @firma, FIRe, Cl@ve Firma

- **AutoFirma**: aplicación de escritorio (no un servicio web) que el ciudadano/funcionario instala localmente para firmar ficheros desde el navegador invocándola mediante un protocolo local (antiguo Miniapplet Java, sustituido por AutoFirma vía asociación de protocolo `afirma://`). Descarga oficial en firmaelectronica.gob.es.
- **@firma**: plataforma de validación y verificación de firmas y certificados electrónicos del sector público, componente de backend (no lo ve el ciudadano).
- **FIRe** ("Servicios de FIRma Electrónica"): solución integral, código abierto, que actúa de orquestador: unifica firma local (invocando AutoFirma/antiguo Miniapplet) y firma en la nube ("certificado centralizado") delegando en **Cl@ve Firma**, gestionado por la Dirección General de la Policía (DGP) y la Gerencia de Informática de la Seguridad Social (GISS). Repositorio oficial en GitHub: `ctt-gob-es/fire`.
- **Cl@ve Firma**: permite firmar sin certificado local, usando un certificado centralizado ("en la nube") ligado a la identidad Cl@ve del usuario, operable desde cualquier dispositivo con navegador — **sí es posible evitar AutoFirma en el lado del ciudadano** integrando FIRe y ofreciendo Cl@ve Firma como opción de firma, siempre que el usuario tenga certificado en la nube activado (requiere registro presencial u online equivalente al de Cl@ve Permanente/Móvil).
- **Formatos**: XAdES, PAdES, CAdES son los formatos estándar soportados por el ecosistema @firma/AutoFirma/FIRe; el sellado de tiempo se realiza contra **TS@** (plataforma de sellado de tiempo del sector público).
- **Validación de firma**: se realiza contra la plataforma @firma (backend); existen visores/validadores públicos como VALIDe en la Sede Electrónica.
- **Esfuerzo estimado**: **medio-alto** si se integra FIRe completo (requiere alta como organismo/entidad usuaria de FIRe, gestión de certificados de aplicación, entorno de pruebas); **bajo-medio** si sólo se necesita firma de documentos internos de flujo administrativo sin implicar al ciudadano final (p.ej. firma de resoluciones por personal funcionario, que puede resolverse con AutoFirma cliente + validación @firma). Complejidad **intrínseca** en la parte de PKI/formatos de firma (XAdES/PAdES son estándares complejos por diseño); **accidental** en la fragmentación de componentes (AutoFirma + Miniapplet legado + FIRe + Cl@ve Firma son cuatro piezas históricas que un ciudadano/integrador debe entender por separado).
- **Fuentes**: [FIRe – PAe CTT](https://administracionelectronica.gob.es/ctt/verPestanaGeneral.htm?idIniciativa=fire), [repo oficial FIRe en GitHub](https://github.com/ctt-gob-es/fire), [AutoFirma – Sede electrónica](https://sede.serviciosmin.gob.es/es-es/firmaelectronica/paginas/autofirma.aspx), [Firma Electrónica – descargas](https://firmaelectronica.gob.es/descargas), [Cl@vefirma – Ministerio de Educación](https://sede.educacion.gob.es/informacion-ayuda/soluciona-tus-dudas/firma-comun/clavefirma.html).

## 3. Notifica / DEHú / Carpeta Ciudadana

- **DEHú (Dirección Electrónica Habilitada única)**: sustituye/unifica el antiguo "Notific@" y la DEH clásica; es el punto centralizado donde el ciudadano/empresa consulta notificaciones electrónicas emitidas por las AAPP adheridas. No requiere alta previa del ciudadano; identificación vía Cl@ve, certificado o DNIe.
  - Para organismos emisores: existe integración vía **servicios web para recepción automatizada de notificaciones para grandes receptores** de alto volumen — la documentación de detalle (WSDL, esquemas) sólo se facilita al organismo tras su alta e integración con la Secretaría General de Administración Digital; no publicada en abierto en las páginas consultadas.
  - **Carpeta Ciudadana**: agregador de información/expedientes propios del ciudadano procedentes de distintos organismos; funciona de forma análoga (integración previa alta).
- **¿Exige Red SARA?**: la integración organismo↔DEHú como *emisor* de notificaciones típicamente se hace a través de infraestructuras del sector público conectadas (Red SARA es el canal habitual para tráfico organismo-a-organismo); **[NO VERIFICADO]** con detalle técnico en fuentes públicas si existe una vía alternativa por Internet con certificado de organismo para quien no tenga PdP SARA propio (puede resolverse vía nodo intermedio autonómico, análogamente al caso PID, ver §5).
- **Esfuerzo estimado**: **alto**. Es integración organismo-a-organismo (no ciudadano-a-organismo), exige alta formal, y la documentación técnica exacta no es pública sin ella — barrera de entrada real.
- **Fuentes**: [Chiclana.es – qué es DEHú](https://www.chiclana.es/sede-electronica/informacion-de-interes/direccion-electronica-habilitada-unica-dehu) (fuente municipal, no oficial AGE, usada sólo como descripción funcional), [AEAT – notificaciones electrónicas DEHú](https://sede.agenciatributaria.gob.es/Sede/ayuda/consultas-informaticas/notificaciones-electronicas-ayuda-tecnica.html). No se ha localizado la ficha oficial de DEHú en el catálogo CTT de PAe con URL estable durante esta investigación — **[NO VERIFICADO]** existencia de manual técnico público de integración como emisor.

## 4. REGAGE / SIR / ORVE (registro electrónico)

- **REGAGE**: Registro Electrónico General de la AGE, regulado por Orden PCM/1382/2021 (BOE-A-2021-20477); es el libro de registro oficial de entrada/salida de la Administración General del Estado.
- **SIR (Sistema de Interconexión de Registros)**: plataforma que interconecta los registros electrónicos de distintas AAPP para el intercambio de asientos registrales sin papel, evitando duplicidad de presentación. Existe una **"Librería de Intercambio SIR"** que los desarrolladores pueden usar para integrar sus propios sistemas de registro con la plataforma de interconexión y agilizar el proceso de certificación — publicada en el catálogo CTT de PAe.
- **ORVE (Oficina de Registro Virtual)**: aplicación en la nube (multiorganismo) proporcionada por el Estado a entidades locales sin registro propio, que digitaliza/firma documentación en el momento del registro presencial y la intercambia vía SIR. Es una alternativa "llave en mano" a construir un registro propio con librería SIR.
- **Obligatoriedad**: la interconexión de registros vía SIR es la vía normativa para el intercambio de asientos entre AAPP (Ley 39/2015 y normativa de desarrollo); un ayuntamiento/organismo con registro propio puede optar por integrarlo con SIR directamente o usar ORVE si no dispone de sistema propio.
- **Esfuerzo estimado**: **medio-alto** para integración directa vía librería SIR (protocolo de intercambio de asientos con certificación previa); **bajo** si se opta por usar ORVE como registro de entrada y no se necesita registro propio (delegación total).
- **Fuentes**: [SIR – PAe CTT](https://administracionelectronica.gob.es/ctt/verPestanaGeneral.htm?idIniciativa=sir), [Librería de Intercambio SIR – PAe CTT](https://administracionelectronica.gob.es/ctt/verPestanaGeneral.htm?idIniciativa=libsir), [ORVE – PAe CTT](https://administracionelectronica.gob.es/ctt/verPestanaGeneralAbstract.htm?idIniciativa=orve), [Orden PCM/1382/2021 – REGAGE](https://www.boe.es/buscar/act.php?id=BOE-A-2021-20477).

## 5. Plataforma de Intermediación de Datos (PID) / SCSP — **crítico**

- **Qué permite consultar**: catálogo de servicios de verificación/consulta de datos entre AAPP para evitar pedir documentos que ya obran en poder de otro organismo — identidad y residencia/empadronamiento, datos tributarios (AEAT), prestaciones (SEPE), datos de Seguridad Social, títulos académicos, discapacidad, familia numerosa, catastro, entre otros. El catálogo exacto de servicios que un organismo puede consumir depende de las **cesiones de datos autorizadas** para cada procedimiento (habilitación legal específica), no es de acceso libre a todo el catálogo.
- **Protocolo**: **SCSP (Sistema de Consulta de Datos entre Sistemas Público)** — es SOAP con mensajes XML firmados electrónicamente, tal como describe el propio nombre del protocolo y su tradición documental (guías técnicas de nodos como Red NEREA, Diputación de Huelva). No es REST; es un contrato de mensajería SOAP/WS-Security con petición y respuesta firmadas.
- **Requisitos de alta**: firma de un **convenio de adhesión** con el Ministerio (o con la Comunidad Autónoma cuando actúa de nodo intermedio, p.ej. la PAI valenciana); además, cada servicio de datos concreto requiere **autorización de cesión específica** vinculada al procedimiento administrativo que la justifica (no basta el convenio marco).
- **¿Exige Red SARA?**: **sí, como regla general** — el consumo de servicios SCSP a través de la PID exige conexión a Red SARA (obligación derivada del art. 43 Ley 11/2007 y art. 13 RD 4/2010, según fuentes de Red NEREA); dicho esto, el Ministerio ofrece una **vía alternativa para entidades pequeñas**: el **"Cliente Ligero SCSP en Cloud"**, un cliente web multiorganismo alojado por el propio Ministerio (`https://clientecloudscsp.redsara.es/scsp-cliente-ligero/`), pensado para ayuntamientos de menos de 50.000 habitantes sin infraestructura SARA propia — accesible por Internet tras darse de alta, sin que el organismo consumidor final necesite un Punto de Presencia SARA propio (el nodo cloud del Ministerio hace de intermediario dentro de SARA). Alternativa análoga: integrarse a través de un **nodo de interoperabilidad autonómico** (p. ej. PAI de la Generalitat Valenciana, Red NEREA de Andalucía) que actúa de pasarela SARA↔organismo.
  - **Consecuencia arquitectónica directa**: un sistema alojado en cloud público (AWS/GCP/Azure/Vercel...) **no puede conectarse directamente a la PID por su cuenta** salvo que contrate/gestione un enlace a Red SARA (VPN dedicada o PdP), lo cual normalmente sólo tiene sentido para grandes organismos. Para un organismo de tamaño pequeño-medio (típico de un ente gestor de vivienda pública municipal/autonómico) la vía realista es: (a) el "Cliente Ligero SCSP Cloud" del Ministerio con interfaz web (no API programática propia, es una app que opera un funcionario, no pensada para integración automatizada punto a punto), o (b) el nodo de interoperabilidad de su Comunidad Autónoma, que sí puede exponer una API/pasarela hacia el sistema del organismo. **Esto determina que la verificación automática de elegibilidad (b) del resumen final dependerá de qué nodo autonómico exista y qué acuerdo tenga el ente gestor con él — no es una integración universal directa.**
- **Esfuerzo estimado**: **alto**. Complejidad intrínseca: SOAP+XML-firma es más pesado que REST/JSON, y cada consulta de datos requiere habilitación legal propia (no es solo un tema técnico). Complejidad accidental: la necesidad de red SARA o de un nodo intermedio autonómico añade una capa de infraestructura/red que no tiene equivalente en integraciones B2B habituales.
- **Fuentes**: [Sede DGT – PID](https://sede.dgt.gob.es/es/otros-tramites/tramites-para-administraciones/plataforma-de-intermediacion-de-datos/), [Wikipedia PID (resumen, no oficial, usado sólo para contexto general)](https://es.wikipedia.org/wiki/Plataforma_de_intermediaci%C3%B3n_de_datos), [Red NEREA – PID Ministerio](https://rednerea.juntadeandalucia.es/drupal/node/71), [Cliente Ligero SCSP Cloud – Junta de Castilla y León](https://rmd.jcyl.es/web/jcyl/MunicipiosDigitales/es/Plantilla100Detalle/1274785511218/1/1284414982622/Comunicacion), [Guía Cliente Ligero SCSP – Red NEREA](https://rednerea.juntadeandalucia.es/drupal/sites/default/files/archivos/Guia_Cliente_Ligero.pdf).

## 6. FACe / Facturae

- **Qué hace**: FACe es el Punto General de Entrada de Facturas Electrónicas del sector público estatal, obligatorio para proveedores conforme a la Ley 25/2013.
- **Formato**: Facturae, versiones 3.2 y 3.2.1 (XML con firma electrónica XAdES incorporada).
- **API/integración**: FACe expone servicios de integración para proveedores/gestorías (existen manuales técnicos de integración encargados por RED.ES); terceros comerciales (B2Brouter, akua.cloud, ZeroComa, Facturama) ofrecen capas de abstracción sobre la API de FACe. No es un servicio que el ente gestor de vivienda consuma como *receptor* salvo que sea, además, emisor de facturas al sector público — para un sistema de gestión de vivienda pública, FACe sería relevante sólo si el propio ente factura a otras AAPP, no para su relación con inquilinos/solicitantes.
- **Esfuerzo estimado**: **bajo-medio**, y probablemente **fuera de alcance** del sistema de gestión de vivienda salvo módulo de facturación a terceros públicos.
- **Fuentes**: [Facturae | FACe](https://www.facturae.gob.es/face), soporte técnico oficial: `soporteface@red.es`.

## 7. Catastro (Dirección General del Catastro)

- **Servicios web libres** (callejero, conversión de coordenadas a referencia catastral): SOAP tradicional, pero también expuestos vía **HTTP REST (GET/POST)** desde la migración documentada en "Servicios web libres de la Sede Electrónica del Catastro" — **no requieren autenticación** para los datos no protegidos (callejero, localización).
- **Servicios de consulta y certificación catastral con datos protegidos**: requieren **autenticación mediante certificado digital de cliente X.509**, emitido por una CA reconocida por la Dirección General del Catastro, vía SSL mutuo (TLS client-cert), sin relación con Cl@ve ni con SARA en las fuentes consultadas.
- **Esfuerzo estimado**: **bajo** para los servicios libres (callejero/coordenadas, REST, sin auth); **medio** para consulta/certificación protegida (gestión de certificado cliente X.509 propio del organismo).
- **Fuentes**: [Servicios web libres del Catastro (PDF v2.6)](https://www.catastro.hacienda.gob.es/ws/Webservices_Libres.pdf), [Servicios web de actualización](https://www.catastro.hacienda.gob.es/ws/Webservices_Actualizacion.pdf), [Servicios WCF de consulta y certificación](https://www.catastro.hacienda.gob.es/ws/WCFservices_Consulta.pdf).

## 8. Red SARA — decisivo para la arquitectura

- **Qué es**: infraestructura de red privada (VPLS/MPLS sobre operadores de telecomunicaciones) que interconecta las AAPP españolas e instituciones europeas, regulada por la Norma Técnica de Interoperabilidad de requisitos de conexión (Resolución de 19/07/2011, BOE-A-2011-13173) y con base legal en el art. 43 Ley 11/2007 / art. 13 RD 4/2010.
- **Quién puede conectarse**: cualquier Administración Pública española, mediante un Punto de Presencia (PdP) — sede con conexión directa a la red, sin organización intermedia — o a través de "nodos" autonómicos que agregan a entidades locales pequeñas.
- **¿Es un requisito de red para PID/SIR/DEHú?**: sí, para el modelo de integración directa; existen mitigaciones para organismos pequeños (Cliente Ligero Cloud de PID, ORVE para registro, nodos autonómicos de interoperabilidad para varios servicios) que evitan que cada organismo pequeño tenga que desplegar su propio PdP SARA.
- **Implicación decisiva para hosting en cloud público**: **un sistema alojado íntegramente en cloud público (fuera de Red SARA) NO puede conectarse directamente y por su cuenta a los servicios "internos" de AAPP (PID/SCSP, SIR, DEHú como emisor)**. Las vías realistas son: (1) que el ente gestor de vivienda tenga, a través de su ayuntamiento/CCAA, acceso a un nodo de interoperabilidad autonómico ya conectado a SARA que exponga API hacia el exterior; (2) usar los clientes/servicios en la nube que el Ministerio pone a disposición de entidades pequeñas (Cliente Ligero SCSP, ORVE), que son aplicaciones operadas manualmente por un funcionario y no pensadas para integración automática punto a punto; (3) contratar un enlace VPN/PdP dedicado a SARA, viable pero con coste y trámite administrativo no triviales, típicamente fuera del alcance de un proyecto software puro.
  - Consecuencia de diseño: la arquitectura debe tratar estas integraciones como **adaptadores de "puerto lento/manual"** — en el caso más pesimista, la verificación de datos vía PID puede acabar siendo un proceso semi-manual (un funcionario consulta el Cliente Ligero SCSP y transcribe el resultado), no una llamada API automática desde el backend en cloud público. Esto debe reflejarse en la estimación de "esfuerzo" y en el diseño de los ports (el puerto de dominio "VerificarElegibilidad" debe admitir tanto un adapter automático SCSP-vía-nodo-autonómico como un adapter manual/semiautomático).
- **Fuentes**: [Red SARA – PAe CTT](https://administracionelectronica.gob.es/ctt/verPestanaGeneral.htm?idIniciativa=redsara), [BOE-A-2011-13173 – NTI conexión Red SARA](https://www.boe.es/buscar/doc.php?id=BOE-A-2011-13173), [Guía NTI conexión Red SARA (Hacienda, PDF)](https://www.hacienda.gob.es/SGT/catalogo_sefp/254_guia_conex-red-aa-pp-esp-internet.pdf), [Red NEREA – PID Ministerio (menciona obligación de conexión SARA para PID)](https://rednerea.juntadeandalucia.es/drupal/node/71).

## 9. SAP, SIGES.Net, Alfresco

- **SAP**: ERP genérico; si el ente gestor de vivienda ya usa SAP para contabilidad/nóminas, la integración típica es vía sus módulos estándar (IDocs, BAPI, OData en S/4HANA) — no es una integración específica de AAPP española, es integración ERP convencional. **[NO VERIFICADO]** si el ámbito concreto de "casa47bis"/anchovy usa SAP; no se ha investigado un despliegue específico.
- **SIGES.Net**: sistema de gestión de subvenciones y convocatorias usado por diversas administraciones autonómicas/locales españolas; la integración típica es de intercambio de datos de expedientes de subvención (no se ha localizado documentación técnica pública de su API durante esta investigación — **[NO VERIFICADO]**, probablemente sólo accesible a organismos usuarios de la aplicación).
- **Alfresco**: gestor documental de código abierto (Community/Enterprise) muy usado en AAPP españolas para expediente electrónico; expone **API REST** propia y también soporta **CMIS** (Content Management Interoperability Services, estándar OASIS) para interoperabilidad documental — esto es estándar de producto, no específico de la AGE, y su documentación es pública (docs.alfresco.com), a diferencia de los servicios anteriores.
- **Esfuerzo estimado**: SAP y Alfresco, **bajo-medio** (integraciones de producto estándar bien documentadas); SIGES.Net, **[NO VERIFICADO — probable alto por opacidad documental]**.

---

## 10. ENS — Real Decreto 311/2022

- **Categorías**: básica, media, alta, determinadas por el **impacto potencial** de un incidente de seguridad sobre cada dimensión de seguridad (Disponibilidad, Autenticidad, Integridad, Confidencialidad, Trazabilidad — "DACIT"), definidas en el Anexo I del RD 311/2022.
- **Certificación/declaración de conformidad**: en categoría **básica** basta con una **autoevaluación/declaración de conformidad** firmada por el responsable de seguridad; en categorías **media y alta** es obligatoria una **auditoría externa de conformidad**, realizada por una entidad certificadora acreditada por **ENAC** (Entidad Nacional de Acreditación). La auditoría/revisión debe repetirse periódicamente (cada dos años según las fuentes consultadas).
- **¿Debe el proveedor de hosting estar certificado?**: el ENS aplica al sistema de información de la AAPP, no exige per se que el proveedor cloud subyacente tenga una certificación ENS propia, pero en la práctica los grandes proveedores (AWS, Azure, Google Cloud, y proveedores españoles como Éxaminetworks) publican **declaraciones de aplicabilidad ENS de su infraestructura** para facilitar que el organismo cliente pueda heredar controles; la responsabilidad final de la conformidad del sistema completo sigue siendo del organismo titular del servicio. **[NO VERIFICADO en detalle en esta investigación cuál es el umbral exacto de "responsabilidad compartida" documentado en el propio RD 311/2022; recomendación: revisar el articulado y el Anexo II directamente en BOE-A-2022-7191 antes de decidir proveedor.]**
- **Medidas concretas que afectan a la arquitectura** (Anexo II, categorías dependientes): cifrado de información en tránsito y en reposo, trazabilidad (registro de actividad/logs con integridad y no repudio), segregación de entornos y de funciones, copias de seguridad con política de retención, planes de continuidad/contingencia, gestión de identidad y control de acceso, auditorías periódicas.
- **Fuentes**: [BOE-A-2022-7191 – RD 311/2022 ENS](https://www.boe.es/buscar/act.php?id=BOE-A-2022-7191).

## 11. ENI (Esquema Nacional de Interoperabilidad) y NTI relevantes

- **Base legal**: regulado por RD 4/2010, actualizado por RD 203/2021.
- **NTI relevantes** (todas Resoluciones de la Secretaría de Estado, publicadas en BOE):
  - **Documento electrónico**: BOE-A-2011-13169 — define componentes obligatorios (contenido, firma, metadatos mínimos), formato y condiciones de intercambio/reproducción.
  - **Expediente electrónico**: BOE-A-2011-13170 — define estructura del expediente (documentos, índice electrónico, firma del índice, metadatos mínimos).
  - **Política de firma y sello electrónicos y certificados**: BOE-A-2016-10146 (sustituye a la de 2012).
  - **Digitalización de documentos**: BOE-A-2011-13168.
  - Existen NTIs adicionales sobre modelo de datos para intercambio de asientos registrales, catálogo de estándares, intermediación de datos, declaración de conformidad, etc., todas dentro del mismo marco ENI.
- **Fuentes**: [BOE-A-2011-13169 – NTI Documento Electrónico](https://www.boe.es/buscar/doc.php?id=BOE-A-2011-13169), [BOE-A-2011-13170 – NTI Expediente Electrónico](https://www.boe.es/buscar/doc.php?id=BOE-A-2011-13170), [BOE-A-2016-10146 – NTI Política de Firma](https://www.boe.es/diario_boe/txt.php?id=BOE-A-2016-10146), [BOE-A-2011-13168 – NTI Digitalización](https://www.boe.es/buscar/doc.php?id=BOE-A-2011-13168).

## 12. Accesibilidad — RD 1112/2018

- **Norma técnica**: EN 301 549 (armonizada), que converge con **WCAG 2.1 nivel AA** como estándar exigido; RD 1112/2018 transpone la Directiva (UE) 2016/2102.
- **Declaración de accesibilidad**: obligatoria, publicada en formato accesible enlazada desde todas las páginas, **actualizada al menos anualmente** o tras cada revisión.
- **Revisión periódica**: al menos **cada tres años** (art. 17).
- **[NO VERIFICADO]**: no se ha confirmado en esta investigación si ya existe obligación normativa de WCAG 2.2 (posterior a WCAG 2.1) para el sector público español; el RD y la norma EN 301 549 vigente citada en fuentes referencian 2.1.
- **Fuentes**: [BOE-A-2018-12699 – RD 1112/2018](https://www.boe.es/buscar/act.php?id=BOE-A-2018-12699).

## 13. RGPD/LOPDGDD aplicado a vivienda pública

- **Base jurídica**: art. 6.1.e RGPD, "misión realizada en interés público" / ejercicio de poderes públicos conferidos al responsable — encaja con la gestión de vivienda pública por un ente adscrito a una administración.
- **DPIA/EIPD**: la AEPD publica listas de tratamientos que la requieren obligatoriamente; entre ellas figuran explícitamente tratamientos sobre **personas en riesgo de exclusión social, personas con discapacidad, personas usuarias de servicios sociales y víctimas de violencia de género** — todos ellos previsibles en criterios de baremación de vivienda social. Conclusión: **es muy probable que el sistema deba someterse a EIPD/DPIA obligatoria**, no opcional, en función de los criterios de elegibilidad efectivamente tratados (discapacidad, violencia de género son categorías especiales del art. 9 RGPD).
- **Categorías especiales de datos**: discapacidad (dato de salud) y violencia de género (dato relativo a infracciones penales/víctima, tratamiento sensible) exigen base jurídica reforzada (art. 9.2.g RGPD, interés público esencial, con amparo en ley) y medidas de seguridad reforzadas.
- **Plazos de conservación**: **[NO VERIFICADO]** — no se ha localizado en esta investigación una norma específica de plazos de conservación para expedientes de vivienda pública; deberá fijarse conforme a la normativa autonómica/sectorial de vivienda y los criterios generales de conservación de documentos administrativos (NTI de Documento Electrónico + normativa de archivos), no puede darse un plazo genérico sin consultar la ley autonómica de vivienda aplicable.
- **Fuentes**: [AEPD – Listas de tratamientos que requieren EIPD (PDF)](https://www.aepd.es/documento/listas-dpia-es-35-4.pdf).

---

## Limitaciones de esta investigación

- No se ha accedido a documentación restringida a organismos dados de alta (PID/SCSP WSDL reales, manual técnico DEHú como emisor, SIGES.Net API) — su ausencia pública es en sí un dato relevante (barrera de entrada), reportado como tal en cada ficha.
- Varias afirmaciones sobre "obligación de Red SARA" proceden de fuentes secundarias (Red NEREA, portales autonómicos) que citan la base legal (art. 43 Ley 11/2007, art. 13 RD 4/2010) pero no se ha verificado el texto literal de esos artículos línea por línea en esta sesión; recomendación: confirmarlo contra el BOE antes de comprometer la arquitectura.
- No se ha verificado si Cl@ve España ofrece ya OpenID Connect en producción (distinto de ClaveÚnica de Chile, que sí es OIDC); esto debe confirmarse directamente con clave.gob.es o el CAU antes de diseñar el adapter de identidad.

---

# ADENDA (2026-09-08) — Conectividad Red SARA con la premisa corregida

**Corrección de premisa.** El análisis inicial de este documento contemplaba como
mitigación el uso de un nodo de interoperabilidad autonómico. **No aplica.** CASA 47 es
la marca operativa de la Entidad Estatal de Vivienda, que es SEPES EPE transformada,
adscrita al Ministerio de Vivienda y Agenda Urbana: es Administración General del Estado.

## 1. Conexión de un organismo AGE a Red SARA

Se realiza mediante **Punto de Presencia (PdP)**: sede con conexión directa a la red
troncal, sin organización intermedia. Condiciones técnicas reguladas por la Resolución de
4 de julio de 2017 de la SEFP (BOE-A-2017-8018). El trámite es una solicitud formal al
Proveedor de Acceso a la Red SARA, con intervención de la Secretaría General de
Administración Digital (SGAD).

No existe conexión automática por el hecho de depender de un ministerio: cada sede tiene
sus propios encaminadores redundantes hacia la troncal VPLS.

`[NO VERIFICADO]` Si SEPES / Ministerio de Vivienda dispone hoy de PdP propio activo.
Es probable, pero no está confirmado en fuente pública.

Fuentes: BOE-A-2017-8018; BOE-A-2011-13173 (NTI de conexión a SARA);
guía de conexión publicada por Hacienda.

## 2. El punto decisivo: aplicación en nube pública ↔ Red SARA

**Existe un mecanismo oficial: NubeSARA.** Es el servicio de nube híbrida cuyo proveedor
es la SGAD, articulado sobre "nodos de consolidación" desplegados tanto en centros de
proceso de datos propios de la AGE como **en proveedores externos de nube pública**. Los
organismos actúan como consumidores del servicio mediante **convenio individual con la
SGAD**, no gestionando su propia conectividad SARA.

Está parcialmente financiado con fondos Next Generation EU y hay convenios publicados en
el BOE con distintos organismos:

- BOE-A-2024-26902 — Agencia Espacial Española
- BOE-A-2024-25018 — Instituto de la Juventud
- BOE-A-2023-23282 — Confederación Hidrográfica del Duero

**Consecuencia para este informe, y es una de las conclusiones más importantes:**

> El precedente de una aplicación de la AGE alojada en nube pública que consume servicios
> internos de Red SARA **existe, es institucional y está publicado en el BOE**. No es un
> apaño. La vía es el convenio NubeSARA con la SGAD.

## 3. Cómo lo resuelve hoy el sistema actual

`[NO VERIFICADO]` No hay fuente pública que documente cómo el sistema actual de CASA 47
conecta Power Pages —que se ejecuta en Azure, fuera de SARA— con la Plataforma de
Intermediación de Datos.

Estructuralmente sólo existen tres opciones:

1. NubeSARA, si SEPES tiene convenio propio.
2. Una pasarela en instalaciones propias, dentro de un PdP, que Power Pages consulta por
   una API expuesta con autenticación reforzada.
3. Consumo manual por un empleado público (Cliente Ligero SCSP), sin integración
   automática.

**Y aquí está el argumento clave:** cualquiera de las tres implica una pasarela o un
trámite. Por tanto:

> La restricción de Red SARA es **simétrica**. Se aplica idénticamente a la arquitectura
> actual sobre Power Pages y a la arquitectura propuesta. **No es un argumento a favor de
> la plataforma propietaria ni en contra de la alternativa.** Es complejidad intrínseca
> del ecosistema de interoperabilidad español, compartida por todos.

Quien sostenga lo contrario debe explicar por qué Power Pages, ejecutándose en Azure,
estaría exento de una restricción que sí afectaría a la misma aplicación ejecutándose en
otro proveedor.

## 4. Cl@ve — confirmado

La integración servicio↔Cl@ve es **SAML 2.0 mediante redirección del navegador del
ciudadano**, no llamadas servidor a servidor. El organismo actúa como proveedor de
servicio SAML y se integra **por Internet público**. No hay requisito documentado de Red
SARA ni para el alta ni para el tráfico de autenticación.
Fuente: BOE-A-2015-14215 (prescripciones técnicas de Cl@ve).

Implicación de diseño: la autenticación de ciudadanos —la funcionalidad que Power Pages
aporta "de fábrica"— es un **puerto de identidad con un adaptador SAML 2.0 estándar**.
Es trabajo conocido y acotado, no un problema abierto.

## 5. DEHú / Notifica — el emisor no exige SARA

El alta como emisor/punto integrado se realiza remitiendo la parte pública de un
certificado y el punto final propio; se exige certificado de los tipos admitidos por
@firma. El modelo es de **llamada entrante con TLS mutuo por Internet**, no un requisito
explícito de PdP.
`[VERIFICACIÓN PARCIAL]` Contenido citado desde el archivo de la lista oficial de
emisores DEHú; la descarga directa falló por un error de certificado TLS en esa lista.
Debe reconfirmarse antes de darlo por definitivo.

## 6. Cuadro resumen de topología exigida

| Servicio | Protocolo | ¿Exige Red SARA? | ¿Simétrico entre ambas arquitecturas? |
|---|---|---|---|
| Cl@ve | SAML 2.0 por navegador | No | Sí |
| PID / SCSP | SOAP con XML firmado | **Sí** | **Sí** |
| SIR / ORVE / REGAGE | Intercambio de asientos | **Sí** | **Sí** |
| DEHú / Notifica (emisor) | TLS mutuo, entrante | No (a confirmar) | Sí |
| Catastro | SOAP y REST; libres sin autenticación, protegidos con certificado X.509 | No | Sí |
| FACe / Facturae | Servicios web | No | Sí |

**Conclusión de la adenda.** La única restricción de red seria —PID y SIR— es real,
es intrínseca, y **no distingue entre arquitecturas**. La forma canónica de resolverla
para un organismo de la AGE con la aplicación en nube es el convenio NubeSARA con la
SGAD, con precedentes publicados en el BOE.
