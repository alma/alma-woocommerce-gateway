# Building the distribution zip

The plugin bundles JS assets that depend on the private `@alma/react-components`
package, hosted on Google Artifact Registry (`europe-npm.pkg.dev`). The
`npm install` step therefore needs an auth token for that registry.

## Prerequisites

- Docker
- [Task](https://taskfile.dev)
- The [Google Cloud SDK](https://cloud.google.com/sdk/docs/install) (`gcloud`),
  authenticated once with:

  ```bash
  gcloud auth login
  ```

## Build

```bash
task 7.4:dist
```

The private registry is declared in the committed `.npmrc` (registry only, no
secret). `task 7.4:dist` runs `npx google-artifactregistry-auth`, which mints a
**short-lived** token from your `gcloud` login and writes it to `$HOME/.npmrc`;
that file is mounted read-only into the PHP container so the `npm install` in
`bin/strauss.sh` can resolve `@alma` packages. The zip is built into `dist/`.

If `npm install` fails with `npm error code E401`, refresh your login with
`gcloud auth login` and retry.
