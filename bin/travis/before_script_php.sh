if [ ! -d downloads ]; then mkdir downloads; fi

if [[ $SYMFONY__PHPCR__TRANSPORT = jackrabbit ]]; then
    if [ ! -f downloads/jackrabbit-standalone-$JACKRABBIT_VERSION.jar ]; then
        cd downloads
        wget http://archive.apache.org/dist/jackrabbit/$JACKRABBIT_VERSION/jackrabbit-standalone-$JACKRABBIT_VERSION.jar
        cd -
    fi
    java -jar downloads/jackrabbit-standalone-$JACKRABBIT_VERSION.jar > /dev/null &
fi

# the content tests are intensive and there are memory leaks, this is more pronounced with the Jackalope DBAL PHPCR implementation.
echo "memory_limit=2048M" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini
phpenv config-rm xdebug.ini
composer self-update
composer update $COMPOSER_FLAGS
if [[ $SYMFONY__PHPCR__TRANSPORT = jackrabbit ]]; then composer require jackalope/jackalope-jackrabbit:~1.2 ; fi
