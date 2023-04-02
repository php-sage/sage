# `cd .` is because it sometimes solves https://github.com/docker/compose/issues/7899
DOCKER = cd . && DOCKER_UID=$(shell id -u) DOCKER_GID=$(shell id -g) docker compose

console:
	$(DOCKER) run php bash


sh:
	make console


build:
	$(DOCKER) up -d
	make test
	$(DOCKER) run php composer build # see composer.json -> "scripts" section


test:
	$(DOCKER) run php pest


update-test-snapshots:
	$(DOCKER) run php pest -d --update-snapshots



nuke-docker:
	@# Help: Nuclear option to force-remove all docker images, volumes and containers
	-$(DOCKER) down --volumes
	-$(DOCKER) rm --force --stop --volumes
	-docker kill $$(docker ps -q)
	-docker volume rm $$(docker volume ls -q)
	-docker rmi --force $$(docker images -a -q)
	# the above is always enough, but the following command would do all of that
	# (and more!) and prune ALL cached images so they will have to be re-downloaded:
	# -docker system prune -f



down-for-good:
	@# Help: Stop docker and delete its volumes
	-$(DOCKER) rm --force --stop --volumes
	-$(DOCKER) down --volumes
