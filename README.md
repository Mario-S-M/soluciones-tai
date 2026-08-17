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
db/migrations/            Cambios de esquema posteriores, para bases ya inicializadas
db/backup.sh              Exporta la base de datos a db/backup/
db/restore.sh             Recrea la base de datos y carga un dump
db/backup/                Respaldos generados (.sql)
www/                      CodeIgniter 3.1.13
www/assets/css/           CSS de la aplicación (fuera de application/, servido directo por Apache)
```

`www/` está montado como volumen: los cambios en el código se ven al recargar, sin reconstruir
nada ni reiniciar contenedores.

### Configuración de CodeIgniter

- `www/application/config/database.php` — driver `postgre`, credenciales leídas del entorno
  (`getenv('DB_HOST')`, etc.) que `docker-compose.yml` inyecta en el contenedor.
- `www/application/config/config.php` — `base_url` en el puerto 8081, `index_page` vacío y
  `csrf_protection` activado (todos los formularios usan `form_open()`, que añade el campo oculto
  solo).
- `www/application/config/autoload.php` — helpers `url` y `form` cargados en todas las peticiones.
- `www/.htaccess` — reglas de `mod_rewrite` para URLs sin `index.php`.

## La aplicación

| Ruta                    | Qué hace                                                     |
|-------------------------|----------------------------------------------------------------|
| `/usuarios`             | Listado de usuarios, en un DataTable con selección y exportación a Excel/PDF |
| `/usuarios/crear`       | Formulario de alta con validación                             |
| `/usuarios/editar/:id`  | Formulario de edición, con la contraseña opcional             |
| `/usuarios/graficas`    | Total general de usuarios y desglose por sexo, con Chart.js   |
| `/usuarios/plantilla`   | Descarga la plantilla CSV para la importación masiva          |
| `/usuarios/importar`    | Formulario de importación masiva desde un CSV, con validación fila por fila |
| `/dbtest`               | Comprueba que Apache, PHP y PostgreSQL se hablan entre sí     |

```
application/models/Usuario_model.php     listar(), crear(), obtener(), editar(), existeOtroCon(),
                                          total(), conteoPorSexo()
application/controllers/Usuarios.php     index(), crear(), editar(), graficas(), unico_excepto(),
                                          plantilla(), importar(), reglas_alta()
application/views/usuarios/              lista.php, form.php, editar.php, graficas.php e importar.php
application/views/plantilla/             cabecera.php y pie.php (comunes)
assets/css/estilos.css                   CSS de cabecera.php y de las vistas de usuarios
```

El alta y la edición validan con `form_validation`: nombre, apellidos y correo obligatorios,
correo con formato válido y único en la tabla, longitudes que coinciden con las del esquema,
contraseña de al menos 8 caracteres (y máximo 72, ver más abajo) y un campo `contrasena_confirmar`
que debe coincidir (`matches[contrasena]`). El teléfono es opcional y se guarda como `NULL` cuando
se deja vacío, no como cadena vacía.

CURP, RFC y teléfono se validan con `regex_match[...]` (el nombre real de la regla en esta versión
de CodeIgniter 3; `regex[...]`, que aparece en la documentación de algunos tutoriales, no existe
como regla y falla siempre sin importar el valor). CURP y RFC además se normalizan a mayúsculas
con `strtoupper` antes de validar y guardar, y son únicos en la tabla. `sexo` viene de un
`<select>` con `required|in_list[M,F,Otro]`, sin necesitar regex.

En la edición, `correo`, `curp` y `rfc` usan el callback `callback_unico_excepto[campo.id]` en vez
de `is_unique[...]`: CodeIgniter 3 no soporta excluir el propio id en `is_unique`, así que el
callback compara contra `Usuario_model::existeOtroCon()`, que sí filtra `WHERE id != :id`. La
contraseña es opcional en la edición —dejarla en blanco no cambia el hash— por eso sus reglas no
llevan `required`, a diferencia del alta.

Los mensajes de error se traducen con `set_message()` en el propio controlador. No se cambia
`$config['language']` a `spanish` porque entonces CodeIgniter esperaría encontrar traducidos
todos sus archivos de idioma y fallaría al cargar los que faltan.

## Decisiones de seguridad

**Por qué bcrypt y no md5/sha1/sha256.** `Usuario_model::crear()` cifra la contraseña con
`password_hash($contrasena, PASSWORD_BCRYPT)`, nativo de PHP desde la 5.5 (el proyecto corre en
5.6.40, no hace falta librería ni extensión adicional). md5, sha1 y sha256 son funciones de
digest rápidas, pensadas para checksums de integridad, no para secretos: esa misma velocidad es
lo que abarata el fuerza bruta y las tablas rainbow si la base de datos se filtra. bcrypt es
deliberadamente lento (factor de coste configurable, con reajuste posible según mejore el
hardware) y añade un salt aleatorio en cada llamada, así que dos contraseñas iguales nunca
producen el mismo hash.

**Por qué la contraseña nunca se muestra.** Dos capas, no solo "no imprimirlo en la vista":
`Usuario_model::listar()` no selecciona `contrasena_hash` de la base de datos —usa `select()`
explícito con una expresión `CASE` que calcula un indicador `'Sí'/'No'`—, así que el hash nunca
llega a PHP ni a `usuarios/lista.php`. La columna de la vista solo pinta ese indicador.

**Por qué `max_length[72]`.** bcrypt trunca en silencio cualquier entrada de más de 72 bytes; se
deja como regla explícita de `form_validation`, igual que el resto de longitudes del formulario,
en vez de dejar que ocurra sin que nadie lo note.

**Por qué se activó CSRF con este cambio.** El formulario ahora maneja una contraseña, así que
tiene sentido cerrar ese hueco a la vez. No hace falta tocar ninguna vista: `form_open()` ya
inyecta el campo oculto cuando `csrf_protection` está en `TRUE`. `usuarios/crear` era el único
POST de la aplicación en ese momento; `usuarios/editar/:id` se sumó después con el mismo mecanismo.

## Decisiones de CURP, RFC, sexo y gráficas

**Por qué CURP y RFC son dos campos separados y únicos.** Son identificadores legales distintos,
con formatos distintos (18 posiciones contra 12 o 13). Fundirlos en un solo campo obligaría a una
regex ambigua que acepte dos formatos diferentes bajo un mismo nombre; separados, cada uno valida
con su propia regla y su propia restricción `UNIQUE`.

**Por qué la regex de CURP valida la estructura completa y no solo la longitud.** Además de
`{18}` posiciones, comprueba fecha (`AAMMDD`), sexo (`H`/`M`), entidad (2 letras) y que las 3
consonantes internas excluyan vocales y `Ñ` (`[B-DF-HJ-NP-TV-Z]`). Una regex de solo longitud
aceptaría cualquier cadena de 18 caracteres alfanuméricos, lo cual no es el ejercicio que pide la
materia de expresiones regulares ni detecta errores de captura reales.

**Por qué el RFC acepta 12 y 13 posiciones.** 13 es persona física (4 letras + fecha + homoclave)
y 12 es persona moral (3 letras + fecha + homoclave). No hay ninguna razón para asumir que todo
usuario del sistema es una persona física.

**Por qué el teléfono exige exactamente 10 dígitos sin separadores.** Formato simple, sin
ambigüedad de cuántos espacios o guiones aceptar, y fácil de reutilizar más adelante —por ejemplo
en un enlace `tel:`—. Sigue siendo opcional: la regla `regex_match[...]` solo corre cuando el
campo trae valor.

**Por qué `sexo` tiene tres opciones (`M`/`F`/`Otro`) y no es un booleano.** Un booleano no puede
representar "Otro", y la gráfica por sexo necesita esa tercera categoría. Se implementa como
`VARCHAR(10)` con `CHECK`, no con un tipo `ENUM` de PostgreSQL, para no tener que alterar un tipo
de enum (operación más costosa en PostgreSQL) si algún día se agrega una cuarta opción.

**Por qué se construyó la edición (`/usuarios/editar/:id`) en esta spec.** El enunciado de la
tarea asume que ya existe un formulario de "registro/edición", pero el código solo tenía alta. Sin
edición no había dónde aplicar CURP, RFC y sexo a usuarios ya dados de alta.

**Por qué Chart.js desde CDN y no una librería de gráficas en PHP.** Se carga solo en
`usuarios/graficas.php`, no en `plantilla/cabecera.php`, así que no se paga su peso en el resto de
páginas. El contenedor `php:5.6-apache` no trae ninguna extensión de gráficas (tipo GD con soporte
de charts), y generarlas en PHP tipo imagen implicaría instalar y mantener una librería adicional
para una necesidad que Chart.js resuelve en el navegador con datos que la vista ya expone vía
`json_encode()`. La contrapartida es que la gráfica no carga si el navegador no tiene salida a
Internet (el contenedor PHP no la necesita, solo el navegador del usuario).

## Decisiones de exportación e importación

**Por qué la exportación a Excel/PDF corre 100% en el navegador.** `usuarios/lista.php` carga
DataTables junto con sus extensiones Buttons y Select desde CDN (mismo criterio que Chart.js:
solo en esta vista, no en `plantilla/cabecera.php`). Los botones `excelHtml5` y `pdfHtml5` generan
el archivo con JSZip y pdfmake directamente en el navegador, a partir de los datos que ya están en
la tabla. La alternativa —generar el archivo en el servidor— requeriría PhpSpreadsheet o dompdf,
ninguno de los cuales soporta PHP 5.6 (ambos piden PHP ≥ 7.2), así que no eran viables sin
congelar una versión antigua e insegura.

**Por qué la columna de checkbox usa la extensión Select de DataTables y no inputs a mano.** Select
es la pieza de DataTables pensada exactamente para esto: agrega la columna, dibuja el estado
marcado/no marcado con CSS y mantiene la selección aunque el usuario cambie de página o filtre con
el buscador. El checkbox de "seleccionar todos" en la cabecera sí se inyecta a mano con jQuery,
porque Select no trae uno por defecto en esta versión.

**Por qué los botones exportan solo lo seleccionado, y si no hay nada marcado exportan todo.** Le
da un propósito real a la columna de checkbox —"selección de datos a exportar"— en vez de dejarla
como adorno visual. La columna de checkbox y la de "Editar" se excluyen siempre de la exportación
por `exportOptions.columns`, ninguna aporta nada fuera de la página.

**Por qué la plantilla de importación es un `.csv` y no un `.xlsx` real.** Leer `.xlsx` en el
servidor con PhpSpreadsheet tiene el mismo problema de PHP 5.6 que la exportación; la alternativa
sin librerías modernas es PHPExcel, abandonado desde 2019 y sin parches de seguridad. Un `.csv` se
lee con `fgetcsv()`, nativo de PHP, sin instalar nada, y Excel lo abre y edita sin fricción —sigue
siendo la misma experiencia de "plantilla de Excel" para quien la llena.

**Por qué la plantilla incluye una columna `contrasena` en texto plano.** `Usuario_model::crear()`
exige una contraseña para dar de alta un usuario, igual que el formulario manual; no hay una forma
de omitirla sin dejar cuentas sin contraseña utilizable. El archivo nunca se guarda en el servidor
—solo se lee en memoria fila por fila y se descarta—, y cada contraseña se hashea con bcrypt antes
de insertarse, igual que en `crear()`. No se pide un campo de confirmación en el CSV porque no hay
doble captura que verificar en un archivo, a diferencia del formulario.

**Por qué la importación valida con `form_validation->set_data()` en vez de reglas propias.**
`Usuarios::reglas_alta()` (extraída de `crear()`) define las reglas una sola vez; tanto el alta
manual como la importación las corren tal cual, incluida `is_unique[...]`, así que un correo/CURP/RFC
repetido se rechaza con el mismo criterio en los dos flujos. `set_data()` es el mecanismo que ofrece
CodeIgniter 3 para validar un array que no viene de `$_POST`, sin tocar la superglobal.

**Por qué una fila inválida no detiene la importación.** Cada fila del CSV se valida y, si falla,
solo esa fila queda fuera —el resto se sigue procesando—. El resultado final lista fila por fila
qué se insertó y qué se rechazó (y por qué), en vez de fallar el archivo completo por un solo dato
mal capturado.

## Comandos útiles

```bash
docker compose exec web bash                              # shell en el contenedor web
docker compose exec db psql -U tai -d soluciones_tai      # consola SQL
docker compose build --no-cache web                       # reconstruir PHP desde cero
```

## Base de datos

La tabla `usuarios` guarda nombre, apellidos, correo, teléfono, CURP, RFC, sexo y el hash bcrypt
de la contraseña. El `id` no usa `SERIAL`: la secuencia se declara aparte (`CREATE SEQUENCE
usuarios_id_seq`) y se ata a la columna con `ALTER SEQUENCE ... OWNED BY`, así queda como un
objeto propio del esquema —visible con `\ds`— pero se sigue borrando junto con la tabla y
`pg_dump` la exporta con su `setval`.

```sql
CREATE SEQUENCE usuarios_id_seq;

CREATE TABLE usuarios (
    id               INTEGER      PRIMARY KEY DEFAULT nextval('usuarios_id_seq'),
    nombre           VARCHAR(100) NOT NULL,
    apellidos        VARCHAR(150) NOT NULL,
    correo           VARCHAR(150) NOT NULL UNIQUE,
    telefono         VARCHAR(20),
    curp             VARCHAR(18)  NOT NULL UNIQUE,
    rfc              VARCHAR(13)  NOT NULL UNIQUE,
    sexo             VARCHAR(10)  NOT NULL CHECK (sexo IN ('M', 'F', 'Otro')),
    contrasena_hash  VARCHAR(255),
    creado_en        TIMESTAMP    NOT NULL DEFAULT NOW()
);

ALTER SEQUENCE usuarios_id_seq OWNED BY usuarios.id;
```

`contrasena_hash` es `NULL`able a nivel de esquema a propósito: lo "obligatorio" se exige en
`form_validation`, no en la base de datos, así que los tres usuarios de ejemplo no necesitan un
hash de relleno.

### Migraciones sobre una base ya inicializada

`db/init/01-init.sql` solo corre una vez, cuando el volumen de Postgres está vacío (ver
"Problemas conocidos" más abajo). Para aplicar un cambio de esquema —como añadir
`contrasena_hash`, o después `curp`/`rfc`/`sexo`— a una base que ya tiene datos, sin perderlos,
`db/migrations/` guarda parches SQL idempotentes con la fecha por delante:

```bash
./db/backup.sh
docker compose exec -T db psql -U tai -d soluciones_tai \
    < db/migrations/2026-08-11-add-contrasena-hash.sql
docker compose exec -T db psql -U tai -d soluciones_tai \
    < db/migrations/2026-08-13-add-curp-rfc-sexo.sql
docker compose exec db psql -U tai -d soluciones_tai -c "\d usuarios"   # verificar
```

La segunda migración también rellena los 3 usuarios de ejemplo con CURP, RFC y sexo ficticios pero
válidos según las regex de arriba (y normaliza sus teléfonos a solo dígitos), para poder declarar
las columnas `NOT NULL` desde ya en vez de dejarlo solo a nivel de `form_validation`.

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

**CSRF roto en PHP < 7.3 por un bug de CodeIgniter 3 (ya parcheado).** `system/core/Security.php`
genera la cookie CSRF de dos formas según la versión de PHP: en PHP ≥ 7.3 usa `setcookie()` con un
array de opciones; en PHP < 7.3 —nuestro caso, 5.6.40— arma la cabecera `Set-Cookie` a mano y
aplicaba `rawurlencode()` al `path`, convirtiendo `/` en `%2F`. Un navegador real no asocia esa
cookie con `Path=%2F` a las rutas normales de la app (`/usuarios/editar/1`, etc.), así que todo
formulario POST fallaba con 403 "The action you have requested is not allowed", aunque `curl` con
un cookie jar no mostraba el problema (no aplica el emparejamiento estricto de `Path` que usan los
navegadores). Se quitó el `rawurlencode()` de esa línea, igual que ya hace la rama de PHP ≥ 7.3,
que nunca codifica el `path`. Detectado probando `/usuarios/editar/:id` con Playwright.

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
