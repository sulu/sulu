#! /bin/bash

function build_bundle {
    if [ -f "Gruntfile.js" ]
    then
        npm install
        rm -rf Resources/public/dist
        npm run build
    fi
}

cd src/Sulu/Bundle

for bundle in *; do
    if [ -d "$bundle" ];
    then
        cd "$bundle"
        build_bundle
        cd ..
    fi
done
