#!/bin/bash

CONTAINER="ebisprofit-php-fpm"
if [[ ! -z "$1"]]
then
    CONTAINER="$1"
fi
docker exec -ti ebisprofit-php-fpm $CONTAINER