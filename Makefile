# `cd .` is because it sometimes solves https://github.com/docker/compose/issues/7899
DOCKER_COMPOSE = cd . && docker compose


console:
	$(DOCKER_COMPOSE) run php bash


sh:
	make console


build:
	#-$(DOCKER_COMPOSE) rm --force --stop --volumes
	-$(DOCKER_COMPOSE) up -d
	-$(DOCKER_COMPOSE) npm install
	-$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) run php composer build # see composer.json -> "scripts" section


test:
	$(DOCKER_COMPOSE) run php pest


update-test-snapshots:
	$(DOCKER_COMPOSE) run php pest -d --update-snapshots
