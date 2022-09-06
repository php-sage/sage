# `cd .` is because it sometimes solves https://github.com/docker/compose/issues/7899
DOCKER_COMPOSE = cd . && docker compose


console:
	$(DOCKER_COMPOSE) run php bash


sh:
	make console


build-phar:
	make docker-build
	$(DOCKER_COMPOSE) run php composer build


build:
	make build-phar


docker-build:
	-$(DOCKER_COMPOSE) rm --force --stop --volumes
	-$(DOCKER_COMPOSE) build
	-$(DOCKER_COMPOSE) up -d


test:
	$(DOCKER_COMPOSE) run php pest


update-test-snapshots:
	$(DOCKER_COMPOSE) run php pest -d --update-snapshots
