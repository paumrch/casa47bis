# Contrato del Sistema Integrado de Gestión (SIG) del Parque de Alquiler Asequible — CASA 47 / EY Transforma

Fecha de investigación: 2026-09-08. Metodología: BOE (texto oficial descargado y extraído con `pdftotext`/`txt.php`), búsqueda web, comprobación directa de cabeceras HTTP del portal en producción (`curl`), y webs institucionales (CASA47, La Moncloa). **No se ha podido acceder al contenido íntegro de los pliegos (PCAP/PPT) del expediente**: ver sección "Lagunas".

## 1. Qué es CASA 47

- CASA 47 es la marca comercial/operativa de la **nueva Entidad Estatal de Vivienda**, resultado de la transformación de **SEPES, Entidad Pública Empresarial de Suelo** (EPE, NIF Q2801671E), adscrita al **Ministerio de Vivienda y Agenda Urbana**. Fuente: https://www.casa47.es/en/que-es-casa47 ; confirmado también porque el propio expediente de contratación del SIG (BOE) se publica bajo el nombre legal "Consejo de Administración SEPES Entidad Pública Empresarial de Suelo", NIF Q2801671E — https://www.boe.es/diario_boe/txt.php?id=BOE-B-2025-28508
- Naturaleza jurídica según el propio anuncio BOE: "Organismo de Derecho público bajo el control de una autoridad estatal", actividad principal "Vivienda y servicios comunitarios" — https://www.boe.es/diario_boe/txt.php?id=BOE-B-2026-771
- Ámbito territorial: estatal (todo el territorio nacional).
- Competencias citadas en fuentes institucionales: gestión de un parque público de vivienda no privatizable, desde la disposición del suelo hasta la entrega y gestión del alquiler; asunción del patrimonio de la antigua Sareb (~40.000 viviendas, >2.000-2.400 parcelas) — https://www.casa47.es/en/que-es-casa47
- Presupuesto/inversión citada en nota de prensa institucional: 13.000 millones € en 10 años para construcción de parque público — https://www.lamoncloa.gob.es/serviciosdeprensa/notasprensa/vivienda-agenda-urbana/paginas/2026/solicitud-casa-47.aspx (dato de inversión global del organismo, **no** del contrato del SIG).
- La web oficial `mivau.gob.es/vivienda/casa47-entidad-estatal-de-vivienda` devolvió HTTP 403 en el momento de la consulta y no pudo verificarse directamente — NO VERIFICADO ese enlace concreto (sí se verificó el mismo contenido vía casa47.es y La Moncloa).

## 2. Ficha del expediente (fuente: BOE, textos oficiales íntegros)

| Campo | Valor | Fuente |
|---|---|---|
| Expediente | 132019 | BOE-B-2025-28508 y BOE-B-2026-771 |
| Órgano de contratación | Consejo de Administración SEPES EPE (Q2801671E) | BOE-B-2025-28508 |
| Objeto | "Servicios de Consultoría de procesos, parametrización, desarrollo y mantenimiento de un Sistema Integrado de Gestión (SIG) para la gestión del Parque de Alquiler Asequible" | BOE-B-2025-28508 |
| Procedimiento | Abierto | BOE-B-2025-28508 |
| CPV | 72000000 (Servicios TI: consultoría, desarrollo de software, Internet y apoyo) | BOE-B-2025-28508 |
| Valor estimado del contrato | 1.880.949,00 € (sin IVA) | BOE-B-2025-28508, §8 |
| Duración | 4 años desde el día siguiente a la formalización | BOE-B-2025-28508, §10 |
| Plazo de presentación de ofertas | hasta las 13:00 h del 10/09/2025 | BOE-B-2025-28508, §19 |
| Envío de anuncio de licitación al DOUE | 24/07/2025 (ID 2025-000865109), publicado DOUE 25/07/2025 (ID 488301-2025) | BOE-B-2026-771, §17 |
| Fecha de adjudicación | 26 de noviembre de 2025 | BOE-B-2026-771, §10 |
| Ofertas recibidas | 8 (2 de PYMEs) | BOE-B-2026-771, §11 |
| Adjudicatario | EY TRANSFORMA SERVICIOS DE CONSULTORIA, S.L., NIF B88428404, Madrid | BOE-B-2026-771, §12 |
| Importe de adjudicación (oferta seleccionada) | 1.184.998,00 € | BOE-B-2026-771, §13 |
| Fecha de envío del anuncio de formalización | 12 de enero de 2026 (publicado BOE 15/01/2026) | BOE-B-2026-771 |

Criterios de adjudicación y ponderación (idénticos en licitación y formalización):
| Criterio | Ponderación |
|---|---|
| Precio | 40% |
| Propuesta metodológica | 37% |
| Dotación y experiencia de medios humanos — Desarrollo e Implantación | 6% |
| Ampliación de oferta de soporte y mantenimiento sobre el mínimo exigido | 5% |
| Dotación y experiencia de medios humanos — Consultoría de Procesos | 4% |
| Certificación ISO 33000 (calidad de desarrollo de software) | 2% |
| Certificación ISO 9001 | 2% |
| Certificación UNE-ISO 20000 (gestión de servicios TI) | 2% |
| Certificado de calificación del fabricante de la tecnología CRM | 2% |

Fuente: https://www.boe.es/diario_boe/txt.php?id=BOE-B-2025-28508 y https://www.boe.es/diario_boe/txt.php?id=BOE-B-2026-771

**Sobre el importe con IVA citado (1.433.847,58 €)**: comprobación aritmética propia — 1.184.998,00 € × 1,21 = 1.433.847,58 €. Cuadra exactamente con IVA del 21%. Verificado por cálculo, no por un documento que exprese explícitamente el importe con IVA del contrato adjudicado (el BOE de formalización solo da la cifra sin IVA). Cálculo: `echo $((1184998*121/100))` → 1.433.847 (redondeo).

**Sobre el valor estimado con impuestos (2.275.948,29 €) citado en prensa/CASA47**: 1.880.949,00 € × 1,21 = 2.275.948,29 €, coincide exactamente con la cifra que reproduce la nota de CASA47 — https://www.casa47.es/w/la-empresa-estatal-de-vivienda-contar%C3%A1-con-un-portal-donde-la-ciudadan%C3%ADa-podr%C3%A1-consultar-y-solicitar-las-viviendas-de-alquiler-asequible-disponibles — es el **valor estimado** (base de licitación sin IVA + IVA), no el importe finalmente adjudicado. Los dos importes (1.880.949 € estimado vs 1.184.998 € adjudicado) son cifras distintas y ambas están documentadas; no deben confundirse.

## 3. Alcance contractual (según objeto oficial y nota de prensa institucional)

- Consultoría de procesos, parametrización, desarrollo, implantación y mantenimiento de un SIG.
- Incluye un Portal público (front-office ciudadano) para consulta de viviendas disponibles, requisitos y registro de solicitudes, y formalización de contratos/gestión de incidencias de inquilinos ya instalados.
- Incluye un CRM para gestión y automatización de procesos internos.
Fuente: https://www.casa47.es/w/la-empresa-estatal-de-vivienda-contar%C3%A1-con-un-portal-donde-la-ciudadan%C3%ADa-podr%C3%A1-consultar-y-solicitar-las-viviendas-de-alquiler-asequible-disponibles

No se ha localizado ninguna nota de prensa oficial ni documento público que describa con mayor detalle funcional el alcance (módulos, fases, entregables) más allá de lo anterior.

## 4. Volumetrías (~50.000 solicitantes, 50.000 inquilinos, 17/115 gestores, 25 proveedores)

**NO VERIFICADO.** No se ha encontrado ninguna fuente pública (BOE, TED, nota de prensa, web institucional) que recoja estas cifras de volumetría del sistema. No aparecen en los anuncios BOE de licitación ni de formalización, que no detallan requisitos técnicos ni volumetrías (esos datos, si existen, estarían en el PPT, al que no se ha podido acceder — ver Lagunas). No se afirma ni se descarta su exactitud: simplemente no se ha podido documentar con fuente pública.

## 5. Integraciones exigidas

**NO VERIFICADO.** Ningún documento accesible públicamente (BOE, TED, prensa) detalla integraciones técnicas exigidas (p. ej. con sistemas de identidad digital, Cl@ve, registros de vivienda, pasarela de pago, etc.). Este detalle normalmente figura en el PPT, no accesible.

## 6. Tecnología exigida

- El pliego exige explícitamente **certificación/calificación de fabricante de tecnología CRM** como criterio de adjudicación puntuable (2%), lo que confirma que el contrato exige o valora una plataforma CRM concreta, pero el anuncio BOE **no nombra el fabricante ni el producto** — https://www.boe.es/diario_boe/txt.php?id=BOE-B-2025-28508, criterio 18.5.
- **Verificación independiente de la tecnología del portal en producción**: comprobación directa de cabeceras HTTP de `https://portal.casa47.es/` (comando `curl -sIL`, ejecutado 2026-09-08) muestra cookies `Dynamics365PortalAnalytics` y cabecera `x-ms-portal-app: s-c68bbe8f-2609-41a3-bf28-d6720424781c-POc`, firmas técnicas inequívocas de **Microsoft Power Pages / Dynamics 365 Portal (Power Platform)**. Esto confirma de forma independiente y verificable que el portal público en producción está servido con Power Pages, consistente con el dato de partida. Fuente: comando ejecutado por este investigador, salida cruda disponible en la sesión; no es una fuente de terceros sino observación directa reproducible con `curl -sIL https://portal.casa47.es/`.
- No se ha podido confirmar si "CRM" del pliego es Dynamics 365 CRM (lo cual sería coherente con Power Pages, ya que Power Pages es la evolución de los portales de Dynamics 365) porque el BOE no lo nombra. Es una inferencia razonable por coherencia técnica (Power Pages se integra nativamente con Dataverse/Dynamics 365), pero **no está documentada explícitamente en fuente pública** — se marca como **NO VERIFICADO** como afirmación explícita del pliego.

## 7. Licencias dentro/fuera del importe adjudicado

**NO VERIFICADO.** No se ha localizado el texto del PCAP/PPT que indique si las licencias de la plataforma CRM/Power Platform están incluidas en el importe adjudicado o si existe un expediente de licencias separado. No se ha encontrado en BOE, TED ni en el perfil del contratante un expediente distinto de licencias de software para CASA 47/SEPES relacionado con este proyecto. Dato clave sin poder confirmar ni descartar.

## 8. Propiedad del código fuente y reversibilidad

**NO VERIFICADO.** No accesible sin el PCAP/PPT íntegros.

## 9. SLA y ENS (Esquema Nacional de Seguridad)

**NO VERIFICADO.** El anuncio de licitación no detalla SLA ni exigencia de ENS; solo consta que el criterio de adjudicación valora una "ampliación de oferta de soporte y mantenimiento sobre el mínimo exigido" (5%), lo que implica que el PCAP/PPT sí fija un mínimo de soporte/mantenimiento exigible, pero su contenido concreto (niveles de disponibilidad, tiempos de respuesta, categorización ENS) no está en el anuncio BOE. Fuente: https://www.boe.es/diario_boe/txt.php?id=BOE-B-2025-28508, criterio 18.1.

## 10. ~9.000 horas de mantenimiento evolutivo en pliego y 5.000 adicionales ofertadas por EY

**NO VERIFICADO.** No se ha encontrado ninguna fuente pública que confirme estas cifras de horas. El criterio de adjudicación "Ampliación de oferta de soporte y mantenimiento sobre el mínimo exigido" (5%, ver BOE-B-2025-28508 §18.1) es coherente con la existencia de un mínimo de horas fijado en pliego y una posible ampliación ofertada por los licitadores, pero el número de horas (9.000 / 5.000) no figura en ningún documento al que se haya tenido acceso.

## 11. Tabla resumen dato / valor / fuente / verificado

| Dato | Valor | Fuente | Verificado |
|---|---|---|---|
| Naturaleza CASA 47 | Entidad Estatal de Vivienda, transformación de SEPES EPE | casa47.es/en/que-es-casa47 | Sí |
| Administración de la que depende | Ministerio de Vivienda y Agenda Urbana | BOE-B-2026-771 (cabecera "Ministerio de Vivienda y Agenda Urbana") | Sí |
| Expediente | 132019 | BOE-B-2025-28508 / BOE-B-2026-771 | Sí |
| Procedimiento | Abierto | BOE-B-2025-28508 | Sí |
| Valor estimado (sin IVA) | 1.880.949,00 € | BOE-B-2025-28508 | Sí |
| Valor estimado (con IVA, calculado) | 2.275.948,29 € | Cálculo propio 1.880.949×1,21; coincide con cifra publicada en casa47.es | Sí |
| Duración | 4 años | BOE-B-2025-28508 | Sí |
| Adjudicatario | EY TRANSFORMA SERVICIOS DE CONSULTORIA, S.L. (B88428404) | BOE-B-2026-771 | Sí |
| Fecha de adjudicación | 26/11/2025 | BOE-B-2026-771 | Sí |
| Ofertas recibidas | 8 (2 PYME) | BOE-B-2026-771 | Sí |
| Importe adjudicado (sin IVA) | 1.184.998,00 € | BOE-B-2026-771 | Sí |
| Importe adjudicado (con IVA, calculado) | 1.433.847,58 € | Cálculo propio 1.184.998×1,21 | Sí (por cálculo, no consta como cifra explícita en BOE) |
| Criterios de adjudicación y ponderación | Precio 40% / Metodología 37% / resto 23% repartido en 6 subcriterios | BOE-B-2025-28508 §18 | Sí |
| Portal en producción con Power Pages | Confirmado por cabeceras HTTP (`Dynamics365PortalAnalytics`, `x-ms-portal-app`) | curl directo a portal.casa47.es, 2026-09-08 | Sí |
| Requisito de certificación de fabricante CRM en pliego | Sí, criterio de adjudicación 2% | BOE-B-2025-28508 §18.5 | Sí (existencia del criterio); NO VERIFICADO el nombre del fabricante/producto |
| Volumetrías (~50.000 solicitantes, 50.000 inquilinos, 17/115 gestores, 25 proveedores) | — | No localizada fuente pública | No — NO VERIFICADO |
| ~9.000 h mantenimiento evolutivo pliego / 5.000 h ofertadas EY | — | No localizada fuente pública | No — NO VERIFICADO |
| Licencias dentro/fuera del importe | — | No accesible (PCAP/PPT no localizados) | No — NO VERIFICADO |
| Propiedad código fuente / reversibilidad | — | No accesible | No — NO VERIFICADO |
| SLA / ENS detallado | — | No accesible | No — NO VERIFICADO |
| Integraciones exigidas | — | No accesible | No — NO VERIFICADO |

## 12. Lagunas de información

- **No se ha podido acceder al contenido íntegro del PCAP ni del PPT del expediente 132019.** La Plataforma de Contratación del Sector Público (contrataciondelestado.es) es una aplicación fuertemente dependiente de JavaScript; las herramientas de esta investigación no pueden renderizarla de forma fiable, y las respuestas obtenidas al intentarlo son inconsistentes con el contenido real (una consulta devolvió información de otro órgano —SEPE, no SEPES— y otra devolvió afirmaciones sobre pliegos que no se han podido corroborar con una descarga real del documento). Se han intentado además dos PDFs alojados en casa47.es cuyos títulos de búsqueda sugerían ser el PPT del SIG, pero al descargarlos y extraer su texto (`pdftotext`) resultaron ser pliegos de **otros expedientes** (vigilancia de seguridad en Son Busquets, Palma de Mallorca; y proyecto de urbanización del Parque de Artillería, Valencia) — es decir, coincidencias falsas de la indexación de buscadores sobre el gestor documental de casa47.es. **Recomendación**: acceder directamente a la ficha de licitación en contrataciondelestado.es con navegador (enlace del perfil del comprador: `https://contrataciondelestado.es/wps/poc?uri=deeplink:perfilContratante&idBp=%2BbVGZw6NyuM%3D` y el deep-link de detalle de licitación referenciado en el propio anuncio BOE) para descargar PCAP y PPT reales.
- No se ha localizado un expediente separado de licencias de software (Power Platform/Dynamics) en BOE ni TED asociado a CASA47/SEPES.
- No se ha verificado si existen modificados o prórrogas contractuales posteriores a enero de 2026.
- La web `mivau.gob.es/vivienda/casa47-entidad-estatal-de-vivienda` no fue accesible (HTTP 403) para verificación directa.

## Rutas relevantes de esta investigación

- Informe: `/Users/paumarch/orca/workspaces/casa47bis/anchovy/docs/research/03-contrato-y-pliegos.md`
- PDFs descargados durante la investigación (no son los pliegos del SIG; se conservan como evidencia de las coincidencias falsas): `/private/tmp/claude-501/-Users-paumarch-orca-workspaces-casa47bis-anchovy/2f56d909-868c-4080-bafd-043f3404b108/scratchpad/formaliz.pdf`, `PPT.pdf`, `PPT2.pdf`

---

# ADENDA — Pliegos reales del expediente 132019 (obtenidos y verificados)

**Método.** La Plataforma de Contratación no permite descarga directa por ser una
aplicación JavaScript. La vía que funcionó: el BOE aportó la fecha de publicación en el
DOUE (25/07/2025); con ella se descargó el fichero mensual de datos abiertos de sindicación
de la PLACSP (`licitacionesPerfilesContratanteCompleto3_202507.zip`), se localizó la
entrada con `ContractFolderID=132019` y, dentro, los nodos CODICE
`<cac:LegalDocumentReference>` y `<cac:TechnicalDocumentReference>` con las URIs directas
a los documentos.

**Verificación de identidad de los documentos.** Se comprobó el encabezado de cada PDF
antes de citarlo, tras los falsos positivos de la primera búsqueda:

- **PCAP**, 46 páginas — "PLIEGO DE CLÁUSULAS ADMINISTRATIVAS PARTICULARES PARA LA
  CONTRATACIÓN DE LOS SERVICIOS DE CONSULTORÍA DE PROCESOS, PARAMETRIZACIÓN, DESARROLLO
  Y MANTENIMIENTO DE UN SISTEMA INTEGRADO DE GESTIÓN (SIG) PARA LA GESTIÓN DEL PARQUE DE
  ALQUILER ASEQUIBLE"
- **PPT**, 40 páginas — mismo objeto
- **Memoria justificativa**

Ambos coinciden en importe con BOE-B-2025-28508 y BOE-B-2026-771.

---

## 1. HALLAZGO PRINCIPAL — las licencias están fuera del contrato

**PPT, cláusula 7.3 "LICENCIAS COMERCIALES", cita literal:**

> "El presente contrato **no incluye la contratación de las licencias de la tecnología
> base** que sean necesarias. Estas serán contratadas a continuación siguiendo los
> procedimientos abiertos por la Dirección General de Racionalización y Centralización
> de la Contratación."

**Queda confirmado documentalmente**, y ya no como hipótesis:

- Los 1.184.998 € adjudicados retribuyen **trabajo**: consultoría, parametrización,
  desarrollo, implantación, formación, soporte y mantenimiento.
- **El coste de las licencias de plataforma es adicional**, se contrata por vía separada
  a través de la DGRCC, y no consta en la cifra que se difunde públicamente como coste
  del sistema.

El adjudicatario debe detallar qué licencias se necesitan, por roles e intensidad de uso,
para producción y para desarrollo/pruebas. Cualquier otro software comercial que SEPES no
posea corre a cargo del adjudicatario.

## 2. La tecnología CRM NO es obligatoria

**PPT, pág. 31, cita literal:**

> "Se considerarán como soluciones principales para el CRM y GESTOR DE CASOS de las que
> se conoce su funcionalidad: Microsoft Dynamics 365. Salesforce. SAP. **Otras
> soluciones: Se podrán admitir otras tecnologías siempre que demuestren
> interoperabilidad, escalabilidad, madurez, durabilidad y compatibilidad**…"

Coherente con el perfil exigido en el PCAP: "Especialista desarrollador en CRM
(Dynamics/Salesforce/SAP/**Alternativa Justificada**)".

**Consecuencia para este informe, y es importante:** el pliego **no impuso** una
plataforma propietaria. Dejó la puerta abierta a una alternativa justificada. La
arquitectura que este informe propone **habría sido admisible en esta licitación.**

Esto reorienta el análisis: la dependencia de plataforma no procede de una imposición del
pliego, sino de una **decisión de diseño de la solución ofertada**. Es una distinción
relevante y hay que hacerla con precisión, sin atribuir intenciones a nadie.

El criterio de adjudicación del 2 % por "certificados de la calificación otorgada por el
fabricante de la tecnología CRM" sí introduce, sin embargo, un incentivo económico
—modesto— a elegir una tecnología con fabricante certificador.

## 3. ENS de nivel ALTO — corrección importante

**PPT, pág. 34:**

> "La solución propuesta cumplirá con lo establecido en el Real Decreto 311/2022, de 3 de
> mayo, por el que se regula el Esquema Nacional de Seguridad, **de nivel alto**"

Y como **requisito obligatorio de solvencia**, no como criterio puntuable
(PCAP, cláusula 7ª):

> "El licitador deberá contar adicionalmente con las siguientes certificaciones:
> Certificación de **Nivel Alto** en el Esquema Nacional de Seguridad (ENS), o
> equivalente […] ISO 27001 […] ISO 27017 […] ISO 27018"

**Este dato corrige al alza las exigencias de seguridad de cualquier propuesta
alternativa.** Nivel alto implica el conjunto más exigente de medidas del Anexo II y
auditoría externa acreditada. Debe reflejarse en el diseño y en el coste.

**Advertencia:** no confundir estas certificaciones —eliminatorias, para poder licitar—
con las del BOE de formalización (ISO 33000 2 %, ISO 9001 2 %, UNE-ISO 20000 2 %,
certificación de fabricante CRM 2 %), que son sólo criterios de puntuación.

## 4. AUSENCIA — no hay cláusula de propiedad intelectual ni de reversibilidad

Búsqueda literal en los textos completos de PCAP y PPT de: "propiedad", "cesión de
derechos", "derechos de explotación", "código fuente", "reversibilidad", "plan de salida".
**Cero coincidencias.** El índice de las 21 cláusulas del PCAP no incluye ninguna
dedicada a propiedad intelectual ni a reversibilidad.

Lo único próximo es la cláusula 7.1 del PPT, "TRANSFERENCIA TECNOLÓGICA":

> "el contratista se compromete […] a facilitar […] la información y documentación que
> éstas soliciten para disponer de un pleno conocimiento de las circunstancias en que se
> desarrollan los trabajos […] de las tecnologías, métodos, y herramientas utilizadas"

Es una obligación genérica de transparencia técnica. **No es cesión de código, ni plan de
salida, ni transferencia a otro adjudicatario.**

`[NO VERIFICADO]` Podría existir en el Anexo I (modelo de contrato), que no forma parte
de los dos PDF descargados. Debe comprobarse antes de afirmarlo de forma concluyente.

**Es el hallazgo de gobernanza más serio del informe.** Un contrato de cuatro años y ~1,2
M€ para construir el sistema de información central de un organismo público, sin cláusula
expresa de propiedad del código ni de reversibilidad, deja al organismo en una posición
débil al vencimiento. Se formula como observación técnica y contractual, no como
imputación de irregularidad: la LCSP y la Ley 40/2015 contienen previsiones generales, y
puede haber previsiones en el modelo de contrato no verificadas aquí.

## 5. Volumetrías — verificadas (PPT, pág. 38)

| Rol | Año 1 | Año 2 | Años 3-4 |
|---|---|---|---|
| Gestores de viviendas (internos) | 5 | 10 | **17** |
| Gestión financiera | 2 | 3 | 4 |
| Control y BI | 2 | 3 | 3 |
| **Solicitantes potenciales** | 50.000 | 50.000 | **50.000** |
| **Inquilinos potenciales** | 15.000 | 30.000 | **50.000** |
| Proveedores | 15 | 20 | **25** |
| **Gestores de vivienda externos** | 35 | 70 | **115** |
| Gestor de CRM para suelos (internos) | — | 7 | — |

Las volumetrías del encargo quedan **confirmadas en fuente primaria**.

## 6. Mantenimiento evolutivo

**PPT, págs. 28-29:**

> "El licitador deberá incluir en su planificación una fase de soporte post-implantación
> con una duración de dos años, y por un total estimado de **9.000 horas** o, en su caso,
> el número de horas ofertado."

**PCAP, cláusula 13ª:** no hay precio/hora fijo. La fórmula es:

> "El importe de la hora se obtendrá de dividir por el número de horas de la bolsa
> ofrecida el **18 % del total del contrato**."

**Observación sobre el incentivo que genera esta fórmula.** Si la bolsa retribuida es un
porcentaje fijo del contrato dividido entre las horas ofertadas, **ofertar más horas
reduce el precio por hora**, no aumenta el importe. Con 9.000 horas, el 18 % de
1.184.998 € da ~23,70 €/hora; con las 14.000 horas citadas en el encargo, ~15,24 €/hora.
Se deja constancia del cálculo, sin extraer conclusiones sobre la conducta de nadie.

## 7. SLA — más limitado de lo que suele suponerse

- Horario de soporte: **lunes a viernes, 9:00-18:00**. Fuera de ese horario, sin servicio.
- Escalado a 24 horas sólo ante "fallos graves que imposibiliten el uso de al menos el
  50 % de las funcionalidades", con respuesta en menos de 2 horas.
- Respuesta a peticiones de mantenimiento: 2 días laborables (4 si superan 20 h estimadas).
- **No hay compromiso de disponibilidad porcentual. No hay penalización ligada a
  disponibilidad.** Las únicas penalidades del PCAP (cláusula 14ª) son las genéricas de
  la LCSP por demora y por impago a subcontratistas.

**Consecuencia analítica.** El argumento habitual "una plataforma enterprise da soporte y
responsabilidad contractual" queda **matizado por el propio pliego**: el servicio
contratado es de horario de oficina y sin compromiso de disponibilidad. Cualquier
comparación debe medirse contra este nivel de servicio real, no contra uno imaginado.

## 8. Accesibilidad

**PPT, 4.6.8:** "Cumplimiento con **WCAG 2.1** para garantizar accesibilidad universal.
Optimización móvil…". No se cita EN 301 549 de forma literal, aunque es la norma
armonizada aplicable por el RD 1112/2018.

## 9. Integraciones exigidas — lista literal (PPT, pág. 32)

> "Como mínimo […] se deberán integrar con: SAP Financiero incluyendo los módulos: SAP
> Tesorería, SAP TRM Tesorería Extendida, SAP Económico-Financiero, SAP EC-CS
> Consolidación, SAP CO Gestión de Costes, SAP PS Gestión de Proyectos, SAP MM Compras,
> SAP SD Ventas, SAP BW, SAP BO, SAP RE-FX, SAP ERP option for e-Document processing
> (Add-on), SAP Cloud Platform Integration service, e-Document Cockpit, SIGES.Net y Bases
> de datos SQL Server, SITE (En desarrollo), Gestor Documental ALFRESCO (En Desarrollo)"

Más los servicios de la AGE: "Punto acceso general y Carpeta Ciudadana, Clave, Autofirma,
Face y Facturae, REGAGE y Registro electrónico común, Red Sara, Plataforma de
intermediación de Datos, Notifica, DEHU, Registro y Sede Electrónica, Catastro."

**Esta lista es la mejor prueba de la tesis de complejidad intrínseca de §6.** Catorce
módulos SAP más una decena de servicios de la AGE es trabajo duro que ninguna elección de
arquitectura evita.

## 10. Alojamiento

- PPT, pág. 31: la solución debe tener "soporte para trabajo en la nube", sin especificar
  de quién.
- PCAP, cláusula 8ª, como condición para formalizar: "declaración en la que ponga de
  manifiesto **dónde van a estar ubicados los servidores** y desde dónde se van a prestar
  los servicios […] debiendo situarse ambos dentro del territorio del **Espacio Económico
  Europeo**."

El alojamiento lo aporta el adjudicatario. La única restricción es el EEE.

## 11. Perfiles exigidos — dato incómodo para nuestra propuesta

PCAP, cláusula 7ª. Perfiles con requisitos mínimos: consultor senior en procesos,
arquitecto de soluciones (CRM/ERP, microservicios, nube), **desarrollador backend
(Java/Python/Node, PostgreSQL/MySQL/MongoDB)**, desarrollador frontend
(React/Angular/Vue, WCAG 2.1), especialista en seguridad y cumplimiento, especialista
desarrollador en CRM (mínimo tres personas), e ingeniero de integraciones.

**Hay que señalarlo aunque no nos convenga:** el pliego enumera Java, Python y Node como
tecnologías de backend. **PHP no aparece.** Una propuesta basada en Laravel habría tenido
que argumentar la equivalencia del perfil. No es un impedimento —la lista no es cerrada y
el pliego admite alternativas justificadas—, pero es un dato real y se recoge en las
decisiones abiertas del informe.

**Dos observaciones adicionales sobre esta lista:**

1. El pliego ya prevé **PostgreSQL** entre las bases de datos del perfil de backend.
2. El pliego exige explícitamente **arquitectura de microservicios** en el perfil de
   arquitecto. Es un requisito de perfil, no de solución, pero muestra el sesgo de partida
   hacia arquitecturas distribuidas que este informe cuestiona con argumentos.
