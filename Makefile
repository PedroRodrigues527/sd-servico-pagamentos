build:
    docker-compose build --no-cache --force-rm
stop: 
    docker-compose stop
up:
    docker-compose up -d
composer-update:
    docker exec laravel-test bash -c "composer updater"