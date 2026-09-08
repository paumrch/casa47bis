# CASA 47 · Sistema de gestión del parque de alquiler asequible

Una demostración, en código que se ejecuta, de que un sistema administrativo público
puede ser **simple, portable, auditable y barato de abandonar** sin renunciar a nada.

> **Estado:** en construcción, en abierto. Se publica lo que está terminado.

---

## De qué va esto

El Estado adjudicó en noviembre de 2025 un contrato de **1.184.998 € sin IVA a cuatro
años** para el sistema de gestión del parque público de alquiler asequible. Las licencias
de la plataforma **no están incluidas** en esa cifra: el pliego las deja expresamente
fuera. El portal ciudadano resultante entrega **1,33 MB de JavaScript** para pintar un
listado de viviendas.

Este repositorio no es una crítica. Es una **contrapropuesta ejecutable**.

Dos preguntas, y las dos se responden con hechos verificables, no con opiniones:

1. **¿Qué compró el Estado con ese dinero?** → [`docs/que-compro-el-estado.md`](docs/que-compro-el-estado.md)
2. **¿Cómo debería ser el código?** → este repositorio.

## La tesis, en una línea

> La complejidad de este problema es casi toda **intrínseca** —normativa, interoperabilidad
> con la Administración, seguridad, accesibilidad— y hay que pagarla igual. Lo que separa
> a las dos arquitecturas es una licencia que crece con el número de ciudadanos atendidos
> y un coste de salida de meses en lugar de días.

El análisis completo, con sus fuentes y su revisión adversarial, está en
[`docs/CASA47-minimal-architecture-review.md`](docs/CASA47-minimal-architecture-review.md).
Incluye las conclusiones que **contradicen** nuestra hipótesis de partida, porque un
informe que sólo confirma lo que ya pensaba su autor no vale nada.

## La pila

```text
Laravel · PostgreSQL · HTML renderizado en servidor · almacenamiento S3 · contenedor OCI
```

Y nada más. Sin Redis, sin motor de búsqueda, sin bus de eventos, sin motor de procesos,
sin microservicios. Cada ausencia está justificada en el informe, componente a componente,
con la señal medible que nos obligaría a cambiar de opinión.

PostgreSQL es el **único** almacén: datos, sesiones, colas, cerrojos, búsqueda, bandeja de
salida de eventos y auditoría.

## Qué hay construido

| Pieza | Estado | Qué demuestra |
|---|---|---|
| Máquina de estados del procedimiento | ✅ | Un procedimiento administrativo no necesita un BPM |
| Reglas de elegibilidad versionadas | ✅ | Una solicitud de 2026 se reevalúa en 2029 con las reglas de 2026 |
| Aritmética de importes en céntimos | ✅ | Un redondeo mal hecho excluye a una familia |
| Puertos de integración | ✅ | Cl@ve, SCSP, DEHú y S3 son adaptadores sustituibles |
| Esquema PostgreSQL (34 migraciones, 50 tablas) | ✅ | Verificado contra PostgreSQL 17 real |
| Unicidad de adjudicación bajo concurrencia | ✅ | La garantía está en la base de datos, no en PHP |
| Presentación de solicitud, transaccional | ✅ | Estado, auditoría y notificación: los tres o ninguno |
| Auditoría inmutable encadenada por hash | ✅ | Borrar o alterar un evento se detecta |
| Bandeja de salida transaccional | ✅ | Reintentos, bandeja de fallos y atasco observable |
| Colas sobre PostgreSQL, sin Redis | ✅ | `SKIP LOCKED` probado con dos conexiones simultáneas |
| Catálogo de viviendas y convocatorias | ✅ | Modelos, filtros, búsqueda tolerante a erratas |
| Fronteras entre módulos | ✅ | Verificadas por CI, no declaradas |
| **Portal público completo** | ✅ | **Cero JavaScript, cero cookies, 2 peticiones** |
| Presupuesto de bytes que rompe la CI | ✅ | El peso no puede crecer sin que nadie se entere |
| Asistente de solicitud | ⏳ | |
| Backoffice de gestión | ⏳ | |
| Adaptadores reales (SCSP, Cl@ve, firma) | ⏳ | |

## Lo que verificamos, en vez de afirmarlo

Es la regla del proyecto: **una propiedad que no se comprueba automáticamente desaparece
en seis meses.**

| Afirmación | Cómo se comprueba |
|---|---|
| El dominio no depende del framework | `deptrac` — la CI falla si alguien lo rompe |
| No hay errores de tipo | PHPStan nivel 8 |
| El procedimiento no tiene estados inalcanzables | Recorrido del grafo en una prueba |
| Los umbrales de renta son exactos al céntimo | Casos límite por encima y por debajo |
| Una vivienda no se adjudica dos veces | Índice único parcial + prueba de violación |
| El portal no entrega JavaScript | Se cuenta cada etiqueta `<script>` en cada página |
| El portal no carga nada de terceros | Se buscan orígenes externos en el HTML servido |
| El portal no instala cookies | Se inspeccionan las cabeceras de la respuesta |
| El peso no crece sin control | Presupuesto de bytes por página, con límite duro |
| El sistema es portable | Suite ejecutada contra dos almacenamientos distintos |
| Nada queda a medias si falla un paso | Se rompe el último paso y se comprueba que no queda rastro |
| Dos trabajadores no toman el mismo evento | Dos conexiones reales compitiendo por la misma bandeja |
| La traza de auditoría detecta manipulación | Se altera un evento y la verificación lo señala |
| El dominio y la base dicen lo mismo | La restricción `CHECK` se compara con la enumeración |

```bash
composer check   # lint · análisis estático · fronteras · pruebas
```

## La cifra

El portal analizado entrega **1.333.490 bytes de JavaScript** para pintar un listado de
viviendas, sobre una plantilla vacía de 1.627 bytes de HTML.

Este portal, medido el 8 de septiembre de 2026 sobre el catálogo completo con paginación,
filtros y 12 fichas:

| Página | HTML | Peticiones | JavaScript | Cookies |
|---|---|---|---|---|
| Inicio | 5.862 B | 2 | **0** | **0** |
| Catálogo de viviendas | 19.814 B | 2 | **0** | **0** |
| Catálogo filtrado | 19.625 B | 2 | **0** | **0** |
| Convocatorias | 3.966 B | 2 | **0** | **0** |
| Requisitos | 4.456 B | 2 | **0** | **0** |

La hoja de estilos —una, escrita a mano— pesa **8.487 bytes** (2.822 comprimida) y se
comparte entre todas las páginas.

> **Catálogo completo: 28.301 bytes** (HTML + CSS) frente a **1.333.490 bytes** de sólo el
> JavaScript del portal analizado. **47 veces menos**, contando el CSS de nuestro lado y
> sin contar el HTML ni el CSS del suyo.

Estas cifras no están escritas a mano en este README: hay un **presupuesto de bytes** que
rompe la integración continua si alguien las empeora. El día que se añada una librería
«pequeña» al portal público, la CI se pondrá en rojo y habrá que justificarlo en la
revisión, que es donde debe discutirse.

**Este proyecto no tiene `package.json`.** No hay npm, ni Vite, ni Tailwind, ni
`node_modules`, ni paso de compilación de front-end. Se eliminaron cuando quedó claro que
no resolvían ningún problema que este portal tuviera. La imagen de contenedor perdió con
ello una etapa entera de construcción.

## Cómo levantarlo

```bash
make up        # aplicación, PostgreSQL y almacenamiento de objetos
make migrate
make test
```

Las pruebas exigen PostgreSQL de verdad, no SQLite en memoria: el esquema usa índices
únicos parciales, tablas particionadas y extensiones, y una suite verde sobre SQLite no
demostraría nada sobre lo que se despliega.

Detalle de operación, despliegue y copias en [`docs/operacion.md`](docs/operacion.md).

## Principio de diseño

> Ningún componente entra en la arquitectura hasta que exista un problema concreto,
> medible y documentado que resuelva mejor que las piezas que ya existen.
> **La complejidad debe ganarse el derecho a existir.**

El plan por fases, con sus puertas y sus frenos, está en [`PLAN.md`](PLAN.md).

## Qué NO es este proyecto

- No es una acusación. No hay imputaciones sobre personas ni sobre la legalidad del
  procedimiento de contratación. El pliego, de hecho, **admitía** una alternativa como
  esta.
- No es una crítica ideológica a un fabricante. El informe dedica un capítulo entero a lo
  que Power Pages hace bien, porque hace cosas bien.
- No es «esto lo monta un freelance en un fin de semana». La estimación honesta son
  **30-34 persona-mes**, y está desglosada.

## Licencia

Código bajo **EUPL-1.2**, la licencia pública de la Unión Europea, compatible con la
reutilización de aplicaciones entre administraciones que prevé el artículo 157 de la
Ley 40/2015.
