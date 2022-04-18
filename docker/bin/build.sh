#!/bin/bash

cd docker/images/nginx
if [[ -z "$1" || "$1" == "nginx" ]]
then
    docker build -t ebisprofit-nginx-image .
fi

if [[ -z "$1" || "$1" == "php-fpm" ]]
then
    cd ../php-fpm
    docker build -t ebisprofit-php-fpm-image .
fi

if [[ -z "$1" || "$1" == "nodejs" ]]
then
    cd ../nodejs
    docker build -t ebisprofit-nodejs-image .
fi

if [[ -z "$1" || "$1" == "postgres" ]]
then
    cd ../
    # docker build -t ebisprofit-postgres-image .
    docker-compose build --build ebisprofit-postgres
fi