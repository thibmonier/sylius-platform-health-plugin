DOCKER_COMPOSE ?= docker compose
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "test"
RUN = ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php

# Every command runs in the php container (never the local PHP). The database is the MySQL of sylius-b2b-demo.

install:
	@$(RUN) composer install --no-interaction --no-scripts --no-plugins

database-reset:
	@$(RUN) vendor/bin/console doctrine:database:drop --force --if-exists
	@$(RUN) vendor/bin/console doctrine:database:create

database-drop:
	@$(RUN) vendor/bin/console doctrine:database:drop --force --if-exists

phpstan:
	@$(RUN) php -d memory_limit=2G vendor/bin/phpstan analyse --no-progress

ecs:
	@$(RUN) vendor/bin/ecs check

phpunit:
	@$(RUN) vendor/bin/phpunit

unit:
	@$(RUN) vendor/bin/phpunit --testsuite=unit

non-unit:
	@$(RUN) vendor/bin/phpunit --testsuite=non-unit

rector:
	@$(RUN) vendor/bin/rector process --dry-run

rector-fix:
	@$(RUN) vendor/bin/rector process
