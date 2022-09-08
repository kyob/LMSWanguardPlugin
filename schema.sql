-- Adminer 4.8.0 PostgreSQL 13.8 dump

DROP TABLE IF EXISTS "alfa_wanguard";
CREATE TABLE "public"."alfa_wanguard" (
    "anomaly_id" integer NOT NULL,
    "status" character varying(16) NOT NULL,
    "anomaly" character varying(128) NOT NULL,
    "direction" character varying(32) NOT NULL,
    "node_id" integer NOT NULL,
    "ipaddr" bigint NOT NULL,
    "location" text,
    "until" bigint NOT NULL,
    CONSTRAINT "alfa_wanguard_anomaly_id" UNIQUE ("anomaly_id")
) WITH (oids = false);


ALTER TABLE ONLY "public"."alfa_wanguard" ADD CONSTRAINT "alfa_wanguard_node_id_fkey" FOREIGN KEY (node_id) REFERENCES nodes(id) ON UPDATE CASCADE ON DELETE CASCADE NOT DEFERRABLE;

-- 2022-09-08 12:32:49.191057+02