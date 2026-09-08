# Qué compró el Estado con 1,18 millones, y qué haríamos cuatro

Documento de trabajo. Todas las cifras llevan su origen. Donde hay hipótesis, se marca.

---

## 1. Descomponer el importe

El contrato son **1.184.998 € sin IVA a cuatro años**. La cifra sola no dice nada. Lo que
importa es en qué se parte.

El propio pliego da la clave para partirla. La bolsa de mantenimiento evolutivo se retribuye
con **el 18 % del total del contrato**, dividido entre las horas ofertadas. De ahí:

| Bloque | % | Importe | Periodo |
|---|---|---|---|
| Construcción e implantación | 82 % | **971.698 €** | ~2 años |
| Mantenimiento evolutivo | 18 % | **213.300 €** | 2 años posteriores |

Y una consecuencia aritmética que merece pararse:

> 213.300 € ÷ 9.000 horas = **23,70 €/hora**.

Ese es el precio implícito de la hora de mantenimiento evolutivo. Para situarlo: es
**menos de lo que cuesta la hora de un desarrollador junior** en España en 2026 una vez
cargados los costes de empresa. Si el licitador ofertó más horas —y consta que se ofertaron
5.000 adicionales—, el precio implícito baja aún más, hasta ~15,24 €/hora.

Esto no es una acusación. Es una observación sobre cómo está diseñado el incentivo: la
fórmula del pliego hace que **ofertar más horas abarate la hora**, no que aumente el
importe. El resultado previsible es que la bolsa de mantenimiento se convierta en un
compromiso nominal, y que el valor real del contrato esté casi todo en los 971.698 € de
construcción.

**Conclusión del apartado:** el Estado compró, en la práctica, **la construcción de un
sistema por ~972.000 € en dos años**, más una bolsa de mantenimiento retribuida por debajo
de mercado.

## 2. Qué equipo paga eso

A tarifas de consultoría al sector público —entre 45 y 70 €/hora facturados, con una hora
productiva anual en torno a 1.680—, 971.698 € en dos años sostienen:

| Tarifa facturada | Horas totales | Personas equivalentes a tiempo completo |
|---|---|---|
| 45 €/h | 21.593 h | **6,4** |
| 55 €/h | 17.667 h | **5,3** |
| 70 €/h | 13.881 h | **4,1** |

Es decir: entre **cuatro y seis personas durante dos años**, según la tarifa. Y en una
entrega de consultoría eso no significa cuatro seniors: significa una pirámide —jefe de
proyecto, arquitecto a tiempo parcial, dos o tres desarrolladores de perfil medio, alguien
de pruebas—, con la parte de gestión, reporting y coordinación que un contrato público
exige y que consume tiempo real.

**Este es el punto que hay que entender bien: el dinero no es absurdo. Lo que es
cuestionable es lo que se obtiene por él.** Por ~972.000 € y dos años, la entrega es un
portal ciudadano que sirve 1,33 MB de JavaScript para pintar un listado, sobre una
plataforma cuya licencia se paga aparte y cuyo modelo de datos no es del organismo.

## 3. Y ahora, cuatro personas

Aquí es donde hay que ser honesto en las dos direcciones, porque el argumento fácil no
funciona.

### 3.1 Lo que NO es cierto

**Cuatro personas no son más baratas que el contrato por unidad de tiempo.** Un senior en
España en 2026, con coste de empresa completo, sale entre 78.000 y 104.000 € al año.
Cuatro son **entre 312.000 y 416.000 € anuales**. El contrato son 296.250 € anuales.

> Cuatro seniors a tiempo completo cuestan **más al año** que el contrato adjudicado.

Cualquier argumento que empiece por "cuatro personas cuestan menos" es falso y se cae en
la primera pregunta de un interventor.

### 3.2 Dónde está el ahorro de verdad

No está en el coste por persona. Está en el **tiempo de calendario**.

El contrato paga cuatro a seis personas durante **dos años** para construir. La tesis es
que cuatro personas del perfil adecuado, con las herramientas de 2026, entregan lo mismo en
**seis a ocho meses**. Y como el coste es tiempo × personas, dividir el calendario por tres
divide el coste de construcción por tres.

Eso hay que justificarlo, no afirmarlo.

### 3.3 Dónde comprime la asistencia de IA y dónde no

La estimación de la Fase 1 fueron **55 persona-mes**. Se parte en dos mitades que se
comportan de forma radicalmente distinta:

**Trabajo mecánico — se comprime mucho** (~28 PM → **10-12 PM**)

| Trabajo | Por qué se comprime |
|---|---|
| Migraciones y esquema | Se derivan del modelo, que es la parte que sí hay que pensar |
| Pantallas de backoffice | Altamente repetitivas |
| Portal público y catálogo | Patrones conocidos |
| Pruebas unitarias y de funcionalidad | El caso de uso donde la asistencia es más efectiva |
| Adaptadores contra APIs documentadas | Catastro, correo, almacenamiento |
| Documentación técnica | |
| Accesibilidad estructural | Semántica correcta desde el primer intento |

**Trabajo irreducible — apenas se comprime** (~27 PM → **20-22 PM**)

| Trabajo | Por qué resiste |
|---|---|
| Leer la norma y traducirla a reglas | Requiere criterio jurídico y decisiones con consecuencias |
| SCSP y firma electrónica | Servicios sin documentación pública, con alta administrativa previa, entornos de prueba lentos y soporte por correo |
| Coordinación con el equipo de SAP | Es tiempo de otras personas, no nuestro |
| Conformidad ENS de nivel alto | Proceso de auditoría con calendario propio |
| Accesibilidad real | La revisión con lector de pantalla y teclado es manual |
| Concurrencia y transacciones en la adjudicación | Es donde un error crea un problema jurídico |
| Migración de datos existentes | Los datos sucios no se limpian solos |

**Total realista: 30-34 persona-mes.** Cuatro personas, siete u ocho meses.

Obsérvese lo que dice esa tabla: **la asistencia de IA no ataca la complejidad intrínseca
de §6 del informe. Ataca la accidental y la mecánica.** Que es exactamente lo que cabía
esperar, y por eso la estimación es creíble: si diera una compresión uniforme del 70 %,
sería mentira.

### 3.4 La cuenta

| Concepto | Importe |
|---|---|
| 32 persona-mes × 7.500 €/PM (coste de empresa de un senior) | **240.000 €** |
| Auditoría ENS nivel alto por entidad acreditada | 30.000 – 60.000 € |
| Auditoría de seguridad y test de intrusión | 20.000 – 35.000 € |
| Auditoría de accesibilidad | 12.000 – 20.000 € |
| Evaluación de impacto en protección de datos | 8.000 – 15.000 € |
| **Construcción, total** | **310.000 – 370.000 €** |

Frente a los **971.698 €** de la fase de construcción del contrato.

> **Diferencia en la construcción: entre 600.000 y 660.000 €.**

Y esa cifra **no incluye** las licencias de plataforma, que el pliego deja expresamente
fuera del contrato y que en la arquitectura propuesta son cero.

## 4. Por qué esto no es una fantasía, y por qué tampoco es fácil

### 4.1 El obstáculo que no se resuelve con código

Cuatro personas **no pueden firmar este contrato**. El pliego exige al licitador
certificación ENS de nivel alto, ISO 27001, ISO 27017 e ISO 27018 como requisito de
solvencia eliminatorio. Eso no lo tiene un equipo pequeño: lo tiene una organización con
departamento de cumplimiento.

Fingir que ese obstáculo no existe sería el error que invalidaría todo el trabajo.

### 4.2 La solución estructural: separar quién construye de quién opera

El obstáculo se disuelve si se deja de tratar el problema como un bloque.

```text
        MODELO ACTUAL                        MODELO PROPUESTO

  Un contrato, un proveedor          Software          Operación
  ┌───────────────────────┐      ┌──────────────┐   ┌──────────────┐
  │ Construye + Opera +   │      │ 4 personas   │   │ Operador con │
  │ Certifica + Posee     │      │ Código       │   │ ENS alto,    │
  │                       │      │ público      │──▶│ guardia 24×7 │
  │ Salida: meses         │      │              │   │ Sustituible  │
  └───────────────────────┘      └──────────────┘   └──────────────┘
```

La certificación ENS es una propiedad **del operador**, no del software. Si el sistema es
aburrido —contenedor OCI, PostgreSQL, almacenamiento S3— cualquier operador certificado
puede ejecutarlo, y **puede sustituirse sin tocar una línea de código**.

Eso convierte la certificación de barrera de entrada en servicio contratable en un mercado
con varios proveedores. Y es exactamente lo contrario de lo que ocurre hoy, donde
construcción, operación y propiedad efectiva están en la misma mano.

### 4.3 Lo que hace falta para que funcione

1. **El código es público desde el primer commit.** El artículo 157 de la Ley 40/2015 ya
   prevé la reutilización de aplicaciones entre administraciones. Publicar no es un gesto:
   es lo que hace sustituible al equipo.
2. **Operación certificada contratada aparte**, con su coste explícito. No se finge que
   cuatro personas hacen guardia.
3. **La portabilidad se verifica en la integración continua**, no se declara.
4. **Los adaptadores inciertos se prototipan primero.** SCSP y firma son los que pueden
   descarrilar el calendario, y son los primeros que hay que tocar.

## 5. Lo que este repositorio va a demostrar

No con argumentos. Con código que se ejecuta y con números que cualquiera puede reproducir.

| Afirmación | Cómo se demuestra |
|---|---|
| El portal público se sirve con menos de 100 KB de JavaScript | Presupuesto de bundle que rompe la construcción si se supera |
| El dominio administrativo cabe en un monolito legible | Contando el código y leyéndolo |
| PostgreSQL basta como único almacén | Colas, búsqueda, outbox y auditoría funcionando, con medición |
| La adjudicación es correcta bajo concurrencia | Prueba que lanza N procesos a por la misma vivienda |
| El sistema es portable | Pruebas contra dos implementaciones de almacenamiento |
| Migrar de proveedor son días | Cronometrando una restauración completa en limpio |
| Cuatro personas pueden construirlo | El historial del repositorio, con sus fechas |

La última fila es la importante y la más honesta: **el propio repositorio, con sus commits
y sus fechas, es la evidencia.** Si tardamos dos años, la tesis será falsa y quedará
registrado.

---

## Resumen en cuatro líneas

1. El Estado compró la construcción de un sistema por ~972.000 € en dos años, más una bolsa
   de mantenimiento retribuida a ~24 €/hora.
2. Cuatro seniors cuestan **más por año** que el contrato. El ahorro no está en el coste
   por persona: está en entregar en ocho meses lo que se contrató en veinticuatro.
3. La asistencia de IA comprime el trabajo mecánico, no la complejidad intrínseca. Por eso
   la estimación es 30-34 persona-mes y no 15.
4. El obstáculo real no es técnico: es que la certificación ENS se exige a quien construye.
   Se resuelve separando construcción de operación, y eso sólo es posible si el software es
   aburrido y público.
