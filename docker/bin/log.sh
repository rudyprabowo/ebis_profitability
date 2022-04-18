#!/bin/bash

# docker run -di --name docker-nginx -p 8080:80 -v "C:\codebase\docker-php\nginx\conf.d":/etc/nginx/conf.d/ -v "C:\codebase\docker-php\app":/var/www  --network web-network docker-nginx-image
# docker run -di --name docker-php-fpm -v "C:\codebase\docker-php\app":/var/www --network web-network docker-php-fpm-image
DIRPATH="D:\TMA\work\telkom\ebis-profitability"

if [[ -z "$1" || "$1" == "php-fpm" ]]
then
    docker logs ebisprofit-php-fpm --tail 50 -f
fi

if [[ -z "$1" || "$1" == "nodejs" ]]
then
    docker logs ebisprofit-nodejs --tail 50 -f
fi

if [[ -z "$1" || "$1" == "nginx" ]]
then
    docker logs ebisprofit-nginx --tail 50 -f
fi

if [[ -z "$1" || "$1" == "postgres" ]]
then
    docker logs ebisprofit-postgres --tail 50 -f
fi