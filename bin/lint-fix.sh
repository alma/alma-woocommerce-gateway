#!/bin/bash
docker run --rm -v "$(pwd):/app" -w /app lint:wc -v --standard=phpcs.xml ./
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Fix the errors with PHPcbf automatic fixer before commit!"
    exit 1
fi
