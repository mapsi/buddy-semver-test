#! /bin/bash

STRING=$(git reflog -1 | sed 's/^.*: //')

if echo $STRING | grep -q "Merged in hotfix/"; then
TAG=$(./semver.sh patch)
elif echo $STRING | grep -q "Merged in release/"; then
TAG=$(./semver.sh minor)
else
TAG=""
fi

git tag $TAG
