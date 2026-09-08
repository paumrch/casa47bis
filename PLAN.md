# Plan del proyecto y mecanismos de control

Este fichero es el contrato del proyecto consigo mismo. Define qué se hace, en qué orden,
qué significa "terminado" en cada fase, y **qué impide que nos salgamos del plan**.

Se actualiza al cerrar cada fase. No se modifica para justificar una desviación: si hay
desviación, se anota como tal.

---

## Principio rector

> Ningún componente tecnológico entra en la arquitectura hasta que exista un problema
> concreto, medible y documentado que ese componente resuelva mejor que las piezas que ya
> existen. La complejidad debe ganarse el derecho a existir.

Corolario operativo: **cada pieza nueva exige una entrada en `docs/decisions/`** con
problema, opciones, decisión, coste, dependencia generada, alternativa y (obligatorio) la
señal medible que nos obligaría a revisar la decisión.

---

## Modelo de publicación

El repositorio es un escaparate, no un taller. **Sólo entra producto terminado**, aunque
sea por partes. Nada a medio hacer en `main`.

Para que algo sea publicable debe cumplir, sin excepción:

1. Está completo para su alcance declarado.
2. Tiene pruebas que lo respaldan, si es código.
3. Tiene documentación que permite entenderlo sin preguntar.
4. Sus cifras tienen fuente, o están marcadas como hipótesis.
5. No contiene secretos, credenciales ni datos personales.

---

## Fases

### Fase 1, Auditoría arquitectónica ✅ **completada**

**Entregable:** `docs/CASA47-minimal-architecture-review.md` + los seis informes de
investigación en `docs/research/`.

**Puerta de salida:** revisión conjunta y aprobación explícita de la arquitectura.
**Estado: esperando revisión.**

### Fase 2, Diseño funcional y modelo de datos

**Entregables:**
- Especificación funcional por módulo.
- Modelo de datos completo: tablas, claves, índices, restricciones, retención.
- Máquinas de estado de cada procedimiento, con actores y precondiciones.
- Reglas de elegibilidad y baremación, versionadas y con sus casos límite.
- Contratos de los puertos de integración.

**Definición de terminado:** el modelo permite responder, sin ambigüedad, a las preguntas
de un recurso administrativo tipo. Un tercero puede implementarlo sin preguntarnos.

### Fase 3, Diseño de interfaz

**Entregables:** sistema de componentes accesibles, recorridos principales, estados de
error y vacío, comportamiento adaptable.

**Definición de terminado:** cada componente cumple WCAG 2.1 AA verificado con teclado y
lector de pantalla, no sólo con herramienta automática.

### Fase 4, Implementación del demostrador

**Entregables:** demostrador A (portal público) y demostrador B (aplicación transaccional
con adaptadores simulados y lógica de dominio real).

**Definición de terminado:** se despliega desde cero con un comando, pasa todas las
pruebas, y un tercero puede levantarlo con el README.

### Fase 5, Medición y comparación económica

**Entregables:** las métricas del Anexo E del informe, reproducibles, con método
documentado.

**Definición de terminado:** cualquiera puede reproducir las cifras desde el repositorio.

---

## Mecanismos de control

Estos son los frenos. Existen para que el proyecto no se desvíe ni se detenga.

### 1. Puerta entre fases

**No se empieza una fase sin cerrar la anterior con aprobación explícita.** El informe de
Fase 1 termina con una condición de parada expresa por este motivo. Saltarse una puerta
es la forma habitual de que un proyecto se convierta en otro proyecto.

### 2. Registro de decisiones

Toda decisión arquitectónica va a `docs/decisions/NNNN-titulo.md`. Si una decisión no está
escrita, no se ha tomado. Si una pieza nueva no tiene su registro, no entra.

### 3. Lista de decisiones abiertas

Se mantiene viva en el informe (§24). **Ninguna incógnita se resuelve por conjetura.** Si
falta un dato, se anota y se sigue con lo que no depende de él.

### 4. Regla de honestidad de las cifras

Cada cifra lleva fuente o etiqueta de hipótesis. Cuando una cifra intermedia resulta estar
mal, **se corrige y se deja constancia del error**, como ya ocurrió con la tarifa de
Power Pages en Fase 1. Una comparación manipulada invalidaría todo el trabajo.

### 5. Simetría obligatoria

Todo argumento contra la arquitectura ajena debe comprobarse también contra la propia. Si
un riesgo aplica a las dos, se declara simétrico y **deja de ser argumento**. Así se
resolvió la cuestión de Red SARA.

### 6. Red team antes de cada entrega

Ninguna fase se cierra sin un intento serio de destruir su propio resultado. La
arquitectura corregida de Fase 1 (§23) salió de ese ejercicio.

### 7. Verificación automática de lo que se afirma

Lo que se declara, se prueba en la integración continua:
- modularidad → prueba de arquitectura que rompe la construcción;
- portabilidad → pruebas contra dos implementaciones de almacenamiento;
- accesibilidad → comprobación automática en cada página;
- rendimiento → presupuesto de JavaScript que falla si se supera.

Una propiedad que no se verifica automáticamente desaparece en seis meses.

### 8. Prohibiciones explícitas

No se admite en ningún entregable:
- "Laravel aguanta millones de usuarios" o equivalentes sin medición;
- "open source es mejor porque es open source";
- precios sin fuente;
- comparaciones que omitan partidas del lado propio;
- conclusiones políticas o imputaciones sobre personas;
- arquitectura de astronauta o diagramas decorativos.

### 9. Alcance congelado por fase

Las ideas nuevas que aparezcan a mitad de una fase van a `docs/backlog.md`. No entran en
la fase en curso. Es el mecanismo que impide que el proyecto crezca hasta no terminar
nunca.

### 10. Nada se detiene por un bloqueo parcial

Si una parte se bloquea por falta de información externa, se termina todo lo demás y se
declara explícitamente qué queda pendiente y por qué. No se para el conjunto.
