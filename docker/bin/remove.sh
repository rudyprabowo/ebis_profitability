#!/bin/bash

if [[ -z "$1" || "$1" == "nginx" ]]
then
    docker rm -f ebisprofit-nginx
fi

if [[ -z "$1" || "$1" == "php-fpm" ]]
then
    docker rm -f ebisprofit-php-fpm
fi

if [[ -z "$1" || "$1" == "nodejs" ]]
then
    docker rm -f ebisprofit-nodejs
fi

if [[ -z "$1" || "$1" == "postgres" ]]
then
    docker rm -f ebisprofit-postgres
fi