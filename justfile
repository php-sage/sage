# `cd .` is because it sometimes solves https://github.com/docker/compose/issues/7899
docker := "cd . && DOCKER_UID=$(id -u) DOCKER_GID=$(id -g) docker compose"

orange := '\033[0;33m'
nocolor := '\033[0m'


console:
    {{ docker }} run php bash

alias sh := console


alias release := build

setup:
    {{ docker }} run php npm install
    just composer-update

build:
    {{ docker }} up -d --remove-orphans
    {{ docker }} run php composer build # see composer.json -> "scripts" section
    just test

# Rebuild docker image (or all of them) to reflect configuration (e.g. Dockerfile) changes.
rebuild:
    {{ docker }} up -d --build --force-recreate --no-deps --remove-orphans

# One part of build process if you're only working with sass/js
compile-resources:
    {{ docker }} run php npm run build

# Run tests. Accepts `p` parameter as filter + repeat parameter: `just test p=fileapitest repeat=1`
test p="" repeat="1":
    #!/usr/bin/env bash
    if [ -z "{{ p }}" ]; then
        {{ docker }} run php pest
    else
        for i in $(seq 1 {{ repeat }}); do
            echo "{{ orange }}Repetition $i of {{ repeat }}{{ nocolor }}"
            echo "{{ docker }} run php pest --filter {{ p }}"
            {{ docker }} run php pest --filter {{ p }}
        done
    fi

php53:
    docker run -it --rm --name my-running-script -v "$PWD":/usr/src/myapp -w /usr/src/myapp \
    orsolin/docker-php-5.3-apache php /usr/src/myapp/tests/temp_tests/php53test.php

php51:
    docker run -it --rm --name my-running-script2 -v "$PWD":/usr/src/myapp -w /usr/src/myapp \
    horitaku1124/php51_centos7 php /usr/src/myapp/tests/temp_tests/php53test.php

update-test-snapshots:
    {{ docker }} run php pest -d --update-snapshots

up:
    {{ docker }} up -d

restart:
    -{{ docker }} down
    {{ docker }} up -d

# Nuclear option to force-remove all docker images, volumes and containers
nuke-docker:
    -{{ docker }} down --volumes
    -{{ docker }} rm --force --stop --volumes
    -docker kill $(docker ps -q)
    -docker volume rm $(docker volume ls -q)
    -docker rmi --force $(docker images -a -q)

# Stop docker and delete its volumes
down-for-good:
    -{{ docker }} rm --force --stop --volumes
    -{{ docker }} down --volumes

composer-update:
    @{{ docker }} up -d php
    {{ docker }} exec php composer update

composer-install:
    @{{ docker }} up -d php
    {{ docker }} exec php composer install

alias cu := composer-update

alias ci := composer-install

play:
    just up
    {{ docker }} exec php php /var/www/playground.php

serve:
    just up
    @echo "go to ----> http://localhost:18181 <-----"
    {{ docker }} exec php php -S 0.0.0.0:18181 playground.php
