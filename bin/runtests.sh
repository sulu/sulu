#!/bin/bash

DB=mysql
OCWD=`pwd`
BUNDLE=$1

if [[ "$DB" == "mysql" ]]; then
    mysql -u root -e "DROP DATABASE sulu_test;"
    mysql -u root -e "CREATE DATABASE sulu_test;"
fi
if [[ "$DB" == "pgsql" ]]; then
    pgsql -c "DROP DATABASE sulu_test;"
    pgsql -c "CREATE DATABASE sulu_test;"
fi

# ./src/Sulu/Bundle/TestBundle/Resources/bin/travis.sh

if [ -z $BUNDLE ]; then
    BUNDLES=`find ./src/Sulu/Bundle/* -maxdepth 1 -name "phpunit.xml.dist"`
else
    BUNDLES=`find ./src/Sulu/Bundle/$BUNDLE -maxdepth 1 -name "phpunit.xml.dist"`
fi

for BUNDLE in $BUNDLES; do

    BUNDLE_DIR=`dirname $BUNDLE`
    BUNDLE_NAME=`basename $BUNDLE_DIR`

    echo ""
    echo "+======================================================+"
    echo "| "$BUNDLE_NAME
    echo "+======================================================+"
    echo "Running tests"
    echo "+-----------+"
    echo ""

    if [ -e $BUNDLE_DIR"/Tests/Resources/app/AppKernel.php" ]; then
        export KERNEL_DIR=$BUNDLE_DIR"/Tests/Resources/app"
    elif [ -e $BUNDLE_DIR"/Tests/app/AppKernel.php" ]; then
        export KERNEL_DIR=$BUNDLE_DIR"/Tests/app"
    else
        export KERNEL_DIR=$BUNDLE_DIR"/tests/app"
    fi

    echo "Kernel: "$KERNEL_DIR

    cd $BUNDLE_DIR
    $OCWD"/src/Sulu/Bundle/TestBundle/Resources/bin/travis.sh"
    php $OCWD/vendor/symfony-cmf/testing/bin/console doctrine:schema:update --force
    if [ ! -e vendor ]; then
        ln -s $OCWD"/vendor" vendor
    fi
    cd -

    echo "RUNNING TESTS"
    echo "============="
    phpunit --configuration phpunit.travis.xml.dist --stop-on-error $BUNDLE_DIR/Tests
done
