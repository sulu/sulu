#! /bin/bash

function build_bundle {
    if [ -f "Gruntfile.js" ]
    then
        grunt build
        OUT=$? # return code of grunt build

        if [ OUT != 0 ];
        then
            npm install
            rm -rf Resources/public/dist
            grunt build
        fi
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
