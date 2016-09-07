#!/bin/bash

echo 'Cleaning up build artificats...'
paths=(wp-dfp/css/* wp-dfp/js/* wp-dfp/externals/*)
for path in "${paths[@]}"; do
  rm -rf "$path"
done
