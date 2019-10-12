#!/usr/bin/env bash

ln -sf docker-compose.dev.yml docker-compose.yml
docker-compose down && docker-compose up -d --build

docker-compose exec mawaqit_php mkdir -p var/logs var/cache var/sessions
docker-compose exec mawaqit_php rm -rf var/logs/* var/cache/* var/sessions/*
docker-compose exec mawaqit_php chmod -R 777 var/logs var/cache var/sessions

echo ""
echo "Waiting for database..."

while ! docker-compose exec mawaqit_mysql mysqladmin ping --silent; do
    sleep 1
done

docker-compose exec mawaqit_php bin/console d:s:u -f
docker-compose exec mawaqit_php bin/console h:f:l -n

echo "------------------------------------------"
echo "Mawaqit is up"
echo "http://localhost:10001 / login: local@local.com / password: local"
echo "Database: host 127.0.0.1, port 10002, user root, password mawaqit"
echo "Maildev: http://localhost:10003"
echo "------------------------------------------"
