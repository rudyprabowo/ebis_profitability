#!/bin/bash

DATAPATH="D:\TMA\work\telkom\ebis-profitability\docker\images\postgres\data"
POSTGRES_PASSWORD="postgres"
docker run -d --name ebisprofit-postgres -e POSTGRES_PASSWORD=${POSTGRES_PASSWORD} -u postgres -e PGDATA=/postgresql/data/pgdata -v ${DATAPATH}:/postgresql/data --network ebisprofit-network  ebisprofit-postgres-image