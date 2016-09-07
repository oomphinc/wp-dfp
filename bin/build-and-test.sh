#!/bin/bash

pushd $(git rev-parse --show-toplevel)

echo 'Updating git submodules...'
git submodule update --recursive --init

echo 'Installing node modules...'
npm install

echo 'Copying "externals/wp-forms-api" to "wp-dfp/externals/wp-forms-api"'
rsync -r --delete --exclude='.git' --exclude='.DS_Store' --exclude='node_modules' externals/wp-forms-api/. wp-dfp/externals/wp-forms-api

echo 'Running Gulp...'
gulp build

popd
