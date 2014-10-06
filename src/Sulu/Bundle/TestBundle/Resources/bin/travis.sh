#!/bin/bash
echo "Creating database"
php vendor/symfony-cmf/testing/bin/console doctrine:database:create

if [[ $? != 0 ]]; then
    php vendor/symfony-cmf/testing/bin/console doctrine:database:drop --force
    php vendor/symfony-cmf/testing/bin/console doctrine:database:create
fi

echo "Updating schema"
php vendor/symfony-cmf/testing/bin/console doctrine:schema:create

echo "Initialzing PHPCR (including doctrine-dbal, this may fail)"
php vendor/symfony-cmf/testing/bin/console doctrine:phpcr:init:dbal
php vendor/symfony-cmf/testing/bin/console sulu:phpcr:init
