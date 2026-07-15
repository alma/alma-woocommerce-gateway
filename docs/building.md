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

The registry itself is declared in the committed `.npmrc`, which references the
auth token as `${NPM_TOKEN}` — no secret is stored in the repo. `task 7.4:dist`
runs `bin/npm-auth.sh` to mint a **short-lived** token (from your `gcloud` login,
valid ~1h), forwards it into the PHP container as `NPM_TOKEN`, and builds the zip
into `dist/`. npm substitutes `${NPM_TOKEN}` at install time.

If you see `❌ Could not obtain a GCP access token`, run `gcloud auth login` and
retry. This replaces the previous cryptic `npm error code E401` failure.

## CI

The release workflow (`.github/workflows/release-publish.yml`) authenticates via
Workload Identity Federation and passes the access token as `NPM_TOKEN`, which
`bin/npm-auth.sh` echoes back into the build — so local and CI builds share one
code path and the same committed `.npmrc`.
