#!/bin/bash

# Run me to develop!
npm install

# Install / update all submodules
git submodule update --recursive --init

gulp clean
gulp develop
