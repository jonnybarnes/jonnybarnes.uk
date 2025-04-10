#!/usr/bin/env zsh

if ! (( $+commands[sd] )) &> /dev/null; then
    echo "sd not installed"
    exit 1
fi

sd 'sourceMappingURL=public' 'sourceMappingURL=' public/assets/css/app.css
