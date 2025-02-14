#!/bin/bash
PROJECT_ROOT=$(git rev-parse --show-toplevel)
docker run --rm -v "$PROJECT_ROOT:/app" -w /app phpcbf:wp --standard=phpcs.xml ./
EXIT_CODE=$?
if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Fix the errors with PHPcbf automatic fixer before commit!"
    exit 1
fi
