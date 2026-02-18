#!/bin/bash
docker run --rm -v "$(pwd):/app" --entrypoint /composer/vendor/bin/phpstan lint:wc analyse ./includes --memory-limit 1024M

EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Check PHP static analysis before commit!"
    exit 1
fi
