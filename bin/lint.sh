#!/bin/bash
docker run --rm -v "$(pwd):/app" -w /app lint:wc --standard=phpcs.xml ./ -s --ignore=\*/vendor/\*,build/*,node_modules/*,coverage/*,.coverage-report/*
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Fix the errors before commit!"
    exit 1
fi
