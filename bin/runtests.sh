#!/bin/bash

DB=mysql
OCWD=`pwd`
BUNDLE=$1

function header {
    echo ""
    echo -e "\e[32m======================================================\e[0m"
    echo $1
    echo -e "\e[32m======================================================\e[0m"
    echo ""
}

function comment {
    echo -e "\e[33m"$1"\e[0m"
    echo ""
}

function error {
    echo ""
    echo -e "\e[31m======================================================\e[0m"
    echo $1
    echo -e "\e[31m======================================================\e[0m"
    echo ""
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

comment "Initializing database"

./src/Sulu/Bundle/TestBundle/Resources/bin/travis.sh &> /dev/null

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

    phpunit --configuration phpunit.travis.xml.dist --stop-on-failure --stop-on-error $BUNDLE_DIR/Tests

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
    echo -e "\e[31m======================================================\e[0m"
    echo "Oh no, "`cat /tmp/failed.tests | wc -l`" Component(s) failed:"
    echo ""
    for line in `cat /tmp/failed.tests`; do
        comment " - "$line
    done
    echo -e "\e[31m======================================================\e[0m"
    echo ""
    exit 1
fi
