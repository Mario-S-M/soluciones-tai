/*pga4dash*/
-- ADAPTADA A POSTGRESQL 9.5.
-- La version original de pgAdmin 7.8 asume un servidor 9.6 o superior y usa tres
-- elementos que no existen en 9.5:
--   wait_event_type / wait_event  (9.6+)  -> aqui se deriva de la columna "waiting"
--   pg_blocking_pids()            (9.6+)  -> no tiene equivalente; se devuelve vacio
--   backend_type                  (10+)   -> se devuelve un valor fijo
-- Sin esto el panel Dashboard falla en bucle con:
--   column "wait_event_type" does not exist
SELECT
    pid,
    datname,
    usename,
    application_name,
    client_addr,
    pg_catalog.to_char(backend_start, 'YYYY-MM-DD HH24:MI:SS TZ') AS backend_start,
    state,
    CASE WHEN waiting THEN 'Lock: waiting' ELSE NULL END AS wait_event,
    ''::text AS blocking_pids,
    query,
    pg_catalog.to_char(state_change, 'YYYY-MM-DD HH24:MI:SS TZ') AS state_change,
    pg_catalog.to_char(query_start, 'YYYY-MM-DD HH24:MI:SS TZ') AS query_start,
    pg_catalog.to_char(xact_start, 'YYYY-MM-DD HH24:MI:SS TZ') AS xact_start,
    'client backend'::text AS backend_type,
    CASE WHEN state = 'active' THEN ROUND((extract(epoch from now() - query_start) / 60)::numeric, 2) ELSE 0 END AS active_since
FROM
    pg_catalog.pg_stat_activity
{% if did %}WHERE
    datname = (SELECT datname FROM pg_catalog.pg_database WHERE oid = {{ did }}){% endif %}
ORDER BY pid
