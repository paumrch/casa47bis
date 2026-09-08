# 04 — Costes de licencias e infraestructura

**Fecha de consulta:** 2026-09-08. Precios en **USD, sin impuestos, precio de lista
público**, con compromiso anual salvo indicación (en las páginas de Microsoft,
"paid yearly" = precio mensual con compromiso de 12 meses).

**Advertencia metodológica que gobierna todo el capítulo.** El precio de lista **no** es
el precio que paga una administración pública. Existen acuerdos marco y descuentos de
sector público que no son públicos. Por tanto:

- Ninguna conclusión de este informe puede depender de un precio de lista tomado como
  precio real.
- Las cifras se usan para establecer **órdenes de magnitud y sensibilidad**, no para
  afirmar lo que CASA 47 paga. No lo sabemos y no lo afirmamos.
- Se marca cada dato como `[VERIFICADO]` (página oficial del fabricante, con URL),
  `[SECUNDARIO]` (fuente de terceros coherente entre sí) o `[HIPÓTESIS]`.

---

## 1. Power Pages — modelo verificado

Fuente primaria: **Power Platform Licensing Guide, edición de diciembre de 2025**,
páginas 16-17 (PDF oficial de Microsoft). `[VERIFICADO]`

**Cita literal (p. 16):**

> "Authenticated user subscription licenses are offered for the number of unique,
> authenticated users accessing a website. These licenses are offered through capacity
> packs with 100 users per capacity pack. **Three tiers of pricing are offered.** Once
> the minimum purchase requirement is met, additional packs can be purchased in units
> of one."

> "Sufficient user capacity must be assigned to the environment to cover the **total
> number of unique users who access the website/month**. Unused capacity does not carry
> over to the next month."

### Tarifa por escalones `[VERIFICADO]`

**Usuarios autenticados** (packs de 100 usuarios):

| Escalón | Compra mínima | Usuarios | Precio por pack/mes |
|---|---|---|---|
| Tier 1 | 1 pack | 100+ | $200 |
| Tier 2 | 100 packs | 10.000+ | **$75** |
| Tier 3 | 1.000 packs | 100.000+ | $50 |

**Usuarios anónimos** (packs de 500 usuarios):

| Escalón | Usuarios | Precio por pack/mes |
|---|---|---|
| Tier 1 | 500+ | $75 |
| Tier 2 | 10.000+ | $37,50 |
| Tier 3 | 100.000+ | $25 |

### Corrección de una estimación previa

Una primera pasada de esta investigación estimó ~$1,2 M anuales aplicando la tarifa
Tier 1 a 50.000 usuarios. **Esa cifra era incorrecta** y no debe usarse: a ese volumen
aplica el Tier 2. La cifra correcta a precio de lista es:

> 50.000 usuarios / 100 = 500 packs × $75 = **$37.500/mes ≈ $450.000/año**

Se deja constancia del error y de su corrección porque la credibilidad del informe
depende de que las cifras resistan verificación independiente. Una comparación que
inflara el coste del sistema actual invalidaría todo el análisis.

### Lo que sí es estructuralmente cierto

Aunque la cifra baja de forma sustancial, el hecho económico de fondo se mantiene, y es
lo que importa para la arquitectura:

1. **El coste de plataforma escala con el número de ciudadanos que usan el servicio.**
   La capacidad se mide por usuarios únicos autenticados **por mes natural**, y la
   capacidad no consumida **no se acumula** al mes siguiente (cita literal arriba).
2. En un sistema de convocatorias, el uso está concentrado en las semanas de apertura y
   cierre de plazo. Hay que dimensionar la capacidad para el **pico mensual**, y pagarla
   también en los meses valle, o gestionar activamente el alta y baja de capacidad.
3. En la arquitectura propuesta, esa partida **no existe**: el coste de servir a un
   ciudadano más es el coste marginal de cómputo, que es de céntimos.

### Sensibilidad a precio de lista `[VERIFICADO la tarifa, [HIPÓTESIS] el patrón de uso]`

| Pico mensual de usuarios autenticados | Packs | Escalón | Coste mensual | Coste anual |
|---|---|---|---|---|
| 2.000 | 20 | Tier 1 | $4.000 | $48.000 |
| 9.900 | 99 | Tier 1 | $19.800 | $237.600 |
| 10.000 | 100 | Tier 2 | $7.500 | $90.000 |
| 25.000 | 250 | Tier 2 | $18.750 | $225.000 |
| 50.000 | 500 | Tier 2 | $37.500 | **$450.000** |

Obsérvese el efecto de umbral: pasar de 9.900 a 10.000 usuarios **reduce** la factura de
$19.800 a $7.500 mensuales. Es una discontinuidad de la tarifa, no un error de cálculo.
Merece señalarse porque cualquiera que modele este coste sin conocer los escalones
obtendrá cifras erróneas, en un sentido o en el otro.

## 2. Dataverse — capacidad `[VERIFICADO]`

Misma fuente, p. 21.

| Concepto | Incremento | Mínimo | Precio |
|---|---|---|---|
| Capacidad de base de datos | 1 GB | 1 | **$40/mes** |
| Capacidad de base de datos, Tier 2 | 1 GB | 1.000 | $30/mes |
| Capacidad de ficheros | 1 GB | 1 | **$2/mes** |
| Capacidad de registros de log | 1 GB | 1 | $10/mes |

**Capacidad incluida de serie** (p. 21): por inquilino, 15 GB de base de datos y 2 GB de
log. Por cada pack de usuarios autenticados: 2 GB de base de datos, 16 GB de fichero y
1 GB de log, agrupados a nivel de inquilino.

**Cálculo relevante.** Con 500 packs, la capacidad de fichero incluida sería del orden de
8 TB, y la de base de datos de 1 TB. Es decir: al volumen de 50.000 usuarios, el
almacenamiento documental probablemente **queda cubierto por la capacidad incluida** y no
genera coste adicional.

Esto matiza una crítica frecuente: a este volumen, el problema del almacenamiento en
Dataverse no es el precio, sino la **portabilidad** de los documentos y del modelo. La
crítica correcta es de dependencia, no de coste. Conviene formularla bien.

**Contraste de precio unitario, para dimensionar la dependencia:** almacenamiento de
objetos equivalente cuesta ~$0,018/GB/mes. La capacidad de base de datos de Dataverse
cuesta $40/GB/mes, del orden de **2.000 veces más**. Mientras esté dentro de lo incluido
no se factura; en cuanto se rebasa, el coste marginal es de otro orden de magnitud.

## 3. Dynamics 365

Fuentes oficiales consultadas 2026-09-08:
`microsoft.com/en-us/dynamics-365/products/customer-service/pricing` y
`.../sales/pricing`.

| Producto | Precio usuario/mes | Estado |
|---|---|---|
| Customer Service Professional | $50,00 | `[VERIFICADO]` |
| Customer Service Enterprise | $105,00 | `[VERIFICADO]` |
| Customer Service Premium | $195,00 | `[VERIFICADO]` |
| Sales Professional | $65,00 | `[VERIFICADO]` |
| Sales Enterprise | $105,00 | `[VERIFICADO]` |
| Sales Premium | $150,00 | `[VERIFICADO]` |
| Team Members | ~$8,00 | `[SECUNDARIO]` |

`[SECUNDARIO]` Team Members es una licencia de acceso limitado: lectura amplia y
escritura muy restringida. No habilita la gestión completa de expedientes. Su aplicación
a los gestores externos dependería de si su trabajo real cabe en esas restricciones, algo
que no podemos determinar desde fuera.

Orden de magnitud para el personal descrito en el pliego, a precio de lista y bajo el
supuesto de Customer Service Enterprise para los gestores internos:

- 17 gestores internos × $105 × 12 = **~$21.400/año** `[HIPÓTESIS de licencia asignada]`
- 115 gestores externos: entre ~$11.000/año (si Team Members bastara) y ~$145.000/año
  (si necesitaran licencia completa). **El rango es de más de un orden de magnitud y
  depende de un detalle funcional que desconocemos.** Se declara como incógnita.

**Observación de honestidad intelectual:** el coste de licencias de los gestores es
comparativamente modesto. El grueso del riesgo económico no está en las ~150 personas
que gestionan, sino en los ~50.000 ciudadanos que acceden. Un análisis que se centrara
en el precio por gestor estaría mirando al sitio equivocado.

## 4. Power Automate

Fuente oficial: `microsoft.com/en-us/power-platform/products/power-automate/pricing`
(2026-09-08). `[VERIFICADO]`

| Plan | Precio | Alcance |
|---|---|---|
| Premium | $15,00 usuario/mes | flujos en la nube + RPA atendido |
| Process | $150,00 bot/mes | RPA desatendido |
| Hosted Process | $215,00 bot/mes | RPA desatendido + máquina virtual |

`[NO VERIFICADO]` Límites de peticiones de API por licencia y comportamiento al
superarlos. Es un punto relevante: en las plataformas de este tipo, el consumo de
peticiones es un coste variable que aparece tarde, y conviene documentarlo.

## 5. Descuentos de sector público `[NO ENCONTRADO]`

No se ha localizado un Acuerdo Marco vigente de la DGRCC específico de licencias
Microsoft con tarifas públicas.

Lo más próximo localizado es el **SDA 25/2022**, un Sistema Dinámico de Adquisición (no
un Acuerdo Marco) con objeto "suministro de software de sistema, desarrollo y
aplicación", en 6 lotes, con vigencia inicial 30/09/2022–29/09/2024 y prórrogas
sucesivas hasta 28/02/2027. Fuente: `contratacioncentralizada.gob.es`, ficha del SDA.
No nombra a Microsoft en su objeto ni publica tarifas: remite a un catálogo que no es
accesible sin sesión.

**Conclusión metodológica:** no existe base pública para afirmar el descuento que aplica
a CASA 47. Cualquier porcentaje sería inventado. En consecuencia, el modelo de TCO
presentará el coste de licencias **como rango**, desde precio de lista hasta precio de
lista con un descuento hipotético declarado, y las conclusiones del informe **no
dependerán de dónde caiga dentro de ese rango**. Si una conclusión sólo se sostiene
suponiendo que no hay descuento, esa conclusión no se publica.

## 6. Azure — precios verificados vía API oficial

Fuente: **Azure Retail Prices API** (`prices.azure.com/api/retail/prices`), API REST
pública y oficial de Microsoft. Consulta 2026-09-08. USD, sin impuestos, pago por uso.
`[VERIFICADO]`

| Concepto | Región | Precio |
|---|---|---|
| PostgreSQL Flexible Server, General Purpose D2ds_v5 (2 vCPU) | West Europe | $0,212/hora ≈ **$155/mes** |
| PostgreSQL Flexible Server, General Purpose D4ds_v5 (4 vCPU) | West Europe | $0,424/hora ≈ **$310/mes** |
| Almacenamiento del servidor | West Europe | $0,1369/GB/mes |
| Almacenamiento de copias de seguridad (LRS) | West Europe | $0,103/GB/mes |
| Alta disponibilidad con redundancia de zona | — | sin contador propio: **duplica** cómputo y almacenamiento |
| Front Door Standard | Zona 1 | $35/mes base + $0,17/GB de salida |
| Front Door Premium (incluye WAF gestionado) | Zona 1 | **$330/mes** base |
| WAF como política independiente | Zonas 3/4 | $5/mes por política + $1/mes por regla |
| App Service P1v3 (2 vCPU / 8 GB) | West Europe | ~$216/mes `[SECUNDARIO]` |
| App Service P2v3 (4 vCPU / 16 GB) | West Europe | ~$432/mes `[SECUNDARIO]` |
| Blob Storage Hot | genérico | ~$0,018/GB/mes `[SECUNDARIO]` |

Estos precios se usan en §18 para construir el OPEX de la arquitectura propuesta **sobre
el mismo proveedor que la arquitectura actual**. Es deliberado: comparar Azure contra
Azure elimina la objeción de que el ahorro procede de irse a un proveedor más barato. El
ahorro que se demuestre así procederá de la arquitectura, no del alojamiento.

## 7. Alternativas de alojamiento

| Proveedor | Producto | Precio | Estado |
|---|---|---|---|
| Hetzner | Cloud CPX41 (8 vCPU AMD) | €30,49/mes | `[SECUNDARIO]` |
| OVHcloud | PostgreSQL gestionado | tarifa no extraída | `[PENDIENTE]` |
| OVHcloud / Arsys / Stackscale / Gigas — oferta con conformidad ENS | — | no consultado | `[PENDIENTE]` |

**Nota importante y deliberada:** este informe **no** usará "un servidor barato" como
argumento de ahorro. La comparación válida es contra infraestructura con conformidad ENS,
alta disponibilidad, copias verificadas y soporte profesional. Los precios de Hetzner se
recogen sólo como referencia de mercado del coste bruto de cómputo, no como propuesta de
alojamiento para un sistema con datos personales de categoría especial.

---

## Qué falta verificar antes de publicar el TCO

1. Escalones de descuento por volumen en Power Pages (si existen, cambian los escenarios altos).
2. Precios de capacidad de Dataverse contra el PDF oficial de la Licensing Guide.
3. Acuerdo marco DGRCC vigente aplicable, y si CASA 47 se acoge a él.
4. Si las licencias están dentro o fuera del importe adjudicado (cruzar con informe 03).
5. Precio de PostgreSQL Flexible Server con alta disponibilidad en región española.
6. Ofertas de alojamiento con conformidad ENS y su precio.

Hasta que 1–4 estén cerradas, el TCO se publica **como rango con hipótesis explícitas**,
nunca como cifra única.
