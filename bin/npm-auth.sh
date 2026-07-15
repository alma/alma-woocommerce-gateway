#!/bin/bash

# Resolves a short-lived access token for the private @alma npm registry hosted
# on Google Artifact Registry (europe-npm.pkg.dev) and prints it to stdout.
#
# The registry itself is declared in the committed .npmrc, which references the
# token as ${NPM_TOKEN}; this script only mints that token so callers can export
# it into the build environment. Nothing sensitive is ever written to disk.
#
# In CI the token is provided via NPM_TOKEN (Workload Identity); locally it is
# derived from the developer's gcloud login.

set -euo pipefail

TOKEN="${NPM_TOKEN:-}"
if [ -z "$TOKEN" ]; then
  if ! command -v gcloud >/dev/null 2>&1; then
    echo "❌ gcloud CLI is required to authenticate npm to the private Alma registry (europe-npm.pkg.dev)." >&2
    echo "   Install the Google Cloud SDK and run: gcloud auth login" >&2
    exit 1
  fi
  TOKEN="$(gcloud auth print-access-token 2>/dev/null || true)"
fi

if [ -z "$TOKEN" ]; then
  echo "❌ Could not obtain a GCP access token. Run: gcloud auth login" >&2
  exit 1
fi

printf '%s' "$TOKEN"
