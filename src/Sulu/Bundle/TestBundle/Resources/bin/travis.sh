#!/bin/bash

TARGET=$1

function init_dbal {
    echo "Creating database"
    php vendor/symfony-cmf/testing/bin/console doctrine:database:create

    if [[ $? != 0 ]]; then
        php vendor/symfony-cmf/testing/bin/console doctrine:database:drop --force
        php vendor/symfony-cmf/testing/bin/console doctrine:database:create
    fi

    echo "Updating schema"
    php vendor/symfony-cmf/testing/bin/console doctrine:schema:create
}

function init_phpcr_dbal {
    echo "Initialzing PHPCR (including doctrine-dbal, this may fail)"
    php vendor/symfony-cmf/testing/bin/console doctrine:phpcr:init:dbal
    echo "Registring node types"
    php vendor/symfony-cmf/testing/bin/console sulu:phpcr:init
}

function init_phpcr_jackalope {
    echo "Registring node types"
    php vendor/symfony-cmf/testing/bin/console sulu:phpcr:init
}

if test -z $TARGET; then
    TARGET="phpcr_dbal"
fi

echo "==================================="
echo "| Test initialization: $TARGET"
echo "==================================="
echo ""

case $TARGET in
    dbal)
        init_dbal
        ;;
    phpcr_dbal)
        init_dbal
        init_phpcr_dbal
        ;;
    phpcr_jackalope)
        init_phpcr_jackalope
        ;;
esac
