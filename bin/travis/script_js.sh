#!/bin/bash
EXIT_STATUS=0

npm run lint:js || EXIT_STATUS=$?
npm run lint:scss || EXIT_STATUS=$?
npm run flow || EXIT_STATUS=$?
npm test || EXIT_STATUS=$?

exit $EXIT_STATUS
