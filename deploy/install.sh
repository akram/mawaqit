#!/bin/bash
set -e

target=$1
tag=$2
baseDir=/var/www/mawaqit
repoDir=$baseDir/repo
dockerContainer=mawaqit_php

cd $repoDir

# maintenance
if [[ "$target" == "prod" ]]; then
    touch $repoDir/docker/data/maintenance
    docker exec mawaqit_nginx nginx -s reload
fi

docker exec $dockerContainer git fetch && git checkout $tag

if [[ "$target" == "pp" ]]; then
    docker exec $dockerContainer git pull origin $tag
fi

echo "Creating symlinks"
docker exec $dockerContainer sh -c "(cd web && ln -snf robots.txt.$target robots.txt)"

echo "Set version"
version=`git symbolic-ref -q --short HEAD`@`git rev-parse --short HEAD`
if [[ "$target" == "prod" ]]; then
    version=$tag
fi

docker exec $dockerContainer sed -i "s/version: .*/version: $version/" app/config/parameters.yml

# Install vendors
docker exec $dockerContainer sh -c "SYMFONY_ENV=prod composer install -o -n --no-dev --no-suggest --prefer-dist --no-progress"

# Migrate DB
docker exec $dockerContainer bin/console doc:mig:mig -n --allow-no-migration -e prod

# cache
docker exec $dockerContainer bin/console c:c -e prod --no-warmup
docker exec $dockerContainer bin/console c:w -e prod

# Install assets
docker exec $dockerContainer bin/console assets:install -e prod
docker exec $dockerContainer bin/console assetic:dump -e prod

# Restart php
docker exec $dockerContainer kill -USR2 1

echo ""
echo "####################################################"
echo "$target has been successfully upgraded to $tag ;)"
echo "####################################################"