# Customs PHPCS rules

This repository contains custom rules for PHP CodeSniffer (PHPCS) that are used in the development of the Customs
project. These rules help maintain code quality and consistency across the project by enforcing specific coding
standards and practices.

## Usage

If you want to edit this rules, you'll nee to rebuild the image of the linter. You can do this by running the following
command in the terminal:

```bash
    docker image rm lint:wc
    task build:lint
```

