#!/bin/bash

# Generates a project .npmrc so `npm install` (run by bin/strauss.sh inside the
# PHP container) can resolve the private @alma packages hosted on Google
# Artifact Registry.
#
# The registry URL is public config; only the auth token is sensitive. We never
# store a long-lived secret: the token is minted fresh on every run and is
# short-lived (~1h). In CI the token is injected via NPM_TOKEN; locally it is
# derived from the developer's gcloud login.

set -euo pipefail

REGISTRY_HOST="europe-npm.pkg.dev"
REGISTRY_PATH="lyrical-carver-335213/node-packages"
NPMRC="$(cd "$(dirname "$0")/.." && pwd)/.npmrc"

TOKEN="${NPM_TOKEN:-}"
if [ -z "$TOKEN" ]; then
  if ! command -v gcloud >/dev/null 2>&1; then
    echo "❌ gcloud CLI is required to authenticate npm to the private Alma registry (${REGISTRY_HOST})." >&2
    echo "   Install the Google Cloud SDK and run: gcloud auth login" >&2
    exit 1
  fi
  TOKEN="$(gcloud auth print-access-token 2>/dev/null || true)"
fi

if [ -z "$TOKEN" ]; then
  echo "❌ Could not obtain a GCP access token. Run: gcloud auth login" >&2
  exit 1
fi

cat > "$NPMRC" <<EOF
@alma:registry=https://${REGISTRY_HOST}/${REGISTRY_PATH}/
//${REGISTRY_HOST}/${REGISTRY_PATH}/:_authToken=${TOKEN}
//${REGISTRY_HOST}/${REGISTRY_PATH}/:always-auth=true
EOF

echo "✅ .npmrc refreshed for @alma packages (short-lived token)."
