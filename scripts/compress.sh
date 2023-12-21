#!/usr/bin/env zsh

if ! type brotli &> /dev/null; then
    echo "brotli not installed"
    exit 1
fi

for file in ./public/assets/css/*.css
do
    brotli --force --quality=11 --output=$file.br -- $file
done

for file in ./public/assets/js/*.js
do
    brotli --force --quality=11 --output=$file.br -- $file
done
