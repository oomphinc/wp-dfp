#!/bin/bash

# clean all artifacts before committing
gulp clean

# generate POT file
gulp makepot

# lint all php
gulp php
