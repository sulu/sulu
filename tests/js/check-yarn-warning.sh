#!/bin/bash

YARN_OUTPUT="$(yarn install 2>&1)"

if [[ $YARN_OUTPUT == *"You must use \"npm install\", yarn is not supported"* ]]; then
    exit 0
fi

exit 1
