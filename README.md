# CASA 47 — Minimal Architecture Review

Análisis técnico independiente del portal público de CASA 47
(`https://portal.casa47.es/`) y diseño de una arquitectura alternativa mínima,
portable y auditable.

> **Estado:** Fase 1 — auditoría arquitectónica. Todavía no hay código de aplicación.

## Qué es esto

Un ejercicio de ingeniería: reconstruir, a partir de información pública, qué
problema resuelve realmente el Sistema Integrado de Gestión del Parque de
Alquiler Asequible, y evaluar si su portal ciudadano y su lógica transaccional
pueden implementarse con una arquitectura sustancialmente más simple, con menor
TCO y sin dependencia de plataforma propietaria — sin degradar seguridad,
accesibilidad, interoperabilidad ni disponibilidad.

## Qué NO es

- No es una acusación. No se hacen imputaciones sobre personas ni sobre la
  legalidad del procedimiento de contratación.
- No es una crítica ideológica a un fabricante.
- No es un pentest. Todo el análisis se basa exclusivamente en información
  pública y en el comportamiento observable de la aplicación.

## Método

1. **Fase 1 — Auditoría arquitectónica** (en curso): reverse specification del
   portal, modelo conceptual, complejidad intrínseca vs accidental, arquitectura
   mínima candidata, TCO, coste de salida, adversarial review.
2. Fase 2 — Diseño funcional y modelo de datos.
3. Fase 3 — Diseño UX/UI.
4. Fase 4 — Implementación del demostrador.
5. Fase 5 — Benchmark y comparación económica.

Cada fase se publica aquí sólo cuando está terminada y es defendible.

## Reglas del proyecto

- Ningún componente entra en la arquitectura hasta que exista un problema
  concreto, medible y documentado que resuelva mejor que lo que ya hay.
- Se distingue siempre **hecho observado** de **inferencia**.
- Ninguna cifra sin fuente. Donde no hay precio público, se usan rangos e
  hipótesis explícitas.

## Licencia

Pendiente de definir para el código. La documentación se publica para revisión
técnica abierta.
