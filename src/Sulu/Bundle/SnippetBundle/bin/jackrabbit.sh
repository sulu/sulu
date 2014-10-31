#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $DIR

VERSION=2.6.3

JAR=jackrabbit-standalone-$VERSION.jar

# download jackrabbit jar from archive, as the dist only contains the latest
# stable versions
if [ ! -f "$DIR/$JAR" ]; then
    wget http://archive.apache.org/dist/jackrabbit/$VERSION/$JAR
fi

java -jar $DIR/$JAR&

echo "Waiting until Jackrabbit is ready on port 8080"
while [[ -z `curl -s 'http://localhost:8080' ` ]]
do
    echo -n "."
    sleep 2s
done

echo "Jackrabbit is up"
