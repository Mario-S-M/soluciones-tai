--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.25
-- Dumped by pg_dump version 9.5.25

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: usuarios; Type: TABLE; Schema: public; Owner: tai
--

CREATE TABLE public.usuarios (
    id integer NOT NULL,
    nombre character varying(100) NOT NULL,
    apellidos character varying(150) NOT NULL,
    correo character varying(150) NOT NULL,
    telefono character varying(20),
    creado_en timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.usuarios OWNER TO tai;

--
-- Name: usuarios_id_seq; Type: SEQUENCE; Schema: public; Owner: tai
--

CREATE SEQUENCE public.usuarios_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.usuarios_id_seq OWNER TO tai;

--
-- Name: usuarios_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: tai
--

ALTER SEQUENCE public.usuarios_id_seq OWNED BY public.usuarios.id;


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: tai
--

ALTER TABLE ONLY public.usuarios ALTER COLUMN id SET DEFAULT nextval('public.usuarios_id_seq'::regclass);


--
-- Data for Name: usuarios; Type: TABLE DATA; Schema: public; Owner: tai
--

COPY public.usuarios (id, nombre, apellidos, correo, telefono, creado_en) FROM stdin;
1	Mario Eduardo	Sanchez Mejia	mario@tai.local	55 1234 5678	2026-08-11 01:33:04.994208
2	Ana Lucia	Ramirez Torres	ana@tai.local	55 8765 4321	2026-08-11 01:33:04.994208
3	Soporte	Soluciones TAI	soporte@tai.local	55 0000 1111	2026-08-11 01:33:04.994208
\.


--
-- Name: usuarios_id_seq; Type: SEQUENCE SET; Schema: public; Owner: tai
--

SELECT pg_catalog.setval('public.usuarios_id_seq', 3, true);


--
-- Name: usuarios_correo_key; Type: CONSTRAINT; Schema: public; Owner: tai
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_correo_key UNIQUE (correo);


--
-- Name: usuarios_pkey; Type: CONSTRAINT; Schema: public; Owner: tai
--

ALTER TABLE ONLY public.usuarios
    ADD CONSTRAINT usuarios_pkey PRIMARY KEY (id);


--
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: tai
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM tai;
GRANT ALL ON SCHEMA public TO tai;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

