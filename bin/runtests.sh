#!/bin/bash

DB=mysql
OCWD=`pwd`
BUNDLE=""
SULU_ORM=${SULU_ORM:-mysql}
SULU_PHPCR=${SULU_PHPCR:-doctrine_dbal}

function header {
    echo ""
    echo -e "\x1b[32m======================================================\x1b[0m"
    echo $1
    echo -e "\x1b[32m======================================================\x1b[0m"
    echo ""
}

function info {
    echo -e "\x1b[32m"$1"\x1b[0m"

    echo ""
}

function comment {
    echo -e "\x1b[33m"$1"\x1b[0m"
    echo ""
}

function error {
    echo ""
    echo -e "\x1b[31m======================================================\x1b[0m"
    echo $1
    echo -e "\x1b[31m======================================================\x1b[0m"
    echo ""
}

function init_database {
    comment "Initializing database"

    init_dbal

    if [[ $SULU_PHPCR == 'doctrine_dbal' ]]; then
        init_phpcr_dbal
    fi

    php vendor/symfony-cmf/testing/bin/console sulu:phpcr:init
}

function show_help {
    echo "Sulu Test Runner"
    echo ""
    echo "Usage:"
    echo ""
    echo "  ./bin/runtests.sh -i -a # initialize and run all tests"
    echo "  ./bin/runtests.sh -t LocationBundle # run only LocationBundle tests"
    echo ""
    echo "Options:"
    echo ""
    echo "  i) Execute the initializaction script before running the tests"
    echo "  t) Specify a target bundle"
    echo "  a) Run all tests"
    exit 0
}

function init_dbal {
    info "Creating database"
    php vendor/symfony-cmf/testing/bin/console doctrine:database:create &> /dev/null

    if [[ $? != 0 ]]; then
        comment "Database already exists"
    else
        echo "Creating schema"
        php vendor/symfony-cmf/testing/bin/console doctrine:schema:create
    fi

}

function init_phpcr_dbal {
    echo "Initialzing PHPCR (including doctrine-dbal, this may fail)"
    php vendor/symfony-cmf/testing/bin/console doctrine:phpcr:init:dbal &> /dev/null
}

cat <<EOT
   _____       _        _____ __  __ ______ 
  / ____|     | |      / ____|  \/  |  ____|
 | (___  _   _| |_   _| |    | \  / | |__   
  \___ \| | | | | | | | |    | |\/| |  __|  
  ____) | |_| | | |_| | |____| |  | | |     
 |_____/ \__,_|_|\__,_|\_____|_|  |_|_|     
                                            
EOT

header "Sulu CMF Test Suite"
comment "ORM: "$SULU_ORM
comment "PHPCR: "$SULU_PHPCR

while getopts ":ait:" OPT; do
    case $OPT in
        i)
            init_database
            ;;
        t)
            BUNDLE=$OPTARG
            ;;
        a)
            ;;
    esac
done

if [[ -z $1 ]]; then
    show_help
fi

if [ -e /tmp/failed.tests ]; then
    rm /tmp/failed.tests
fi

touch /tmp/failed.tests

if [ -z $BUNDLE ]; then
    BUNDLES=`find ./src/Sulu/Bundle/* -maxdepth 1 -name "phpunit.xml.dist"`
else
    BUNDLES=`find ./src/Sulu/Bundle/$BUNDLE -maxdepth 1 -name "phpunit.xml.dist"`
fi

for BUNDLE in $BUNDLES; do

    BUNDLE_DIR=`dirname $BUNDLE`
    BUNDLE_NAME=`basename $BUNDLE_DIR`

    header $BUNDLE_NAME

    if [ -e $BUNDLE_DIR"/Tests/Resources/app/AppKernel.php" ]; then
        export KERNEL_DIR=$BUNDLE_DIR"/Tests/Resources/app"
    elif [ -e $BUNDLE_DIR"/Tests/app/AppKernel.php" ]; then
        export KERNEL_DIR=$BUNDLE_DIR"/Tests/app"
    else
        export KERNEL_DIR=""
    fi

    cd $BUNDLE_DIR

    if [ ! -e vendor ]; then
        ln -s $OCWD"/vendor" vendor
    fi

    if [[ ! -z "$KERNEL_DIR" ]]; then
        CONSOLE="env KERNEL_DIR=$OCWD"/"$KERNEL_DIR $OCWD/bin/console"
        comment "Kernel: "$KERNEL_DIR

        $CONSOLE container:debug | cut -d' ' -f2 | grep "^doctrine.orm" &> /dev/null \
            && comment "Doctrine ORM detected" \
            && $CONSOLE doctrine:schema:update --force
    fi

    cd -
    comment "Running tests"

    phpunit --configuration phpunit.travis.xml.dist $BUNDLE_DIR/Tests

    if [ $? -ne 0 ]; then
        echo $BUNDLE_NAME >> /tmp/failed.tests
    fi
done

if [[ ! -s /tmp/failed.tests ]]; then
    # Everything was OK
    header "Everythig is AWESOME! \o/"
    exit 0
else
    # There were failures
    echo ""
    echo -e "\x1b[31m======================================================\x1b[0m"
    echo "Oh no, "`cat /tmp/failed.tests | wc -l`" Component(s) failed:"
    echo ""
    for line in `cat /tmp/failed.tests`; do
        comment " - "$line
    done
    echo -e "\x1b[31m======================================================\x1b[0m"
    echo ""
    exit 1
fi
