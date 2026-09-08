# CASA 47 · Una alternativa, escrita en código

**El Estado adjudicó 1.184.998 € para construir el sistema con el que se gestionan las
viviendas públicas de alquiler asequible. Este repositorio contiene una alternativa
funcionando, y los números para compararlas.**

No es un artículo de opinión ni una propuesta comercial. Es software que se ejecuta, con
sus pruebas, sus mediciones y su código abierto para que cualquiera lo verifique.

---

## Índice

- [De qué va esto, en tres minutos](#de-qué-va-esto-en-tres-minutos)
- [Qué encontramos al mirar el portal público](#qué-encontramos-al-mirar-el-portal-público)
- [Qué dicen los pliegos del contrato](#qué-dicen-los-pliegos-del-contrato)
- [Qué hemos construido](#qué-hemos-construido)
- [Las cifras](#las-cifras)
- [Qué demuestra esto y qué no](#qué-demuestra-esto-y-qué-no)
- [Qué falta](#qué-falta)
- [Cómo comprobarlo tú mismo](#cómo-comprobarlo-tú-mismo)
- [Para quien lea código](#para-quien-lea-código)
- [Qué NO es este proyecto](#qué-no-es-este-proyecto)

---

## De qué va esto, en tres minutos

### El problema que hay que resolver

Un organismo público —la Entidad Estatal de Vivienda, que opera con la marca CASA 47—
gestiona un parque de viviendas de alquiler asequible. Necesita un sistema informático
para:

- publicar qué viviendas hay disponibles;
- recibir las solicitudes de hasta 50.000 personas;
- comprobar si cada solicitante cumple los requisitos —ingresos, residencia legal, no
  tener otra vivienda, estar al día con Hacienda—;
- ordenarlas según un baremo, adjudicar las viviendas y notificarlo con validez legal;
- y gestionar después los contratos de alquiler durante años.

Esto **no es una web**. Es una aplicación administrativa donde cada clic puede crear o
denegar un derecho, y donde todo tiene que poder justificarse ante un juez dos años
después.

### Lo que se contrató

En noviembre de 2025 se adjudicó ese sistema por **1.184.998 € sin IVA, a cuatro años**.
El portal público resultante está en `portal.casa47.es`.

### La pregunta

¿Hacía falta construirlo así? ¿Cuánto de ese coste es el problema, y cuánto es la forma
de resolverlo?

### La respuesta corta, que probablemente no es la que esperas

**El precio del contrato no es escandaloso.** Son unos 296.000 € al año para pagar a un
equipo que hace consultoría, desarrollo, formación, soporte y mantenimiento durante cuatro
años, integrándose con catorce módulos de SAP y una decena de servicios de la
Administración. Ese trabajo cuesta dinero, lo haga quien lo haga.

**El problema está en otra parte, y en tres sitios concretos.** Los detallamos a
continuación.

---

## Qué encontramos al mirar el portal público

Analizamos el portal desde fuera, como cualquier visitante: sin entrar en zonas privadas,
sin probar contraseñas, sin nada que no haga un navegador normal. Todo el método está
documentado.

### 1. El portal descarga 1,3 megabytes de programa para enseñarte una lista de pisos

Cuando abres `portal.casa47.es`, tu navegador recibe primero una página **vacía** de 1.627
bytes y después **1.333.490 bytes de JavaScript** —un programa completo— que se ejecuta en
tu móvil para dibujar el contenido.

Por qué importa: la gente que busca vivienda asequible no suele tener el último teléfono ni
la mejor conexión. Cada megabyte es tiempo de espera, datos consumidos y batería. Y si el
programa falla o no se descarga, la página se queda **completamente en blanco**: no hay
nada debajo.

### 2. La plataforma "sin programar" está programada a medida

El portal está montado sobre Microsoft Power Pages, una plataforma que se vende con el
argumento de que permite hacer aplicaciones **sin programar**.

Pero lo que se sirve al ciudadano es una aplicación **programada a medida** metida dentro
de la plantilla vacía de esa plataforma. La plantilla original ni siquiera se ha
retocado: su comentario de fábrica —"*Default studio template. Please do not modify*"—
sigue ahí.

Es decir: se está pagando el alquiler de una plataforma para no programar, **y programando
igualmente**.

### 3. El portal usa herramientas de analítica sobre páginas de vivienda social

Entre los servicios externos que carga hay una herramienta de Microsoft que permite grabar
y reproducir sesiones de usuario. En un portal donde la gente consulta ayudas a la
vivienda, el simple hecho de estar visitándolo ya dice algo sobre tu situación personal.

No afirmamos que se esté usando indebidamente. Sí decimos que es algo que hay que
justificar, y que en este proyecto se ha resuelto por la vía simple: **no cargar nada de
nadie.**

---

## Qué dicen los pliegos del contrato

Descargamos los pliegos reales del expediente desde los datos abiertos de la Plataforma de
Contratación del Estado. Tres hallazgos, todos con la cita literal en el
[informe completo](docs/CASA47-minimal-architecture-review.md).

### Las licencias no están dentro del millón

> *"El presente contrato **no incluye la contratación de las licencias de la tecnología
> base** que sean necesarias."*
> — Pliego de prescripciones técnicas, cláusula 7.3

Esos 1.184.998 € pagan **trabajo**. El alquiler de la plataforma sobre la que se construye
va aparte, por otro expediente, y **no aparece en la cifra que se difunde como coste del
sistema**.

Y ese alquiler tiene una característica incómoda: **se paga por número de ciudadanos que
usan el servicio cada mes**. A tarifa pública, atender a 50.000 personas en un mes son
unos 450.000 dólares al año sólo en esa línea. Dicho de otro modo: **cuanta más gente use
el servicio público, más alta es la factura.**

### El pliego NO obligaba a usar esa plataforma

> *"Otras soluciones: **Se podrán admitir otras tecnologías** siempre que demuestren
> interoperabilidad, escalabilidad, madurez, durabilidad y compatibilidad."*
> — Pliego de prescripciones técnicas, pág. 31

Esto es importante y hay que decirlo con cuidado: **nadie obligó a elegir esa plataforma**.
El pliego dejaba la puerta abierta. Una propuesta como la de este repositorio habría sido
admisible en aquella licitación.

### No hay ninguna cláusula sobre quién es dueño del código

Buscamos en el texto completo de los dos pliegos las palabras "propiedad", "código
fuente", "cesión de derechos", "reversibilidad" y "plan de salida".

**Cero coincidencias.**

Un contrato de cuatro años para construir el sistema de información central de un
organismo público, **sin decir de quién es el código ni cómo se sale del contrato**. Es
el hallazgo que más nos preocupa, y no es técnico: es de gobierno.

---

## Qué hemos construido

Una aplicación que hace lo mismo, con tecnología abierta y sin licencias por usuario.

### La parte pública — terminada

Cualquiera puede consultar el catálogo de viviendas, filtrarlo, ver la ficha de cada una,
consultar las convocatorias abiertas y leer los requisitos de acceso.

**Sin JavaScript. Sin cookies. Sin cargar nada de terceros.**

No hay banner de cookies porque no hay cookies que consentir. No es una promesa: se puede
comprobar en el navegador, y hay una prueba automática que impide que eso cambie sin que
nadie se entere.

### El expediente administrativo — terminado y probado

El corazón del sistema. Cuando alguien presenta una solicitud ocurren cuatro cosas —cambia
el estado, se registra en el histórico, se escribe la auditoría y se encola la
notificación— y **o pasan las cuatro, o no pasa ninguna**. Nunca queda una solicitud
registrada que nadie vaya a notificar.

Los requisitos se comprueban consultando a las administraciones que tienen los datos, para
no pedirle al ciudadano papeles que el Estado ya tiene.

### Lo que pasa cuando la Administración no responde — esto es lo importante

Los servicios del Estado se caen. Es un hecho, no una hipótesis.

Cuando eso ocurre, este sistema **no se queda bloqueado ni resuelve a ciegas**: pide la
documentación al ciudadano y el expediente sigue adelante. Porque si se detuviera, el
plazo seguiría corriendo en contra de una persona por una avería que no es suya.

Y hay un detalle pequeño que dice mucho: cuando no se puede comprobar un dato, el sistema
lo guarda como *"no se sabe"*, **no como "no cumple"**. No es lo mismo, y la diferencia
puede costarle una vivienda a alguien.

### La documentación del expediente — terminada

Se guardan nóminas, declaraciones de la renta, certificados. Fuera de la base de datos,
con enlaces de descarga que caducan en minutos, pasando por antivirus antes de darlos por
válidos, y comprobando que un fichero llamado `nomina.pdf` **es realmente un PDF** y no un
programa disfrazado.

El cifrado de los datos más sensibles —los de salud, por ejemplo— está diseñado y
documentado, pero **todavía no implementado**. Aparece en la lista de lo que falta.

---

## Las cifras

Medidas el 8 de septiembre de 2026, sobre el catálogo completo con filtros y 12 viviendas.

### Peso de las páginas

| | Portal analizado | Este proyecto |
|---|---|---|
| JavaScript descargado | **1.333.490 bytes** | **0 bytes** |
| Peso total del catálogo | — | **28.301 bytes** |
| Peticiones de red | no contadas una a una | **2** |
| Cookies instaladas | varias | **0** |
| Servicios externos que cargan | 8 | **0** |

> El catálogo completo de este proyecto pesa **47 veces menos** que *sólo* el JavaScript
> del portal analizado. Y la comparación está hecha en nuestra contra: contamos nuestro
> diseño y no contamos el suyo.

### Tamaño del proyecto

| | |
|---|---|
| Código de la aplicación | 7.587 líneas |
| Código de pruebas | 3.203 líneas |
| Pruebas automáticas | **166**, todas en verde |
| Bases de datos | **1** |
| Dependencias externas directas | **3** |
| Dependencias de JavaScript | **0** — este proyecto no tiene `package.json` |
| Licencias de software por usuario | **0 €** |

Esa última fila es la que cambia el modelo económico: aquí **atender a un ciudadano más
no cuesta más**.

### Lo que cuesta operarlo

Unos **30.000 € al año** de infraestructura, calculados **sobre el mismo proveedor de nube
que usa el sistema actual**, a propósito: así ningún ahorro puede atribuirse a habernos
mudado a un sitio más barato. El ahorro que salga, sale de la arquitectura.

---

## Qué demuestra esto y qué no

Somos igual de estrictos con nuestras propias afirmaciones.

### Lo que sí demuestra

- Que el portal ciudadano se puede servir con **dos órdenes de magnitud menos de peso**.
- Que el expediente administrativo, con su auditoría y sus garantías, **cabe en una
  aplicación normal con una base de datos normal**.
- Que el sistema **puede funcionar desde el primer día** aunque las integraciones con la
  Administración todavía no estén dadas de alta.
- Que **no hace falta pagar una licencia por cada ciudadano** que use el servicio.
- Que salir de aquí —llevarse los datos y el sistema a otro proveedor— **son días, no
  meses**.

### Lo que NO demuestra, y no vamos a fingir

- **No demuestra que el sistema completo sea más barato de construir.** La parte difícil
  —la normativa, la firma electrónica, integrarse con SAP y con los servicios del
  Estado, la certificación de seguridad— cuesta lo mismo aquí que allí. Es la mayor parte
  del trabajo.
- **No hemos integrado los servicios reales del Estado.** No por falta de código, sino
  porque exigen alta administrativa, convenios y certificados que no dependen de un equipo
  de desarrollo. La arquitectura está preparada para ello y funciona sin ellos mientras
  tanto.
- **A cuatro años y con poco uso, las dos opciones cuestan parecido.** La diferencia se
  hace grande a partir del quinto año y cuando mucha gente usa el servicio.
- **Cuatro personas no pueden firmar ese contrato.** El pliego exige a la empresa
  certificaciones de seguridad que un equipo pequeño no tiene. Lo tratamos de frente en el
  informe, y proponemos una salida: separar quién construye el software de quién lo opera.

---

## Qué falta

Con la misma honestidad. Lo que hay está terminado y probado; lo que no está, no está.

### Terminado

- [x] Catálogo público, convocatorias, requisitos y páginas legales
- [x] Modelo de datos completo (50 tablas), verificado contra PostgreSQL real
- [x] El procedimiento administrativo como máquina de estados
- [x] Reglas de acceso versionadas, para poder reevaluar una solicitud años después
- [x] Presentación de solicitud, con garantía de que no queda nada a medias
- [x] Comprobación de requisitos, incluido qué hacer si la Administración no responde
- [x] Auditoría que detecta si alguien la ha manipulado
- [x] Cola de notificaciones con reintentos y sin perder nada
- [x] Custodia de documentos con antivirus y enlaces que caducan
- [x] Infraestructura reproducible y verificación automática de todo lo anterior

### Pendiente

- [ ] **Zona privada del ciudadano**: identificarse y ver el estado de su expediente
- [ ] **Asistente de solicitud**: el formulario paso a paso, con guardado de borrador
- [ ] **Backoffice**: las pantallas con las que trabajan los gestores
- [ ] **Baremación**: ordenar y desempatar las solicitudes admitidas
- [ ] **Adjudicación, contratos y recibos**: la vida del alquiler después de la concesión
- [ ] **Adaptadores reales** de Cl@ve, firma electrónica y consulta de datos al Estado —
      requieren trámites administrativos, no código
- [ ] **Medición formal** de rendimiento y prueba de carga (fase 5 del plan)
- [ ] **Cifrado de los datos de categoría especial** a nivel de campo — diseñado y
      documentado en el esquema, pendiente de implementar

El orden de trabajo está en el [plan del proyecto](PLAN.md), junto con las reglas que nos
impiden desviarnos de él.

---

## Cómo comprobarlo tú mismo

No hace falta creernos.

### Si no programas

- Los **hallazgos sobre el contrato** están en el
  [informe completo](docs/CASA47-minimal-architecture-review.md), con la cita literal de
  cada pliego y el enlace al BOE.
- Las **cifras de peso del portal** se pueden reproducir en cualquier navegador: abre
  `portal.casa47.es`, pulsa F12, ve a la pestaña "Red" y mira lo que descarga.
- El **razonamiento económico**, con el desglose de en qué se va el dinero, está en
  [qué compró el Estado](docs/que-compro-el-estado.md).

### Si programas

```bash
make up        # levanta la aplicación y la base de datos
make migrate
make test      # las 166 pruebas
```

```bash
composer check # estilo, análisis estático, fronteras entre módulos y pruebas
```

---

## Para quien lea código

Las decisiones técnicas, con su justificación una a una, están en el
[informe de arquitectura](docs/CASA47-minimal-architecture-review.md). Lo esencial:

**La pila entera:** Laravel · PostgreSQL · HTML generado en el servidor ·
almacenamiento compatible con S3 · un contenedor estándar.

Sin Redis, sin motor de búsqueda, sin bus de eventos, sin motor de procesos, sin
microservicios. **Cada ausencia está justificada**, y cada decisión declara qué dato
concreto nos obligaría a cambiar de opinión.

**La regla del proyecto:**

> Ningún componente entra en la arquitectura hasta que exista un problema concreto,
> medible y documentado que resuelva mejor que las piezas que ya existen.
> **La complejidad debe ganarse el derecho a existir.**

**Lo que no se declara, se comprueba.** Una propiedad que no se verifica automáticamente
desaparece en seis meses, así que estas afirmaciones rompen la integración continua si
dejan de ser ciertas:

| Afirmación | Cómo se comprueba |
|---|---|
| El portal no entrega JavaScript | Se cuenta cada etiqueta `<script>` de cada página |
| El portal no instala cookies | Se inspeccionan las cabeceras de la respuesta |
| El peso no crece sin control | Presupuesto de bytes por página, con límite duro |
| El dominio no depende del framework | Comprobación de fronteras entre módulos |
| Una vivienda no se adjudica dos veces | Restricción en la base de datos, con su prueba |
| El sistema es portable | Las pruebas se ejecutan contra dos almacenamientos distintos |
| El procedimiento no se detiene si falla el Estado | Se simula la caída y se comprueba el desenlace |
| Nada queda a medias si falla un paso | Se rompe el último paso a propósito |

---

## Qué NO es este proyecto

Esto importa tanto como lo demás.

- **No es una acusación.** No hay ninguna imputación sobre personas, ni sobre la legalidad
  del procedimiento de contratación. Fue un procedimiento abierto con ocho ofertas. Lo que
  se discute son **decisiones técnicas y sus consecuencias**, que es una discusión legítima
  y necesaria.
- **No es una crítica ideológica a Microsoft.** El informe dedica un capítulo entero a lo
  que Power Pages hace bien —y hace cosas bien, empezando por su configuración de
  seguridad, que es mejor que la de muchos desarrollos a medida.
- **No es "esto lo monta un becario en un fin de semana".** Nuestra estimación honesta son
  **30-34 personas-mes**. Está desglosada y se puede discutir.
- **No es un servicio real.** Todas las viviendas y convocatorias que aparecen son
  ficticias y se generan automáticamente. Aquí no se tramita nada.

---

## Licencia

Código bajo **EUPL-1.2**, la licencia pública de la Unión Europea. Es la que permite que
cualquier administración lo reutilice, tal como prevé el artículo 157 de la Ley 40/2015.

---

*Todas las afirmaciones de este repositorio llevan fuente o están marcadas como hipótesis.
Si encuentras un error, es un error nuestro y queremos saberlo.*
