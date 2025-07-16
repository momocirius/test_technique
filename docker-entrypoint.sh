#!/bin/sh
set -e

if [ -z "$(ls -A 'vendor/' 2>/dev/null)" ]; then
  echo "> Installing composer dependencies"
  composer install --prefer-dist --no-progress --no-interaction
else
  echo "> Dependencies already installed, skipping."
fi

tail -f /dev/null
