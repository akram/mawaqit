#!/bin/bash

ln -sf docker-compose.dev.yml docker-compose.yml
docker-compose exec php composer install -n
echo ""
echo "Waiting for database..."

while ! docker-compose exec db mysqladmin ping -h "127.0.0.1" --silent; do
    sleep 1
done

docker-compose exec php bin/console d:s:u -f
docker-compose exec php bin/console h:f:l -n --purge-with-truncate
docker-compose exec php chmod 777 -R var/cache var/logs var/sessions web/upload

echo "------------------------------------------"
echo "Mawaqit is up"
echo "http://mawaqit.localhost:10001 / login: local@local.com / password: Local101010."
echo "Database: host 127.0.0.1, port 10002, user root, password mawaqit"
echo "Maildev: http://localhost:10003"
echo "------------------------------------------"