#!/bin/bash

pushd $(git rev-parse --show-toplevel)

echo 'Linting PHP...'
for f in $(find wp-dfp -name '*.php'); do
  php -l "$f"
done

popd
