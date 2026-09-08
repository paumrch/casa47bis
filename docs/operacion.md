# Operación

CASA47bis se distribuye como una imagen OCI estándar. No usa nada específico
de un proveedor de nube: el único almacén de datos es PostgreSQL (también
para colas, sesiones y caché) y el almacenamiento de objetos usa el protocolo
S3, servido por MinIO en local y por el servicio S3-compatible que ofrezca
cada proveedor en producción. Cambiar de proveedor es cambiar variables de
entorno, no código.

## Levantar el entorno en local

Requisitos: Docker y Docker Compose.

```bash
cp .env.example .env
make up          # construye las imágenes y levanta app, postgres, minio, worker
make migrate      # aplica las migraciones
```

La aplicación queda en `http://localhost:8000`, la consola de MinIO en
`http://localhost:9001` (usuario `dev` / contraseña `devdevdev`, ver
`compose.yaml`). `make help` lista el resto de objetivos.

En desarrollo, `compose.yaml` monta el código por volumen y no cachea
config/rutas/vistas, para que los cambios se reflejen sin reconstruir la
imagen. `vendor/` y `public/build` se sirven desde volúmenes con nombre
para no pisar lo que generó la imagen con un `vendor/` vacío del host.

## Desplegar en cualquier proveedor

1. Construir la imagen con el `Dockerfile` de la raíz (`docker build .`) y
   subirla al registro del proveedor (ACR, ECR, un registro OVH, un
   registro propio…). La imagen no lleva secretos ni fichero `.env`: todo
   entra por variables de entorno en tiempo de ejecución.
2. Arrancar el contenedor con las variables de la sección siguiente. El
   contenedor expone el puerto `8000` (HTTP interno); la terminación TLS la
   hace el balanceador o proxy del proveedor.
3. Ejecutar `php artisan migrate --force` una vez, contra el PostgreSQL de
   destino, antes de servir tráfico.
4. Arrancar al menos un contenedor con `php artisan queue:work` (mismo
   imagen, comando distinto) para procesar la cola.

No hay nada más: ni Redis, ni disco local persistente para sesiones/caché/
colas (todo vive en PostgreSQL), ni configuración de un CDN o balanceador
concreto que la aplicación exija.

## Variables de entorno

| Variable | Qué hace |
|---|---|
| `APP_KEY` | Clave de cifrado de la aplicación. Generar con `php artisan key:generate --show` y guardarla en el gestor de secretos del proveedor. |
| `APP_ENV` | `production` en despliegues reales. |
| `APP_DEBUG` | `false` en producción (nunca exponer trazas). |
| `APP_URL` | URL pública de la aplicación. |
| `DB_CONNECTION` | `pgsql`. Es el único driver soportado. |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Conexión al PostgreSQL 17 del proveedor. |
| `SESSION_DRIVER` | `database`. Las sesiones viven en PostgreSQL, no en disco ni en Redis. |
| `CACHE_STORE` | `database`. La caché vive en PostgreSQL. |
| `QUEUE_CONNECTION` | `database`. Las colas viven en PostgreSQL. |
| `FILESYSTEM_DISK` | `s3` en producción (contra el S3-compatible del proveedor) o `local` si el proveedor no ofrece uno y se acepta disco efímero/montado. |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY` | Credenciales del almacenamiento de objetos (S3, MinIO, o el compatible del proveedor). |
| `AWS_DEFAULT_REGION` | Región del bucket. Cualquier valor no vacío sirve para MinIO/S3-compatibles que no usan regiones. |
| `AWS_BUCKET` | Nombre del bucket. |
| `AWS_ENDPOINT` | URL del endpoint S3-compatible. Vacío para AWS S3 real; obligatorio para MinIO o cualquier otro proveedor. |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` para MinIO y la mayoría de S3-compatibles fuera de AWS; `false` para AWS S3. |
| `MAIL_*` | Configuración del proveedor de correo saliente (SMTP, API…). |
| `SERVER_PORT` | Puerto HTTP interno que sirve FrankenPHP (por defecto `8000`). |

> **Pendiente de dependencia:** el disco `s3` de Laravel necesita el paquete
> `league/flysystem-aws-s3-v3`, que no viene instalado por defecto. Sin él,
> `FILESYSTEM_DISK=s3` falla con `Class League\Flysystem\AwsS3V3\... not
> found` (comprobado en local contra MinIO). Añadir con
> `composer require league/flysystem-aws-s3-v3` antes de usar MinIO o S3 en
> serio; el disco `local` funciona sin este paquete.

## Copias de seguridad y restauración

Toda la información de negocio vive en PostgreSQL (incluidas sesiones y
colas, que no hace falta respaldar) y en el bucket S3-compatible.

**Copia de PostgreSQL:**

```bash
pg_dump -U <usuario> -d <base_de_datos> --format=custom --file=backup.dump
```

**Restauración:**

```bash
createdb -U <usuario> <base_de_datos_destino>
pg_restore -U <usuario> -d <base_de_datos_destino> backup.dump
```

**Copia del bucket:** cualquier cliente S3 (`aws s3 sync`, `mc mirror` de
MinIO Client, `rclone`) sirve para replicar el bucket a otro almacenamiento
o proveedor, porque el protocolo es el mismo en origen y destino.

`make restore-test` ejecuta un ensayo cronometrado completo (volcado →
restauración contra una base de datos limpia → limpieza) para comprobar,
con una cifra real, cuánto tarda recuperar el sistema desde cero.

## Migración a otro proveedor (procedimiento cronometrado)

Objetivo: demostrar que abandonar un proveedor no es un proyecto, es un
procedimiento con tiempos conocidos.

1. **T+0.** Anotar la hora de inicio. Poner la aplicación en modo
   mantenimiento (`php artisan down`) o aceptar una ventana corta de
   solo-lectura.
2. **Copia de datos.** `pg_dump` contra el PostgreSQL de origen y `mc mirror`
   (o equivalente) del bucket de origen al bucket de destino. Cronometrar
   ambos pasos por separado: el tamaño de la base de datos y del bucket son
   los que determinan cuánto va a tardar una migración real.
3. **Restauración en destino.** `pg_restore` contra el PostgreSQL nuevo.
   Verificar con `make restore-test` (o el equivalente manual) contra el
   proveedor de destino antes de cortar tráfico.
4. **Arranque de la aplicación en destino.** `docker build` + `docker run`
   (o el servicio de contenedores del proveedor: Azure Container Apps, AWS
   ECS/App Runner, un servicio de OVHcloud, un `docker compose` en un CPD
   propio) con las variables de entorno de la sección anterior apuntando al
   nuevo PostgreSQL y al nuevo bucket. `php artisan migrate --force` no
   debería aplicar nada nuevo si la copia de datos incluyó la tabla
   `migrations`.
5. **Corte de tráfico.** Cambiar DNS/balanceador al nuevo despliegue. Salir
   de modo mantenimiento.
6. **T+fin.** Anotar la hora de fin. La diferencia con T+0 es el tiempo real
   de migración entre proveedores, y debería poder repetirse: si el
   procedimiento tarda más la segunda vez que la primera, el procedimiento
   está mal, no el proveedor.

Ningún paso de este procedimiento requiere reescribir código de la
aplicación: solo variables de entorno y comandos de copia de datos
estándar (`pg_dump`/`pg_restore`, un cliente S3). Esa es la prueba de que
el sistema es portable.
