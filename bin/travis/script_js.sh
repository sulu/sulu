#!/bin/bash
EXIT_STATUS=0

npm run lint:js || EXIT_STATUS=$?
npm run lint:scss || EXIT_STATUS=$?
npm run flow || EXIT_STATUS=$?

shopt -s nullglob
for folder in src/Sulu/Bundle/*/Resources/js
do
    if [ -d $folder ]; then
        cd $folder
        npm install
        npm run test || EXIT_STATUS=$?
    fi
done

exit $EXIT_STATUS
