#!/bin/bash

# clean all artifacts before committing
gulp clean

# build info
gulp info

# generate POT file
gulp makepot

# lint all php
gulp php
