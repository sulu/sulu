#!/bin/sh
DIR_NAME=`dirname $0`
CONSOLE_DIR=$DIR_NAME"/../../vendor/symfony-cmf/testing/bin"

# composer install --dev
php $CONSOLE_DIR"/console" doctrine:database:create --env=orm
php $CONSOLE_DIR"/console" doctrine:schema:create --env=orm
php $CONSOLE_DIR"/console" doctrine:phpcr:init:dbal
php $CONSOLE_DIR"/console" sulu:phpcr:init
php $CONSOLE_DIR"/console" sulu:webspace:init
