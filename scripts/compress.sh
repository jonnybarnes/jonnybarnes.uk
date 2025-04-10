#!/usr/bin/env zsh

if ! (( $+commands[fd] )) &> /dev/null; then
    echo "fd not installed"
    exit 1
fi

if ! (( $+commands[brotli] )) &> /dev/null; then
    echo "brotli not installed"
    exit 1
fi

if ! (( $+commands[zstd] )) &> /dev/null; then
    echo "zstd not installed"
    exit 1
fi

# Make .br files
fd --extension css --extension js --search-path ./public/assets --type f --exec brotli --force --best --output={}.br {}
# Make .zst files
fd --extension css --extension js --search-path ./public/assets --type f --exec zstd --quiet --force --ultra -22 --exclude-compressed {} -o {}.zst
