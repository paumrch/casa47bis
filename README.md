# CASA 47: arquitectura mínima

Implementación alternativa del sistema de gestión del parque público de alquiler
asequible, con las mediciones para compararla con la solución en producción.

| | |
|---|---|
| **Estado** | Fase 2 de 5. Dominio y portal público implementados |
| **Pila** | PHP 8.3 / Laravel 13, PostgreSQL 17, HTML server-rendered, almacenamiento S3, OCI |
| **Pruebas** | 166 verdes contra PostgreSQL real |
| **Análisis estático** | PHPStan nivel 8, 0 errores |
| **Fronteras de módulos** | deptrac, 0 violaciones, 0 sin cubrir |
| **Dependencias directas** | 3 de producción, 0 de JavaScript |
| **Licencia de aplicación** | 0 EUR por usuario |
| **Código** | EUPL-1.2 |

---

## 1. Contexto

| Dato | Valor | Fuente |
|---|---|---|
| Órgano de contratación | SEPES / Entidad Estatal de Vivienda (marca CASA 47) | BOE-B-2025-28508 |
| Expediente | 132019, procedimiento abierto, CPV 72000000 | BOE-B-2025-28508 |
| Valor estimado | 1.880.949,00 EUR sin IVA | BOE-B-2025-28508 |
| Importe adjudicado | 1.184.998,00 EUR sin IVA, 4 años | BOE-B-2026-771 |
| Adjudicataria | EY Transforma Servicios de Consultoría, S.L. | BOE-B-2026-771 |
| Ofertas presentadas | 8 (2 PYME) | BOE-B-2026-771 |
| Volumetría | 50.000 solicitantes, 50.000 inquilinos, 17 gestores internos, 115 externos | PPT p. 38 |
| ENS exigido | Nivel alto, también como solvencia del licitador | PPT p. 34, PCAP cl. 7 |
| Plataforma en producción | Microsoft Power Pages sobre Dataverse | Observación directa |

Descomposición del importe según la fórmula del propio pliego (mantenimiento = 18 % del
contrato):

```
Construcción e implantación   82 %     971.698 EUR    ~2 años
Mantenimiento evolutivo       18 %     213.300 EUR    9.000 h -> 23,70 EUR/h
```

---

## 2. Hallazgos

### 2.1 Sobre el contrato

| # | Hallazgo | Evidencia |
|---|---|---|
| 1 | Las licencias de plataforma quedan **fuera** del importe adjudicado | PPT cl. 7.3: *"El presente contrato no incluye la contratación de las licencias de la tecnología base que sean necesarias"* |
| 2 | El pliego **no imponía** CRM comercial | PPT p. 31: *"Otras soluciones: Se podrán admitir otras tecnologías siempre que demuestren interoperabilidad, escalabilidad, madurez, durabilidad y compatibilidad"* |
| 3 | **No existe** cláusula de propiedad intelectual, cesión de código ni reversibilidad | Búsqueda literal de "propiedad", "código fuente", "cesión de derechos", "reversibilidad" y "plan de salida" en PCAP y PPT completos: 0 coincidencias |
| 4 | SLA contratado: L-V 9:00-18:00, sin compromiso de disponibilidad porcentual ni penalización asociada | PPT p. 30, PCAP cl. 14 |

Consecuencia de (1): el coste de plataforma escala con usuarios únicos autenticados por
mes natural, sin acumulación de capacidad no consumida. A tarifa de lista y escalón
aplicable, un pico de 50.000 usuarios/mes son ~450.000 USD/año en esa única línea.

### 2.2 Sobre el portal en producción

Reconocimiento pasivo, 2026-09-08. Sólo peticiones GET a recursos públicos, DNS, TLS y
lectura del bundle servido. Sin fuzzing, sin formularios, sin credenciales, sin invocar
los endpoints descubiertos.

| Observación | Valor |
|---|---|
| HTML servido en la raíz | 1.627 bytes, sin contenido |
| Bundle JavaScript | **1.333.490 bytes**, bloqueante |
| Plantilla | `<!-- Default studio template. Please do not modify -->` sin modificar |
| API de datos | `_api/accounts`, `_api/contacts`, `_api/incidents`, `_api/cloudflow` (Dataverse) |
| Entidades a medida | prefijo `ey_dv_cus_*` |
| Orígenes externos permitidos por CSP | 8 (Clarity, Application Insights, c.bing.com, SharePoint, Google Fonts, YouTube, OpenFreeMap, Floorfy) |
| Cookies sin autenticar | `ARRAffinity`, `Dynamics365PortalAnalytics`, `WebPageCaching` |
| TTFB | ~245 ms |
| `/robots.txt` | 404 |
| `/sitemap.xml` | 200 con cuerpo de página de error |
| `/.well-known/security.txt` | ausente |

Lectura: el portal ciudadano no usa las capacidades declarativas de Power Pages. Es una
SPA a medida servida dentro de la plantilla por defecto. La justificación habitual de una
plataforma low-code no aplica a lo observable en la parte pública.

Valoración en sentido contrario: la postura de cabeceras de seguridad del portal es buena
(HSTS con preload, CSP explícita, `nosniff`, `frame-ancestors 'none'`) y viene de fábrica
con la plataforma. Cualquier alternativa debe igualarla.

---

## 3. Arquitectura

```
Ciudadano / Gestor
        |
        v
  CDN + WAF + TLS
        |
        v
   Balanceador
    /        \
 App 01    App 02        Laravel: web, backoffice, jobs, scheduler
    \        /
        |
   +----+--------------------+
   |         |               |
PostgreSQL  Workers      S3 storage
 (HA)       (colas +
             outbox)
                |
          Adaptadores
           /    |     \
       Cl@ve  Firma  DEHú  ...
                |
         Pasarela Red SARA (NubeSARA / PdP)
                |
           PID / SCSP / SIR
```

### 3.1 Componentes y justificación

| Componente | Decisión | Motivo |
|---|---|---|
| Aplicación | Monolito modular Laravel | Equipo de 3-6 personas, flujo fuertemente transaccional, carga estacional previsible |
| Interfaz | Blade, HTML server-rendered | Accesibilidad y rendimiento por defecto; islas JS sólo donde haya requisito |
| Datastore | PostgreSQL único | Datos, sesiones, colas, locks, FTS, outbox y auditoría |
| Colas | `FOR UPDATE SKIP LOCKED` sobre PostgreSQL | Encolar dentro de la misma transacción que el cambio de estado |
| Eventos | Transactional outbox | Atomicidad entre cambio de estado e intención de notificar |
| Workflow | FSM en el dominio | Un procedimiento reglado cambia cuando cambia la norma, no semanalmente |
| Documentos | Object storage S3 | Binarios fuera de la base: copias, restauración y réplica |
| Integraciones | Ports and adapters | Los servicios de la AGE cambian de protocolo y de nombre |
| Backoffice | Misma aplicación y modelo | Elimina la sincronización entre dos representaciones del expediente |

### 3.2 Componentes descartados

| Descartado | Umbral que obligaría a introducirlo |
|---|---|
| Redis | Latencia de toma de trabajo > 5 s sostenida en pico, o contención medida en la tabla de jobs |
| Motor de búsqueda | p95 de consulta de catálogo > 200 ms tras índices y caché |
| Bus de eventos | Consumidor externo que exija replay del histórico o suscripción continua |
| Motor BPM | Necesidad acreditada de modificar el flujo en producción sin ciclo de despliegue |
| PostGIS | Primer requisito con geometrías de área |
| Microservicios | Módulo con perfil de escalado o ventana de despliegue incompatible, medido |
| Kubernetes | Dos réplicas tras balanceador dejan de cubrir el objetivo de servicio |

Regla del proyecto: ningún componente entra hasta que exista un problema concreto,
medible y documentado que resuelva mejor que las piezas ya presentes.

### 3.3 Módulos

```
app/
  Domains/
    Applications/    Solicitud, FSM, snapshot de unidad de convivencia
    Eligibility/     Reglas versionadas, PHP puro sin framework
    Housing/         Promociones, viviendas, scopes de catálogo
    Calls/           Convocatorias, plazos, baremo aplicable
    Documents/       Metadatos, validación, escaneo, URLs firmadas
    Notifications/   Decisión fehaciente vs aviso
    Audit/           Eventos inmutables encadenados por hash
    Shared/Outbox/   Recorder, dispatcher, handlers
  Integrations/
    Contracts/       Puertos: identidad, verificación, notificación, storage
    Scsp/            UnavailableDataVerificationGateway (por defecto)
    Storage/         FilesystemDocumentStorage (S3 y local)
    Testing/         Dobles
  Http/Controllers/Portal/
```

Fronteras verificadas con deptrac. Reglas relevantes:

```
Eligibility          -> sin dependencias de framework
IntegrationContracts -> sin dependencias
Housing              -> no conoce Calls (la dirección es la inversa)
Documents            -> sólo Audit e IntegrationContracts
Notifications        -> SharedDomain e IntegrationContracts
```

---

## 4. Métricas

### 4.1 Peso del portal público

Medido 2026-09-08 sobre catálogo con filtros y 12 fichas.

| Página | HTML | Peticiones | JavaScript | Cookies |
|---|---|---|---|---|
| `/` | 5.862 B | 2 | 0 | 0 |
| `/viviendas` | 19.814 B | 2 | 0 | 0 |
| `/viviendas?dormitorios=2` | 19.625 B | 2 | 0 | 0 |
| `/convocatorias` | 3.966 B | 2 | 0 | 0 |
| `/requisitos` | 4.456 B | 2 | 0 | 0 |

```
portal.css                      8.487 B  (gzip 2.822 B)
Catálogo completo (HTML + CSS) 28.301 B
Bundle del portal analizado 1.333.490 B  (sólo JS)
Ratio                              47,1x
```

El ratio compara nuestro HTML más nuestro CSS contra sólo el JavaScript ajeno. La
comparación juega en contra de la propuesta a propósito.

### 4.2 Tamaño

| Métrica | Valor |
|---|---|
| Líneas de aplicación (app, migraciones, factories, seeders, routes, config) | 7.587 |
| Líneas de pruebas | 3.203 |
| Vistas Blade | 12 |
| Migraciones / tablas | 34 / 50 |
| Dependencias directas de producción | 3 |
| Dependencias instaladas (prod, transitivas) | 82 |
| Dependencias de JavaScript | 0 (no existe `package.json`) |
| Contenedores en producción | 3 (app, worker, outbox) |
| Bases de datos | 1 |

### 4.3 Coste

| Concepto | Importe |
|---|---|
| Infraestructura anual (Azure, precios de la Retail Prices API) | ~30.300 EUR |
| Licencia de aplicación por usuario | 0 EUR |
| Coste marginal del ciudadano n+1 | céntimos de cómputo |
| CAPEX estimado, 30-34 persona-mes más auditorías | 310.000-370.000 EUR |

Dimensionado sobre el mismo proveedor de nube que la solución actual, deliberadamente:
así ningún ahorro se atribuye al alojamiento.

---

## 5. Implementación

### 5.1 Completado

| Área | Detalle |
|---|---|
| Portal público | Catálogo con filtros GET, fichas, convocatorias, requisitos, páginas legales |
| Portal sin estado | Sin sesión, sin CSRF, sin cookies; respuestas cacheables en CDN |
| Esquema | 34 migraciones, 50 tablas, verificado contra PostgreSQL 17 |
| FSM del procedimiento | 13 estados, transiciones exhaustivas, estados finales irreversibles |
| Elegibilidad | Reglas versionadas (`RuleSet2026`), `Money` en céntimos, evaluación sin cortocircuito |
| `SubmitApplication` | Estado, transición, auditoría y outbox en una transacción |
| `VerifyApplication` | Modo degradado ante indisponibilidad de servicios AGE |
| Auditoría | Sólo inserción, particionada por mes, encadenada por hash, `verifyChain()` |
| Outbox | Reintentos con backoff, dead letters sin descarte, backlog observable |
| Notificación | Separación fehaciente / aviso, con adaptador de pruebas |
| Documentos | MIME real por contenido, deduplicación por hash con restricción en BD, URLs firmadas, escáner como puerto |
| Infraestructura | Dockerfile multietapa (FrankenPHP), compose, CI de 6 trabajos, Makefile |

### 5.2 Pendiente

| Área | Bloqueado por |
|---|---|
| Zona autenticada del ciudadano | Falta `FakeIdentityGateway`; el puerto existe |
| Asistente de solicitud con borrador | Depende de la anterior |
| Backoffice de gestión | - |
| Baremación y desempate | Tabla `scores` existe, lógica no |
| Adjudicación, contratos y recibos | Tablas existen, lógica no |
| Cifrado a nivel de campo (art. 9 RGPD) | Diseñado y documentado en el esquema, no implementado |
| Adaptadores Cl@ve, FIRe, SCSP reales | Alta administrativa, convenio de cesión, certificados, conectividad SARA |
| Adaptador del sistema económico | Depende del sistema destino |
| Lighthouse y prueba de carga | Fase 5 |

---

## 6. Verificaciones automáticas

Cada afirmación del proyecto tiene una comprobación que rompe la CI si deja de ser cierta.

| Afirmación | Mecanismo |
|---|---|
| El portal no entrega JavaScript | Recuento de `<script>` por página |
| El portal no carga orígenes externos | Análisis de `src`/`href` en el HTML servido |
| El portal no instala cookies | Inspección de cabeceras de respuesta |
| El peso no crece | Presupuesto de bytes por página, límite duro |
| El dominio no depende del framework | deptrac, capa `Eligibility` sin `Framework` |
| No hay dependencias sin clasificar | `deptrac --fail-on-uncovered` |
| Una vivienda no se adjudica dos veces | Índice único parcial `WHERE status = 'active'` |
| Un documento no se duplica en un expediente | Índice único `(documentable, checksum)` |
| Nada queda a medias si falla un paso | Se rompe el último paso y se comprueba el rollback |
| Dos workers no toman el mismo evento | Dos conexiones simultáneas contra la misma bandeja |
| La auditoría detecta manipulación | Se altera un evento y `verifyChain()` lo localiza |
| El enum de estados y el `CHECK` coinciden | Comparación contra `pg_get_constraintdef` |
| Las reglas y las comprobaciones coinciden | Comparación de `describe()` con `REQUIRED_CHECKS` |
| El sistema es portable | Suite completa contra dos backends de almacenamiento |
| El procedimiento no se detiene si falla la AGE | Se programa la caída y se comprueba el desenlace |

```bash
composer check   # pint, phpstan, deptrac, phpunit
```

---

## 7. Límites del análisis

Enunciados explícitamente para que puedan contrastarse.

| Límite | Detalle |
|---|---|
| El importe adjudicado no es desproporcionado | 296.250 EUR/año para el alcance contratado. La tesis de "esto se hacía por mucho menos" no se sostiene en la partida de trabajo |
| La complejidad intrínseca no se abarata | Normativa, SCSP, firma, ENS alto, accesibilidad y SAP cuestan igual en ambas arquitecturas |
| A 4 años con uso bajo los rangos se solapan | No puede afirmarse ahorro en ese supuesto |
| No se ha visto la zona autenticada del portal analizado | Toda observación se limita a la superficie pública |
| Precios de licencia son de lista | No se conoce el precio real; el modelo usa rangos con hipótesis declaradas |
| Un equipo de 4 personas no puede licitar esto | El PCAP exige ENS nivel alto, ISO 27001, 27017 y 27018 al licitador. Salida propuesta: separar construcción de operación |
| El PCAP lista Java, Python y Node como backend | PHP no aparece. La tesis del informe no depende del lenguaje: sobrevive en .NET, Java y Python |

---

## 8. Ejecución

```bash
make up          # app, PostgreSQL 17, MinIO, workers
make migrate
make test        # 166 pruebas
make fresh       # migrate:fresh --seed con catálogo de demostración
make restore-test # ensayo cronometrado de restauración de copia
```

Las pruebas exigen PostgreSQL, no SQLite en memoria: el esquema usa índices únicos
parciales, tablas particionadas y extensiones.

Variables de entorno y despliegue en [`docs/operacion.md`](docs/operacion.md).

---

## 9. Documentación

| Documento | Contenido |
|---|---|
| [`docs/CASA47-minimal-architecture-review.md`](docs/CASA47-minimal-architecture-review.md) | Informe completo: 25 secciones, revisión adversarial, red team, TCO a 1/4/5/10 años |
| [`docs/que-compro-el-estado.md`](docs/que-compro-el-estado.md) | Descomposición del contrato y estimación de esfuerzo |
| [`docs/operacion.md`](docs/operacion.md) | Despliegue, copias, restauración, migración de proveedor |
| [`docs/research/01..06`](docs/research/) | Informes de investigación con las fuentes primarias |
| [`PLAN.md`](PLAN.md) | Fases, definición de terminado y mecanismos de control |

---

## 10. Alcance y advertencias

- Demostrador técnico. Los datos de viviendas y convocatorias son ficticios y generados.
- No contiene imputaciones sobre personas ni sobre la legalidad del procedimiento de
  contratación, que fue abierto y con 8 ofertas.
- El informe dedica un capítulo a las capacidades reales de Power Pages y a lo que la
  plataforma resuelve bien.
- Toda cifra lleva fuente o está marcada como hipótesis.

## Licencia

EUPL-1.2. Compatible con la reutilización de aplicaciones entre administraciones prevista
en el artículo 157 de la Ley 40/2015.
