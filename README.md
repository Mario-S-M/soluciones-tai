# Soluciones TAI — entorno de desarrollo

Pila legacy en Docker: **Apache 2.4.25 + PHP 5.6.40 + PostgreSQL 9.5.25 + CodeIgniter 3.1.13**,
con pgAdmin 4 como cliente gráfico.

Sustituye a Laragon, que solo existe para Windows. Docker Compose cumple la misma función
—levantar Apache, PHP y la base de datos con un comando— y además es idéntico en cualquier
máquina del equipo.

## Arranque

```bash
docker compose up -d          # levanta los tres servicios
docker compose ps             # estado
docker compose logs -f web    # logs de Apache/PHP
docker compose down           # detener (los datos se conservan)
docker compose down -v        # detener y BORRAR la base de datos
```

## Accesos

| Servicio       | URL / conexión           | Credenciales                  |
|----------------|--------------------------|-------------------------------|
| Aplicación     | http://localhost:8081    | —                             |
| Verificación   | http://localhost:8081/dbtest | —                         |
| Usuarios       | http://localhost:8081/usuarios | —                       |
| pgAdmin        | http://localhost:5050    | `admin@tai.local` / `admin`   |
| PostgreSQL     | `localhost:5432`         | `tai` / `tai_dev_2026`        |

Todo se configura en `.env`. Los puertos se publican solo en `127.0.0.1`, no en la red local.

### Conectar pgAdmin a PostgreSQL

El servidor ya viene registrado como **PostgreSQL 9.5 (Docker)**. Al abrirlo pedirá la
contraseña (`tai_dev_2026`); marca *Save password* y no volverá a pedirla.

Si tuvieras que darlo de alta a mano — *Add New Server*:

- **General → Name**: cualquier nombre
- **Connection → Host**: `db` ← el nombre del servicio, **no** `localhost`. Dentro de la red de
  Docker, `localhost` sería el propio contenedor de pgAdmin.
- **Port**: `5432` · **Database**: `soluciones_tai` · **Username**: `tai`

Desde un cliente del Mac (DBeaver, TablePlus, `psql`) sí se usa `localhost:5432`, porque ahí
el puerto está publicado en el host.

## Estructura

```
docker-compose.yml        Los tres servicios
.env                      Credenciales y puertos (no se versiona)
docker/php/Dockerfile     php:5.6-apache + pdo_pgsql/pgsql + mod_rewrite
docker/php/php.ini        Ajustes de desarrollo (display_errors On)
docker/apache/vhost.conf  DocumentRoot y AllowOverride All
docker/pgadmin/servers.json  Servidor precargado en pgAdmin
db/init/01-init.sql       Esquema inicial (secuencia + tabla usuarios)
db/backup.sh              Exporta la base de datos a db/backup/
db/restore.sh             Recrea la base de datos y carga un dump
db/backup/                Respaldos generados (.sql)
www/                      CodeIgniter 3.1.13
```

`www/` está montado como volumen: los cambios en el código se ven al recargar, sin reconstruir
nada ni reiniciar contenedores.

### Configuración de CodeIgniter

- `www/application/config/database.php` — driver `postgre`, credenciales leídas del entorno
  (`getenv('DB_HOST')`, etc.) que `docker-compose.yml` inyecta en el contenedor.
- `www/application/config/config.php` — `base_url` en el puerto 8081 e `index_page` vacío.
- `www/application/config/autoload.php` — helpers `url` y `form` cargados en todas las peticiones.
- `www/.htaccess` — reglas de `mod_rewrite` para URLs sin `index.php`.

## La aplicación

| Ruta              | Qué hace                                                    |
|-------------------|-------------------------------------------------------------|
| `/usuarios`       | Listado de usuarios                                          |
| `/usuarios/crear` | Formulario de alta con validación                            |
| `/dbtest`         | Comprueba que Apache, PHP y PostgreSQL se hablan entre sí    |

```
application/models/Usuario_model.php     listar(), crear(), total()
application/controllers/Usuarios.php     index() y crear()
application/views/usuarios/              lista.php y form.php
application/views/plantilla/             cabecera.php y pie.php (comunes)
```

El alta valida con `form_validation`: nombre, apellidos y correo obligatorios, correo con formato
válido y único en la tabla, y longitudes que coinciden con las del esquema. El teléfono es
opcional y se guarda como `NULL` cuando se deja vacío, no como cadena vacía.

Los mensajes de error se traducen con `set_message()` en el propio controlador. No se cambia
`$config['language']` a `spanish` porque entonces CodeIgniter esperaría encontrar traducidos
todos sus archivos de idioma y fallaría al cargar los que faltan.

## Comandos útiles

```bash
docker compose exec web bash                              # shell en el contenedor web
docker compose exec db psql -U tai -d soluciones_tai      # consola SQL
docker compose build --no-cache web                       # reconstruir PHP desde cero
```

## Base de datos

La tabla `usuarios` guarda nombre, apellidos, correo y teléfono. El `id` no usa `SERIAL`: la
secuencia se declara aparte (`CREATE SEQUENCE usuarios_id_seq`) y se ata a la columna con
`ALTER SEQUENCE ... OWNED BY`, así queda como un objeto propio del esquema —visible con `\ds`—
pero se sigue borrando junto con la tabla y `pg_dump` la exporta con su `setval`.

```sql
CREATE SEQUENCE usuarios_id_seq;

CREATE TABLE usuarios (
    id        INTEGER      PRIMARY KEY DEFAULT nextval('usuarios_id_seq'),
    nombre    VARCHAR(100) NOT NULL,
    apellidos VARCHAR(150) NOT NULL,
    correo    VARCHAR(150) NOT NULL UNIQUE,
    telefono  VARCHAR(20),
    creado_en TIMESTAMP    NOT NULL DEFAULT NOW()
);

ALTER SEQUENCE usuarios_id_seq OWNED BY usuarios.id;
```

### Respaldo y restauración

```bash
./db/backup.sh                                        # exportar -> db/backup/soluciones_tai_<fecha>.sql
./db/restore.sh db/backup/soluciones_tai_<fecha>.sql  # borrar y restaurar
```

`restore.sh` no se limita a cargar el archivo: corta las conexiones abiertas, hace `DROP DATABASE`
y `CREATE DATABASE`, y después carga el dump. Es el ciclo completo de recuperación, no un simple
`psql <archivo`.

El paso de cerrar conexiones es necesario porque PostgreSQL rechaza borrar una base con clientes
conectados, y tanto pgAdmin como el contenedor web mantienen sesiones abiertas:

```sql
SELECT pg_terminate_backend(pid) FROM pg_stat_activity
WHERE datname = 'soluciones_tai' AND pid <> pg_backend_pid();
```

(En PostgreSQL 9.5 la columna es `pid`; en versiones anteriores a la 9.2 se llamaba `procpid`.)

Para hacerlo a mano, sin los scripts:

```bash
docker compose exec -T db pg_dump -U tai -d soluciones_tai > respaldo.sql
docker compose exec -T db psql -U tai -d postgres -c "DROP DATABASE soluciones_tai;"
docker compose exec -T db psql -U tai -d postgres -c "CREATE DATABASE soluciones_tai OWNER tai;"
docker compose exec -T db psql -U tai -d soluciones_tai < respaldo.sql
```

Diferencia con `docker compose down -v`: eso borra el volumen entero y vuelve a ejecutar
`db/init/`, dejando los datos de ejemplo. La restauración devuelve exactamente lo que había
cuando se hizo el respaldo, incluido el valor de la secuencia.

## Problemas conocidos

**Lentitud.** `php:5.6-apache` y `postgres:9.5` solo se publican para `linux/amd64`; en Apple
Silicon corren emulados (de ahí el `platform: linux/amd64` en el compose). Funciona, pero es
más lento que una imagen nativa. Activar *Use Rosetta for x86/amd64 emulation* en Docker
Desktop (Settings → General) mejora bastante.

**Los scripts de `db/init/` no se ejecutan.** Solo corren cuando el volumen de datos está vacío.
Para reejecutarlos: `docker compose down -v && docker compose up -d`. Esto **borra la base de datos**.

**El puerto 8081.** Se eligió porque el 8080 estaba ocupado por otro servidor PHP local. Para
cambiarlo, edita `WEB_PORT` en `.env` y el `base_url` en `www/application/config/config.php`.

**`column "wait_event_type" does not exist` en pgAdmin.** Las consultas del panel *Dashboard*
que trae pgAdmin 7.8 asumen un servidor 9.6 o superior: `wait_event_type`/`wait_event` y
`pg_blocking_pids()` son de 9.6, y `backend_type` de 10. Ya está resuelto — `docker/pgadmin/sql/`
contiene las plantillas adaptadas a 9.5 y el compose las monta sobre las originales. Bajar la
versión de pgAdmin no sirve: las anteriores usan la misma consulta y no publican imagen arm64.

La pestaña *System Statistics* sigue vacía porque necesita la extensión opcional `system_stats`
de pgAdmin, que no está instalada en el servidor. No afecta a nada más.

**La tabla no aparece en el árbol de pgAdmin.** Mismo origen que lo anterior. Al expandir
*Schemas → public → Tables* no salía nada, y el log del contenedor mostraba:

```
ERROR pgadmin: Failed to execute query (execute_2darray) for the server #1 - DB:soluciones_tai
Error Message: column rel.relispartition does not exist
```

`relispartition` se añadió a `pg_class` en PostgreSQL 10, con el particionamiento declarativo. La
plantilla más antigua que trae pgAdmin 7.8 (`.../tables/sql/default/`) ya la da por supuesta, así
que contra un 9.5 la consulta del árbol falla entera y el nodo se queda vacío. Están parcheadas en
`docker/pgadmin/tables/` (`nodes.sql`, `count.sql`, `properties.sql`, `get_inherits.sql`) y el
compose las monta encima. El parche solo quita la condición `AND NOT rel.relispartition`, que en
9.5 no descarta nada porque no existen las particiones declarativas.

Junto a eso, `docker/pgadmin/connect/check_recovery.sql` cambia `pg_is_wal_replay_paused()` por
`pg_is_xlog_replay_paused()`, su nombre en 9.5 —el renombrado `xlog` → `wal` es de la 10—. Ese no
rompía nada visible, pero dejaba un error en el log en cada conexión.

Si acabas de aplicar estos cambios, recarga la pestaña de pgAdmin: el árbol se cachea en el
navegador y una pestaña abierta desde antes sigue mostrando el estado anterior.

**`relation "pg_catalog.pg_sequence" does not exist`.** Aparecía al abrir las columnas de una
tabla o las propiedades de una secuencia. `pg_sequence` es un catálogo nuevo de PostgreSQL 10:
antes, los metadatos de una secuencia se leían consultando la propia secuencia. Lo mismo con
`att.attidentity`, la columna de `pg_attribute` que marca las columnas `GENERATED AS IDENTITY`,
también de la 10. Parcheados en `docker/pgadmin/columns/` y `docker/pgadmin/sequences/`:

- En las columnas, el `JOIN` contra `pg_sequence` se sustituye por una subconsulta que nunca casa
  —el mismo resultado que un `pg_sequence` vacío, que es la verdad en 9.5— y `attidentity` pasa a
  `NULL`. Ninguna columna puede ser *identity* en 9.5, así que el resultado es correcto.
- En las secuencias, `get_def.sql` lee `usuarios_id_seq` directamente. La secuencia ya expone
  `last_value`, `min_value`, `max_value`, `start_value`, `cache_value`, `is_cycled`,
  `increment_by` e `is_called` con esos mismos nombres, así que la consulta sale más simple que
  la original.

### Resumen: pgAdmin 7.8 contra PostgreSQL 9.5

Los cuatro problemas anteriores son el mismo: pgAdmin 7.8 ya no da soporte a servidores tan
antiguos y sus plantillas SQL más viejas asumen PostgreSQL 10. En total hay unas 40 plantillas en
esa situación, pero la mayoría (16) son del nodo de particiones, que en 9.5 no llega a usarse
porque no existe el particionamiento declarativo.

Está parcheado todo lo que se toca en el uso normal: árbol de tablas, columnas, secuencias,
Dashboard y la comprobación de recuperación al conectar. Quedan sin tocar las plantillas de
*Search objects* y del *Grant Wizard*; si alguna vez usas esas herramientas y fallan, será por lo
mismo y se arregla igual —`docker/pgadmin/` y un montaje más en el compose—.

Todos los parches viven en `docker/pgadmin/` y se montan sobre los originales, así que la imagen
no se modifica y basta con borrar el montaje para volver al comportamiento de fábrica.

**Avisos de GPG al construir.** Debian 9 está archivado y sus claves de firma expiraron en 2022,
por eso el Dockerfile usa `[trusted=yes]`. Es aceptable en un contenedor local y desechable.

## Sobre las versiones

PHP 5.6 (fin de soporte: 2019) y PostgreSQL 9.5 (2021) no reciben parches de seguridad. El
entorno es válido para desarrollo local aislado, pero esta pila no debería exponerse a Internet.
