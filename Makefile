ifndef BUILD_ENV
BUILD_ENV=php7.2
endif

QA_DOCKER_IMAGE=parkmanager/phpqa:latest
QA_DOCKER_COMMAND=docker run --init --interactive --tty --rm --env "COMPOSER_HOME=/composer" --user "$(shell id -u):$(shell id -g)" --volume /tmp/tmp-phpqa-$(shell id -u):/tmp:delegated --volume "$(shell pwd):/project:delegated" --volume "${HOME}/.composer:/composer:delegated" --workdir /project ${QA_DOCKER_IMAGE}

install: composer-install
dist: composer-validate cs phpstan psalm test
ci: check test
check: composer-validate cs-check phpstan psalm
test: phpunit-coverage

clean:
	rm -rf var/

composer-validate: ensure
	sh -c "${QA_DOCKER_COMMAND} composer validate"

composer-install: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade"

composer-install-lowest: fetch ensure clean
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --prefer-lowest"

composer-install-dev: fetch ensure clean
	rm -f composer.lock
	cp composer.json _composer.json
	sh -c "${QA_DOCKER_COMMAND} composer config minimum-stability dev"
	sh -c "${QA_DOCKER_COMMAND} composer upgrade --no-progress --no-interaction --no-suggest --optimize-autoloader --ansi"
	mv _composer.json composer.json

cs:
	sh -c "${QA_DOCKER_COMMAND} php vendor/bin/phpcbf"

cs-check:
	sh -c "${QA_DOCKER_COMMAND} php vendor/bin/phpcs"

phpstan: ensure
	sh -c "${QA_DOCKER_COMMAND} phpstan analyse"

psalm: ensure
	sh -c "${QA_DOCKER_COMMAND} psalm --show-info=false"

phpunit-coverage: ensure
	sh -c "${QA_DOCKER_COMMAND} phpdbg -qrr vendor/bin/phpunit --verbose --coverage-text --log-junit=var/phpunit.junit.xml --coverage-xml var/coverage-xml/"

phpunit:
	sh -c "${QA_DOCKER_COMMAND} phpunit --verbose"

ensure:
	mkdir -p ${HOME}/.composer /tmp/tmp-phpqa-$(shell id -u)

fetch:
	docker pull "${QA_DOCKER_IMAGE}"
