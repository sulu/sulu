#! /bin/bash

git status
changes=$(git status -s | grep dist -c)
expected=12
exitCode=$(expr $changes - $expected)
echo $exitCode
exit $exitCode
