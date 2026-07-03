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

This runs `bin/npm-auth.sh`, which writes a project-local `.npmrc` with a
**short-lived** token (minted from your `gcloud` login, valid ~1h and never
committed — `.npmrc` is git-ignored), then builds the zip into `dist/`.

If you see `❌ Could not obtain a GCP access token`, run `gcloud auth login` and
retry. This replaces the previous cryptic `npm error code E401` failure.

## CI

The release workflow (`.github/workflows/release-publish.yml`) authenticates via
Workload Identity Federation and passes the token to the same `bin/npm-auth.sh`
through the `NPM_TOKEN` environment variable, so local and CI builds share one
code path.
