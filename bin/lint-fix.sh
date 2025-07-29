#!/bin/bash
docker run --rm --entrypoint /composer/vendor/bin/phpcbf -v "$(pwd):/app" -w /app lint:wc --standard=phpcs.xml ./ --ignore=\*/vendor/\*,build/*,node_modules/*,coverage/*,.coverage-report/*
EXIT_CODE=$?

if [[ $EXIT_CODE -ne 0 ]]; then
    echo "Commit errors fixed by PHPcbf!"
    exit 1
fi
