# Dockerfiles

## Dockerfile

Used to run the tests with the command

```task test```

*Tests need MySQL to run. The MySQL container is started by the `docker-compose.yml` file.*

## Legacy Dockerfile

Used to build the ZIP file to release the plugin.

## PHPCS Dockerfile

Used to run the `lint` and `lint:fix` command by the pre-commit hook or manually with the command

```task lint```
```task lint:fix```