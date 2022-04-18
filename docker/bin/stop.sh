#!/bin/bash

DIRPATH="D:\TMA\work\telkom\ebis-profitability"

if [[ -z "$1" || "$1" == "php-fpm" ]]
then
    docker stop ebisprofit-php-fpm
fi

if [[ -z "$1" || "$1" == "nodejs" ]]
then
    docker stop ebisprofit-nodejs
fi

if [[ -z "$1" || "$1" == "nginx" ]]
then
    docker stop ebisprofit-nginx
fi

if [[ -z "$1" || "$1" == "postgres" ]]
then
    docker stop ebisprofit-postgres
fi