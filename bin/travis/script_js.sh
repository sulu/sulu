#!/bin/bash
npm run lint
npm run flow

shopt -s nullglob
for folder in src/Sulu/Bundle/*/Resources/js
do
    if [ -d $folder ]; then
        cd $folder
        npm install
        npm run test
    fi
done
