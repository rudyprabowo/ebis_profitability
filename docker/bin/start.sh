#!/bin/bash

# docker run -di --name docker-nginx -p 8080:80 -v "C:\codebase\docker-php\nginx\conf.d":/etc/nginx/conf.d/ -v "C:\codebase\docker-php\app":/var/www  --network web-network docker-nginx-image
# docker run -di --name docker-php-fpm -v "C:\codebase\docker-php\app":/var/www --network web-network docker-php-fpm-image
DIRPATH="D:\TMA\work\telkom\ebis-profitability"

if [[ -z "$1" || "$1" == "php-fpm" ]]
then
    docker run -di --name ebisprofit-php-fpm -v $DIRPATH:/var/www --network ebisprofit-network ebisprofit-php-fpm-image
fi

if [[ -z "$1" || "$1" == "nodejs" ]]
then
    docker run -di --name ebisprofit-nodejs -v $DIRPATH:/var/www --network ebisprofit-network ebisprofit-nodejs-image
fi

if [[ -z "$1" || "$1" == "nginx" ]]
then
    docker run -di --name ebisprofit-nginx -p 8080:80  -v $DIRPATH:/var/www  --network ebisprofit-network ebisprofit-nginx-image
fi

if [[ -z "$1" || "$1" == "postgres" ]]
then
    DATAPATH="D:\TMA\work\telkom\ebis-profitability\docker\images\postgres\data"
    POSTGRES_PASSWORD="postgres"
    docker run -d --name ebisprofit-postgres -p 5420:5432 -e POSTGRES_PASSWORD=$POSTGRES_PASSWORD --network ebisprofit-network postgres:14.2-alpine
    # docker run -d --name ebisprofit-postgres --user $(id -u):$(id -g) -e POSTGRES_PASSWORD=$POSTGRES_PASSWORD -e PGDATA=/postgresql/data/pgdata -v $DATAPATH:/postgresql/data --network ebisprofit-network postgres:14.2-alpine
    # docker-compose  -f "docker\docker-compose.yml" up -d --build ebisprofit-postgres
fi