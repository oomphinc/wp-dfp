#!/bin/bash

# clean all artifacts before committing
gulp clean

# build
gulp build

# generate POT file
gulp compress
