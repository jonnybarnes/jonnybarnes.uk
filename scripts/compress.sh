#!/usr/bin/env zsh

if ! type fd &> /dev/null; then
    echo "fd not installed"
    exit 1
fi

if ! type brotli &> /dev/null; then
    echo "brotli not installed"
    exit 1
fi

fd -e css -e js --search-path ./public/assets --type f -x brotli --force --best --output={}.br {}
