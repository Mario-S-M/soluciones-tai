{# Version para PostgreSQL 9.5 de la plantilla que trae pgAdmin 7.8.          #}
{# La original lee los metadatos del catalogo pg_catalog.pg_sequence, que no  #}
{# existe hasta la 10. En 9.5 esos valores se obtienen consultando la propia  #}
{# secuencia, que ya expone las columnas con estos mismos nombres:            #}
{# last_value, start_value, increment_by, max_value, min_value, cache_value,  #}
{# is_cycled e is_called.                                                     #}
SELECT
    last_value,
    min_value,
    max_value,
    start_value,
    cache_value,
    is_cycled,
    increment_by,
    is_called
FROM {{ conn|qtIdent(data.schema) }}.{{ conn|qtIdent(data.name) }}
